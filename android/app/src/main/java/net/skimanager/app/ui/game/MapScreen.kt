package net.skimanager.app.ui.game

import android.graphics.Bitmap
import android.graphics.BitmapFactory
import androidx.compose.foundation.Canvas
import androidx.compose.foundation.background
import androidx.compose.foundation.gestures.detectTapGestures
import androidx.compose.foundation.gestures.detectTransformGestures
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.ModalBottomSheet
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableFloatStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.produceState
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clipToBounds
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.ImageBitmap
import androidx.compose.ui.graphics.Path
import androidx.compose.ui.graphics.StrokeCap
import androidx.compose.ui.graphics.StrokeJoin
import androidx.compose.ui.graphics.asImageBitmap
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.ui.graphics.drawscope.withTransform
import androidx.compose.ui.input.pointer.pointerInput
import androidx.compose.ui.platform.LocalDensity
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.IntOffset
import androidx.compose.ui.unit.IntSize
import androidx.compose.ui.unit.dp
import com.google.gson.JsonObject
import com.google.gson.JsonParser
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import net.skimanager.app.api.ApiResult
import net.skimanager.app.api.SERVER_ERROR
import net.skimanager.app.api.bool
import net.skimanager.app.api.int
import net.skimanager.app.api.obj
import net.skimanager.app.api.objects
import net.skimanager.app.api.str
import net.skimanager.app.ui.AppState
import net.skimanager.app.ui.common.Loaded
import net.skimanager.app.ui.common.OFFLINE_TEXT
import net.skimanager.app.ui.common.PrimaryButton
import net.skimanager.app.ui.theme.LocalSkiColors
import kotlin.math.hypot
import kotlin.math.max
import kotlin.math.min

/** A map segment in map units: x runs right, y runs down, same space as the map image */
private class Segment(val data: JsonObject, val points: List<Offset>) {
    val id = data.str("id")
    val type = data.str("type")
    val path = Path().apply {
        points.forEachIndexed { i, p -> if (i == 0) moveTo(p.x, p.y) else lineTo(p.x, p.y) }
    }

    fun distanceTo(p: Offset): Float {
        var best = Float.MAX_VALUE
        points.zipWithNext { a, b ->
            val d = b - a
            val len = d.x * d.x + d.y * d.y
            val t = if (len == 0f) 0f else (((p.x - a.x) * d.x + (p.y - a.y) * d.y) / len).coerceIn(0f, 1f)
            best = min(best, hypot(p.x - (a.x + d.x * t), p.y - (a.y + d.y * t)))
        }
        return best
    }
}

private const val MAX_MAP_PIXELS = 3000

private val EDITOR_NAME = Regex("""^(Lift Line|Slope Path) \d+$""")

/** Unbuilt segments still carry the map editor's placeholder name */
private fun titleOf(s: Segment, built: JsonObject?): String {
    built?.str("name")?.takeIf { it.isNotEmpty() && !EDITOR_NAME.matches(it) }?.let { return it }
    val name = s.data.str("name")
    if (name.isNotEmpty() && !EDITOR_NAME.matches(name)) return name
    return when {
        s.type == "lift" -> "Lift"
        s.type == "snowpark" -> "Terrain park"
        else -> kindOf(s) + " run"
    }
}

private fun kindOf(s: Segment): String = when (s.data.str("difficulty")) {
    "green" -> "Green"
    "blue" -> "Blue"
    "black" -> "Black"
    "double_black" -> "Double black"
    else -> "Ski"
}

