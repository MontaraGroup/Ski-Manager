package net.skimanager.app.ui.game

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.clickable
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
import androidx.compose.material.icons.filled.Build
import androidx.compose.material.icons.filled.Cloud
import androidx.compose.material.icons.filled.ConfirmationNumber
import androidx.compose.material.icons.filled.Leaderboard
import androidx.compose.material.icons.filled.Notifications
import androidx.compose.material.icons.filled.Settings
import androidx.compose.material.icons.filled.Snowboarding
import androidx.compose.material.icons.filled.Storefront
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.dp
import kotlinx.coroutines.launch
import net.skimanager.app.api.ApiResult
import net.skimanager.app.api.SERVER_ERROR
import net.skimanager.app.api.bool
import net.skimanager.app.api.int
import net.skimanager.app.api.obj
import net.skimanager.app.api.objects
import net.skimanager.app.api.str
import net.skimanager.app.api.strOrNull
import net.skimanager.app.ui.AppState
import net.skimanager.app.ui.common.ChoiceRow
import net.skimanager.app.ui.common.EmptyText
import net.skimanager.app.ui.common.Figure
import net.skimanager.app.ui.common.Loaded
import net.skimanager.app.ui.common.OFFLINE_TEXT
import net.skimanager.app.ui.common.Panel
import net.skimanager.app.ui.common.PrimaryButton
import net.skimanager.app.ui.common.Row2
import net.skimanager.app.ui.common.ScreenColumn
import net.skimanager.app.ui.common.SecondaryButton
import net.skimanager.app.ui.common.StatusBadge
import net.skimanager.app.ui.common.openWebsite
import net.skimanager.app.ui.theme.LocalSkiColors

@Composable
private fun money(positive: Boolean) = if (positive) LocalSkiColors.current.success else LocalSkiColors.current.danger

@Composable
fun DashboardScreen(app: AppState) {
    Loaded("dashboard", { app.api.get("dashboard") }) { data, _ ->
        val hero = data.obj("hero")
        val kpis = data.obj("kpis")
        val weather = kpis.obj("weather")
        val open = hero.bool("resortOpen")
        ScreenColumn {
            Panel {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically,
                ) {
                    Column(modifier = Modifier.weight(1f).padding(end = 12.dp)) {
                        Text(hero.str("resortMapName"), style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.Bold)
                        Text(
                            "Season ${hero.int("seasonNum")} · Day ${hero.int("gameDay")}",
                            color = LocalSkiColors.current.muted,
                            style = MaterialTheme.typography.bodyMedium,
                        )
                    }
                    StatusBadge(
                        text = if (open) "OPEN" else "CLOSED",
                        color = if (open) LocalSkiColors.current.success else LocalSkiColors.current.danger,
                        backgroundColor = (if (open) LocalSkiColors.current.success else LocalSkiColors.current.danger).copy(alpha = 0.15f),
                    )
                }
                Text(
                    "${hero.int("liftCount")} of ${hero.int("totalLifts")} lifts · ${hero.int("slopeCount")} of ${hero.int("totalSlopes")} slopes",
                    style = MaterialTheme.typography.bodyMedium,
                )
            }
            data.obj("estimate").takeIf { it.size() > 0 }?.let { e ->
                Panel("Tonight's estimate") {
                    Text(
                        "About ${e.str("skiers")} skiers, ${e.str("income")} income, ${e.str("costs")} costs. Your first day is settled at 00:05 server time.",
                        style = MaterialTheme.typography.bodyMedium,
                    )
                }
            }
            data.objects("alerts").forEach { alert ->
                val danger = alert.str("type") == "danger"
                val tint = if (danger) LocalSkiColors.current.danger else LocalSkiColors.current.warning
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    shape = MaterialTheme.shapes.medium,
                    colors = CardDefaults.cardColors(containerColor = tint.copy(alpha = 0.12f)),
                    border = BorderStroke(1.dp, tint.copy(alpha = 0.35f)),
                ) {
                    Text(
                        alert.str("msg"),
                        color = tint,
                        fontWeight = FontWeight.SemiBold,
                        style = MaterialTheme.typography.bodyMedium,
                        modifier = Modifier.padding(14.dp),
                    )
                }
            }
            Figure("Cash", kpis.str("cashBalanceText"), "Lifetime ${kpis.str("netProfitText")}", if (kpis.bool("cashNegative")) LocalSkiColors.current.danger else androidx.compose.ui.graphics.Color.Unspecified)
            Figure("Net per day", kpis.str("dailyProfitText"), "${kpis.str("incomeText")} in · ${kpis.str("expensesText")} out", money(kpis.bool("dailyProfitPositive")))
            Figure("Visitors today", kpis.str("dailyVisitorsFormatted"), "Ticket ${kpis.str("ticketPriceText")}")
            Figure("Weather", weather.str("tempFormatted"), "${weather.str("condition_name")} · ${weather.str("snowBaseFormatted")} base")
        }
    }
}

