package net.skimanager.app.ui.game

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Switch
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.google.gson.JsonObject
import kotlinx.coroutines.launch
import net.skimanager.app.api.ApiResult
import net.skimanager.app.api.bool
import net.skimanager.app.api.int
import net.skimanager.app.api.obj
import net.skimanager.app.api.objects
import net.skimanager.app.api.str
import net.skimanager.app.api.strOrNull
import net.skimanager.app.ui.AppState
import net.skimanager.app.ui.common.ChoiceRow
import net.skimanager.app.ui.common.EmptyText
import net.skimanager.app.ui.common.OFFLINE_TEXT
import net.skimanager.app.ui.common.Panel
import net.skimanager.app.ui.common.Row2
import net.skimanager.app.ui.theme.LocalSkiColors

/** /alliances: the player's alliance, or the directory when they have none */
@Composable
fun AlliancesScreen(app: AppState) {
    ApiPage(app, "alliances") { data, act ->
        if (data.str("mode") == "member") AllianceDashboard(data.obj("page"), act) else AllianceDirectory(data.obj("page"), act)
    }
}

@Composable
fun AllianceDirectoryScreen(app: AppState) {
    ApiPage(app, "alliances/directory", actionBase = "alliances") { data, act -> AllianceDirectory(data, act) }
}

private val CREST_ICONS = listOf("fa-mountain-sun" to "Mountain", "fa-snowflake" to "Snowflake", "fa-shield-halved" to "Shield", "fa-crown" to "Crown")
private val CREST_COLORS = listOf("#3b82f6" to "Blue", "#10b981" to "Green", "#f59e0b" to "Amber", "#ef4444" to "Red")

@Composable
private fun AllianceDirectory(data: JsonObject, act: PageActions) {
    val open = LocalOpen.current
    val user = data.obj("user")
    var applyTo by remember { mutableStateOf<JsonObject?>(null) }
    var founding by rememberSaveable { mutableStateOf(false) }
    FigureRow(
        Triple("Your reputation", user.int("rep").toString(), null),
        Triple("Cash", user.str("cashText"), "Founding costs ${data.str("foundingFeeText")}"),
    )
    data.get("myApplication")?.takeIf { it.isJsonObject }?.let { Notice("Your application is waiting for an answer.", LocalSkiColors.current.warning) }
    ButtonRow {
        ActButton("Rankings", { open("alliances/leaderboard") }, Modifier.weight(1f), style = "outlined")
        ActButton("Found one", { founding = true }, Modifier.weight(1f), enabled = user.bool("canFound") && !act.busy)
    }
    val list = data.objects("alliances")
    if (list.isEmpty()) Panel { EmptyText("No alliances yet. Found the first one.") }
    list.forEach { a ->
        Panel {
            TitleRow("[${a.str("tag")}] ${a.str("name")}", if (a.bool("isFull")) "full" else if (a.bool("is_recruiting")) "open" else "closed")
            Muted(a.str("motto"))
            Row2("Members", "${a.int("member_count")} / ${a.int("max_members")}")
            Row2("Level", a.int("level").toString())
            Row2("Pass", "${a.str("passTierName")} (+${a.int("passVisitorPct")}% visitors)")
            Row2("Minimum reputation", a.int("min_reputation").toString(), if (a.bool("meetsRep")) LocalSkiColors.current.success else LocalSkiColors.current.danger)
            ButtonRow {
                ActButton("View", { open("alliances/view/${a.int("id")}") }, Modifier.weight(1f), style = "outlined")
                when {
                    a.bool("canJoin") -> ActButton("Join", { act.post("join/${a.int("id")}") }, Modifier.weight(1f), enabled = !act.busy,
                        confirm = "Join ${a.str("name")}?")
                    a.bool("canApply") -> ActButton("Apply", { applyTo = a }, Modifier.weight(1f), enabled = !act.busy)
                }
            }
        }
    }
    applyTo?.let { a ->
        InputDialog("Apply to ${a.str("name")}", "Message to the leaders", help = "Say why you'd like to join.", confirmLabel = "Apply", onDismiss = { applyTo = null }) {
            applyTo = null
            act.post("join/${a.int("id")}", mapOf("message" to it))
        }
    }
    if (founding) FoundAllianceDialog(data.str("foundingFeeText"), onDismiss = { founding = false }) { form ->
        founding = false
        act.post("create", form)
    }
}

