package net.skimanager.app.ui.game

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ColumnScope
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.RowScope
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.ExperimentalMaterial3ExpressiveApi
import androidx.compose.material3.LinearProgressIndicator
import androidx.compose.material3.LoadingIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.staticCompositionLocalOf
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import com.google.gson.JsonElement
import com.google.gson.JsonObject
import kotlinx.coroutines.launch
import net.skimanager.app.api.ApiResult
import net.skimanager.app.api.SERVER_ERROR
import net.skimanager.app.ui.AppState
import net.skimanager.app.ui.common.MessageState
import net.skimanager.app.ui.common.OFFLINE_TEXT
import net.skimanager.app.ui.common.OutlinedPrimaryButton
import net.skimanager.app.ui.common.PrimaryButton
import net.skimanager.app.ui.common.ScreenColumn
import net.skimanager.app.ui.common.SecondaryButton
import net.skimanager.app.ui.theme.LocalSkiColors

/** Opens another screen by its route, such as "alliances/view/4". Provided by the game shell */
val LocalOpen = staticCompositionLocalOf<(String) -> Unit> { {} }

/** What a page's content can do: post one of the page's actions, and whether one is running */
class PageActions(val busy: Boolean, private val run: (String, Map<String, String>) -> Unit) {
    /** Posts to "<page path>/<action>" and shows the server's answer above the page */
    fun post(action: String, form: Map<String, String> = emptyMap()) = run(action, form)
}

/**
 * A website page in the app: loads GET path, shows loading, offline and error states, and runs
 * actions. An action's answer is the page again, so it replaces what is shown without a reload.
 * actionBase: where actions post, when it differs from the page path (alliance sub-pages).
 */
@OptIn(ExperimentalMaterial3ExpressiveApi::class)
@Composable
fun ApiPage(
    app: AppState,
    path: String,
    actionBase: String = path,
    content: @Composable ColumnScope.(JsonObject, PageActions) -> Unit,
) {
    val scope = rememberCoroutineScope()
    var attempt by remember { mutableIntStateOf(0) }
    var result by remember(path) { mutableStateOf<ApiResult?>(null) }
    var notice by remember(path) { mutableStateOf<Pair<String, Boolean>?>(null) }
    var busy by remember { mutableStateOf(false) }
    LaunchedEffect(path, attempt) { result = app.api.get(path) }

    val actions = PageActions(busy) { action, form ->
        busy = true
        notice = null
        scope.launch {
            when (val r = app.api.post("$actionBase/$action", form)) {
                is ApiResult.Ok -> {
                    notice = (r.message ?: "Done.") to true
                    if (actionBase == path && r.data?.isJsonObject == true) {
                        result = r
                    } else {
                        (app.api.get(path) as? ApiResult.Ok)?.let { result = it }
                    }
                }
                is ApiResult.Failed -> notice = r.message.ifBlank { SERVER_ERROR } to false
                is ApiResult.Offline -> notice = OFFLINE_TEXT to false
            }
            busy = false
        }
    }

    when (val r = result) {
        null -> Column(
            Modifier.fillMaxSize(),
            verticalArrangement = Arrangement.spacedBy(12.dp, Alignment.CenterVertically),
            horizontalAlignment = Alignment.CenterHorizontally,
        ) {
            LoadingIndicator()
            Text("Loading…", style = MaterialTheme.typography.bodyMedium, color = LocalSkiColors.current.muted)
        }
        is ApiResult.Offline -> MessageState(OFFLINE_TEXT) { result = null; attempt++ }
        is ApiResult.Failed -> MessageState(if (r.code == null) SERVER_ERROR else r.message) { result = null; attempt++ }
        is ApiResult.Ok -> ScreenColumn {
            notice?.let { (text, ok) -> Notice(text, if (ok) LocalSkiColors.current.success else LocalSkiColors.current.danger) }
            content(r.dataObject(), actions)
        }
    }
}

/** A tinted message card: success, warning, danger or info */
@Composable
fun Notice(text: String, color: Color = MaterialTheme.colorScheme.primary) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = MaterialTheme.shapes.medium,
        colors = CardDefaults.cardColors(containerColor = color.copy(alpha = 0.12f)),
        border = BorderStroke(1.dp, color.copy(alpha = 0.35f)),
    ) {
        Text(text, color = color, fontWeight = FontWeight.SemiBold, style = MaterialTheme.typography.bodyMedium, modifier = Modifier.padding(14.dp))
    }
}

@Composable
fun Muted(text: String) {
    if (text.isNotBlank()) Text(text, color = LocalSkiColors.current.muted, style = MaterialTheme.typography.bodySmall)
}

@Composable
fun Heading(text: String) {
    Text(text, fontWeight = FontWeight.Bold, style = MaterialTheme.typography.titleSmall)
}

