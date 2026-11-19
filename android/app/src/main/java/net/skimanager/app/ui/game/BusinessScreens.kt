package net.skimanager.app.ui.game

import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.Modifier
import net.skimanager.app.api.bool
import net.skimanager.app.api.int
import net.skimanager.app.api.obj
import net.skimanager.app.api.objects
import net.skimanager.app.api.str
import net.skimanager.app.api.strOrNull
import net.skimanager.app.ui.AppState
import net.skimanager.app.ui.common.EmptyText
import net.skimanager.app.ui.common.Panel
import net.skimanager.app.ui.common.Row2
import net.skimanager.app.ui.theme.LocalSkiColors

@Composable
fun RealEstateScreen(app: AppState) {
    ApiPage(app, "real-estate") { data, act ->
        val market = data.obj("market")
        val plots = data.obj("plots")
        val stats = data.obj("stats")
        FigureRow(
            Triple("Land value", market.str("valueText"), "${stats.int("stars")}★ · ${stats.int("openSlopes")} open slopes"),
            Triple("Plots", "${plots.int("used")} / ${plots.int("total")}", plots.int("nextPlotAt").takeIf { it > 0 }?.let { "Next plot at $it stars" }),
            Triple("Rent per day", stats.str("rentPerDayText"), "${stats.str("upkeepPerDayText")} upkeep · ${stats.str("feesPerDayText")} fees"),
            Triple("Sales", stats.str("salesTotalText"), "${stats.int("salesCount")} sold"),
        )
        val parts = market.obj("parts")
        Panel("What sets the land value") {
            Row2("Base", parts.str("base"))
            Row2("Resort rating", parts.str("rating"))
            Row2("Open slopes", parts.str("slopes"))
            Row2("Slope condition", parts.str("condition"))
        }
        Panel("Your properties") {
            val props = data.objects("properties")
            if (props.isEmpty()) EmptyText("No properties yet. Develop a plot below.")
            props.forEach { p ->
                val state = p.str("state")
                TitleRow(p.str("name"), when (state) { "leased" -> "active"; "vacant" -> "closed"; "building" -> "under_construction"; else -> state })
                Muted("${p.obj("project").str("name")} · level ${p.int("level")}")
                when (state) {
                    "building" -> Muted("Ready in ${p.int("build_days_left")} days")
                    "sold" -> Row2("Sold for", p.str("salePriceText"))
                    else -> {
                        Row2("Rent per day", p.str("rentText"))
                        Row2("Upkeep per day", p.str("upkeepText"))
                        Row2("Sale value now", p.str("saleNowText"), if (p.int("gain") >= 0) LocalSkiColors.current.success else LocalSkiColors.current.danger)
                        ConditionRow(p.int("condition_pct"))
                        ButtonRow {
                            if (state != "broken") {
                                ActButton(if (state == "leased") "Stop leasing" else "Lease", { act.post("toggle/${p.int("id")}") }, Modifier.weight(1f), enabled = !act.busy)
                            }
                            ActButton("Sell", { act.post("sell/${p.int("id")}") }, Modifier.weight(1f), enabled = !act.busy, style = "outlined",
                                confirm = "Sell ${p.str("name")} for ${p.str("saleNowText")}? The agent's fee is ${p.str("feeText")}.")
                        }
                        if (p.bool("canRepair")) {
                            ActButton("Repair ${p.str("repairText")}", { act.post("repair/${p.int("id")}") }, Modifier.fillMaxWidth(), enabled = !act.busy)
                        }
                    }
                }
            }
        }
        Panel("Develop a plot") {
            if (plots.int("free") == 0) Muted("No free plots. Raise your resort rating to get more.")
            data.objects("projects").forEach { pr ->
                Heading(pr.str("name"))
                Muted("${pr.int("days")} days to build · pays back in ${pr.str("paybackText")}")
                Row2("Rent per day now", pr.str("rentNowText"))
                Row2("Net per day", pr.str("netNowText"))
                Row2("Sale value when built", "${pr.str("saleNowText")} (${pr.str("gainText")})")
                ActButton("Develop for ${pr.str("costText")}", { act.post("develop", mapOf("level" to pr.int("level").toString())) }, Modifier.fillMaxWidth(),
                    enabled = !act.busy && pr.bool("canAfford") && plots.int("free") > 0,
                    confirm = "Build a ${pr.str("name")} for ${pr.str("costText")}? It's ready in ${pr.int("days")} days.")
            }
        }
    }
}

