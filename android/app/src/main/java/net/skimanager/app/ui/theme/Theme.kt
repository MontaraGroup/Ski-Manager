package net.skimanager.app.ui.theme

import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.ExperimentalMaterial3ExpressiveApi
import androidx.compose.material3.MaterialExpressiveTheme
import androidx.compose.material3.MotionScheme
import androidx.compose.material3.Shapes
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.runtime.CompositionLocalProvider
import androidx.compose.runtime.staticCompositionLocalOf
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.unit.dp

/** The site's color tokens (DESIGN.md), converted from oklch, for what Material has no slot for */
data class SkiColors(val muted: Color, val link: Color, val success: Color, val warning: Color, val danger: Color, val surfaceSecondary: Color)

private val Snow = Color(0xFFFFFFFF)

private val DarkSki = SkiColors(
    muted = Color(0xFF9BA6B1), link = Color(0xFF3BA6F5), success = Color(0xFF00C470),
    warning = Color(0xFFEE9733), danger = Color(0xFFEC5B57), surfaceSecondary = Color(0xFF1C222B),
)
private val LightSki = SkiColors(
    muted = Color(0xFF515B6C), link = Color(0xFF0053CB), success = Color(0xFF007434),
    warning = Color(0xFF9E4700), danger = Color(0xFFC21725), surfaceSecondary = Color(0xFFE4E8EF),
)

private val DarkScheme = darkColorScheme(
    primary = Color(0xFF006CC8), onPrimary = Snow,
    background = Color(0xFF0C1016), onBackground = Color(0xFFD3E0EA),
    surface = Color(0xFF151B24), onSurface = Color(0xFFD3E0EA),
    surfaceVariant = Color(0xFF1C222B), onSurfaceVariant = Color(0xFF9BA6B1),
    surfaceContainer = Color(0xFF151B24), surfaceContainerHigh = Color(0xFF1C222B),
    secondaryContainer = Color(0xFF1C222B), onSecondaryContainer = Color(0xFFD3E0EA),
    error = Color(0xFFEC5B57), onError = Color(0xFF0C1016), outline = Color(0xFF9BA6B1), outlineVariant = Color(0xFF2A313B),
)
private val LightScheme = lightColorScheme(
    primary = Color(0xFF0053CB), onPrimary = Snow,
    background = Color(0xFFEFF2F7), onBackground = Color(0xFF1D293D),
    surface = Color(0xFFFDFDFF), onSurface = Color(0xFF1D293D),
    surfaceVariant = Color(0xFFE4E8EF), onSurfaceVariant = Color(0xFF515B6C),
    surfaceContainer = Color(0xFFFDFDFF), surfaceContainerHigh = Color(0xFFE4E8EF),
    secondaryContainer = Color(0xFFE4E8EF), onSecondaryContainer = Color(0xFF1D293D),
    error = Color(0xFFC21725), onError = Snow, outline = Color(0xFF515B6C), outlineVariant = Color(0xFFD5DAE2),
)

val LocalSkiColors = staticCompositionLocalOf { DarkSki }

private val ExpressiveShapes = Shapes(
    extraSmall = RoundedCornerShape(8.dp),
    small = RoundedCornerShape(12.dp),
    medium = RoundedCornerShape(20.dp),
    large = RoundedCornerShape(28.dp),
    extraLarge = RoundedCornerShape(32.dp),
)

/** Material 3 Expressive motion and shapes, with the site's colors rather than wallpaper ones */
@OptIn(ExperimentalMaterial3ExpressiveApi::class)
@Composable
fun SkiManagerTheme(content: @Composable () -> Unit) {
    val dark = isSystemInDarkTheme()
    CompositionLocalProvider(LocalSkiColors provides if (dark) DarkSki else LightSki) {
        MaterialExpressiveTheme(
            colorScheme = if (dark) DarkScheme else LightScheme,
            motionScheme = MotionScheme.expressive(),
            shapes = ExpressiveShapes,
            content = content,
        )
    }
}
