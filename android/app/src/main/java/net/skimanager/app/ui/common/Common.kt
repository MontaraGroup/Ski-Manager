package net.skimanager.app.ui.common

import android.content.Context
import android.content.Intent
import android.net.Uri
import androidx.compose.foundation.BorderStroke
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
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.ButtonGroupDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.ExperimentalMaterial3ExpressiveApi
import androidx.compose.material3.FilledTonalButton
import androidx.compose.material3.LoadingIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.ToggleButton
import androidx.compose.material3.ToggleButtonDefaults
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
        Card(
            shape = MaterialTheme.shapes.large,
            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceContainer),
            border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant),
            modifier = Modifier.fillMaxWidth().padding(horizontal = 8.dp),
        ) {
            Column(
                Modifier.padding(24.dp).fillMaxWidth(),
                horizontalAlignment = Alignment.CenterHorizontally,
                verticalArrangement = Arrangement.spacedBy(16.dp),
            ) {
                Text(text, textAlign = TextAlign.Center, style = MaterialTheme.typography.bodyLarge)
                if (onRetry != null) PrimaryButton("Try again", onRetry)
            }
        }
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
            Text("Loading…", style = MaterialTheme.typography.bodyMedium, color = LocalSkiColors.current.muted)
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

/** Expressive Panel card with surface container depth and subtle outline stroke */
@Composable
fun Panel(title: String? = null, modifier: Modifier = Modifier, content: @Composable ColumnScope.() -> Unit) {
    Card(
        modifier.fillMaxWidth(),
        shape = MaterialTheme.shapes.large,
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.surfaceContainer,
            contentColor = MaterialTheme.colorScheme.onSurface,
        ),
        border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant),
    ) {
        Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
            if (title != null) Text(title, fontWeight = FontWeight.Bold, style = MaterialTheme.typography.titleMedium)
            content()
        }
    }
}

/** A label on the left and its value on the right */
@Composable
fun Row2(label: String, value: String, valueColor: Color = Color.Unspecified) {
    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
        Text(label, color = LocalSkiColors.current.muted, modifier = Modifier.weight(1f).padding(end = 12.dp))
        Text(value, color = valueColor, fontWeight = FontWeight.SemiBold)
    }
}

@OptIn(ExperimentalMaterial3ExpressiveApi::class)
@Composable
fun Figure(label: String, value: String, note: String? = null, valueColor: Color = Color.Unspecified, modifier: Modifier = Modifier) {
    Panel(modifier = modifier) {
        Text(label, color = LocalSkiColors.current.muted, style = MaterialTheme.typography.labelMedium)
        Text(value, color = valueColor, style = MaterialTheme.typography.headlineMediumEmphasized, fontWeight = FontWeight.Bold)
        if (note != null) Text(note, color = LocalSkiColors.current.muted, style = MaterialTheme.typography.bodySmall)
    }
}

@Composable
fun EmptyText(text: String) {
    Box(Modifier.fillMaxWidth().padding(vertical = 24.dp), contentAlignment = Alignment.Center) {
        Text(text, color = LocalSkiColors.current.muted, textAlign = TextAlign.Center, style = MaterialTheme.typography.bodyMedium)
    }
}

/** Expressive status pill badge */
@Composable
fun StatusBadge(text: String, color: Color, backgroundColor: Color, modifier: Modifier = Modifier) {
    Surface(
        shape = RoundedCornerShape(percent = 50),
        color = backgroundColor,
        modifier = modifier.heightIn(min = 26.dp),
    ) {
        Text(
            text,
            color = color,
            fontWeight = FontWeight.Bold,
            style = MaterialTheme.typography.labelMedium,
            modifier = Modifier.padding(horizontal = 10.dp, vertical = 4.dp),
        )
    }
}

/** One-tap choice between a few options, using Material 3 Expressive Connected Toggle Buttons */
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
                border = if (checked) null else BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant),
            ) { Text(label, maxLines = 1, textAlign = TextAlign.Center, fontWeight = if (checked) FontWeight.Bold else FontWeight.Medium) }
        }
    }
}

/** Primary button using Material 3 Expressive morphing shapes */
@OptIn(ExperimentalMaterial3ExpressiveApi::class)
@Composable
fun PrimaryButton(text: String, onClick: () -> Unit, modifier: Modifier = Modifier, enabled: Boolean = true) {
    Button(
        onClick = onClick,
        shapes = ButtonDefaults.shapes(),
        modifier = modifier.heightIn(min = 48.dp),
        enabled = enabled,
    ) { Text(text, fontWeight = FontWeight.SemiBold) }
}

/** Secondary filled tonal button using Material 3 Expressive shapes */
@OptIn(ExperimentalMaterial3ExpressiveApi::class)
@Composable
fun SecondaryButton(text: String, onClick: () -> Unit, modifier: Modifier = Modifier, enabled: Boolean = true) {
    FilledTonalButton(
        onClick = onClick,
        shapes = ButtonDefaults.shapes(),
        modifier = modifier.heightIn(min = 48.dp),
        enabled = enabled,
    ) { Text(text, fontWeight = FontWeight.SemiBold) }
}

/** Outlined button using Material 3 Expressive shapes */
@OptIn(ExperimentalMaterial3ExpressiveApi::class)
@Composable
fun OutlinedPrimaryButton(text: String, onClick: () -> Unit, modifier: Modifier = Modifier, enabled: Boolean = true) {
    OutlinedButton(
        onClick = onClick,
        shapes = ButtonDefaults.shapes(),
        modifier = modifier.heightIn(min = 48.dp),
        enabled = enabled,
    ) { Text(text, fontWeight = FontWeight.SemiBold) }
}

