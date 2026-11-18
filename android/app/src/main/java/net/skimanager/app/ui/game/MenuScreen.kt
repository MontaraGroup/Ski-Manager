package net.skimanager.app.ui.game

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowForwardIos
import androidx.compose.material.icons.automirrored.filled.OpenInNew
import androidx.compose.material.icons.filled.AcUnit
import androidx.compose.material.icons.filled.AccountBalance
import androidx.compose.material.icons.filled.Apps
import androidx.compose.material.icons.filled.Assessment
import androidx.compose.material.icons.filled.Bolt
import androidx.compose.material.icons.filled.Build
import androidx.compose.material.icons.filled.Campaign
import androidx.compose.material.icons.filled.Cloud
import androidx.compose.material.icons.filled.ConfirmationNumber
import androidx.compose.material.icons.filled.DirectionsBus
import androidx.compose.material.icons.filled.EmojiEvents
import androidx.compose.material.icons.filled.Eco
import androidx.compose.material.icons.filled.Gavel
import androidx.compose.material.icons.filled.Groups
import androidx.compose.material.icons.filled.Handshake
import androidx.compose.material.icons.filled.HowToVote
import androidx.compose.material.icons.filled.History
import androidx.compose.material.icons.filled.Hotel
import androidx.compose.material.icons.filled.Landscape
import androidx.compose.material.icons.filled.Leaderboard
import androidx.compose.material.icons.filled.LocalFireDepartment
import androidx.compose.material.icons.filled.LocalHospital
import androidx.compose.material.icons.filled.LocalParking
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material.icons.filled.Map
import androidx.compose.material.icons.automirrored.filled.MenuBook
import androidx.compose.material.icons.filled.MilitaryTech
import androidx.compose.material.icons.filled.Mood
import androidx.compose.material.icons.filled.Newspaper
import androidx.compose.material.icons.filled.Nightlight
import androidx.compose.material.icons.filled.Notifications
import androidx.compose.material.icons.filled.Paid
import androidx.compose.material.icons.filled.PhotoCamera
import androidx.compose.material.icons.filled.Restaurant
import androidx.compose.material.icons.filled.School
import androidx.compose.material.icons.filled.Science
import androidx.compose.material.icons.filled.Security
import androidx.compose.material.icons.filled.Settings
import androidx.compose.material.icons.filled.ShoppingBag
import androidx.compose.material.icons.filled.Snowboarding
import androidx.compose.material.icons.filled.Spa
import androidx.compose.material.icons.filled.Star
import androidx.compose.material.icons.filled.Storefront
import androidx.compose.material.icons.filled.SupportAgent
import androidx.compose.material.icons.filled.WaterDrop
import androidx.compose.material.icons.filled.WbSunny
import androidx.compose.material.icons.filled.Apartment
import androidx.compose.material.icons.filled.AccountBalanceWallet
import androidx.compose.material.icons.filled.Agriculture
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.google.gson.JsonObject
import net.skimanager.app.api.bool
import net.skimanager.app.api.objects
import net.skimanager.app.api.str
import net.skimanager.app.ui.AppState
import net.skimanager.app.ui.common.StatusBadge
import net.skimanager.app.ui.common.openWebsite
import net.skimanager.app.ui.theme.LocalSkiColors

/** Website path to the app route that shows it. Everything in the website's menu has a native screen */
fun routeForPath(path: String): String? = when (path) {
    "hotels" -> "buildings/hotel"
    "restaurants" -> "buildings/restaurant"
    "rentals" -> "buildings/rental"
    "retail" -> "buildings/retail"
    "transportation" -> "buildings/transportation"
    "ski-patrol" -> "buildings/ski_patrol"
    in NATIVE_PAGES -> path
    else -> null
}

/** Routes registered in the game shell (App.kt) under the same name as the website path */
val NATIVE_PAGES = setOf(
    "dashboard", "map", "staff", "finances", "bank", "tickets", "equipment", "grooming", "snowmaking", "weather",
    "notifications", "leaderboard", "settings", "resort", "night-skiing", "scenic-lifts", "terrain-parks", "parking",
    "resort-analysis", "energy", "water", "real-estate", "off-season", "marketing", "ski-lessons", "compliance",
    "alliances", "emergency", "tournaments", "vip-guests", "achievements", "daily-bonus", "genepis", "activity", "vote",
    "roadmap", "support", "insurance", "government", "environment", "morale", "updates", "beta",
)