@Composable
private fun FoundAllianceDialog(fee: String, onDismiss: () -> Unit, onDone: (Map<String, String>) -> Unit) {
    var name by remember { mutableStateOf("") }
    var tag by remember { mutableStateOf("") }
    var motto by remember { mutableStateOf("") }
    var icon by remember { mutableStateOf(CREST_ICONS.first().first) }
    var color by remember { mutableStateOf(CREST_COLORS.first().first) }
    var minRep by remember { mutableStateOf("0") }
    var recruiting by remember { mutableStateOf(true) }
    androidx.compose.material3.AlertDialog(
        onDismissRequest = onDismiss,
        shape = MaterialTheme.shapes.extraLarge,
        title = { Text("Found an alliance", fontWeight = FontWeight.Bold) },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(8.dp), modifier = Modifier.padding(top = 4.dp)) {
                Text("It costs $fee.", style = MaterialTheme.typography.bodyMedium)
                OutlinedTextField(name, { name = it }, Modifier.fillMaxWidth(), label = { Text("Name") }, singleLine = true, shape = MaterialTheme.shapes.medium)
                OutlinedTextField(tag, { tag = it.uppercase().take(5) }, Modifier.fillMaxWidth(), label = { Text("Tag, up to 5 letters") }, singleLine = true, shape = MaterialTheme.shapes.medium)
                OutlinedTextField(motto, { motto = it }, Modifier.fillMaxWidth(), label = { Text("Motto") }, singleLine = true, shape = MaterialTheme.shapes.medium)
                OutlinedTextField(minRep, { minRep = it.filter(Char::isDigit) }, Modifier.fillMaxWidth(), label = { Text("Minimum reputation to join") }, singleLine = true, shape = MaterialTheme.shapes.medium)
                ChoiceRow(CREST_ICONS, icon) { icon = it }
                ChoiceRow(CREST_COLORS, color) { color = it }
                androidx.compose.foundation.layout.Row(verticalAlignment = androidx.compose.ui.Alignment.CenterVertically) {
                    Text("Open to new members", Modifier.weight(1f))
                    Switch(recruiting, { recruiting = it })
                }
            }
        },
        confirmButton = {
            androidx.compose.material3.TextButton({
                onDone(buildMap {
                    put("name", name.trim()); put("tag", tag.trim()); put("motto", motto.trim()); put("description", "")
                    put("crest_icon", icon); put("crest_color", color); put("min_reputation", minRep.ifBlank { "0" })
                    if (recruiting) put("is_recruiting", "1")
                })
            }, enabled = name.isNotBlank() && tag.isNotBlank()) { Text("Found", fontWeight = FontWeight.Bold) }
        },
        dismissButton = { androidx.compose.material3.TextButton(onDismiss) { Text("Cancel") } },
    )
}