@Composable
fun FinancesScreen(app: AppState) {
    Loaded("finances", { app.api.get("finances") }) { data, _ ->
        val cash = data.obj("cash")
        val profit = data.obj("profit")
        val income = data.obj("income")
        val expenses = data.obj("expenses")
        val lifetime = data.obj("lifetime")
        val transactions = data.objects("transactions")
        ScreenColumn {
            Figure("Cash", cash.str("text"), null, if (cash.bool("negative")) LocalSkiColors.current.danger else androidx.compose.ui.graphics.Color.Unspecified)
            Figure("Profit per day", profit.str("text"), profit.strOrNull("note"), money(profit.bool("positive")))
            Panel("Income: ${income.str("total")}") { income.objects("rows").forEach { Row2(it.str("label"), it.str("amount")) } }
            Panel("Costs: ${expenses.str("total")}") { expenses.objects("rows").forEach { Row2(it.str("label"), it.str("amount")) } }
            Panel("All time") {
                Row2("Income", lifetime.str("income"))
                Row2("Costs", lifetime.str("expenses"))
                Row2("Net", lifetime.str("net"), money(lifetime.bool("positive")))
            }
            Panel("Recent transactions") {
                if (transactions.isEmpty()) EmptyText("No transactions yet. Your first day is settled tonight.")
                transactions.forEach { t ->
                    Row2("Day ${t.int("day")}: ${t.str("description")}", t.str("amount"), money(t.bool("income")))
                }
            }
        }
    }
}

@Composable
fun WeatherScreen(app: AppState) {
    Loaded("weather", { app.api.get("weather") }) { data, _ ->
        val now = data.obj("now")
        ScreenColumn {
            Figure(now.str("condition"), now.str("temp"), "High ${now.str("high")} · Low ${now.str("low")}")
            Panel {
                Row2("New snow", now.str("snowfall"))
                Row2("Snow base", now.str("snowBase"), if (now.bool("thinBase")) LocalSkiColors.current.warning else androidx.compose.ui.graphics.Color.Unspecified)
                Row2("Wind", now.str("wind"))
                Row2("Visibility", now.str("visibility"))
                Row2("Humidity", now.str("humidity"))
            }
            Panel("What it does to your resort") {
                data.objects("effects").forEach { e ->
                    Text(e.str("title"), fontWeight = FontWeight.SemiBold, style = MaterialTheme.typography.titleSmall)
                    Text(e.str("text"), color = LocalSkiColors.current.muted, style = MaterialTheme.typography.bodyMedium)
                }
            }
            Panel("Next days") {
                data.objects("forecast").forEach { f ->
                    Row2("Day ${f.int("day")}: ${f.str("condition")}", "${f.str("temp")} · ${f.str("skiers")}")
                }
            }
        }
    }
}

@Composable
fun NotificationsScreen(app: AppState) {
    val scope = rememberCoroutineScope()
    Loaded("notifications", { app.api.get("notifications") }) { data, reload ->
        val items = data.objects("items")
        ScreenColumn {
            if (data.int("unreadCount") > 0) {
                SecondaryButton("Mark all read", {
                    scope.launch { app.api.post("notifications/read-all"); reload() }
                }, Modifier.fillMaxWidth())
            }
            if (items.isEmpty()) EmptyText("No notifications yet.")
            items.forEach { n ->
                Panel {
                    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                        Text(n.str("title"), fontWeight = FontWeight.Bold, style = MaterialTheme.typography.titleSmall, modifier = Modifier.weight(1f))
                        if (!n.bool("isRead")) {
                            StatusBadge("NEW", MaterialTheme.colorScheme.primary, MaterialTheme.colorScheme.primary.copy(alpha = 0.15f))
                        }
                    }
                    Text(n.str("message"), style = MaterialTheme.typography.bodyMedium)
                    Text(n.str("time"), color = LocalSkiColors.current.muted, style = MaterialTheme.typography.labelSmall)
                }
            }
        }
    }
}

