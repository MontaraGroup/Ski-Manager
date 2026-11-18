package net.skimanager.app.feedback

import android.app.Activity
import android.app.ActivityManager
import android.content.Context
import android.content.Intent
import android.content.IntentFilter
import android.content.res.Configuration
import android.graphics.Bitmap
import android.os.BatteryManager
import android.os.Build
import android.os.Handler
import android.os.Looper
import android.os.PowerManager
import android.os.Process
import android.os.SystemClock
import android.view.PixelCopy
import android.view.View
import android.view.ViewGroup
import androidx.compose.ui.platform.ViewRootForTest
import androidx.compose.ui.semantics.SemanticsNode
import androidx.compose.ui.semantics.SemanticsProperties
import androidx.compose.ui.semantics.getOrNull
import com.google.gson.JsonArray
import com.google.gson.JsonObject
import kotlinx.coroutines.suspendCancellableCoroutine
import net.skimanager.app.BuildConfig
import java.io.ByteArrayOutputStream
import java.io.File
import java.util.Locale
import kotlin.coroutines.resume

/** Which items the player switched on. Names match the sections the admin page shows */
data class Include(
    val deviceInfo: Boolean = true,
    val appLogs: Boolean = true,
    val network: Boolean = true,
    val screens: Boolean = true,
    val screenshot: Boolean = false,
    val layout: Boolean = false,
    val savedData: Boolean = false,
    val system: Boolean = false,
    val notificationHistory: Boolean = false,
)

/** The screen as it was when feedback was opened: taken before the feedback screen covers it */
class Capture(val route: String, val screenshot: ByteArray?, val layout: JsonObject?, val fromShake: Boolean)

object Diagnostics {
    private const val MAX_SHOT_SIDE = 1600
    private const val MAX_NODES = 400

    /** Screenshot and layout of what the activity shows now. Only the app's own window */
    suspend fun capture(activity: Activity, route: String, fromShake: Boolean): Capture {
        val shot = runCatching { screenshot(activity) }.getOrNull()
        val layout = runCatching { layout(activity.window.decorView) }.getOrNull()
        return Capture(route, shot, layout, fromShake)
    }

    private suspend fun screenshot(activity: Activity): ByteArray? {
        val view = activity.window.decorView
        if (view.width == 0 || view.height == 0) return null
        val full = Bitmap.createBitmap(view.width, view.height, Bitmap.Config.ARGB_8888)
        val ok = suspendCancellableCoroutine { done ->
            PixelCopy.request(activity.window, full, { result -> done.resume(result == PixelCopy.SUCCESS) }, Handler(Looper.getMainLooper()))
        }
        if (!ok) return null
        val scale = minOf(1f, MAX_SHOT_SIDE.toFloat() / maxOf(full.width, full.height))
        val bitmap = if (scale < 1f) Bitmap.createScaledBitmap(full, (full.width * scale).toInt(), (full.height * scale).toInt(), true) else full
        return ByteArrayOutputStream().use { out ->
            bitmap.compress(Bitmap.CompressFormat.JPEG, 80, out)
            out.toByteArray()
        }
    }

    /** The Compose semantics tree: roles, labels and bounds. What the player typed is never included */
    private fun layout(root: View): JsonObject? {
        val compose = findCompose(root) ?: return null
        val count = intArrayOf(0)
        return node(compose.semanticsOwner.unmergedRootSemanticsNode, count)
    }

    private fun findCompose(view: View): ViewRootForTest? {
        if (view is ViewRootForTest) return view
        if (view is ViewGroup) for (i in 0 until view.childCount) findCompose(view.getChildAt(i))?.let { return it }
        return null
    }

    private fun node(n: SemanticsNode, count: IntArray): JsonObject? {
        if (count[0]++ >= MAX_NODES) return null
        val c = n.config
        val o = JsonObject()
        c.getOrNull(SemanticsProperties.Role)?.let { o.addProperty("role", it.toString()) }
        val editable = c.getOrNull(SemanticsProperties.EditableText) != null
        val password = c.contains(SemanticsProperties.Password)
        if (editable || password) {
            o.addProperty("field", "[redacted]")
        } else {
            c.getOrNull(SemanticsProperties.Text)?.joinToString(" ")?.takeIf { it.isNotBlank() }?.let { o.addProperty("text", Redact.text(it).take(120)) }
        }
        c.getOrNull(SemanticsProperties.ContentDescription)?.joinToString(" ")?.let { o.addProperty("label", Redact.text(it).take(120)) }
        c.getOrNull(SemanticsProperties.TestTag)?.let { o.addProperty("tag", it) }
        if (c.getOrNull(SemanticsProperties.Disabled) != null) o.addProperty("disabled", true)
        val b = n.boundsInRoot
        o.addProperty("bounds", "${b.left.toInt()},${b.top.toInt()} ${b.width.toInt()}x${b.height.toInt()}")
        val kids = JsonArray()
        n.children.forEach { child -> node(child, count)?.let { kids.add(it) } }
        if (kids.size() > 0) o.add("children", kids)
        return o
    }