@Composable
private fun AllianceDashboard(data: JsonObject, act: PageActions) {
    val open = LocalOpen.current
    val alliance = data.obj("alliance")
    val stats = data.obj("stats")
    val pass = data.obj("pass")
    val user = data.obj("user")
    var donating by remember { mutableStateOf(false) }
    Panel {
        TitleRow("[${alliance.str("tag")}] ${alliance.str("name")}")
        Muted(alliance.str("motto"))
        Row2("Level", "${alliance.int("level")} · ${alliance.int("xp")} XP")
        Row2("Treasury", alliance.str("treasuryText"))
        Row2("Members", "${stats.int("memberCount")} / ${stats.int("maxMembers")}")
        Row2("Combined value", stats.str("combinedValuationText"))
        Row2("Slopes · lifts", "${stats.int("totalSlopes")} · ${stats.int("totalLifts")} (${stats.num("totalSlopeKm")} km)")
    }
    ButtonRow {
        ActButton("Donate", { donating = true }, Modifier.weight(1f), enabled = !act.busy)
        ActButton("Rankings", { open("alliances/leaderboard") }, Modifier.weight(1f), style = "outlined")
    }
    Panel("Alliance pass: ${pass.str("tierName")}") {
        Muted(pass.str("desc"))
        Row2("Visitor boost", "+${pass.int("visitorPct")}% (+${pass.int("sisterBonus")}% sister bonus) = +${pass.int("totalBoost")}%")
        pass.get("nextTier")?.takeIf { it.isJsonObject }?.asJsonObject?.let { n ->
            Row2("Next: ${n.str("name")}", "+${n.int("visitor_pct")}% visitors")
            if (!n.bool("meetsLevel")) Muted("Needs alliance level ${n.int("req_level")}.")
            if (user.bool("isLeader")) {
                ActButton("Upgrade for ${n.str("costText")}", { act.post("upgrade-pass") }, Modifier.fillMaxWidth(),
                    enabled = !act.busy && n.bool("canAfford") && n.bool("meetsLevel"), confirm = "Pay ${n.str("costText")} from the treasury?")
            }
        }
    }
    Panel("Perks") {
        data.objects("perks").forEach { p ->
            Row2(p.str("name"), if (p.int("curLevel") > 0) "Level ${p.int("curLevel")}" else "Locked")
            Muted(p.strOrNull("curEffect") ?: p.str("desc"))
            if (!p.bool("isMax") && user.bool("isLeader")) {
                ActButton("Level ${p.int("nextLevel")}: ${p.str("nextCostText")}", { act.post("unlock-perk", mapOf("perk_key" to p.str("key"))) }, Modifier.fillMaxWidth(),
                    enabled = !act.busy && p.bool("canAfford") && p.bool("meetsLevel"), style = "outlined",
                    confirm = "${p.str("nextEffect")} Paid from the treasury: ${p.str("nextCostText")}.")
            }
        }
    }
    val apps = data.objects("applications")
    if (apps.isNotEmpty() && user.bool("isLeader")) {
        Panel("Applications") {
            apps.forEach { a ->
                Row2(a.str("username"), "${a.int("reputation")} rep · ${a.str("cashText")}")
                Muted(a.str("message"))
                ButtonRow {
                    ActButton("Accept", { act.post("review-application", mapOf("application_id" to a.int("id").toString(), "action" to "accept")) }, Modifier.weight(1f), enabled = !act.busy)
                    ActButton("Reject", { act.post("review-application", mapOf("application_id" to a.int("id").toString(), "action" to "reject")) }, Modifier.weight(1f), enabled = !act.busy, style = "outlined")
                }
            }
        }
    }
    Panel("Members") {
        data.objects("members").forEach { m ->
            Row2("${m.str("username")} · ${m.str("role")}", "${m.int("reputation")} rep")
            Muted("${m.int("open_slopes")} slopes · ${m.int("open_lifts")} lifts · donated ${m.str("donatedText")}")
            if (m.bool("canManage") && !m.bool("isCurrentUser")) {
                val target = m.int("user_id").toString()
                fun manage(action: String) = act.post("manage-member", mapOf("target_user_id" to target, "action" to action))
                ButtonRow {
                    if (m.str("role") == "member") ActButton("Promote", { manage("promote") }, Modifier.weight(1f), enabled = !act.busy)
                    if (m.str("role") == "officer") ActButton("Demote", { manage("demote") }, Modifier.weight(1f), enabled = !act.busy)
                    ActButton("Remove", { manage("kick") }, Modifier.weight(1f), enabled = !act.busy, style = "outlined", confirm = "Remove ${m.str("username")} from the alliance?")
                }
                if (user.bool("isFounder")) {
                    ActButton("Make founder", { manage("transfer_founder") }, Modifier.fillMaxWidth(), enabled = !act.busy, style = "outlined",
                        confirm = "Hand the alliance over to ${m.str("username")}? You become an officer.")
                }
            }
        }
    }
    Panel("Activity") {
        val list = data.objects("activities")
        if (list.isEmpty()) EmptyText("Nothing yet.")
        list.forEach { a -> Row2(a.str("message"), a.str("timeAgoText")) }
    }
    ActButton("Leave the alliance", { act.post("leave") }, Modifier.fillMaxWidth(), enabled = !act.busy, style = "outlined",
        confirm = if (user.bool("isFounder")) "You're the founder. Leaving may hand over or close the alliance." else "Leave ${alliance.str("name")}?")
    if (donating) {
        InputDialog("Donate to the treasury", "Amount", number = true, help = "You have ${user.str("cashText")}.", confirmLabel = "Donate", onDismiss = { donating = false }) {
            donating = false
            act.post("donate", mapOf("amount" to it))
        }
    }
}

