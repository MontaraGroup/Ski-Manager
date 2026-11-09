package net.skimanager.app.ui.common

import android.content.Context
import android.content.Intent
import android.net.Uri
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ColumnScope
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.imePadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonGroupDefaults
import androidx.compose.material3.ToggleButton
import androidx.compose.material3.ToggleButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.ExperimentalMaterial3ExpressiveApi
import androidx.compose.material3.LoadingIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import com.google.gson.JsonObject
import net.skimanager.app.BuildConfig
import net.skimanager.app.api.ApiResult
import net.skimanager.app.api.SERVER_ERROR
import net.skimanager.app.ui.theme.LocalSkiColors

const val OFFLINE_TEXT = "You're offline. Connect to the internet to play."

fun openWebsite(context: Context, path: String) {
    context.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(BuildConfig.BASE_URL + path)))
}

/** The screen a failed request leaves: offline, maintenance, season not started, or a server error */
@Composable
fun MessageState(text: String, onRetry: (() -> Unit)? = null) {
    Column(
        Modifier.fillMaxSize().padding(24.dp),
        verticalArrangement = Arrangement.spacedBy(16.dp, Alignment.CenterVertically),
        horizontalAlignment = Alignment.CenterHorizontally,
    ) {
        Text(text, textAlign = TextAlign.Center, style = MaterialTheme.typography.bodyLarge)
        if (onRetry != null) PrimaryButton("Try again", onRetry)
    }
}

/**
 * Loads one screen's data and shows loading, error and offline states on the way.
 * content gets the data and a reload function for after an action.
 */
@OptIn(ExperimentalMaterial3ExpressiveApi::class)
@Composable
fun Loaded(key: Any?, load: suspend () -> ApiResult, content: @Composable (JsonObject, reload: () -> Unit) -> Unit) {
    var attempt by remember { mutableIntStateOf(0) }
    var result by remember(key) { mutableStateOf<ApiResult?>(null) }
    LaunchedEffect(key, attempt) { result = load() }

    when (val r = result) {
        null -> Column(
            Modifier.fillMaxSize(),
            verticalArrangement = Arrangement.spacedBy(12.dp, Alignment.CenterVertically),
            horizontalAlignment = Alignment.CenterHorizontally,
        ) {
            LoadingIndicator()
            Text("Loading…")
        }
        is ApiResult.Offline -> MessageState(OFFLINE_TEXT) { result = null; attempt++ }
        is ApiResult.Failed -> MessageState(if (r.code == null) SERVER_ERROR else r.message) { result = null; attempt++ }
        is ApiResult.Ok -> content(r.data?.takeIf { it.isJsonObject }?.asJsonObject ?: JsonObject()) { attempt++ }
    }
}

@Composable
fun ScreenColumn(content: @Composable ColumnScope.() -> Unit) {
    Column(
        Modifier.fillMaxSize().imePadding().verticalScroll(rememberScrollState()).padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
        content = content,
    )
}

@Composable
fun Panel(title: String? = null, content: @Composable ColumnScope.() -> Unit) {
    Card(
        Modifier.fillMaxWidth(),
        shape = MaterialTheme.shapes.medium,
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
    ) {
        Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
            if (title != null) Text(title, fontWeight = FontWeight.SemiBold, style = MaterialTheme.typography.titleMedium)
            content()
        }
    }
}

/** A label on the left and its value on the right */
@Composable
fun Row2(label: String, value: String, valueColor: Color = Color.Unspecified) {
    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
        Text(label, color = LocalSkiColors.current.muted, modifier = Modifier.weight(1f).padding(end = 12.dp))
        Text(value, color = valueColor, fontWeight = FontWeight.Medium)
    }
}

@OptIn(ExperimentalMaterial3ExpressiveApi::class)
@Composable
fun Figure(label: String, value: String, note: String? = null, valueColor: Color = Color.Unspecified) {
    Panel {
        Text(label, color = LocalSkiColors.current.muted, style = MaterialTheme.typography.bodyMedium)
        Text(value, color = valueColor, style = MaterialTheme.typography.headlineMediumEmphasized, fontWeight = FontWeight.SemiBold)
        if (note != null) Text(note, color = LocalSkiColors.current.muted, style = MaterialTheme.typography.bodySmall)
    }
}

@Composable
fun EmptyText(text: String) {
    Box(Modifier.fillMaxWidth().padding(vertical = 24.dp), contentAlignment = Alignment.Center) {
        Text(text, color = LocalSkiColors.current.muted, textAlign = TextAlign.Center)
    }
}

/** One-tap choice between a few options, such as units or difficulty */
@OptIn(ExperimentalMaterial3ExpressiveApi::class)
@Composable
fun ChoiceRow(options: List<Pair<String, String>>, selected: String?, onSelect: (String) -> Unit) {
    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(ButtonGroupDefaults.ConnectedSpaceBetween)) {
        options.forEachIndexed { index, (key, label) ->
            val checked = key == selected
            ToggleButton(
                checked = checked,
                onCheckedChange = { onSelect(key) },
                modifier = Modifier.weight(1f).heightIn(min = 48.dp),
                shapes = when (index) {
                    0 -> ButtonGroupDefaults.connectedLeadingButtonShapes()
                    options.lastIndex -> ButtonGroupDefaults.connectedTrailingButtonShapes()
                    else -> ButtonGroupDefaults.connectedMiddleButtonShapes()
                },
                colors = ToggleButtonDefaults.colors(
                    containerColor = LocalSkiColors.current.surfaceSecondary,
                    contentColor = MaterialTheme.colorScheme.onSurface,
                    checkedContainerColor = MaterialTheme.colorScheme.primary,
                    checkedContentColor = MaterialTheme.colorScheme.onPrimary,
                ),
                border = if (checked) null else androidx.compose.foundation.BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant),
            ) { Text(label, maxLines = 1, textAlign = TextAlign.Center) }
        }
    }
}

/** The shape morphs on press, which is Expressive's main button change */
@OptIn(ExperimentalMaterial3ExpressiveApi::class)
@Composable
fun PrimaryButton(text: String, onClick: () -> Unit, modifier: Modifier = Modifier, enabled: Boolean = true) {
    Button(onClick = onClick, shapes = ButtonDefaults.shapes(), modifier = modifier.heightIn(min = 48.dp), enabled = enabled) { Text(text) }
}
