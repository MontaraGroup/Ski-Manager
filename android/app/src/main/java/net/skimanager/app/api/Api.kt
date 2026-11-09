package net.skimanager.app.api

import com.google.gson.JsonArray
import com.google.gson.JsonElement
import com.google.gson.JsonObject
import com.google.gson.JsonParser
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import net.skimanager.app.BuildConfig
import okhttp3.FormBody
import okhttp3.HttpUrl.Companion.toHttpUrl
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.RequestBody.Companion.toRequestBody
import java.io.IOException
import java.util.concurrent.TimeUnit

/** What the server answered. It decides every rule and formats every number; the app only shows it */
sealed interface ApiResult {
    data class Ok(val data: JsonElement?, val message: String?) : ApiResult

    /** code: the server's errors.code, such as "maintenance". fields: field name to message, for forms */
    data class Failed(val status: Int, val message: String, val code: String?, val fields: Map<String, String>) : ApiResult

    data object Offline : ApiResult
}

const val SERVER_ERROR = "Something went wrong. Try again."

/** /api/v1/app. onSignedOut runs when the server no longer accepts the token */
class Api(private val tokens: () -> String?, private val onSignedOut: () -> Unit) {
    private val client = OkHttpClient.Builder().connectTimeout(15, TimeUnit.SECONDS).readTimeout(30, TimeUnit.SECONDS).build()
    private val base = BuildConfig.BASE_URL + "/api/v1/app/"

    suspend fun get(path: String, query: Map<String, String> = emptyMap()): ApiResult {
        val url = (base + path).toHttpUrl().newBuilder().apply { query.forEach { (k, v) -> addQueryParameter(k, v) } }.build()
        return send(Request.Builder().url(url).get())
    }

    suspend fun post(path: String, form: Map<String, String> = emptyMap()): ApiResult {
        val body = FormBody.Builder().apply { form.forEach { (k, v) -> add(k, v) } }.build()
        return send(Request.Builder().url(base + path).post(body))
    }

    suspend fun postJson(path: String, json: JsonObject): ApiResult =
        send(Request.Builder().url(base + path).post(json.toString().toRequestBody("application/json".toMediaType())))

    private suspend fun send(builder: Request.Builder): ApiResult = withContext(Dispatchers.IO) {
        tokens()?.let { builder.header("Authorization", "Bearer $it") }
        builder.header("Accept", "application/json").header("X-Requested-With", "XMLHttpRequest")
        try {
            client.newCall(builder.build()).execute().use { response ->
                val root = runCatching { JsonParser.parseString(response.body?.string().orEmpty()).asJsonObject }.getOrNull()
                val result = parse(response.code, root)
                if (result is ApiResult.Failed && result.code == "unauthenticated") withContext(Dispatchers.Main) { onSignedOut() }
                result
            }
        } catch (e: IOException) {
            ApiResult.Offline
        }
    }

    private fun parse(status: Int, root: JsonObject?): ApiResult {
        if (root != null && root.bool("ok")) {
            return ApiResult.Ok(root.get("data")?.takeUnless { it.isJsonNull }, root.strOrNull("message"))
        }
        val errors = root?.get("errors")?.takeIf { it.isJsonObject }?.asJsonObject
        val fields = errors?.entrySet().orEmpty()
            .filter { it.key != "code" && it.key != "email" && it.value.isJsonPrimitive }
            .associate { it.key to it.value.asString }
        return ApiResult.Failed(status, root?.strOrNull("message") ?: SERVER_ERROR, errors?.strOrNull("code"), fields)
    }
}

fun JsonObject.strOrNull(name: String): String? = get(name)?.takeIf { it.isJsonPrimitive }?.asString
fun JsonObject.str(name: String): String = strOrNull(name).orEmpty()
fun JsonObject.bool(name: String): Boolean = get(name)?.takeIf { it.isJsonPrimitive }?.asBoolean ?: false
fun JsonObject.int(name: String): Int = get(name)?.takeIf { it.isJsonPrimitive }?.asInt ?: 0
fun JsonObject.obj(name: String): JsonObject = get(name)?.takeIf { it.isJsonObject }?.asJsonObject ?: JsonObject()
fun JsonObject.objects(name: String): List<JsonObject> =
    (get(name)?.takeIf { it.isJsonArray }?.asJsonArray ?: JsonArray()).filter { it.isJsonObject }.map { it.asJsonObject }