/** A 0-100 bar */
@Composable
fun Meter(pct: Int, color: Color = MaterialTheme.colorScheme.primary) {
    LinearProgressIndicator(
        progress = { pct.coerceIn(0, 100) / 100f },
        modifier = Modifier.fillMaxWidth().heightIn(min = 6.dp),
        color = color,
        trackColor = LocalSkiColors.current.surfaceSecondary,
        drawStopIndicator = {},
    )
}

/** Green at 70+, amber at 40+, red below: the website's condition colors */
@Composable
fun conditionColor(pct: Int): Color = when {
    pct >= 70 -> LocalSkiColors.current.success
    pct >= 40 -> LocalSkiColors.current.warning
    else -> LocalSkiColors.current.danger
}

@Composable
fun ButtonRow(content: @Composable RowScope.() -> Unit) {
    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp), verticalAlignment = Alignment.CenterVertically, content = content)
}

/**
 * A button that runs onConfirm, after a dialog when confirm is set.
 * style: "primary", "secondary" or "outlined"
 */
@Composable
fun ActButton(
    label: String,
    onConfirm: () -> Unit,
    modifier: Modifier = Modifier,
    enabled: Boolean = true,
    confirm: String? = null,
    style: String = "secondary",
) {
    var asking by remember { mutableStateOf(false) }
    val click = { if (confirm != null) asking = true else onConfirm() }
    when (style) {
        "primary" -> PrimaryButton(label, click, modifier, enabled)
        "outlined" -> OutlinedPrimaryButton(label, click, modifier, enabled)
        else -> SecondaryButton(label, click, modifier, enabled)
    }
    if (asking && confirm != null) {
        AlertDialog(
            onDismissRequest = { asking = false },
            shape = MaterialTheme.shapes.extraLarge,
            title = { Text(label, fontWeight = FontWeight.Bold) },
            text = { Text(confirm) },
            confirmButton = {
                TextButton({ asking = false; onConfirm() }, Modifier.heightIn(min = 48.dp)) { Text(label, fontWeight = FontWeight.Bold) }
            },
            dismissButton = { TextButton({ asking = false }, Modifier.heightIn(min = 48.dp)) { Text("Cancel") } },
        )
    }
}

/** One text or number field in a dialog, for a price, an amount or a name */
@Composable
fun InputDialog(
    title: String,
    label: String,
    initial: String = "",
    number: Boolean = false,
    help: String? = null,
    confirmLabel: String = "Save",
    onDismiss: () -> Unit,
    onDone: (String) -> Unit,
) {
    var value by remember { mutableStateOf(initial) }
    AlertDialog(
        onDismissRequest = onDismiss,
        shape = MaterialTheme.shapes.extraLarge,
        title = { Text(title, fontWeight = FontWeight.Bold) },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                help?.let { Text(it, style = MaterialTheme.typography.bodyMedium) }
                OutlinedTextField(
                    value, { value = if (number) it.filter { c -> c.isDigit() || c == '.' } else it },
                    Modifier.fillMaxWidth(),
                    label = { Text(label) },
                    singleLine = !(!number && label.contains("message", ignoreCase = true)),
                    shape = MaterialTheme.shapes.medium,
                    keyboardOptions = KeyboardOptions(keyboardType = if (number) KeyboardType.Decimal else KeyboardType.Text),
                )
            }
        },
        confirmButton = {
            TextButton({ onDone(value.trim()) }, Modifier.heightIn(min = 48.dp), enabled = value.isNotBlank()) { Text(confirmLabel, fontWeight = FontWeight.Bold) }
        },
        dismissButton = { TextButton(onDismiss, Modifier.heightIn(min = 48.dp)) { Text("Cancel") } },
    )
}

fun JsonObject.num(name: String): Double = runCatching { get(name)?.takeIf { it.isJsonPrimitive }?.asDouble }.getOrNull() ?: 0.0

/** A list of strings, skipping anything else */
fun JsonObject.strings(name: String): List<String> =
    get(name)?.takeIf { it.isJsonArray }?.asJsonArray?.filter { it.isJsonPrimitive }?.map(JsonElement::getAsString).orEmpty()

/** A map's values as objects, for PHP arrays keyed by type ({ "hotel": {...} }) as well as lists */
fun JsonObject.entries(name: String): List<Pair<String, JsonObject>> {
    val e = get(name) ?: return emptyList()
    return when {
        e.isJsonObject -> e.asJsonObject.entrySet().filter { it.value.isJsonObject }.map { it.key to it.value.asJsonObject }
        e.isJsonArray -> e.asJsonArray.filter { it.isJsonObject }.mapIndexed { i, v -> i.toString() to v.asJsonObject }
        else -> emptyList()
    }
}
