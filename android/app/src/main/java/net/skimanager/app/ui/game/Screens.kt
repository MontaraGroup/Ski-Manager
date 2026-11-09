package net.skimanager.app.ui.game

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.FilterChip
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
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
import net.skimanager.app.ui.common.EmptyText
import net.skimanager.app.ui.common.Figure
import net.skimanager.app.ui.common.Loaded
import net.skimanager.app.ui.common.OFFLINE_TEXT
import net.skimanager.app.ui.common.Panel
import net.skimanager.app.ui.common.PrimaryButton
import net.skimanager.app.ui.common.Row2
import net.skimanager.app.ui.common.ScreenColumn
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
        ScreenColumn {
            Panel {
                Text(hero.str("resortMapName"), style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.SemiBold)
                Text(
                    (if (hero.bool("resortOpen")) "Open" else "Closed") + " · Season ${hero.int("seasonNum")} · Day ${hero.int("gameDay")}",
                    color = LocalSkiColors.current.muted,
                )
                Text("${hero.int("liftCount")} of ${hero.int("totalLifts")} lifts · ${hero.int("slopeCount")} of ${hero.int("totalSlopes")} slopes")
            }
            data.obj("estimate").takeIf { it.size() > 0 }?.let { e ->
                Panel("Tonight's estimate") {
                    Text("About ${e.str("skiers")} skiers, ${e.str("income")} income, ${e.str("costs")} costs. Your first day is settled at 00:05 server time.")
                }
            }
            data.objects("alerts").forEach { alert ->
                Panel {
                    Text(alert.str("msg"), color = if (alert.str("type") == "danger") LocalSkiColors.current.danger else LocalSkiColors.current.warning)
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
                    Text(e.str("title"), fontWeight = FontWeight.Medium)
                    Text(e.str("text"), color = LocalSkiColors.current.muted)
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
                TextButton({ scope.launch { app.api.post("notifications/read-all"); reload() } }, Modifier.heightIn(min = 48.dp)) { Text("Mark all read") }
            }
            if (items.isEmpty()) EmptyText("No notifications yet.")
            items.forEach { n ->
                Panel {
                    Text((if (n.bool("isRead")) "" else "New: ") + n.str("title"), fontWeight = FontWeight.Medium)
                    Text(n.str("message"))
                    Text(n.str("time"), color = LocalSkiColors.current.muted, style = MaterialTheme.typography.bodySmall)
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
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                listOf("easy" to "Easy", "standard" to "Standard", "hard" to "Hard").forEach { (key, label) ->
                    FilterChip(shown == key, { difficulty = key }, { Text(label) }, Modifier.heightIn(min = 48.dp))
                }
            }
            if (players.isEmpty()) EmptyText("No resorts ranked yet.")
            players.forEach { p ->
                Panel {
                    Text("${p.int("rank")}. ${p.str("username")}" + if (p.bool("isMe")) " (you)" else "", fontWeight = FontWeight.Medium)
                    Row2(if (data.bool("v2")) "Season profit" else "Cash", if (data.bool("v2")) p.str("profit") else p.str("cash"))
                    Text("${p.int("lifts")} lifts · ${p.int("slopes")} slopes · ${p.int("buildings")} buildings", color = LocalSkiColors.current.muted)
                }
            }
        }
    }
}

private val WEBSITE_PAGES = listOf(
    "Buildings" to "/hotels", "Snowmaking" to "/snowmaking", "Grooming" to "/grooming", "Bank" to "/bank",
    "Equipment" to "/equipment", "Alliances" to "/alliances", "Tournaments" to "/tournaments", "Génépis" to "/genepis", "Support" to "/support",
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
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    listOf("metric" to "Metric, euros", "imperial" to "Imperial, dollars").forEach { (key, label) ->
                        FilterChip((key == "imperial") == imperial, { scope.launch { app.api.post("settings/units", mapOf("units" to key)); reload() } }, { Text(label) }, Modifier.heightIn(min = 48.dp))
                    }
                }
            }
            Panel("More on the website") {
                WEBSITE_PAGES.forEach { (label, path) ->
                    Text(
                        label,
                        color = LocalSkiColors.current.link,
                        modifier = Modifier.fillMaxWidth().heightIn(min = 48.dp).clickable { openWebsite(context, path) }.padding(vertical = 12.dp),
                    )
                }
            }
            PrimaryButton("Sign out", {
                scope.launch {
                    app.api.post("auth/logout")
                    app.forget()
                }
            }, Modifier.fillMaxWidth())
            TextButton({ deleting = true }, Modifier.fillMaxWidth().heightIn(min = 48.dp)) {
                Text("Delete account", color = LocalSkiColors.current.danger)
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
        title = { Text("Delete your account?") },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                Text("This permanently deletes your account, resort and progress. It can't be undone.")
                OutlinedTextField(password, { password = it }, Modifier.fillMaxWidth(), label = { Text("Password") }, singleLine = true, visualTransformation = PasswordVisualTransformation())
                OutlinedTextField(confirm, { confirm = it }, Modifier.fillMaxWidth(), label = { Text("Type DELETE to confirm") }, singleLine = true)
                error?.let { Text(it, color = LocalSkiColors.current.danger) }
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
                Text("Delete account", color = LocalSkiColors.current.danger)
            }
        },
        dismissButton = { TextButton(onClose, Modifier.heightIn(min = 48.dp)) { Text("Cancel") } },
    )
}

@Composable
fun MoreScreen(onOpen: (String) -> Unit) {
    ScreenColumn {
        listOf("tickets" to "Lift tickets", "weather" to "Weather", "notifications" to "Notifications", "leaderboard" to "Leaderboard", "settings" to "Settings").forEach { (route, label) ->
            Panel {
                Text(label, Modifier.fillMaxWidth().heightIn(min = 48.dp).clickable { onOpen(route) }.padding(vertical = 12.dp), fontWeight = FontWeight.Medium)
            }
        }
        HorizontalDivider()
    }
}
