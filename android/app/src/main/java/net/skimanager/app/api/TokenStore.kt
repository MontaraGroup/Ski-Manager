package net.skimanager.app.api

import android.content.Context
import android.security.keystore.KeyGenParameterSpec
import android.security.keystore.KeyProperties
import android.util.Base64
import java.security.KeyStore
import javax.crypto.Cipher
import javax.crypto.KeyGenerator
import javax.crypto.SecretKey
import javax.crypto.spec.GCMParameterSpec

/** The sign-in token, encrypted with an AES key that stays inside Android Keystore */
class TokenStore(context: Context) {
    private val prefs = context.getSharedPreferences("session", Context.MODE_PRIVATE)

    fun read(): String? {
        val stored = prefs.getString(KEY, null) ?: return null
        return try {
            val bytes = Base64.decode(stored, Base64.NO_WRAP)
            val cipher = Cipher.getInstance(TRANSFORM)
            cipher.init(Cipher.DECRYPT_MODE, key(), GCMParameterSpec(128, bytes, 0, IV_SIZE))
            String(cipher.doFinal(bytes, IV_SIZE, bytes.size - IV_SIZE))
        } catch (e: Exception) {
            clear() // A key lost after a restore can't open this: sign in again
            null
        }
    }

    fun write(token: String) {
        val cipher = Cipher.getInstance(TRANSFORM)
        cipher.init(Cipher.ENCRYPT_MODE, key())
        val bytes = cipher.iv + cipher.doFinal(token.toByteArray())
        prefs.edit().putString(KEY, Base64.encodeToString(bytes, Base64.NO_WRAP)).apply()
    }

    fun clear() {
        prefs.edit().remove(KEY).apply()
    }

    private fun key(): SecretKey {
        val store = KeyStore.getInstance("AndroidKeyStore").apply { load(null) }
        (store.getKey(ALIAS, null) as? SecretKey)?.let { return it }
        val generator = KeyGenerator.getInstance(KeyProperties.KEY_ALGORITHM_AES, "AndroidKeyStore")
        generator.init(
            KeyGenParameterSpec.Builder(ALIAS, KeyProperties.PURPOSE_ENCRYPT or KeyProperties.PURPOSE_DECRYPT)
                .setBlockModes(KeyProperties.BLOCK_MODE_GCM)
                .setEncryptionPaddings(KeyProperties.ENCRYPTION_PADDING_NONE)
                .build(),
        )
        return generator.generateKey()
    }

    private companion object {
        const val KEY = "token"
        const val ALIAS = "ski_manager_session"
        const val TRANSFORM = "AES/GCM/NoPadding"
        const val IV_SIZE = 12
    }
}