@Composable
fun OffSeasonScreen(app: AppState) {
    ApiPage(app, "off-season") { data, act ->
        val season = data.obj("season")
        val health = data.obj("health")
        FigureRow(
            Triple("Season", if (season.bool("isSummer")) "Summer" else "Winter", "Day ${season.int("seasonDay")} of ${season.int("seasonLength")}"),
            Triple(
                if (season.bool("isSummer")) "Winter in" else "Summer in",
                "${if (season.bool("isSummer")) season.int("daysUntilWinter") else season.int("daysUntilSummer")} days", null,
            ),
            Triple("Cash", data.str("cashFormatted"), null),
        )
        Panel("Resort health") {
            listOf("slopes" to "Slopes", "lifts" to "Lifts", "buildings" to "Buildings", "equipment" to "Equipment", "terrainParks" to "Terrain parks").forEach { (k, label) ->
                val h = health.obj(k)
                Row2("$label (${h.int("count")})", "${h.int("avgCondition")}%", conditionColor(h.int("avgCondition")))
            }
        }
        Panel("Maintenance") {
            data.objects("maintenanceTasks").forEach { t ->
                Heading(t.str("name"))
                Muted(t.str("desc"))
                Row2("Needs work", "${t.int("needs_repair")} of ${t.int("total_items")} ${t.str("target")}")
                ActButton(
                    if (t.int("needs_repair") == 0) "Nothing to do" else "Run for ${t.str("total_cost_formatted")}",
                    { act.post("maintenance", mapOf("task" to t.str("key"))) }, Modifier.fillMaxWidth(),
                    enabled = !act.busy && t.bool("canAfford") && t.int("needs_repair") > 0,
                    confirm = "${t.str("name")}: ${t.int("needs_repair")} ${t.str("target")} for ${t.str("total_cost_formatted")}.",
                )
            }
        }
        Panel("Summer activities") {
            val acts = data.objects("summerActivities")
            if (acts.isEmpty()) EmptyText("No summer activities yet.")
            acts.forEach { a ->
                TitleRow(a.str("name"), if (a.bool("isActive")) "active" else a.str("status"))
                Muted("${a.str("revenueFormatted")} per day · ${a.str("upkeepFormatted")} upkeep · ${a.int("capacity")} guests")
            }
        }
        Panel("Build an activity") {
            data.objects("catalogActivities").forEach { c ->
                Heading(c.str("name"))
                Muted(c.str("desc"))
                Muted("${c.str("revenueFormatted")} per day · ${c.str("upkeepFormatted")} upkeep · ${c.int("capacity")} guests")
                ActButton("Build for ${c.str("costFormatted")}", { act.post("build", mapOf("activity_key" to c.str("key"))) }, Modifier.fillMaxWidth(),
                    enabled = !act.busy && c.bool("canAfford"), confirm = "Build ${c.str("name")} for ${c.str("costFormatted")}?")
            }
        }
    }
}

@Composable
fun MarketingScreen(app: AppState) {
    ApiPage(app, "marketing") { data, act ->
        val stats = data.obj("stats")
        FigureRow(
            Triple("Active campaigns", "${stats.int("activeCount")} / ${stats.int("totalCampaigns")}", null),
            Triple("Cost per day", stats.str("dailyCostText"), null),
            Triple("Visitor boost", "+${stats.int("totalVisitorBoost")}%", null),
            Triple("Reputation boost", "+${stats.int("totalRepBoost")}", null),
        )
        Panel("Your campaigns") {
            val campaigns = data.objects("campaigns")
            if (campaigns.isEmpty()) EmptyText("No campaigns running.")
            campaigns.forEach { c ->
                TitleRow(c.str("name"), c.str("status"))
                Muted("${c.str("dailyCostText")} per day · +${c.int("visitor_boost")}% visitors · +${c.int("reputation_boost")} reputation · ${c.int("days_remaining")} days left")
                ButtonRow {
                    ActButton(if (c.str("status") == "active") "Pause" else "Resume", { act.post("toggle/${c.int("id")}") }, Modifier.weight(1f), enabled = !act.busy)
                    ActButton("Cancel", { act.post("cancel/${c.int("id")}") }, Modifier.weight(1f), enabled = !act.busy, style = "outlined",
                        confirm = "Cancel ${c.str("name")}? Days already paid aren't refunded.")
                }
            }
        }
        Panel("Launch a campaign") {
            data.objects("campaignTypes").forEach { t ->
                Heading(t.str("name"))
                Muted("+${t.int("visitors")}% visitors · +${t.int("rep")} reputation · ${t.int("days")} days")
                ActButton("Launch for ${t.str("costText")}", { act.post("launch", mapOf("type" to t.str("key"))) }, Modifier.fillMaxWidth(),
                    enabled = !act.busy, confirm = "Launch ${t.str("name")} for ${t.str("costText")}?")
            }
        }
    }
}

