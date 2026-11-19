package net.skimanager.app.ui.game

import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import net.skimanager.app.api.bool
import net.skimanager.app.api.int
import net.skimanager.app.api.obj
import net.skimanager.app.api.objects
import net.skimanager.app.api.str
import net.skimanager.app.api.strOrNull
import net.skimanager.app.ui.AppState
import net.skimanager.app.ui.common.ChoiceRow
import net.skimanager.app.ui.common.EmptyText
import net.skimanager.app.ui.common.Panel
import net.skimanager.app.ui.common.Row2
import net.skimanager.app.ui.theme.LocalSkiColors

/** Website links on checklists and tips, opened natively where the app has the page */
private fun linkRoute(link: String?): String? = when (link?.trimStart('/')) {
    null, "" -> null
    "staff/hire" -> "hire"
    else -> routeForPath(link.trimStart('/').substringBefore('?'))
}

@Composable
fun EmergencyScreen(app: AppState) {
    val open = LocalOpen.current
    ApiPage(app, "emergency") { data, _ ->
        val risk = data.str("riskLevel")
        FigureRow(
            Triple("Safety score", "${data.int("safetyScore")}/100", "Risk: $risk"),
            Triple("Slope coverage", "${data.int("coverageRatio")}%", "${data.int("uncoveredSlopes")} slopes uncovered"),
            Triple("Patrol stations", "${data.int("activeStations")} / ${data.int("totalStations")}", "${data.int("patrolStaff")} patrollers · ${data.int("medicsCount")} medics"),
            Triple("Insurance", "${data.int("insuranceCount")} policies", null),
        )
        Meter(data.int("safetyScore"), conditionColor(data.int("safetyScore")))
        Panel("Safety checklist") {
            data.objects("checklist").forEach { c ->
                val done = c.bool("done")
                Row2(c.str("text"), if (done) "Done" else "To do", if (done) LocalSkiColors.current.success else LocalSkiColors.current.warning)
                if (!done) linkRoute(c.strOrNull("link"))?.let { route -> ActButton("Fix this", { open(route) }, Modifier.fillMaxWidth(), style = "outlined") }
            }
        }
        Panel("Recent incidents") {
            val list = data.objects("incidents")
            if (list.isEmpty()) EmptyText("No incidents. Keep it that way.")
            list.forEach { i -> Row2(i.str("message"), i.str("timeAgo")) }
        }
    }
}

@Composable
fun VipGuestsScreen(app: AppState) {
    ApiPage(app, "vip-guests") { data, _ ->
        FigureRow(
            Triple("Staying now", data.int("activeCount").toString(), null),
            Triple("Satisfied", data.int("satisfiedCount").toString(), null),
            Triple("Earned from VIPs", data.str("totalEarnedFormatted"), null),
        )
        Panel("Staying now") {
            val list = data.objects("activeVips")
            if (list.isEmpty()) EmptyText("No VIPs at your resort right now.")
            list.forEach { v ->
                TitleRow(v.str("name"), v.str("status"))
                Muted("${v.str("typeName")} · ${v.int("days_remaining")} days left · reward ${v.str("rewardFormatted")}, +${v.int("reputation_bonus")} reputation")
                Muted("Wants: ${v.str("requirements")}")
                Meter(v.int("stayPct"))
            }
        }
        Panel("Who can visit") {
            data.objects("typesCatalog").forEach { t ->
                Row2(t.str("name"), t.str("rarityLabel"))
                Muted("${t.int("duration")} days · ${t.str("rewardFormatted")} · +${t.int("rep_bonus")} reputation · wants ${t.str("requirements")}")
            }
        }
        Panel("Past visits") {
            val list = data.objects("pastVips")
            if (list.isEmpty()) EmptyText("No past visits yet.")
            list.forEach { v ->
                Row2("${v.str("name")} · day ${v.int("game_day_arrived")}", if (v.bool("isSatisfied")) v.str("rewardFormatted") else "Left unhappy",
                    if (v.bool("isSatisfied")) LocalSkiColors.current.success else LocalSkiColors.current.danger)
            }
        }
    }
}

@Composable
fun AchievementsScreen(app: AppState) {
    var filter by rememberSaveable { mutableStateOf("all") }
    ApiPage(app, "achievements") { data, act ->
        FigureRow(
            Triple("Completed", "${data.int("completed")} / ${data.int("total")}", null),
            Triple("Earned", data.str("earned"), null),
        )
        if (data.int("readyCount") > 0) {
            ActButton("Claim ${data.int("readyCount")} for ${data.str("readyReward")}", { act.post("claim-all") }, Modifier.fillMaxWidth(),
                enabled = !act.busy, style = "primary")
        }
        val locked = data.objects("lockedFeatures")
        if (locked.isNotEmpty()) {
            Panel("Features to unlock") {
                locked.forEach { f ->
                    Row2(f.str("feature"), if (f.bool("ready")) "Ready to claim" else "${f.int("pct")}%")
                    Muted("${f.str("name")}: ${f.str("description")}")
                    Meter(f.int("pct"))
                }
            }
        }
        ChoiceRow(listOf("all" to "All", "ready" to "Ready", "progress" to "To do", "claimed" to "Done"), filter) { filter = it }
        data.objects("groups").forEach { g ->
            val items = g.objects("items").filter { filter == "all" || it.str("state") == filter }
            if (items.isEmpty()) return@forEach
            Panel("${g.str("category")} · ${g.int("done")}/${g.objects("items").size}") {
                items.forEach { a ->
                    val state = a.str("state")
                    TitleRow(a.str("name"), when (state) { "ready" -> "ready"; "claimed" -> "claimed"; else -> null })
                    Muted(a.str("description"))
                    a.strOrNull("unlocks")?.let { Muted("Unlocks $it") }
                    if (state == "progress") {
                        Row2(a.str("progress"), a.strOrNull("reward") ?: "")
                        Meter(a.int("pct"))
                    }
                    if (state == "ready") {
                        ActButton("Claim ${a.strOrNull("reward") ?: ""}".trim(), { act.post("claim/${a.int("id")}") }, Modifier.fillMaxWidth(), enabled = !act.busy, style = "primary")
                    }
                }
            }
        }
    }
}

