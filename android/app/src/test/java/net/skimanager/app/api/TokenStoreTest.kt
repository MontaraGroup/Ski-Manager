package net.skimanager.app.api

import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNotNull
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Test
import java.util.Base64
import javax.crypto.KeyGenerator
import javax.crypto.SecretKey

class TokenStoreTest {

    private class InMemoryStorage : StringStorage {
        val map = mutableMapOf<String, String>()
        override fun getString(key: String): String? = map[key]
        override fun putString(key: String, value: String) { map[key] = value }
        override fun remove(key: String) { map.remove(key) }
    }

    private fun generateAesKey(): SecretKey {
        val gen = KeyGenerator.getInstance("AES")
        gen.init(128)
        return gen.generateKey()
    }

    @Test
    fun testWriteAndReadToken() {
        val storage = InMemoryStorage()
        val key = generateAesKey()
        val store = TokenStore(storage = storage, keyProvider = { key })

        val testToken = "test_bearer_token_xyz_987654321"
        store.write(testToken)

        val retrieved = store.read()
        assertEquals(testToken, retrieved)
    }

    @Test
    fun testStoredDataIsEncryptedNotPlaintext() {
        val storage = InMemoryStorage()
        val key = generateAesKey()
        val store = TokenStore(storage = storage, keyProvider = { key })

        val secretToken = "very_confidential_session_token"
        store.write(secretToken)

        val rawStored = storage.getString("token")
        assertNotNull(rawStored)
        assertFalse("Stored value must not contain plaintext token", rawStored!!.contains(secretToken))

        // Ensure it is valid Base64 and contains at least 12-byte IV + ciphertext
        val decoded = Base64.getDecoder().decode(rawStored)
        assertTrue("Ciphertext must include at least 12-byte IV", decoded.size > 12)
    }

    @Test
    fun testReadEmptyStoreReturnsNull() {
        val storage = InMemoryStorage()
        val key = generateAesKey()
        val store = TokenStore(storage = storage, keyProvider = { key })

        assertNull(store.read())
    }

    @Test
    fun testClearRemovesToken() {
        val storage = InMemoryStorage()
        val key = generateAesKey()
        val store = TokenStore(storage = storage, keyProvider = { key })

        store.write("token_to_clear")
        assertNotNull(store.read())

        store.clear()
        assertNull(store.read())
        assertNull(storage.getString("token"))
    }

    @Test
    fun testOverwritingToken() {
        val storage = InMemoryStorage()
        val key = generateAesKey()
        val store = TokenStore(storage = storage, keyProvider = { key })

        store.write("initial_token")
        assertEquals("initial_token", store.read())

        store.write("updated_token")
        assertEquals("updated_token", store.read())
    }

    @Test
    fun testCorruptedCiphertextReturnsNullAndClears() {
        val storage = InMemoryStorage()
        val key = generateAesKey()
        val store = TokenStore(storage = storage, keyProvider = { key })

        store.write("token_for_corruption_test")

        // Intentionally corrupt the stored Base64 string
        storage.putString("token", "corrupted_non_gcm_payload")

        // Reading corrupted data should catch exception, clear the corrupt state, and return null
        assertNull(store.read())
        assertNull(storage.getString("token"))
    }

    @Test
    fun testDecryptionWithDifferentKeyReturnsNullAndClears() {
        val storage = InMemoryStorage()
        val key1 = generateAesKey()
        val key2 = generateAesKey()

        val store1 = TokenStore(storage = storage, keyProvider = { key1 })
        val store2 = TokenStore(storage = storage, keyProvider = { key2 })

        store1.write("token_for_key_mismatch")

        // Reading with mismatched key (e.g. after key lost on restore) should fail and clear
        assertNull(store2.read())
        assertNull(storage.getString("token"))
    }
}