@Composable
fun AllianceLeaderboardScreen(app: AppState) {
    val open = LocalOpen.current
    ApiPage(app, "alliances/leaderboard", actionBase = "alliances") { data, _ ->
        val list = data.objects("alliances")
        if (list.isEmpty()) Panel { EmptyText("No alliances ranked yet.") }
        list.forEach { a ->
            Panel {
                TitleRow("${a.int("rank")}. [${a.str("tag")}] ${a.str("name")}", if (a.bool("isMyAlliance")) "active" else null)
                Row2("Value", a.str("valuationText"))
                Row2("Members", "${a.int("member_count")} / ${a.int("max_members")}")
                Muted("Level ${a.int("level")} · ${a.int("total_slopes")} slopes (${a.num("slopeKm")} km) · ${a.int("total_lifts")} lifts · ${a.str("passTierName")} pass")
                ActButton("View", { open("alliances/view/${a.int("id")}") }, Modifier.fillMaxWidth(), style = "outlined")
            }
        }
    }
}

@Composable
fun AllianceViewScreen(app: AppState, id: String) {
    ApiPage(app, "alliances/view/$id", actionBase = "alliances") { data, act ->
        val a = data.obj("alliance")
        val pass = data.obj("pass")
        Panel {
            TitleRow("[${a.str("tag")}] ${a.str("name")}", if (a.bool("is_recruiting")) "open" else "closed")
            Muted(a.str("motto"))
            Muted(a.str("description"))
            Row2("Founder", data.obj("founder").str("username"))
            Row2("Level", a.int("level").toString())
            Row2("Treasury", a.str("treasuryText"))
            Row2("Pass", "${pass.str("name")} (+${pass.int("visitorPct")}% visitors)")
        }
        if (data.bool("canJoin")) {
            ActButton("Join", { act.post("join/${a.int("id")}") }, Modifier.fillMaxWidth(), enabled = !act.busy, style = "primary", confirm = "Join ${a.str("name")}?")
        }
        Panel("Members") {
            data.objects("members").forEach { m ->
                Row2("${m.str("username")} · ${m.str("role")}", "${m.int("reputation")} rep")
                Muted("${m.int("open_slopes")} slopes · ${m.int("open_lifts")} lifts")
            }
        }
    }
}