@Composable
fun LeaderboardScreen(app: AppState) {
    var difficulty by rememberSaveable { mutableStateOf<String?>(null) }
    Loaded(difficulty, { app.api.get("leaderboard", difficulty?.let { mapOf("difficulty" to it) }.orEmpty()) }) { data, _ ->
        val shown = data.str("difficulty")
        val players = data.objects("players")
        ScreenColumn {
            ChoiceRow(listOf("easy" to "Easy", "standard" to "Standard", "hard" to "Hard"), shown) { difficulty = it }
            if (players.isEmpty()) EmptyText("No resorts ranked yet.")
            players.forEach { p ->
                val isMe = p.bool("isMe")
                val rank = p.int("rank")
                Panel {
                    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                        Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            Text("$rank.", fontWeight = FontWeight.Bold, style = MaterialTheme.typography.titleMedium, color = when (rank) {
                                1 -> LocalSkiColors.current.warning
                                2 -> LocalSkiColors.current.muted
                                else -> MaterialTheme.colorScheme.onSurface
                            })
                            Text(p.str("username"), fontWeight = if (isMe) FontWeight.Bold else FontWeight.SemiBold, style = MaterialTheme.typography.titleMedium)
                        }
                        if (isMe) {
                            StatusBadge("YOU", MaterialTheme.colorScheme.primary, MaterialTheme.colorScheme.primary.copy(alpha = 0.15f))
                        }
                    }
                    Row2(if (data.bool("v2")) "Season profit" else "Cash", if (data.bool("v2")) p.str("profit") else p.str("cash"))
                    Text("${p.int("lifts")} lifts · ${p.int("slopes")} slopes · ${p.int("buildings")} buildings", color = LocalSkiColors.current.muted, style = MaterialTheme.typography.bodySmall)
                }
            }
        }
    }
}

private val WEBSITE_PAGES = listOf(
    "Alliances" to "/alliances", "Tournaments" to "/tournaments", "Génépis" to "/genepis", "Support" to "/support",
)

@Composable
fun SettingsScreen(app: AppState) {
    val scope = rememberCoroutineScope()
    val context = LocalContext.current
    var deleting by remember { mutableStateOf(false) }
    Loaded("settings", { app.api.get("settings") }) { data, reload ->
        val user = data.obj("user")
        val imperial = data.str("units") == "imperial"
        ScreenColumn {
            Panel("Account") {
                Row2("Username", user.str("username"))
                Row2("Email", user.str("email"))
                Row2("Resort", data.obj("resort").str("name"))
                Row2("Difficulty", data.str("difficulty").replaceFirstChar { it.uppercase() })
            }
            Panel("Units") {
                ChoiceRow(listOf("metric" to "Metric, €", "imperial" to "Imperial, $"), if (imperial) "imperial" else "metric") { key ->
                    scope.launch { app.api.post("settings/units", mapOf("units" to key)); reload() }
                }
            }
            Panel("More on the website") {
                WEBSITE_PAGES.forEach { (label, path) ->
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .heightIn(min = 48.dp)
                            .clickable { openWebsite(context, path) }
                            .padding(vertical = 12.dp),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically,
                    ) {
                        Text(label, color = LocalSkiColors.current.link, fontWeight = FontWeight.SemiBold)
                        Icon(Icons.AutoMirrored.Filled.OpenInNew, null, tint = LocalSkiColors.current.link, modifier = Modifier.size(18.dp))
                    }
                }
            }
            SecondaryButton("Sign out", {
                scope.launch {
                    app.api.post("auth/logout")
                    app.forget()
                }
            }, Modifier.fillMaxWidth())
            TextButton({ deleting = true }, Modifier.fillMaxWidth().heightIn(min = 48.dp)) {
                Text("Delete account", color = LocalSkiColors.current.danger, fontWeight = FontWeight.SemiBold)
            }
        }
    }
    if (deleting) DeleteAccountDialog(app) { deleting = false }
}