@Composable
fun SkiLessonsScreen(app: AppState) {
    val open = LocalOpen.current
    ApiPage(app, "ski-lessons") { data, _ ->
        val stats = data.obj("stats")
        FigureRow(
            Triple("Instructors working", "${stats.int("activeInstructors")} / ${stats.int("totalInstructors")}", null),
            Triple("Daily capacity", stats.int("dailyCapacity").toString(), "${stats.int("lessonsToday")} lessons today"),
            Triple("Estimated revenue", stats.str("estimatedRevenueText"), "${stats.int("openSlopesCount")} open slopes"),
        )
        data.strOrNull("recentLessonLog")?.let { Notice(it, LocalSkiColors.current.success) }
        if (stats.int("totalInstructors") == 0) Notice("Hire ski instructors to run lessons.", LocalSkiColors.current.warning)
        ActButton("Hire instructors", { open("hire") }, Modifier.fillMaxWidth(), style = "primary")
        Panel("Lesson programs") {
            data.objects("lessonPrograms").forEach { p ->
                Row2(p.str("name"), p.str("priceText"))
                Muted("${p.str("duration")} · ${p.int("instructors")} instructor(s) · up to ${p.int("maxStudents")} students")
            }
        }
        Panel("Instructors") {
            val list = data.objects("instructors")
            if (list.isEmpty()) EmptyText("No instructors yet.")
            list.forEach { i ->
                TitleRow(i.str("name"), i.str("status"))
                Muted("Level ${i.int("level")} · morale ${i.int("morale")}% · ${i.str("salaryText")}" + (i.strOrNull("assignedTo")?.let { " · $it" } ?: ""))
            }
        }
    }
}

/** /compliance: the summary of insurance, regulations and the environment, each with its own screen */
@Composable
fun ComplianceScreen(app: AppState) {
    val open = LocalOpen.current
    ApiPage(app, "compliance") { data, _ ->
        val s = data.obj("summary")
        FigureRow(
            Triple("Insurance", "${s.int("activeInsCount")} / ${s.int("totalInsCount")}", "${s.str("totalPremiumText")} per day"),
            Triple("Regulations", "${s.int("compliantCount")} / ${s.int("totalRegCount")}", "Score ${s.int("complianceScore")}%"),
            Triple("Eco score", s.int("ecoScore").toString(), null),
        )
        ButtonRow {
            ActButton("Insurance", { open("insurance") }, Modifier.weight(1f))
            ActButton("Government", { open("government") }, Modifier.weight(1f))
        }
        ActButton("Environment", { open("environment") }, Modifier.fillMaxWidth())
        Panel("Inspections") {
            val list = data.obj("government").objects("inspections")
            if (list.isEmpty()) EmptyText("No inspections yet.")
            list.forEach { i ->
                Row2(i.str("dateText"), if (i.bool("passed")) "Passed" else (i.strOrNull("fineText") ?: "Failed"),
                    if (i.bool("passed")) LocalSkiColors.current.success else LocalSkiColors.current.danger)
                Muted(i.str("message"))
            }
        }
    }
}

@Composable
fun InsuranceScreen(app: AppState) {
    ApiPage(app, "insurance") { data, act ->
        val stats = data.obj("stats")
        FigureRow(
            Triple("Active policies", "${stats.int("activeCount")} / ${stats.int("totalCount")}", null),
            Triple("Premiums per day", stats.str("totalPremiumText"), null),
            Triple("Total cover", stats.str("totalCoverageText"), null),
        )
        Panel("Policies") {
            data.objects("policies").forEach { p ->
                val active = p.bool("active")
                TitleRow(p.str("name"), if (active) "active" else "off")
                Row2("Premium per day", p.str("premiumText"))
                Row2("Cover", p.str("coverageText"))
                ActButton(if (active) "Cancel policy" else "Activate", { act.post("toggle/${p.int("id")}") }, Modifier.fillMaxWidth(), enabled = !act.busy,
                    style = if (active) "outlined" else "secondary", confirm = if (active) "Cancel ${p.str("name")}? You lose its cover." else null)
            }
        }
    }
}