@Composable
fun TournamentsScreen(app: AppState) {
    ApiPage(app, "tournaments") { data, act ->
        val stats = data.obj("stats")
        FigureRow(
            Triple("Running", stats.int("activeTournaments").toString(), "${stats.int("upcomingTournaments")} coming up"),
            Triple("Hosted", stats.int("totalHosted").toString(), "${stats.int("activeEvents")} special events now"),
        )
        Panel("Your events") {
            val list = data.objects("tournaments")
            if (list.isEmpty()) EmptyText("No events yet. Host one below.")
            list.forEach { t ->
                TitleRow(t.str("name"), if (t.str("status") == "upcoming") "pending" else t.str("status"))
                Muted("Days ${t.int("start_day")}–${t.int("end_day")}" + if (t.str("status") == "upcoming") " · starts in ${t.int("daysUntil")} days" else "")
                Muted("+${t.int("visitors_boost")}% visitors · +${t.int("reputation_boost")} reputation · prize ${t.str("prizeText")}")
                if (t.bool("canCancel")) {
                    ActButton("Cancel", { act.post("cancel/${t.int("id")}") }, Modifier.fillMaxWidth(), enabled = !act.busy, style = "outlined", confirm = "Cancel ${t.str("name")}?")
                }
            }
        }
        val events = data.objects("events")
        if (events.isNotEmpty()) {
            Panel("Special events") {
                events.forEach { e ->
                    TitleRow(e.str("name"), if (e.bool("active")) "active" else null)
                    Muted("${e.str("description")} Day ${e.int("game_day")}, ${e.int("duration_days")} days.")
                }
            }
        }
        Panel("Host an event") {
            data.objects("types").forEach { t ->
                Heading(t.str("name"))
                Muted(t.str("desc"))
                Muted("+${t.int("visitors")}% visitors · +${t.int("reputation")} reputation · ${t.int("duration")} days")
                ActButton("Host for ${t.str("costText")}", { act.post("host", mapOf("type" to t.str("key"))) }, Modifier.fillMaxWidth(), enabled = !act.busy,
                    confirm = "Host ${t.str("name")} for ${t.str("costText")}?")
            }
        }
    }
}

/** /vote: the monthly resort vote, or the older Season 4 vote while that flag is off */
@Composable
fun VoteScreen(app: AppState) {
    ApiPage(app, "vote") { data, act ->
        if (data.str("mode") == "monthly") MonthlyVote(app, data) else Season4Vote(data, act)
    }
}

@Composable
private fun Season4Vote(data: JsonObject, act: PageActions) {
    val season = data.obj("season")
    val user = data.obj("userVote")
    FigureRow(
        Triple("Votes cast", data.obj("stats").int("totalVotes").toString(), data.obj("stats").strOrNull("leadingName")?.let { "$it leads" }),
        Triple("Voting closes", "${season.int("daysRemaining")} days", "Season ${season.int("number")}"),
    )
    if (user.bool("hasVoted")) {
        Notice("You voted for ${user.str("resortName")}.", LocalSkiColors.current.success)
        ActButton("Take back my vote", { act.post("retract") }, Modifier.fillMaxWidth(), enabled = !act.busy, style = "outlined")
    }
    data.objects("options").forEach { o ->
        val specs = o.obj("specs")
        Panel {
            TitleRow(o.str("name"), if (o.bool("isUserVote")) "active" else null)
            Muted("${o.str("location")} · ${o.str("tagline")}")
            Muted(o.str("desc"))
            Row2("Vertical · area", "${specs.str("vertical")} · ${specs.str("acres")}")
            Row2("Perk", o.obj("perk").str("title"))
            Row2("Votes", "${o.int("votes")} (${o.int("percentage")}%)")
            Meter(o.int("percentage"))
            if (!user.bool("hasVoted")) {
                ActButton("Vote for ${o.str("name")}", { act.post("cast", mapOf("resort" to o.str("key"))) }, Modifier.fillMaxWidth(), enabled = !act.busy)
            }
        }
    }
}