    /** Everything switched on, as the JSON sent with the report. The app version always goes */
    fun collect(context: Context, include: Include, capture: Capture?): JsonObject {
        val root = JsonObject()
        root.add("include", JsonObject().apply {
            addProperty("deviceInfo", include.deviceInfo)
            addProperty("appLogs", include.appLogs)
            addProperty("network", include.network)
            addProperty("screens", include.screens)
            addProperty("screenshot", include.screenshot && capture?.screenshot != null)
            addProperty("layout", include.layout && capture?.layout != null)
            addProperty("savedData", include.savedData)
            addProperty("system", include.system)
            addProperty("notificationHistory", include.notificationHistory)
        })
        root.add("app", JsonObject().apply {
            addProperty("version", "${BuildConfig.VERSION_NAME} (${BuildConfig.VERSION_CODE})")
            addProperty("build", BuildConfig.BUILD_TYPE)
            capture?.let { addProperty("screen", it.route) }
        })
        if (include.deviceInfo) root.add("device", device(context))
        if (include.appLogs) {
            root.add("logs", JsonArray().apply { AppLog.lines().forEach { add(it) } })
            AppLog.lastCrash()?.let { root.addProperty("lastCrash", it) }
        }
        if (include.network) root.add("network", JsonArray().apply {
            AppLog.calls().forEach { c ->
                add(JsonObject().apply {
                    addProperty("at", AppLog.time(c.at))
                    addProperty("method", c.method)
                    addProperty("path", c.path)
                    addProperty("status", if (c.status == 0) "offline" else c.status.toString())
                    addProperty("ms", c.ms)
                    addProperty("bytes", c.bytes)
                })
            }
        })
        if (include.screens) root.add("screens", JsonArray().apply { AppLog.visits().forEach { add("${AppLog.time(it.at)} ${it.route}") } })
        if (include.layout) capture?.layout?.let { root.add("layout", it) }
        if (include.savedData) root.add("savedData", savedData(context))
        if (include.system) root.add("system", system(context))
        return root
    }

    private fun device(context: Context): JsonObject {
        val res = context.resources
        val config = res.configuration
        val metrics = res.displayMetrics
        return JsonObject().apply {
            addProperty("manufacturer", Build.MANUFACTURER)
            addProperty("model", Build.MODEL)
            addProperty("android", Build.VERSION.RELEASE)
            addProperty("sdk", Build.VERSION.SDK_INT)
            addProperty("language", Locale.getDefault().toLanguageTag())
            addProperty("screen", "${config.screenWidthDp}x${config.screenHeightDp} dp")
            addProperty("density", metrics.densityDpi)
            addProperty("fontScale", config.fontScale)
            addProperty("darkMode", config.uiMode and Configuration.UI_MODE_NIGHT_MASK == Configuration.UI_MODE_NIGHT_YES)
            addProperty("orientation", if (config.orientation == Configuration.ORIENTATION_LANDSCAPE) "landscape" else "portrait")
        }
    }

    /** The app's own settings files: key names and value types. Values only for the keys listed in VISIBLE */
    private val VISIBLE = setOf("feedback/shake")

    private fun savedData(context: Context): JsonObject {
        val out = JsonObject()
        val dir = File(context.applicationInfo.dataDir, "shared_prefs")
        dir.listFiles()?.filter { it.name.endsWith(".xml") }?.sortedBy { it.name }?.forEach { file ->
            val name = file.name.removeSuffix(".xml")
            val values = JsonObject()
            context.getSharedPreferences(name, Context.MODE_PRIVATE).all.toSortedMap().forEach { (key, value) ->
                val shown = if ("$name/$key" in VISIBLE) value.toString() else "[set, ${value?.javaClass?.simpleName ?: "null"}]"
                values.addProperty(key, shown)
            }
            out.add(name, values)
        }
        out.add("files", JsonObject().apply {
            context.filesDir.listFiles()?.sortedBy { it.name }?.forEach { addProperty(it.name, "${it.length()} bytes") }
        })
        return out
    }

    private fun system(context: Context): JsonObject {
        val am = context.getSystemService(ActivityManager::class.java)
        val mem = ActivityManager.MemoryInfo().also { am.getMemoryInfo(it) }
        val rt = Runtime.getRuntime()
        val pm = context.getSystemService(PowerManager::class.java)
        // A sticky broadcast: reading it needs no permission and registers nothing
        val battery = context.registerReceiver(null, IntentFilter(Intent.ACTION_BATTERY_CHANGED))
        val level = battery?.let { b ->
            val l = b.getIntExtra(BatteryManager.EXTRA_LEVEL, -1)
            val s = b.getIntExtra(BatteryManager.EXTRA_SCALE, -1)
            if (l >= 0 && s > 0) l * 100 / s else null
        }
        val plugged = (battery?.getIntExtra(BatteryManager.EXTRA_PLUGGED, 0) ?: 0) != 0
        return JsonObject().apply {
            addProperty("appHeapUsedMb", (rt.totalMemory() - rt.freeMemory()) / MB)
            addProperty("appHeapMaxMb", rt.maxMemory() / MB)
            addProperty("deviceMemoryFreeMb", mem.availMem / MB)
            addProperty("deviceMemoryTotalMb", mem.totalMem / MB)
            addProperty("lowMemory", mem.lowMemory)
            addProperty("appStorageMb", (dirSize(File(context.applicationInfo.dataDir)) / MB))
            addProperty("freeStorageMb", context.filesDir.usableSpace / MB)
            level?.let { addProperty("batteryPercent", it) }
            addProperty("charging", plugged)
            addProperty("powerSaver", pm.isPowerSaveMode)
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) addProperty("thermalStatus", pm.currentThermalStatus)
            addProperty("appRunningMinutes", (SystemClock.elapsedRealtime() - Process.getStartElapsedRealtime()) / 60000)
            addProperty("deviceUptimeHours", SystemClock.elapsedRealtime() / 3600000)
        }
    }

    private fun dirSize(dir: File): Long = dir.walkTopDown().maxDepth(6).filter { it.isFile }.take(5000).sumOf { it.length() }

    private const val MB = 1024L * 1024L
}
