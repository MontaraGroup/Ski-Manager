package net.skimanager.app.api

import android.content.Context
import android.security.keystore.KeyGenParameterSpec
import android.security.keystore.KeyProperties
import java.security.KeyStore
import java.util.Base64
import javax.crypto.Cipher
import javax.crypto.KeyGenerator
import javax.crypto.SecretKey
import javax.crypto.spec.GCMParameterSpec

internal interface StringStorage {
    fun getString(key: String): String?
    fun putString(key: String, value: String)
    fun remove(key: String)
}

/** The sign-in token, encrypted with an AES key that stays inside Android Keystore */
class TokenStore internal constructor(
    private val storage: StringStorage,
    private val keyProvider: () -> SecretKey,
) {
    constructor(context: Context) : this(
        storage = SharedPreferencesStorage(context),
        keyProvider = { androidKeystoreKey() },
    )

    fun read(): String? {
        val stored = storage.getString(KEY) ?: return null
        return try {
            val bytes = Base64.getDecoder().decode(stored)
            val cipher = Cipher.getInstance(TRANSFORM)
            cipher.init(Cipher.DECRYPT_MODE, keyProvider(), GCMParameterSpec(128, bytes, 0, IV_SIZE))
            String(cipher.doFinal(bytes, IV_SIZE, bytes.size - IV_SIZE))
        } catch (e: Exception) {
            clear() // A key lost after a restore can't open this: sign in again
            null
        }
    }

    fun write(token: String) {
        val cipher = Cipher.getInstance(TRANSFORM)
        cipher.init(Cipher.ENCRYPT_MODE, keyProvider())
        val bytes = cipher.iv + cipher.doFinal(token.toByteArray())
        storage.putString(KEY, Base64.getEncoder().encodeToString(bytes))
    }

    fun clear() {
        storage.remove(KEY)
    }

    private companion object {
        const val KEY = "token"
        const val ALIAS = "ski_manager_session"
        const val TRANSFORM = "AES/GCM/NoPadding"
        const val IV_SIZE = 12

        fun androidKeystoreKey(): SecretKey {
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
    }
}

private class SharedPreferencesStorage(context: Context) : StringStorage {
    private val prefs = context.getSharedPreferences("session", Context.MODE_PRIVATE)

    override fun getString(key: String): String? = prefs.getString(key, null)

    override fun putString(key: String, value: String) {
        prefs.edit().putString(key, value).apply()
    }

    override fun remove(key: String) {
        prefs.edit().remove(key).apply()
    }
}
