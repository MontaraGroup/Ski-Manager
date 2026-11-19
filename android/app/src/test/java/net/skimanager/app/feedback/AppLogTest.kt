package net.skimanager.app.feedback

import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test

class AppLogTest {

    @Test
    fun pathDropsHostQueryAndPrefix() {
        assertEquals("staff/12/fire", Redact.path("https://ski-manager.net/api/v1/app/staff/12/fire?token=abc&x=1"))
        assertEquals("dashboard", Redact.path("/api/v1/app/dashboard#top"))
    }

    @Test
    fun textMasksTokensAndEmails() {
        val masked = Redact.text("Authorization: Bearer abc.def-123 for player@example.com id 9f8e7d6c5b4a39281706f5e4d3c2b1a0")
        assertFalse(masked.contains("abc.def-123"))
        assertFalse(masked.contains("player@example.com"))
        assertFalse(masked.contains("9f8e7d6c5b4a39281706f5e4d3c2b1a0"))
        assertTrue(masked.contains("[email]"))
    }

    @Test
    fun buffersKeepOnlyTheNewest() {
        repeat(AppLog.MAX_CALLS + 10) { AppLog.call("GET", "/api/v1/app/page$it", 200, 5, 10) }
        val calls = AppLog.calls()
        assertEquals(AppLog.MAX_CALLS, calls.size)
        assertEquals("page${AppLog.MAX_CALLS + 9}", calls.last().path)
        assertTrue(AppLog.lines().size <= AppLog.MAX_LINES)
    }

    @Test
    fun repeatedScreenIsRecordedOnce() {
        AppLog.screen("map")
        AppLog.screen("map")
        assertEquals(1, AppLog.visits().count { it.route == "map" })
    }
}