@Composable
fun GovernmentScreen(app: AppState) {
    ApiPage(app, "government") { data, act ->
        val stats = data.obj("stats")
        FigureRow(
            Triple("Compliance", "${stats.int("complianceScore")}%", "${stats.int("compliantCount")} of ${stats.int("totalCount")} rules"),
            Triple("Cost per day", stats.str("totalCostText"), "Full compliance ${stats.str("totalPossibleCostText")}"),
            Triple("Fine risk", stats.str("totalRiskText"), "Inspection chance ${stats.int("inspectionChance")}%"),
            Triple("Penalties", "-${stats.int("reputationPenalty")} rep", "-${stats.int("visitorPenalty")}% visitors"),
        )
        if (stats.int("compliantCount") < stats.int("totalCount")) {
            ActButton("Comply with everything", { act.post("comply-all") }, Modifier.fillMaxWidth(), enabled = !act.busy, style = "primary",
                confirm = "Comply with every regulation? It costs ${stats.str("totalPossibleCostText")} a day.")
        }
        data.objects("tiers").forEach { tier ->
            Panel(tier.str("title")) {
                Muted(tier.str("subtitle"))
                tier.objects("regulations").forEach { r ->
                    val ok = r.bool("compliant")
                    TitleRow(r.str("name"), if (ok) "active" else "off")
                    Muted(r.str("benefit"))
                    Row2("Cost per day", r.str("costText"))
                    Row2("Fine if inspected", "${r.str("penaltyText")} (${r.int("penalty_risk")}% risk)")
                    ActButton(if (ok) "Stop complying" else "Comply", { act.post("toggle/${r.int("id")}") }, Modifier.fillMaxWidth(), enabled = !act.busy,
                        style = if (ok) "outlined" else "secondary")
                }
            }
        }
        Panel("Recent inspections") {
            val list = data.objects("inspections")
            if (list.isEmpty()) EmptyText("No inspections yet.")
            list.forEach { i -> Row2(i.str("message"), i.str("dateText")) }
        }
    }
}

@Composable
fun EnvironmentScreen(app: AppState) {
    ApiPage(app, "environment") { data, act ->
        val env = data.obj("env")
        val carbon = data.obj("carbon")
        FigureRow(
            Triple("Eco score", env.int("eco_score").toString(), null),
            Triple("Renewable energy", "${env.int("renewable_pct")}%", null),
            Triple("Waste management", "${env.int("waste_management")}%", null),
            Triple("Wildlife impact", "${env.int("wildlife_impact")}%", "Lower is better"),
        )
        Panel("Carbon per day") {
            Row2("Total", carbon.int("total").toString())
            Row2("Snow cannons", carbon.int("fromCannons").toString())
            Row2("Night lights", carbon.int("fromLights").toString())
            Row2("Buildings", carbon.int("fromBuildings").toString())
        }
        Panel("Green upgrades") {
            data.objects("upgrades").forEach { up ->
                Heading(up.str("name"))
                Muted(up.str("desc"))
                ActButton("Buy for ${up.str("costText")}", {
                    act.post("upgrade", mapOf("name" to up.str("name"), "field" to up.str("field"), "boost" to up.int("boost").toString()))
                }, Modifier.fillMaxWidth(), enabled = !act.busy, confirm = "Buy ${up.str("name")} for ${up.str("costText")}?")
            }
        }
    }
}