@Composable
fun DailyBonusScreen(app: AppState) {
    ApiPage(app, "daily-bonus") { data, act ->
        val next = data.obj("next")
        FigureRow(
            Triple("Streak", "${data.int("streak")} days", "Best ${data.int("bestStreak")}"),
            Triple("Claimed so far", data.str("totalClaimedFormatted"), null),
        )
        if (data.int("lostStreak") > 0) Notice("You missed a day and lost a ${data.int("lostStreak")}-day streak.", LocalSkiColors.current.warning)
        Panel(if (data.bool("claimedToday")) "Come back tomorrow" else "Today's bonus") {
            Row2("Day ${next.int("day")} of week ${next.int("week")}", next.str("cashFormatted"))
            if (next.int("genepis") > 0) Row2("Plus", "${next.int("genepis")} Génépis")
            if (next.int("multPct") > 100) Muted("Week bonus: ${next.int("multPct")}% of the base reward")
            if (data.bool("claimedToday")) {
                val s = data.int("secondsToReset")
                Muted("Next bonus in ${s / 3600}h ${(s % 3600) / 60}m.")
            } else {
                ActButton("Claim ${next.str("cashFormatted")}", { act.post("claim") }, Modifier.fillMaxWidth(), enabled = !act.busy, style = "primary")
            }
        }
        Panel("This week") {
            data.objects("days").forEach { d ->
                Row2(
                    "Day ${d.int("day")}" + if (d.int("genepis") > 0) " + ${d.int("genepis")} Génépis" else "",
                    d.str("cashFormatted"),
                    when {
                        d.bool("done") -> LocalSkiColors.current.success
                        d.bool("next") -> LocalSkiColors.current.link
                        else -> LocalSkiColors.current.muted
                    },
                )
            }
            Muted("Each full week of streak adds ${data.int("weekBonusPct")}%, up to ${data.num("maxMultiplier")}×. Day 7 also pays ${data.int("weeklyGenepis")} Génépis.")
        }
    }
}

@Composable
fun GenepisScreen(app: AppState) {
    val open = LocalOpen.current
    var exchanging by remember { mutableStateOf(false) }
    ApiPage(app, "genepis") { data, act ->
        FigureRow(
            Triple("Balance", data.str("balanceFormatted"), "1 Génépi = ${data.str("rateFormatted")}"),
            Triple("Earned / spent", "${data.str("totalEarned")} / ${data.str("totalSpent")}", null),
        )
        ActButton("Convert to cash", { exchanging = true }, Modifier.fillMaxWidth(), enabled = !act.busy && data.int("balance") > 0)
        Panel("Shop") {
            data.objects("shopItems").forEach { item ->
                TitleRow(item.str("name"), if (item.bool("isActive")) "active" else null)
                Muted(item.str("desc") + (item.get("duration")?.takeIf { !it.isJsonNull }?.let { " Lasts ${item.int("duration")} days." } ?: ""))
                item.strOrNull("expiresAt")?.let { Muted("Active until $it") }
                ActButton("Buy for ${item.int("cost")} Génépis", { act.post("buy", mapOf("item" to item.str("key"))) }, Modifier.fillMaxWidth(),
                    enabled = !act.busy && !item.bool("isActive") && data.int("balance") >= item.int("cost"),
                    confirm = "Buy ${item.str("name")} for ${item.int("cost")} Génépis?")
            }
        }
        Panel("How to earn them") {
            data.objects("earnMethods").forEach { m ->
                Row2(m.str("name"), m.str("amount"))
                Muted(m.str("desc"))
                linkRoute(m.strOrNull("link"))?.let { route -> ActButton("Open", { open(route) }, Modifier.fillMaxWidth(), style = "outlined") }
            }
        }
        Panel("History") {
            val logs = data.objects("logs")
            if (logs.isEmpty()) EmptyText("No Génépis yet.")
            logs.forEach { l ->
                val amount = l.int("amount")
                Row2("${l.str("reason")} · ${l.str("timeAgo")}", if (amount > 0) "+$amount" else "$amount",
                    if (amount > 0) LocalSkiColors.current.success else LocalSkiColors.current.danger)
            }
        }
        if (exchanging) {
            InputDialog("Convert Génépis", "Génépis to convert", "", number = true,
                help = "Each Génépi converts to ${data.str("rateFormatted")}. You have ${data.int("balance")}.",
                confirmLabel = "Convert", onDismiss = { exchanging = false }) { amount ->
                exchanging = false
                act.post("exchange", mapOf("direction" to "to_cash", "amount" to amount))
            }
        }
    }
}

@Composable
fun ActivityScreen(app: AppState) {
    ApiPage(app, "activity") { data, _ ->
        val logs = data.objects("logs")
        Panel {
            if (logs.isEmpty()) EmptyText("Nothing has happened yet.")
            logs.forEach { l ->
                Row2(l.str("message"), "Day ${l.int("day")}")
                Muted(l.str("category"))
            }
        }
    }
}