@Composable
private fun DeleteAccountDialog(app: AppState, onClose: () -> Unit) {
    val scope = rememberCoroutineScope()
    var password by remember { mutableStateOf("") }
    var confirm by remember { mutableStateOf("") }
    var error by remember { mutableStateOf<String?>(null) }
    var busy by remember { mutableStateOf(false) }
    AlertDialog(
        onDismissRequest = onClose,
        shape = MaterialTheme.shapes.extraLarge,
        title = { Text("Delete your account?", fontWeight = FontWeight.Bold) },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                Text("This permanently deletes your account, resort and progress. It can't be undone.")
                OutlinedTextField(
                    password, { password = it }, Modifier.fillMaxWidth(),
                    shape = MaterialTheme.shapes.medium,
                    label = { Text("Password") }, singleLine = true,
                    visualTransformation = PasswordVisualTransformation(),
                )
                OutlinedTextField(
                    confirm, { confirm = it }, Modifier.fillMaxWidth(),
                    shape = MaterialTheme.shapes.medium,
                    label = { Text("Type DELETE to confirm") }, singleLine = true,
                )
                error?.let { Text(it, color = LocalSkiColors.current.danger, fontWeight = FontWeight.Medium) }
            }
        },
        confirmButton = {
            TextButton({
                busy = true
                error = null
                scope.launch {
                    val result = app.api.post("account/delete", mapOf("password" to password, "confirm" to confirm))
                    busy = false
                    when (result) {
                        is ApiResult.Ok -> app.forget("Your account has been deleted.")
                        is ApiResult.Offline -> error = OFFLINE_TEXT
                        is ApiResult.Failed -> error = result.message.ifBlank { SERVER_ERROR }
                    }
                }
            }, Modifier.heightIn(min = 48.dp), enabled = !busy && confirm == "DELETE" && password.isNotEmpty()) {
                Text("Delete account", color = LocalSkiColors.current.danger, fontWeight = FontWeight.Bold)
            }
        },
        dismissButton = { TextButton(onClose, Modifier.heightIn(min = 48.dp)) { Text("Cancel") } },
    )
}

private data class MoreItem(val route: String, val label: String, val icon: ImageVector, val subtitle: String)

private val MORE_ITEMS = listOf(
    MoreItem("buildings", "Buildings", Icons.Default.Storefront, "Hotels, restaurants, rentals and patrol"),
    MoreItem("bank", "Bank & Loans", Icons.Default.AccountBalance, "Borrow capital and manage debt"),
    MoreItem("equipment", "Equipment", Icons.Default.Build, "Snowcats, snow guns and maintenance"),
    MoreItem("grooming", "Grooming", Icons.Default.Snowboarding, "Piste preparation and overnight runs"),
    MoreItem("snowmaking", "Snowmaking", Icons.Default.AcUnit, "Cannon coverage and cold forecasts"),
    MoreItem("tickets", "Lift tickets", Icons.Default.ConfirmationNumber, "Pricing and visitor demand"),
    MoreItem("weather", "Weather", Icons.Default.Cloud, "Temperatures, snow base and forecasts"),
    MoreItem("notifications", "Notifications", Icons.Default.Notifications, "In-game alerts and messages"),
    MoreItem("leaderboard", "Leaderboard", Icons.Default.Leaderboard, "Global resort rankings"),
    MoreItem("settings", "Settings", Icons.Default.Settings, "Account, units and preferences"),
)

@Composable
fun MoreScreen(onOpen: (String) -> Unit) {
    ScreenColumn {
        MORE_ITEMS.forEach { item ->
            Card(
                modifier = Modifier
                    .fillMaxWidth()
                    .clickable { onOpen(item.route) },
                shape = MaterialTheme.shapes.large,
                colors = CardDefaults.cardColors(
                    containerColor = MaterialTheme.colorScheme.surfaceContainer,
                    contentColor = MaterialTheme.colorScheme.onSurface,
                ),
                border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant),
            ) {
                Row(
                    modifier = Modifier.fillMaxWidth().padding(16.dp),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(16.dp),
                ) {
                    Surface(
                        shape = MaterialTheme.shapes.medium,
                        color = MaterialTheme.colorScheme.primary.copy(alpha = 0.12f),
                        modifier = Modifier.size(44.dp),
                    ) {
                        Box(contentAlignment = Alignment.Center) {
                            Icon(item.icon, null, tint = MaterialTheme.colorScheme.primary, modifier = Modifier.size(24.dp))
                        }
                    }
                    Column(Modifier.weight(1f)) {
                        Text(item.label, fontWeight = FontWeight.Bold, style = MaterialTheme.typography.titleMedium)
                        Text(item.subtitle, color = LocalSkiColors.current.muted, style = MaterialTheme.typography.bodySmall)
                    }
                    Icon(Icons.AutoMirrored.Filled.ArrowForwardIos, null, tint = LocalSkiColors.current.muted, modifier = Modifier.size(16.dp))
                }
            }
        }
    }
}