@Composable
fun MoraleScreen(app: AppState) {
    ApiPage(app, "morale") { data, act ->
        val stats = data.obj("stats")
        FigureRow(
            Triple("Average morale", "${stats.int("avgMorale")}%", "${stats.int("totalStaff")} staff"),
            Triple("Happy / unhappy", "${stats.int("highMoraleCount")} / ${stats.int("lowMoraleCount")}", null),
        )
        Panel("Boost morale") {
            data.objects("boosts").forEach { b ->
                Heading(b.str("name"))
                Muted("${b.str("desc")} +${b.int("morale")}% morale.")
                ActButton(b.strOrNull("costText")?.let { "Run for $it" } ?: "Run", { act.post("boost", mapOf("action" to b.str("key"))) }, Modifier.fillMaxWidth(),
                    enabled = !act.busy, confirm = b.str("confirmBody").ifBlank { null })
            }
        }
        Panel("Staff") {
            val staff = data.objects("staff")
            if (staff.isEmpty()) EmptyText("No staff yet.")
            staff.forEach { s -> Row2("${s.str("name")} · ${s.str("roleLabel")}", "${s.int("morale")}%", conditionColor(s.int("morale"))) }
        }
    }
}

/** The website's tone names as colors; anything else is the primary color */
@Composable
fun toneColor(tone: String): Color = when (tone) {
    "success" -> LocalSkiColors.current.success
    "warning" -> LocalSkiColors.current.warning
    "danger" -> LocalSkiColors.current.danger
    "muted", "default" -> LocalSkiColors.current.muted
    else -> MaterialTheme.colorScheme.primary
}

@Composable
fun ResortAnalysisScreen(app: AppState) {
    val open = LocalOpen.current
    ApiPage(app, "resort-analysis") { data, act ->
        val today = data.get("todayReport")?.takeIf { it.isJsonObject }?.asJsonObject
        FigureRow(
            Triple("Your Génépis", data.int("balance").toString(), null),
            Triple("Report cost", "${data.int("cost")} Génépis", "One report per game day"),
        )
        if (today != null) {
            ActButton("Open today's report", { open("resort-analysis/view/${today.int("id")}") }, Modifier.fillMaxWidth(), style = "primary")
        } else {
            ActButton("Order today's report", { act.post("order") }, Modifier.fillMaxWidth(), enabled = !act.busy && data.bool("canAfford"), style = "primary",
                confirm = "Order an analysis of your resort for ${data.int("cost")} Génépis?")
        }
        Panel("Past reports") {
            val reports = data.objects("reports")
            if (reports.isEmpty()) EmptyText("No reports yet.")
            reports.forEach { r ->
                Row2("Day ${r.int("game_day")} · ${r.str("created_date")}", "${r.int("score")}/100", toneColor(r.str("score_tone")))
                Muted("${r.int("recommendations_count")} recommendations, ${r.int("urgent_count")} urgent")
                r.objects("top_recommendations").take(2).forEach { Muted("• ${it.str("text")}") }
                ActButton("Open", { open("resort-analysis/view/${r.int("id")}") }, Modifier.fillMaxWidth(), style = "outlined")
            }
        }
    }
}

@Composable
fun ReportScreen(app: AppState, id: String) {
    val open = LocalOpen.current
    ApiPage(app, "resort-analysis/view/$id", actionBase = "resort-analysis") { data, _ ->
        val report = data.obj("report")
        val summary = data.obj("stats_summary")
        Panel {
            Heading("Day ${report.int("game_day")} · ${report.str("created_date")}")
            Text("${data.int("overall_score")}/100", color = toneColor(data.str("score_tone")), style = MaterialTheme.typography.headlineMedium)
            Muted(data.str("headline"))
            Muted("${summary.int("urgent_count")} urgent · ${summary.int("warning_count")} warnings · ${summary.int("total_recommendations")} recommendations")
        }
        val recs = data.objects("recommendations")
        if (recs.isNotEmpty()) {
            Panel("Recommendations") {
                recs.forEach { r ->
                    Notice("${r.str("label")}: ${r.str("text")}", toneColor(r.str("tone")))
                    routeForPath(r.str("link").trimStart('/'))?.let { route -> ActButton("Go to ${r.str("area")}", { open(route) }, Modifier.fillMaxWidth(), style = "outlined") }
                }
            }
        }
        data.objects("sections").forEach { s ->
            Panel(s.str("name")) {
                s.get("score")?.takeIf { !it.isJsonNull }?.let { Row2("Score", "${s.int("score")}/100", toneColor(s.str("score_tone"))) }
                Muted(s.str("summary"))
                s.objects("metrics").forEach { m -> Row2(m.str("label"), m.str("value")) }
            }
        }
        data.objects("legacyScores").takeIf { it.isNotEmpty() }?.let { scores ->
            Panel("Scores") { scores.forEach { Row2(it.str("name"), "${it.int("score")}/100") } }
        }
    }
}