private fun colorOf(s: Segment): Color = when {
    s.type == "lift" -> Color(0xFFEE9733)
    s.type == "snowpark" -> Color(0xFF9B6BFF)
    else -> when (s.data.str("difficulty")) {
        "green" -> Color(0xFF00A35C)
        "blue" -> Color(0xFF1D6FE0)
        else -> Color(0xFF14181F)
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun MapScreen(app: AppState) {
    val scope = rememberCoroutineScope()
    var selectedId by remember { mutableStateOf<String?>(null) }
    var note by remember { mutableStateOf<String?>(null) }
    var busy by remember { mutableStateOf(false) }

    Loaded("map", { app.api.get("map") }) { data, reload ->
        val config = data.obj("mapConfig")
        val worldW = config.int("width").toFloat().coerceAtLeast(1f)
        val worldH = config.int("height").toFloat().coerceAtLeast(1f)
        val segments = remember(data) {
            data.objects("segments").mapNotNull { seg ->
                val raw = runCatching { JsonParser.parseString(seg.str("points")).asJsonArray }.getOrNull() ?: return@mapNotNull null
                // Stored as [lat, lng] with lat counted from the bottom of the image
                val pts = raw.map { it.asJsonArray }.map { Offset(it[1].asFloat, worldH - it[0].asFloat) }
                if (pts.size < 2) null else Segment(seg, pts)
            }
        }
        val builtItems = remember(data) { builtBySegment(data) }
        val image by produceState<ImageBitmap?>(null, config.str("image")) {
            value = app.api.bytes(config.str("image"))?.let { withContext(Dispatchers.Default) { decodeMap(it) } }
        }

        var canvas by remember { mutableStateOf(IntSize.Zero) }
        // What the view looks at: a point on the map and how many pixels one map unit takes
        var center by remember { mutableStateOf(Offset(worldW / 2f, worldH / 2f)) }
        var scale by remember { mutableFloatStateOf(1f) }
        var touched by remember { mutableStateOf(false) }
        val minScale = if (canvas.width == 0) 1f else min(canvas.width / worldW, canvas.height / worldH)
        // Opens on the lifts and runs, not the whole wide image
        LaunchedEffect(canvas, segments) {
            val all = segments.flatMap { it.points }
            if (!touched && canvas.width > 0 && all.isNotEmpty()) {
                val left = all.minOf { it.x }; val right = all.maxOf { it.x }
                val top = all.minOf { it.y }; val bottom = all.maxOf { it.y }
                center = Offset((left + right) / 2f, (top + bottom) / 2f)
                scale = min(canvas.width / ((right - left) * 1.1f), canvas.height / ((bottom - top) * 1.1f)).coerceIn(minScale, minScale * 12f)
            }
        }
        val origin = Offset(canvas.width / 2f, canvas.height / 2f) - center * scale
        val tapRadius = with(LocalDensity.current) { 24.dp.toPx() }
        val colors = LocalSkiColors.current

        Box(Modifier.fillMaxSize().background(MaterialTheme.colorScheme.background)) {
            Canvas(
                Modifier.fillMaxSize().clipToBounds()
                    .pointerInput(minScale) {
                        detectTransformGestures { _, panBy, zoomBy, _ ->
                            touched = true
                            scale = (scale * zoomBy).coerceIn(minScale, minScale * 12f)
                            center = (center - panBy / scale).let { Offset(it.x.coerceIn(0f, worldW), it.y.coerceIn(0f, worldH)) }
                        }
                    }
                    .pointerInput(segments, scale, origin) {
                        detectTapGestures { tap ->
                            val world = (tap - origin) / scale
                            selectedId = segments.minByOrNull { it.distanceTo(world) }
                                ?.takeIf { it.distanceTo(world) * scale <= tapRadius }?.id
                        }
                    },
            ) {
                canvas = IntSize(size.width.toInt(), size.height.toInt())
                withTransform({
                    translate(origin.x, origin.y)
                    scale(scale, scale, Offset.Zero)
                }) {
                    image?.let {
                        drawImage(it, dstOffset = IntOffset.Zero, dstSize = IntSize(worldW.toInt(), worldH.toInt()))
                    }
                    val thin = 2.dp.toPx() / scale
                    segments.forEach { s ->
                        val built = builtItems.containsKey(s.id)
                        val selected = s.id == selectedId
                        drawPath(
                            s.path, colorOf(s).copy(alpha = if (built || selected) 1f else 0.45f),
                            style = Stroke(if (selected) thin * 2.5f else if (built) thin * 1.6f else thin, cap = StrokeCap.Round, join = StrokeJoin.Round),
                        )
                    }
                }
            }
            Column(
                Modifier.align(Alignment.TopStart).padding(12.dp)
                    .background(MaterialTheme.colorScheme.surface.copy(alpha = 0.92f), RoundedCornerShape(8.dp))
                    .padding(horizontal = 12.dp, vertical = 8.dp),
            ) {
                Text(data.str("userCashText"), fontWeight = FontWeight.SemiBold)
                Text("Tap a lift or run", color = colors.muted, style = MaterialTheme.typography.bodySmall)
            }
            note?.let {
                Text(
                    it,
                    Modifier.align(Alignment.BottomCenter).padding(12.dp).fillMaxWidth()
                        .background(MaterialTheme.colorScheme.surface, RoundedCornerShape(8.dp)).padding(12.dp),
                )
            }
        }

        val selected = segments.firstOrNull { it.id == selectedId }
        if (selected != null) {
            val built = builtItems[selected.id]
            val meters = selected.data.int("length_meters")
            val length = if (data.bool("isImperial")) "${(meters * 3.28084f).toInt()} ft" else "$meters m"
            ModalBottomSheet({ selectedId = null }, containerColor = MaterialTheme.colorScheme.surface) {
                Column(Modifier.fillMaxWidth().padding(16.dp).padding(bottom = 16.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    Text(titleOf(selected, built), style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.SemiBold)
                    val kind = if (selected.type == "lift") "Lift" else if (selected.type == "snowpark") "Terrain park" else kindOf(selected) + " run"
                    Text("$kind · $length", color = colors.muted)
                    if (built == null) {
                        PrimaryButton("Build for ${selected.data.str("costText")}", {
                            busy = true
                            scope.launch {
                                val result = app.api.postJson("map/build", JsonObject().apply { addProperty("segment_id", selected.data.int("id")) })
                                busy = false
                                selectedId = null
                                note = outcome(result, "Built.")
                                reload()
                            }
                        }, Modifier.fillMaxWidth(), enabled = !busy)
                    } else {
                        var confirm by remember { mutableStateOf(false) }
                        if (confirm) {
                            Text("Demolish ${titleOf(selected, built)}?", fontWeight = FontWeight.Medium)
                            Text("This can't be undone.")
                            PrimaryButton("Demolish", {
                                busy = true
                                scope.launch {
                                    val result = app.api.post("map/demolish/${built.int("id")}")
                                    busy = false
                                    selectedId = null
                                    note = outcome(result, "Demolished.")
                                    reload()
                                }
                            }, Modifier.fillMaxWidth(), enabled = !busy)
                            TextButton({ confirm = false }, Modifier.fillMaxWidth().heightIn(min = 48.dp)) { Text("Keep it") }
                        } else {
                            TextButton({ confirm = true }, Modifier.fillMaxWidth().heightIn(min = 48.dp)) { Text("Demolish", color = colors.danger) }
                        }
                    }
                    if (built == null) TextButton({ selectedId = null }, Modifier.fillMaxWidth().heightIn(min = 48.dp)) { Text("Cancel") }
                }
            }
        }
    }
}

/** The map images are far bigger than a phone can draw, so they're decoded at a size it can */
private fun decodeMap(bytes: ByteArray): ImageBitmap? = runCatching {
    val bounds = BitmapFactory.Options().apply { inJustDecodeBounds = true }
    BitmapFactory.decodeByteArray(bytes, 0, bytes.size, bounds)
    var sample = 1
    while (max(bounds.outWidth, bounds.outHeight) / sample > MAX_MAP_PIXELS) sample *= 2
    val options = BitmapFactory.Options().apply {
        inSampleSize = sample
        inPreferredConfig = Bitmap.Config.RGB_565
    }
    BitmapFactory.decodeByteArray(bytes, 0, bytes.size, options)?.asImageBitmap()
}.getOrNull()

/** builtItems is an object keyed by segment id when there are any, and an empty list when not */
private fun builtBySegment(data: JsonObject): Map<String, JsonObject> {
    val raw = data.get("builtItems") ?: return emptyMap()
    return when {
        raw.isJsonObject -> raw.asJsonObject.entrySet().filter { it.value.isJsonObject }.associate { it.key to it.value.asJsonObject }
        raw.isJsonArray -> raw.asJsonArray.filter { it.isJsonObject }.map { it.asJsonObject }.associateBy { it.str("segment_id") }
        else -> emptyMap()
    }
}

fun outcome(result: ApiResult, done: String): String = when (result) {
    is ApiResult.Ok -> result.message ?: done
    is ApiResult.Offline -> OFFLINE_TEXT
    is ApiResult.Failed -> result.message.ifBlank { SERVER_ERROR }
}