@Composable
private fun MonthlyVote(app: AppState, data: JsonObject) {
    val scope = rememberCoroutineScope()
    val round = data.obj("round")
    var query by rememberSaveable { mutableStateOf("") }
    var results by remember { mutableStateOf<List<JsonObject>>(emptyList()) }
    var searchNote by remember { mutableStateOf<String?>(null) }
    // Voting goes through resort-vote, the endpoint the website's island uses, rather than vote/*
    var voteNote by remember { mutableStateOf<Pair<String, Boolean>?>(null) }
    var current by remember(data) { mutableStateOf(data) }
    fun voteCall(path: String, form: Map<String, String>) {
        scope.launch {
            when (val r = app.api.post(path, form)) {
                is ApiResult.Ok -> { voteNote = (r.message ?: "Saved.") to true; current = r.dataObject() }
                is ApiResult.Failed -> voteNote = r.message to false
                is ApiResult.Offline -> voteNote = OFFLINE_TEXT to false
            }
        }
    }
    voteNote?.let { (t, ok) -> Notice(t, if (ok) LocalSkiColors.current.success else LocalSkiColors.current.danger) }
    Panel(round.str("label")) {
        Muted(if (round.bool("open")) "Voting closes ${round.str("closesLabel")}. ${round.int("votes")} votes so far." else (round.strOrNull("closedLabel") ?: "Voting is closed."))
        current.get("myPick")?.takeIf { it.isJsonObject }?.asJsonObject?.let { pick ->
            Row2("Your vote", pick.str("name"), LocalSkiColors.current.success)
            if (current.bool("canVote")) ActButton("Take back my vote", { voteCall("resort-vote/retract", emptyMap()) }, Modifier.fillMaxWidth(), style = "outlined")
        }
    }
    if (current.bool("canVote")) {
        Panel("Pick a real resort") {
            OutlinedTextField(query, { query = it }, Modifier.fillMaxWidth(), label = { Text("Search ${current.int("candidateCount")} resorts") }, singleLine = true, shape = MaterialTheme.shapes.medium)
            ActButton("Search", {
                scope.launch {
                    searchNote = null
                    when (val r = app.api.get("resort-vote/search", mapOf("q" to query.trim()))) {
                        is ApiResult.Ok -> {
                            results = r.data?.takeIf { it.isJsonArray }?.asJsonArray?.filter { it.isJsonObject }?.map { it.asJsonObject }.orEmpty()
                            if (results.isEmpty()) searchNote = "No resorts match."
                        }
                        is ApiResult.Failed -> searchNote = r.message
                        is ApiResult.Offline -> searchNote = OFFLINE_TEXT
                    }
                }
            }, Modifier.fillMaxWidth(), enabled = query.trim().length >= 2)
            searchNote?.let { Muted(it) }
            results.forEach { a ->
                Row2(a.str("name"), a.str("place"))
                Muted("${a.int("runs")} runs · ${a.int("lifts")} lifts")
                ActButton("Vote for ${a.str("name")}", { voteCall("resort-vote", mapOf("areaId" to a.int("id").toString())); results = emptyList() }, Modifier.fillMaxWidth())
            }
        }
    }
    Panel("Leading") {
        val top = current.objects("top")
        if (top.isEmpty()) EmptyText("No votes yet.")
        top.forEach { t ->
            Row2(t.str("name"), "${t.int("votes")} (${t.int("pct")}%)")
            Meter(t.int("pct"))
        }
    }
    current.objects("past").takeIf { it.isNotEmpty() }?.let { past ->
        Panel("Past rounds") { past.forEach { p -> Row2(p.str("label"), p.obj("winner").strOrNull("name") ?: "No winner") } }
    }
    Muted(current.str("attribution"))
}

@Composable
fun RoadmapScreen(app: AppState) {
    var suggesting by remember { mutableStateOf(false) }
    ApiPage(app, "roadmap") { data, act ->
        val me = data.obj("me")
        val stats = data.obj("stats")
        FigureRow(Triple("Ideas", stats.int("totalItems").toString(), null), Triple("Votes", stats.int("totalVotes").toString(), null))
        if (me.bool("canSuggest")) {
            ActButton("Suggest an idea (${me.int("suggestionsLeft")} left)", { suggesting = true }, Modifier.fillMaxWidth(), enabled = !act.busy, style = "primary")
        }
        data.objects("columns").forEach { col ->
            Panel("${col.str("title")} · ${col.objects("items").size}") {
                Muted(col.str("description"))
                col.objects("items").forEach { item ->
                    Heading(item.str("title"))
                    Muted(item.str("description"))
                    Row2(item.str("category").replace('_', ' ').replaceFirstChar { it.uppercase() } + " · " + item.str("author"), "${item.int("upvotes")} votes")
                    if (me.bool("canVote") && col.str("status") != "completed") {
                        ActButton(if (item.bool("voted")) "Remove vote" else "Vote", { act.post("vote/${item.int("id")}") }, Modifier.fillMaxWidth(),
                            enabled = !act.busy, style = if (item.bool("voted")) "outlined" else "secondary")
                    }
                }
                if (col.objects("items").isEmpty()) EmptyText("Nothing here yet.")
            }
        }
        if (suggesting) SuggestDialog(onDismiss = { suggesting = false }) { form ->
            suggesting = false
            act.post("suggest", form)
        }
    }
}

