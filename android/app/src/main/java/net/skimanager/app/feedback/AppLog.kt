package net.skimanager.app.feedback

import android.content.Context
import java.io.File
import java.text.SimpleDateFormat
import java.util.ArrayDeque
import java.util.Date
import java.util.Locale

/**
 * What the app itself saw, kept in memory for feedback reports: its own log lines, its own API calls
 * and the screens opened. Android doesn't let an app read the system log, and nothing here leaves
 * the phone unless the player sends a report with that item switched on.
 */
object AppLog {
    const val MAX_LINES = 300
    const val MAX_CALLS = 50
    const val MAX_SCREENS = 30
    private const val CRASH_FILE = "last_crash.txt"
    private const val MAX_CRASH = 8000

    data class Call(val at: Long, val method: String, val path: String, val status: Int, val ms: Long, val bytes: Long)
    data class Visit(val at: Long, val route: String)

    private val lines = ArrayDeque<String>()
    private val calls = ArrayDeque<Call>()
    private val visits = ArrayDeque<Visit>()
    private var crashFile: File? = null

    @Synchronized
    fun log(message: String) {
        lines.addLast(time(System.currentTimeMillis()) + " " + Redact.text(message))
        while (lines.size > MAX_LINES) lines.removeFirst()
    }

    /** One API call. path is cleaned: no query, no host, long tokens and emails masked */
    @Synchronized
    fun call(method: String, path: String, status: Int, ms: Long, bytes: Long) {
        val clean = Redact.path(path)
        calls.addLast(Call(System.currentTimeMillis(), method, clean, status, ms, bytes))
        while (calls.size > MAX_CALLS) calls.removeFirst()
        log("$method $clean " + (if (status == 0) "offline" else status.toString()) + " ${ms}ms")
    }

    @Synchronized
    fun screen(route: String) {
        if (visits.peekLast()?.route == route) return
        visits.addLast(Visit(System.currentTimeMillis(), route))
        while (visits.size > MAX_SCREENS) visits.removeFirst()
        log("screen $route")
    }

    @Synchronized fun lines(): List<String> = lines.toList()
    @Synchronized fun calls(): List<Call> = calls.toList()
    @Synchronized fun visits(): List<Visit> = visits.toList()

    /** The crash the app wrote before it last closed, if any */
    fun lastCrash(): String? = crashFile?.takeIf { it.exists() }?.runCatching { readText() }?.getOrNull()

    fun clearCrash() {
        crashFile?.delete()
    }

    /** Writes an uncaught crash to app storage, then lets Android handle it as before */
    fun installCrashHandler(context: Context) {
        if (crashFile != null) return
        val file = File(context.filesDir, CRASH_FILE)
        crashFile = file
        val previous = Thread.getDefaultUncaughtExceptionHandler()
        Thread.setDefaultUncaughtExceptionHandler { thread, error ->
            runCatching {
                val trace = Redact.text(error.stackTraceToString()).take(MAX_CRASH)
                file.writeText("${time(System.currentTimeMillis())} on ${thread.name}\n$trace")
            }
            previous?.uncaughtException(thread, error)
        }
    }

    fun time(at: Long): String = SimpleDateFormat("HH:mm:ss", Locale.US).format(Date(at))
}

/** Masks what shouldn't leave the phone in a log line or a URL path */
object Redact {
    private val email = Regex("[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}")
    private val bearer = Regex("(?i)bearer\\s+[A-Za-z0-9._~+/=-]+")
    private val longToken = Regex("[A-Za-z0-9_-]{24,}")

    fun text(value: String): String =
        value.replace(bearer, "Bearer [hidden]").replace(email, "[email]").replace(longToken, "[hidden]")

    /** api/v1/app/staff/12/fire?x=1 -> staff/12/fire */
    fun path(value: String): String {
        val noQuery = value.substringBefore('?').substringBefore('#')
        val noHost = noQuery.replace(Regex("^[a-z]+://[^/]+"), "")
        return text(noHost.removePrefix("/").removePrefix("api/v1/app/"))
    }
}