private val ICONS: Map<String, ImageVector> = mapOf(
    "fa-mountain-sun" to Icons.Default.Landscape, "fa-map" to Icons.Default.Map, "fa-tractor" to Icons.Default.Agriculture,
    "fa-snowflake" to Icons.Default.AcUnit, "fa-moon" to Icons.Default.Nightlight, "fa-camera" to Icons.Default.PhotoCamera,
    "fa-person-snowboarding" to Icons.Default.Snowboarding, "fa-square-parking" to Icons.Default.LocalParking,
    "fa-clipboard-check" to Icons.Default.Assessment, "fa-bolt" to Icons.Default.Bolt, "fa-droplet" to Icons.Default.WaterDrop,
    "fa-hotel" to Icons.Default.Hotel, "fa-utensils" to Icons.Default.Restaurant, "fa-person-skiing" to Icons.Default.ShoppingBag,
    "fa-store" to Icons.Default.Storefront, "fa-city" to Icons.Default.Apartment, "fa-bus" to Icons.Default.DirectionsBus,
    "fa-sun" to Icons.Default.WbSunny, "fa-shield-halved" to Icons.Default.Security, "fa-coins" to Icons.Default.Paid,
    "fa-building-columns" to Icons.Default.AccountBalance, "fa-ticket" to Icons.Default.ConfirmationNumber,
    "fa-users" to Icons.Default.Groups, "fa-bullhorn" to Icons.Default.Campaign, "fa-chalkboard-user" to Icons.Default.School,
    "fa-scale-balanced" to Icons.Default.Gavel, "fa-shop" to Icons.Default.Build, "fa-handshake" to Icons.Default.Handshake,
    "fa-cloud-sun" to Icons.Default.Cloud, "fa-truck-medical" to Icons.Default.LocalHospital, "fa-trophy" to Icons.Default.EmojiEvents,
    "fa-star" to Icons.Default.Star, "fa-award" to Icons.Default.MilitaryTech, "fa-ranking-star" to Icons.Default.Leaderboard,
    "fa-fire" to Icons.Default.LocalFireDepartment, "fa-seedling" to Icons.Default.Spa, "fa-clock-rotate-left" to Icons.Default.History,
    "fa-check-to-slot" to Icons.Default.HowToVote, "fa-map-location-dot" to Icons.Default.Map, "fa-book" to Icons.AutoMirrored.Filled.MenuBook,
    "fa-headset" to Icons.Default.SupportAgent, "fa-gear" to Icons.Default.Settings, "fa-leaf" to Icons.Default.Eco,
    "fa-face-smile" to Icons.Default.Mood, "fa-newspaper" to Icons.Default.Newspaper, "fa-flask" to Icons.Default.Science,
    "fa-bell" to Icons.Default.Notifications, "fa-landmark" to Icons.Default.AccountBalanceWallet,
)

/** The More tab: the website's menu (GET menu), so switched-off and locked features match the site */
@Composable
fun MenuScreen(app: AppState, onOpen: (String) -> Unit) {
    val context = LocalContext.current
    ApiPage(app, "menu") { data, _ ->
        data.objects("sections").forEach { section ->
            Text(
                section.str("title"),
                style = MaterialTheme.typography.titleSmall,
                fontWeight = FontWeight.Bold,
                color = LocalSkiColors.current.muted,
                modifier = Modifier.padding(top = 8.dp, start = 4.dp),
            )
            section.objects("items").forEach { item ->
                val route = if (item.bool("locked")) "achievements" else routeForPath(item.str("path"))
                MenuRow(item) {
                    when {
                        item.bool("external") -> openWebsite(context, item.str("url"))
                        route != null -> onOpen(route)
                        else -> openWebsite(context, item.str("url"))
                    }
                }
            }
        }
        // Notifications live in the site's top bar rather than its menu
        MenuRow(JsonObject().apply { addProperty("label", "Notifications"); addProperty("icon", "fa-bell") }) { onOpen("notifications") }
    }
}

@Composable
private fun MenuRow(item: JsonObject, onClick: () -> Unit) {
    val locked = item.bool("locked")
    Card(
        onClick = onClick,
        modifier = Modifier.fillMaxWidth().heightIn(min = 56.dp),
        shape = MaterialTheme.shapes.large,
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceContainer, contentColor = MaterialTheme.colorScheme.onSurface),
        border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant),
    ) {
        Row(
            Modifier.fillMaxWidth().padding(horizontal = 16.dp, vertical = 12.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(14.dp),
        ) {
            Surface(shape = MaterialTheme.shapes.medium, color = MaterialTheme.colorScheme.primary.copy(alpha = 0.12f), modifier = Modifier.size(40.dp)) {
                Box(contentAlignment = Alignment.Center) {
                    Icon(ICONS[item.str("icon")] ?: Icons.Default.Apps, null, tint = MaterialTheme.colorScheme.primary, modifier = Modifier.size(22.dp))
                }
            }
            Column(Modifier.weight(1f)) {
                Text(item.str("label"), fontWeight = FontWeight.SemiBold, style = MaterialTheme.typography.titleMedium)
                if (locked) Text("Locked: see Achievements", color = LocalSkiColors.current.muted, style = MaterialTheme.typography.bodySmall)
            }
            when {
                item.bool("bonusDue") -> StatusBadge("READY", LocalSkiColors.current.success, LocalSkiColors.current.success.copy(alpha = 0.15f))
                item.bool("isNew") -> StatusBadge("NEW", MaterialTheme.colorScheme.primary, MaterialTheme.colorScheme.primary.copy(alpha = 0.15f))
                item.bool("beta") -> StatusBadge("BETA", LocalSkiColors.current.warning, LocalSkiColors.current.warning.copy(alpha = 0.15f))
            }
            Icon(
                when {
                    locked -> Icons.Default.Lock
                    item.bool("external") -> Icons.AutoMirrored.Filled.OpenInNew
                    else -> Icons.AutoMirrored.Filled.ArrowForwardIos
                },
                null, tint = LocalSkiColors.current.muted, modifier = Modifier.size(16.dp),
            )
        }
    }
}