@Composable
private fun SuggestDialog(onDismiss: () -> Unit, onDone: (Map<String, String>) -> Unit) {
    var title by remember { mutableStateOf("") }
    var description by remember { mutableStateOf("") }
    var category by remember { mutableStateOf("gameplay") }
    androidx.compose.material3.AlertDialog(
        onDismissRequest = onDismiss,
        shape = MaterialTheme.shapes.extraLarge,
        title = { Text("Suggest an idea", fontWeight = FontWeight.Bold) },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                OutlinedTextField(title, { title = it }, Modifier.fillMaxWidth(), label = { Text("Title") }, singleLine = true, shape = MaterialTheme.shapes.medium)
                OutlinedTextField(description, { description = it }, Modifier.fillMaxWidth(), label = { Text("What should change, and why?") }, minLines = 3, shape = MaterialTheme.shapes.medium)
                ChoiceRow(listOf("gameplay" to "Gameplay", "economy" to "Economy"), category.takeIf { it in listOf("gameplay", "economy") }) { category = it }
                ChoiceRow(listOf("quality_of_life" to "Quality of life", "mobile" to "Mobile"), category.takeIf { it in listOf("quality_of_life", "mobile") }) { category = it }
            }
        },
        confirmButton = {
            androidx.compose.material3.TextButton({ onDone(mapOf("title" to title.trim(), "description" to description.trim(), "category" to category)) },
                enabled = title.isNotBlank() && description.isNotBlank()) { Text("Send", fontWeight = FontWeight.Bold) }
        },
        dismissButton = { androidx.compose.material3.TextButton(onDismiss) { Text("Cancel") } },
    )
}

@Composable
fun SupportScreen(app: AppState) {
    var message by rememberSaveable { mutableStateOf("") }
    ApiPage(app, "support") { data, act ->
        Muted("Messages go to the Ski Manager team. Answers appear here and in your notifications.")
        Panel("Conversation") {
            val messages = data.objects("messages")
            if (messages.isEmpty()) EmptyText("No messages yet.")
            messages.forEach { m ->
                val mine = m.str("sender") == "player"
                Text(if (mine) "You" else "Ski Manager team", fontWeight = FontWeight.Bold, style = MaterialTheme.typography.labelMedium,
                    color = if (mine) MaterialTheme.colorScheme.primary else LocalSkiColors.current.success)
                Text(m.str("message"), style = MaterialTheme.typography.bodyMedium)
                Muted(m.strOrNull("dateFormatted") ?: "${m.str("date")} ${m.str("time")}")
            }
        }
        val max = data.int("maxLength").takeIf { it > 0 } ?: 2000
        OutlinedTextField(message, { message = it.take(max) }, Modifier.fillMaxWidth(), label = { Text("Your message") }, minLines = 4, shape = MaterialTheme.shapes.medium,
            supportingText = { Text("${message.length} / $max") })
        ActButton("Send", {
            act.post("send", mapOf("message" to message.trim()))
            message = ""
        }, Modifier.fillMaxWidth(), enabled = !act.busy && message.isNotBlank(), style = "primary")
    }
}

