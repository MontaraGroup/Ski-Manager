package net.skimanager.app.feedback

import android.graphics.BitmapFactory
import androidx.compose.foundation.Image
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.selection.toggleable
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Switch
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.asImageBitmap
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.google.gson.GsonBuilder
import com.google.gson.JsonObject
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import net.skimanager.app.api.ApiResult
import net.skimanager.app.ui.AppState
import net.skimanager.app.ui.common.ChoiceRow
import net.skimanager.app.ui.common.OFFLINE_TEXT
import net.skimanager.app.ui.common.Panel
import net.skimanager.app.ui.common.PrimaryButton
import net.skimanager.app.ui.common.ScreenColumn
import net.skimanager.app.ui.common.SecondaryButton
import net.skimanager.app.ui.theme.LocalSkiColors

private const val MAX_MESSAGE = 2000
/** The server takes 256 KB of details; the layout tree is the first thing dropped when over */
private const val MAX_DETAILS = 250_000

/**
 * Send feedback: a message, a category, and only the details the player switches on. Opened from
 * More, Settings, or by shaking the phone (which also captures the screen the player was on).
 */
@Composable
fun FeedbackScreen(app: AppState, onOpenSupport: () -> Unit, onDone: () -> Unit) {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    // Taken once: the capture belongs to this visit only
    val capture = remember { app.feedbackCapture.also { app.feedbackCapture = null } }
    val shotBitmap = remember(capture) { capture?.screenshot?.let { BitmapFactory.decodeByteArray(it, 0, it.size)?.asImageBitmap() } }

    var category by remember { mutableStateOf("bug") }
    var message by remember { mutableStateOf("") }
    var include by remember { mutableStateOf(Include(screenshot = capture?.fromShake == true && shotBitmap != null)) }
    var preview by remember { mutableStateOf<String?>(null) }
    var busy by remember { mutableStateOf(false) }
    var error by remember { mutableStateOf<String?>(null) }
    var sent by remember { mutableStateOf<String?>(null) }

    suspend fun details(): JsonObject = withContext(Dispatchers.Default) {
        val d = Diagnostics.collect(context.applicationContext, include, capture)
        if (d.toString().length > MAX_DETAILS) d.remove("layout")
        d
    }

    sent?.let { note ->
        ScreenColumn {
            Panel("Feedback sent") {
                Text(note)
                Text("Replies show up in Support.", color = LocalSkiColors.current.muted)
            }
            PrimaryButton("Open Support", onOpenSupport, Modifier.fillMaxWidth())
            SecondaryButton("Done", onDone, Modifier.fillMaxWidth())
        }
        return
    }

    ScreenColumn {
        Panel("What's it about?") {
            ChoiceRow(listOf("bug" to "Bug", "idea" to "Idea", "other" to "Other"), category) { category = it }
            OutlinedTextField(
                message,
                { if (it.length <= MAX_MESSAGE) message = it },
                Modifier.fillMaxWidth(),
                shape = MaterialTheme.shapes.medium,
                label = { Text(if (category == "bug") "What went wrong?" else "Your message") },
                minLines = 4,
                supportingText = { Text("${message.length} / $MAX_MESSAGE") },
            )
        }

        Panel("Screenshot") {
            if (shotBitmap != null) {
                Image(
                    shotBitmap,
                    contentDescription = "Screenshot of the ${capture?.route ?: "current"} screen",
                    modifier = Modifier.fillMaxWidth().heightIn(max = 260.dp),
                    contentScale = ContentScale.Fit,
                )
                ToggleRow("Attach this screenshot", "It shows what was on screen, including your resort's numbers.", include.screenshot) {
                    include = include.copy(screenshot = it)
                }
            } else {
                Text(
                    "To attach a screenshot, go to the screen with the problem and shake your phone.",
                    color = LocalSkiColors.current.muted,
                    style = MaterialTheme.typography.bodyMedium,
                )
            }
        }

        Panel("Details to attach") {
            ToggleRow("Device info", "Phone model, Android version, language, screen size.", include.deviceInfo) { include = include.copy(deviceInfo = it) }
            ToggleRow("App logs", "What the app did recently, and its last crash if it had one.", include.appLogs) { include = include.copy(appLogs = it) }
            ToggleRow("Network activity", "The app's last 50 requests: page, result, time. No passwords or sign-in token.", include.network) { include = include.copy(network = it) }
            ToggleRow("Recent screens", "The last 30 screens you opened, with times.", include.screens) { include = include.copy(screens = it) }
            if (capture?.layout != null) {
                ToggleRow("Screen layout", "The buttons and labels on that screen. Text you typed is left out.", include.layout) { include = include.copy(layout = it) }
            }
            ToggleRow("Saved app data", "Names of the settings the app keeps. Your sign-in token is never sent.", include.savedData) { include = include.copy(savedData = it) }
            ToggleRow("System diagnostics", "Memory, storage, battery and temperature state.", include.system) { include = include.copy(system = it) }
            ToggleRow("Notification history", "Titles of your last 20 in-game notifications.", include.notificationHistory) { include = include.copy(notificationHistory = it) }
        }

        TextButton(
            { scope.launch { preview = GsonBuilder().setPrettyPrinting().create().toJson(details()) } },
            Modifier.fillMaxWidth().heightIn(min = 48.dp),
        ) { Text("See what will be sent", fontWeight = FontWeight.SemiBold) }

        Text(
            "Sending shares your message, the items switched on above and the app version with Ski Manager staff, " +
                "linked to your account. We use them only to fix problems and delete the attachments after 90 days. " +
                "Nothing else on your phone is read.",
            color = LocalSkiColors.current.muted,
            style = MaterialTheme.typography.bodySmall,
        )

        error?.let { Text(it, color = LocalSkiColors.current.danger, fontWeight = FontWeight.Medium) }

        PrimaryButton(if (busy) "Sending…" else "Send feedback", {
            busy = true
            error = null
            scope.launch {
                val json = details()
                val shot = if (include.screenshot) capture?.screenshot else null
                val result = app.api.postMultipart(
                    "feedback",
                    mapOf("message" to message.trim(), "category" to category, "diagnostics" to json.toString()),
                    shot?.let { "screenshot.jpg" to it },
                    fileField = "screenshot",
                )
                busy = false
                when (result) {
                    is ApiResult.Ok -> {
                        if (include.appLogs) AppLog.clearCrash()
                        AppLog.log("feedback sent")
                        sent = result.message ?: "Thanks for the report."
                    }
                    is ApiResult.Failed -> error = result.message
                    ApiResult.Offline -> error = OFFLINE_TEXT
                }
            }
        }, Modifier.fillMaxWidth(), enabled = !busy && message.isNotBlank())
    }

    preview?.let { text ->
        AlertDialog(
            onDismissRequest = { preview = null },
            shape = MaterialTheme.shapes.extraLarge,
            title = { Text("What will be sent", fontWeight = FontWeight.Bold) },
            text = {
                Column(Modifier.fillMaxWidth().verticalScroll(rememberScrollState()), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    Text("Message: ${message.trim().ifBlank { "(empty)" }}")
                    Text("Category: $category")
                    Text(if (include.screenshot && shotBitmap != null) "Screenshot: attached" else "Screenshot: none")
                    Text(
                        text,
                        fontFamily = FontFamily.Monospace,
                        fontSize = 11.sp,
                        lineHeight = 14.sp,
                        modifier = Modifier.horizontalScroll(rememberScrollState()),
                    )
                }
            },
            confirmButton = { TextButton({ preview = null }, Modifier.heightIn(min = 48.dp)) { Text("Close") } },
        )
    }
}

@Composable
private fun ToggleRow(title: String, description: String, checked: Boolean, onChange: (Boolean) -> Unit) {
    Row(
        Modifier.fillMaxWidth().heightIn(min = 56.dp).toggleable(checked, role = Role.Switch, onValueChange = onChange).padding(vertical = 4.dp),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        Column(Modifier.weight(1f)) {
            Text(title, fontWeight = FontWeight.SemiBold)
            Text(description, color = LocalSkiColors.current.muted, style = MaterialTheme.typography.bodySmall)
        }
        Switch(checked, onCheckedChange = null)
    }
}
