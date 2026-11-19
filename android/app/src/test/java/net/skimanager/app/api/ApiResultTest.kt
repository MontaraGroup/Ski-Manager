package net.skimanager.app.api

import com.google.gson.JsonParser
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Test

class ApiResultTest {

    @Test
    fun testParseSuccessWithDataAndMessage() {
        val json = """
            {
                "ok": true,
                "data": {
                    "resort": "Chamonix",
                    "cash": 75000
                },
                "message": "Resort loaded"
            }
        """.trimIndent()

        val result = Api.parseBody(200, json)
        assertTrue("Expected ApiResult.Ok", result is ApiResult.Ok)
        val ok = result as ApiResult.Ok
        assertEquals("Resort loaded", ok.message)

        val data = ok.data?.asJsonObject
        assertEquals("Chamonix", data?.str("resort"))
        assertEquals(75000, data?.int("cash"))
    }

    @Test
    fun testParseSuccessWithNullData() {
        val json = """{"ok": true, "data": null, "message": "Updated"}"""
        val result = Api.parseBody(200, json)
        assertTrue("Expected ApiResult.Ok", result is ApiResult.Ok)
        val ok = result as ApiResult.Ok
        assertNull(ok.data)
        assertEquals("Updated", ok.message)
    }

    @Test
    fun testParseSuccessMinimal() {
        val json = """{"ok": true}"""
        val result = Api.parseBody(200, json)
        assertTrue("Expected ApiResult.Ok", result is ApiResult.Ok)
        val ok = result as ApiResult.Ok
        assertNull(ok.data)
        assertNull(ok.message)
    }

    @Test
    fun testParseFailedWithErrorsAndFields() {
        val json = """
            {
                "ok": false,
                "message": "Validation failed",
                "errors": {
                    "code": "invalid_parameters",
                    "email": "user@ski.net",
                    "username": "Username already taken",
                    "price": "Price must be positive"
                }
            }
        """.trimIndent()

        val result = Api.parseBody(422, json)
        assertTrue("Expected ApiResult.Failed", result is ApiResult.Failed)
        val failed = result as ApiResult.Failed
        assertEquals(422, failed.status)
        assertEquals("Validation failed", failed.message)
        assertEquals("invalid_parameters", failed.code)
        // Ensure "code" and "email" are excluded from fields map
        assertEquals(
            mapOf(
                "username" to "Username already taken",
                "price" to "Price must be positive",
            ),
            failed.fields,
        )
    }

    @Test
    fun testParseFailedDefaultServerErrorMessage() {
        val json = """{"ok": false}"""
        val result = Api.parseBody(500, json)
        assertTrue("Expected ApiResult.Failed", result is ApiResult.Failed)
        val failed = result as ApiResult.Failed
        assertEquals(500, failed.status)
        assertEquals(SERVER_ERROR, failed.message)
        assertNull(failed.code)
        assertTrue(failed.fields.isEmpty())
    }

    @Test
    fun testParseFailedUnauthenticatedCode() {
        val json = """
            {
                "ok": false,
                "message": "Token expired",
                "errors": {
                    "code": "unauthenticated"
                }
            }
        """.trimIndent()

        val result = Api.parseBody(401, json)
        assertTrue("Expected ApiResult.Failed", result is ApiResult.Failed)
        val failed = result as ApiResult.Failed
        assertEquals(401, failed.status)
        assertEquals("unauthenticated", failed.code)
        assertEquals("Token expired", failed.message)
    }

    @Test
    fun testParseMalformedJsonReturnsFailed() {
        val html = "<html><body>502 Bad Gateway</body></html>"
        val result = Api.parseBody(502, html)
        assertTrue("Expected ApiResult.Failed", result is ApiResult.Failed)
        val failed = result as ApiResult.Failed
        assertEquals(502, failed.status)
        assertEquals(SERVER_ERROR, failed.message)
        assertNull(failed.code)
        assertTrue(failed.fields.isEmpty())
    }

    @Test
    fun testParseEmptyBodyReturnsFailed() {
        val result = Api.parseBody(500, "")
        assertTrue("Expected ApiResult.Failed", result is ApiResult.Failed)
        val failed = result as ApiResult.Failed
        assertEquals(500, failed.status)
        assertEquals(SERVER_ERROR, failed.message)
    }

    @Test
    fun testParseNonObjectJsonReturnsFailed() {
        val jsonArray = "[1, 2, 3]"
        val result = Api.parseBody(200, jsonArray)
        assertTrue("Expected ApiResult.Failed", result is ApiResult.Failed)
        val failed = result as ApiResult.Failed
        assertEquals(200, failed.status)
        assertEquals(SERVER_ERROR, failed.message)
    }

    @Test
    fun testJsonObjectExtensionHelpers() {
        val json = """
            {
                "name": "Powder Paradise",
                "active": true,
                "inactive": false,
                "groomer_count": 5,
                "cash": 120000,
                "null_field": null,
                "nested_obj": {
                    "model": "PistenBully 600",
                    "hours": 120
                },
                "items": [
                    { "id": "g1", "name": "Bully 1" },
                    "non-object-element",
                    { "id": "g2", "name": "Bully 2" }
                ]
            }
        """.trimIndent()

        val obj = JsonParser.parseString(json).asJsonObject

        // str and strOrNull
        assertEquals("Powder Paradise", obj.str("name"))
        assertEquals("Powder Paradise", obj.strOrNull("name"))
        assertEquals("", obj.str("missing"))
        assertNull(obj.strOrNull("missing"))
        assertEquals("", obj.str("null_field"))
        assertNull(obj.strOrNull("null_field"))

        // bool
        assertTrue(obj.bool("active"))
        assertFalse(obj.bool("inactive"))
        assertFalse(obj.bool("missing"))
        assertFalse(obj.bool("name"))

        // int
        assertEquals(5, obj.int("groomer_count"))
        assertEquals(120000, obj.int("cash"))
        assertEquals(0, obj.int("missing"))
        assertEquals(0, obj.int("name"))

        // obj
        val nested = obj.obj("nested_obj")
        assertEquals("PistenBully 600", nested.str("model"))
        assertEquals(120, nested.int("hours"))
        val missingObj = obj.obj("missing_nested")
        assertTrue(missingObj.entrySet().isEmpty())

        // objects (filters out non-objects)
        val items = obj.objects("items")
        assertEquals(2, items.size)
        assertEquals("g1", items[0].str("id"))
        assertEquals("Bully 1", items[0].str("name"))
        assertEquals("g2", items[1].str("id"))
        assertEquals("Bully 2", items[1].str("name"))

        val missingItems = obj.objects("missing_items")
        assertTrue(missingItems.isEmpty())
    }
}