@Composable
fun UpdatesScreen(app: AppState) {
    ApiPage(app, "updates") { data, _ ->
        val updates = data.objects("updates")
        if (updates.isEmpty()) Panel { EmptyText("No updates yet.") }
        updates.forEach { u ->
            Panel("${u.str("version")} · ${u.str("title")}") {
                Muted(u.str("releasedAtFormatted"))
                Muted(u.str("description"))
                u.objects("categories").forEach { c ->
                    Heading(c.str("label"))
                    c.objects("items").forEach { i ->
                        // Most items have only a description; the title is often empty
                        val title = i.str("title")
                        Text("• ${title.ifBlank { i.str("description") }}", style = MaterialTheme.typography.bodyMedium)
                        if (title.isNotBlank()) Muted(i.str("description"))
                    }
                }
            }
        }
    }
}

@Composable
fun BetaScreen(app: AppState) {
    var feedbackFor by remember { mutableStateOf<JsonObject?>(null) }
    ApiPage(app, "beta") { data, act ->
        val member = data.bool("member")
        if (!data.bool("eligible")) Notice("Demo accounts can't join the beta program.", LocalSkiColors.current.warning)
        Panel("Beta program") {
            Muted("Try features before everyone else and tell us what you think. You can leave at any time.")
            ActButton(if (member) "Leave the beta" else "Join the beta", { act.post(if (member) "leave" else "join") }, Modifier.fillMaxWidth(),
                enabled = !act.busy && data.bool("eligible"), style = if (member) "outlined" else "primary")
        }
        val features = data.objects("features")
        if (features.isEmpty()) Panel { EmptyText("No features are in beta right now.") }
        features.forEach { f ->
            Panel {
                androidx.compose.foundation.layout.Row(verticalAlignment = androidx.compose.ui.Alignment.CenterVertically) {
                    Text(f.str("name"), fontWeight = FontWeight.Bold, style = MaterialTheme.typography.titleMedium, modifier = Modifier.weight(1f))
                    Switch(f.bool("on"), { on -> act.post("feature/${f.str("key")}", mapOf("on" to on.toString())) }, enabled = member && !act.busy)
                }
                Muted(f.str("description"))
                f.obj("feedback").takeIf { it.size() > 0 }?.let { Muted("Your rating: ${it.int("rating")} out of 5") }
                if (member) ActButton(if (f.get("feedback")?.isJsonObject == true) "Edit feedback" else "Give feedback", { feedbackFor = f }, Modifier.fillMaxWidth(), style = "outlined")
            }
        }
        feedbackFor?.let { f ->
            BetaFeedbackDialog(f, onDismiss = { feedbackFor = null }) { rating, comment ->
                feedbackFor = null
                act.post("feedback/${f.str("key")}", mapOf("rating" to rating.toString(), "comment" to comment))
            }
        }
    }
}

@Composable
private fun BetaFeedbackDialog(feature: JsonObject, onDismiss: () -> Unit, onDone: (Int, String) -> Unit) {
    val old = feature.obj("feedback")
    var rating by remember { mutableStateOf(old.int("rating").takeIf { it > 0 }?.toString()) }
    var comment by remember { mutableStateOf(old.str("comment")) }
    androidx.compose.material3.AlertDialog(
        onDismissRequest = onDismiss,
        shape = MaterialTheme.shapes.extraLarge,
        title = { Text(feature.str("name"), fontWeight = FontWeight.Bold) },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                Text("Your rating", style = MaterialTheme.typography.bodyMedium)
                ChoiceRow((1..5).map { it.toString() to it.toString() }, rating) { rating = it }
                OutlinedTextField(comment, { comment = it.take(500) }, Modifier.fillMaxWidth(), label = { Text("What works, what doesn't") }, minLines = 3, shape = MaterialTheme.shapes.medium)
            }
        },
        confirmButton = {
            androidx.compose.material3.TextButton({ onDone(rating?.toInt() ?: 0, comment.trim()) }, enabled = rating != null) { Text("Send", fontWeight = FontWeight.Bold) }
        },
        dismissButton = { androidx.compose.material3.TextButton(onDismiss) { Text("Cancel") } },
    )
}
