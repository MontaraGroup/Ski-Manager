package net.skimanager.app.ui.game

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.google.gson.JsonObject
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
import net.skimanager.app.ui.common.Panel
import net.skimanager.app.ui.common.Row2
import net.skimanager.app.ui.common.StatusBadge
import net.skimanager.app.ui.theme.LocalSkiColors

/** A status word as a colored pill: open/active green, closed/off grey, broken red, building amber */
@Composable
fun StatusPill(status: String) {
    val c = LocalSkiColors.current
    val color = when (status) {
        "open", "active", "claimed", "running", "ready", "passed" -> c.success
        "broken", "full" -> c.danger
        "under_construction", "building", "pending" -> c.warning
        else -> c.muted
    }
    val text = when (status) {
        "under_construction" -> "BUILDING"
        else -> status.replace('_', ' ').uppercase()
    }
    StatusBadge(text, color, color.copy(alpha = 0.15f))
}

/** Title on the left, status pill on the right */
@Composable
fun TitleRow(title: String, status: String? = null, trailing: (@Composable () -> Unit)? = null) {
    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
        Text(title, fontWeight = FontWeight.Bold, style = MaterialTheme.typography.titleMedium, modifier = Modifier.weight(1f))
        status?.let { StatusPill(it) }
        trailing?.invoke()
    }
}

@Composable
fun ConditionRow(pct: Int) {
    Row2("Condition", "$pct%", conditionColor(pct))
    Meter(pct, conditionColor(pct))
}

@Composable
fun FigureRow(vararg figures: Triple<String, String, String?>) {
    figures.toList().chunked(2).forEach { pair ->
        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(12.dp)) {
            pair.forEach { (label, value, note) -> Column(Modifier.weight(1f)) { Figure(label, value, note) } }
            if (pair.size == 1) Column(Modifier.weight(1f)) {}
        }
    }
}

@Composable
fun ResortScreen(app: AppState) {
    val open = LocalOpen.current
    ApiPage(app, "resort") { data, act ->
        val resort = data.obj("resort")
        val stats = data.obj("stats")
        val weather = data.obj("weather")
        val isOpen = resort.bool("is_open")
        Panel {
            TitleRow(resort.str("name").ifBlank { "Your resort" }, if (isOpen) "open" else "closed")
            Muted("${weather.str("condition")} · ${weather.str("tempFormatted")} · ${weather.str("snowBaseFormatted")} base · wind ${weather.str("windSpeedFormatted")}")
            ActButton(
                if (isOpen) "Close the resort" else "Open the resort", { act.post("toggle-resort") }, Modifier.fillMaxWidth(),
                enabled = !act.busy, style = if (isOpen) "outlined" else "primary",
                confirm = if (isOpen) "No skiers come while the resort is closed, so you earn no ticket income." else null,
            )
        }
        FigureRow(
            Triple("Open slopes", "${stats.int("openSlopes")} / ${stats.int("totalSlopes")}", null),
            Triple("Open lifts", "${stats.int("openLifts")} / ${stats.int("totalLifts")}", null),
            Triple("Average condition", "${stats.int("avgCondition")}%", null),
            Triple("Cash", data.str("cashFormatted"), "${stats.int("staffCount")} staff · ${stats.int("buildingCount")} buildings"),
        )
        ButtonRow {
            ActButton("Open all", { act.post("open-all") }, Modifier.weight(1f), enabled = !act.busy)
            ActButton("Close all", { act.post("close-all") }, Modifier.weight(1f), enabled = !act.busy, style = "outlined")
        }
        val sectors = data.objects("sectors")
        if (sectors.isEmpty()) {
            Panel { EmptyText("Nothing built yet. Build lifts and slopes on the trail map.") }
            ActButton("Open the trail map", { open("map") }, Modifier.fillMaxWidth(), style = "primary")
        }
        sectors.forEach { sector ->
            Panel(sector.str("name").ifBlank { "Sector ${sector.int("sectorNum")}" }) {
                sector.objects("lifts").forEach { lift -> LiftRow(lift, act) }
                sector.objects("slopes").forEach { slope -> SlopeRow(slope, act) }
                if (sector.objects("lifts").isEmpty() && sector.objects("slopes").isEmpty()) Muted("Nothing built in this sector.")
            }
        }
        Muted("To replace a lift with another type, open it on the trail map.")
    }
}

@Composable
private fun LiftRow(lift: JsonObject, act: PageActions) {
    val status = lift.str("status")
    Column(verticalArrangement = Arrangement.spacedBy(6.dp)) {
        TitleRow(lift.str("name"), status)
        Muted("${lift.str("subtypeName")} · ${lift.int("seats")} seats · ${lift.int("capacity")} riders/h")
        ConditionRow(lift.int("condition_pct"))
        ButtonRow {
            if (status != "broken") {
                ActButton(if (status == "open") "Close" else "Open", { act.post("toggle/${lift.int("id")}") }, Modifier.weight(1f), enabled = !act.busy)
            }
            if (lift.int("condition_pct") < 100) {
                ActButton(
                    "Repair ${lift.str("repair_cost_formatted")}", { act.post("repair/${lift.int("id")}") }, Modifier.weight(1f),
                    enabled = !act.busy && lift.bool("canAffordRepair"),
                    confirm = "Repair ${lift.str("name")} for ${lift.str("repair_cost_formatted")}?",
                )
            }
        }
    }
}

@Composable
private fun SlopeRow(slope: JsonObject, act: PageActions) {
    val status = slope.str("status")
    Column(verticalArrangement = Arrangement.spacedBy(6.dp)) {
        TitleRow(slope.str("name"), status)
        Muted("${slope.str("difficultyLabel")} · ${slope.str("snow_depth_formatted")} snow · ${slope.str("snow_quality")}")
        ConditionRow(slope.int("condition_pct"))
        if (status == "broken") {
            Muted("Groom this slope to bring it back.")
        } else {
            ActButton(if (status == "open") "Close" else "Open", { act.post("toggle/${slope.int("id")}") }, Modifier.fillMaxWidth(), enabled = !act.busy)
        }
    }
}

@Composable
fun NightSkiingScreen(app: AppState) {
    ApiPage(app, "night-skiing") { data, act ->
        val stats = data.obj("stats")
        FigureRow(
            Triple("Slope coverage", "${stats.int("totalCoverage")}%", null),
            Triple("Lights on", "${stats.int("activeCount")} / ${stats.int("totalCount")}", null),
            Triple("Energy per night", stats.str("totalEnergyText"), null),
            Triple("Average condition", "${stats.int("avgCondition")}%", "${stats.int("mechanics")} mechanics"),
        )
        if (stats.int("mechanics") == 0 && stats.int("totalCount") > 0) {
            Notice("Without a mechanic, lights wear down faster. Hire one on the Staff screen.", LocalSkiColors.current.warning)
        }
        if (data.objects("lights").isNotEmpty()) {
            ButtonRow {
                ActButton("All on", { act.post("toggle-all", mapOf("action" to "on")) }, Modifier.weight(1f), enabled = !act.busy)
                ActButton("All off", { act.post("toggle-all", mapOf("action" to "off")) }, Modifier.weight(1f), enabled = !act.busy, style = "outlined")
            }
        }
        Panel("Your lights") {
            val lights = data.objects("lights")
            if (lights.isEmpty()) EmptyText("No lights yet. Buy one below to open slopes at night.")
            lights.forEach { light ->
                val on = light.str("status") == "active"
                TitleRow(light.str("name"), light.str("status"))
                Muted("${light.int("coverage")}% coverage · ${light.str("energyCostText")} energy")
                ConditionRow(light.int("condition_pct"))
                ButtonRow {
                    ActButton(if (on) "Turn off" else "Turn on", { act.post("toggle/${light.int("id")}") }, Modifier.weight(1f), enabled = !act.busy)
                    if (light.int("condition_pct") < 100) {
                        ActButton("Repair", { act.post("repair/${light.int("id")}") }, Modifier.weight(1f), enabled = !act.busy,
                            confirm = "Repair ${light.str("name")} for ${light.str("repairCostText")}?")
                    }
                    ActButton("Remove", { act.post("sell/${light.int("id")}") }, Modifier.weight(1f), enabled = !act.busy, style = "outlined",
                        confirm = "Remove ${light.str("name")}? You get ${light.str("refundText")} back.")
                }
            }
        }
        Panel("Buy lights") {
            data.objects("lightTypes").forEach { t ->
                Heading(t.str("name"))
                Muted(t.str("desc"))
                Row2("Coverage", "${t.int("coverage")}%")
                Row2("Energy", t.str("energyText"))
                ActButton("Buy for ${t.str("costText")}", { act.post("buy", mapOf("type" to t.str("key"))) }, Modifier.fillMaxWidth(), enabled = !act.busy)
            }
        }
    }
}

@Composable
fun ParkingScreen(app: AppState) {
    var feeFor by remember { mutableStateOf<JsonObject?>(null) }
    ApiPage(app, "parking") { data, act ->
        val stats = data.obj("stats")
        FigureRow(
            Triple("Capacity", stats.str("totalCapacityText"), "${stats.int("facilityCount")} lots"),
            Triple("Occupancy", "${stats.int("occupancyPct")}%", null),
            Triple("Revenue per day", stats.str("totalRevenueText"), null),
        )
        if (stats.bool("nearlyFull")) Notice("Your parking is nearly full. Guests who can't park go elsewhere.", LocalSkiColors.current.warning)
        Panel("Your lots") {
            val lots = data.objects("facilities")
            if (lots.isEmpty()) EmptyText("No parking yet. Build a lot below.")
            lots.forEach { f ->
                val status = f.str("status")
                TitleRow(f.str("name"), status)
                if (status == "under_construction") {
                    Muted("${f.str("typeLabel")} · ready in ${f.int("build_days_left")} days")
                } else {
                    Muted("${f.str("typeLabel")} · ${f.str("occupiedText")} of ${f.str("capacityText")} · ${f.str("dailyRevenueText")} per day")
                    Row2("Fee per day", if (f.int("fee_per_day") > 0) f.str("fee_per_day") else "Default (${f.str("defaultFeeText")})")
                    ConditionRow(f.int("condition_pct"))
                    ButtonRow {
                        ActButton(if (status == "closed") "Open" else "Close", { act.post("toggle/${f.int("id")}") }, Modifier.weight(1f), enabled = !act.busy)
                        ActButton("Set fee", { feeFor = f }, Modifier.weight(1f), enabled = !act.busy)
                    }
                    if (f.bool("canRepair")) {
                        ActButton("Repair ${f.str("repairCostText")}", { act.post("repair/${f.int("id")}") }, Modifier.fillMaxWidth(), enabled = !act.busy,
                            confirm = "Repair ${f.str("name")} for ${f.str("repairCostText")}?")
                    }
                }
                ActButton("Demolish", { act.post("demolish/${f.int("id")}") }, Modifier.fillMaxWidth(), enabled = !act.busy, style = "outlined",
                    confirm = "Demolish ${f.str("name")}? This can't be undone.")
            }
        }
        Panel("Build parking") {
            data.objects("catalog").forEach { c ->
                Heading(c.str("label"))
                Muted("${c.str("capacityText")} spaces · ${c.str("upkeepText")} upkeep · ${c.int("build_days")} days to build")
                ActButton("Build for ${c.str("costText")}", { act.post("build", mapOf("parking_type" to c.str("key"))) }, Modifier.fillMaxWidth(),
                    enabled = !act.busy && c.bool("canAfford"), confirm = "Build a ${c.str("label")} for ${c.str("costText")}?")
            }
        }
        feeFor?.let { f ->
            InputDialog("Parking fee", "Fee per day", f.int("fee_per_day").takeIf { it > 0 }?.toString().orEmpty(), number = true,
                help = "Leave the default (${f.str("defaultFeeText")}) unless you want to change demand.", onDismiss = { feeFor = null }) { fee ->
                feeFor = null
                act.post("update-fee/${f.int("id")}", mapOf("fee" to fee))
            }
        }
    }
}

@Composable
fun ScenicLiftsScreen(app: AppState) {
    var priceFor by remember { mutableStateOf<JsonObject?>(null) }
    var designate by remember { mutableStateOf<JsonObject?>(null) }
    ApiPage(app, "scenic-lifts") { data, act ->
        val stats = data.obj("stats")
        val rules = data.obj("rules")
        FigureRow(
            Triple("Scenic lifts", "${stats.int("scenicCount")} / ${stats.int("totalLifts")}", null),
            Triple("Season", if (stats.bool("isSummer")) "Summer" else "Winter", "Day ${stats.int("seasonDay")} of ${stats.int("seasonLength")}"),
            Triple("Summer revenue", stats.str("summerRevenueText"), null),
            Triple("Season revenue", stats.str("seasonRevenueText"), null),
        )
        Muted("In winter, scenic lifts sell ${stats.int("winterSharePct")}% of their summer rides. Reference price ${rules.str("refPriceText")}.")
        Panel("Scenic lifts") {
            val lifts = data.objects("scenicLifts")
            if (lifts.isEmpty()) EmptyText("No scenic lifts yet. Designate one of your lifts below.")
            lifts.forEach { lift ->
                TitleRow(lift.str("name"), lift.str("status"))
                Muted("${lift.str("subtypeLabel")} · ${lift.str("lengthText")} · sector ${lift.int("sector")}")
                Row2("Ticket price", lift.str("priceText"))
                Row2("Riders today", lift.int("riders").toString())
                Row2("Revenue today", lift.str("todayRevenueText"))
                lift.objects("upgrades").takeIf { it.isNotEmpty() }?.let { ups -> Muted("Upgrades: " + ups.joinToString { it.str("name") }) }
                ButtonRow {
                    ActButton("Set price", { priceFor = lift }, Modifier.weight(1f), enabled = !act.busy)
                    ActButton("Remove", { act.post("remove/${lift.int("id")}") }, Modifier.weight(1f), enabled = !act.busy, style = "outlined",
                        confirm = "Stop running ${lift.str("name")} as a scenic lift?")
                }
                val installed = lift.strings("installedKeys").toSet()
                data.objects("upgrades").filter { it.str("key") !in installed }.forEach { up ->
                    ActButton("Add ${up.str("name")}: ${up.str("costText")}", { act.post("upgrade", mapOf("upgrade" to up.str("key"), "item_id" to lift.int("id").toString())) },
                        Modifier.fillMaxWidth(), enabled = !act.busy, style = "outlined",
                        confirm = "${up.str("desc")} Revenue ${up.str("revenueBoostText")}. Cost ${up.str("costText")}.")
                }
            }
        }
        val available = data.objects("availableLifts")
        if (available.isNotEmpty()) {
            Panel("Designate a lift") {
                available.forEach { l ->
                    Row2(l.str("name"), "${l.str("subtypeLabel")} · ${l.str("lengthText")}")
                    ActButton("Make scenic", { designate = l }, Modifier.fillMaxWidth(), enabled = !act.busy)
                }
            }
        }
        val priceHelp = "Between ${rules.int("minPrice")} and ${rules.int("maxPrice")}. Reference ${rules.str("refPriceText")}."
        priceFor?.let { l ->
            InputDialog("Ticket price", "Price", l.int("price").toString(), number = true, help = priceHelp, onDismiss = { priceFor = null }) {
                priceFor = null
                act.post("update-price/${l.int("id")}", mapOf("price" to it))
            }
        }
        designate?.let { l ->
            InputDialog("Make ${l.str("name")} scenic", "Ticket price", rules.int("refPrice").toString(), number = true, help = priceHelp,
                confirmLabel = "Make scenic", onDismiss = { designate = null }) {
                designate = null
                act.post("designate", mapOf("item_id" to l.int("id").toString(), "ticket_price" to it))
            }
        }
    }
}

@Composable
fun TerrainParksScreen(app: AppState) {
    val open = LocalOpen.current
    var type by rememberSaveable { mutableStateOf<String?>(null) }
    var size by rememberSaveable { mutableStateOf("small") }
    var slope by rememberSaveable { mutableStateOf<String?>(null) }
    var naming by remember { mutableStateOf(false) }
    ApiPage(app, "terrain-parks") { data, act ->
        val stats = data.obj("stats")
        FigureRow(
            Triple("Open features", stats.int("openCount").toString(), "${stats.int("buildingCount")} being built"),
            Triple("Visitors per day", stats.int("dailyVisitors").toString(), null),
            Triple("Park crew", stats.int("crewCount").toString(), null),
            Triple("Average condition", "${stats.int("avgCondition")}%", data.str("cashFormatted") + " cash"),
        )
        if (stats.int("crewCount") == 0) {
            Notice("Without park crew, features lose condition faster.", LocalSkiColors.current.warning)
            ActButton("Hire park crew", { open("hire") }, Modifier.fillMaxWidth())
        }
        Panel("Your features") {
            val parks = data.objects("parks")
            if (parks.isEmpty()) EmptyText("No terrain park features yet.")
            parks.forEach { p ->
                val status = p.str("status")
                TitleRow(p.str("name"), status)
                Muted(listOfNotNull(p.str("typeLabel"), p.str("size").replaceFirstChar { it.uppercase() }, p.strOrNull("slope_name")).joinToString(" · "))
                if (status == "under_construction") {
                    Muted("Ready in ${p.int("build_days_left")} days")
                } else {
                    Row2("Visitors per day", p.int("daily_visitors").toString())
                    Row2("Popularity", "${p.int("popularity")}%")
                    Row2("Upkeep", p.str("upkeepFormatted"))
                    ConditionRow(p.int("condition_pct"))
                    ButtonRow {
                        ActButton(if (status == "open") "Close" else "Open", { act.post("toggle/${p.int("id")}") }, Modifier.weight(1f), enabled = !act.busy)
                        if (p.int("condition_pct") < 100) {
                            ActButton("Repair ${p.str("repair_cost_formatted")}", { act.post("repair/${p.int("id")}") }, Modifier.weight(1f),
                                enabled = !act.busy && p.bool("canAffordRepair"))
                        }
                    }
                }
                ActButton("Demolish", { act.post("demolish/${p.int("id")}") }, Modifier.fillMaxWidth(), enabled = !act.busy, style = "outlined",
                    confirm = "Demolish ${p.str("name")}? You get ${p.str("refund_formatted")} back.")
            }
        }
        val config = data.entries("parkConfig")
        val slopes = data.objects("slopes")
        if (config.isNotEmpty()) {
            Panel("Build a feature") {
                val chosen = config.firstOrNull { it.second.str("key") == type }?.second ?: config.first().second
                Heading("Type")
                config.map { it.second }.chunked(3).forEach { row ->
                    ChoiceRow(row.map { it.str("key") to it.str("label") }, chosen.str("key")) { type = it }
                }
                Heading("Size")
                ChoiceRow(listOf("small" to "Small", "medium" to "Medium", "large" to "Large"), size) { size = it }
                val cfg = chosen.obj("sizes").obj(size)
                Row2("Cost", cfg.str("costFormatted"))
                Row2("Upkeep", cfg.str("upkeepFormatted"))
                Row2("Build time", "${cfg.int("build_days")} days")
                if (slopes.isNotEmpty()) {
                    Heading("Slope")
                    slopes.chunked(2).forEach { row ->
                        ChoiceRow(row.map { it.int("id").toString() to it.str("name") }, slope) { slope = it }
                    }
                }
                ActButton("Build", { naming = true }, Modifier.fillMaxWidth(), enabled = !act.busy, style = "primary")
                if (naming) {
                    InputDialog("Name the feature", "Name", "${chosen.str("label")} ${size.replaceFirstChar { it.uppercase() }}",
                        help = "${cfg.str("costFormatted")}, ready in ${cfg.int("build_days")} days.", confirmLabel = "Build", onDismiss = { naming = false }) { name ->
                        naming = false
                        act.post("build", buildMap {
                            put("name", name); put("park_type", chosen.str("key")); put("size", size)
                            slope?.let { put("slope_id", it) }
                        })
                    }
                }
            }
        }
    }
}

/** /energy and /water: one screen, two categories */
@Composable
fun UtilityScreen(app: AppState, category: String) {
    var building by remember { mutableStateOf<JsonObject?>(null) }
    ApiPage(app, category) { data, act ->
        val demand = data.obj("demand")
        FigureRow(
            Triple("Demand per day", demand.str("totalFormatted"), null),
            Triple("Your output", data.str("outputFormatted"), null),
            Triple("Shortfall", data.str("deficitFormatted"), "Bought at ${data.str("tariffLabel")}"),
            Triple("Cost per day", data.str("totalDailyCostFormatted"), "${data.str("upkeepFormatted")} upkeep + ${data.str("importCostFormatted")} bought"),
        )
        if (data.int("deficit") > 0) {
            Notice("You buy ${data.str("deficitFormatted")} a day at ${data.str("tariffLabel")}. More sources cut that cost.", LocalSkiColors.current.warning)
        }
        Panel("What uses it") { demand.objects("parts").forEach { Row2(it.str("label"), it.str("valueFormatted")) } }
        Panel("Your sources") {
            val sources = data.objects("sources")
            if (sources.isEmpty()) EmptyText("No sources yet.")
            sources.forEach { s ->
                val status = s.str("status")
                TitleRow(s.str("name"), status)
                Muted("${s.str("typeLabel")} · ${s.str("outputFormatted")} · ${s.str("upkeepFormatted")} upkeep")
                s.strOrNull("dailyValueFormatted")?.let { Muted("Saves $it a day") }
                if (status == "under_construction") {
                    Muted("Ready in ${s.int("build_days_left")} days")
                } else {
                    ConditionRow(s.int("condition_pct"))
                    ButtonRow {
                        if (status != "broken") {
                            ActButton(if (status == "active") "Turn off" else "Turn on", { act.post("toggle/${s.int("id")}") }, Modifier.weight(1f), enabled = !act.busy)
                        }
                        if (s.int("condition_pct") < 100) {
                            ActButton("Repair ${s.str("repair_cost_formatted")}", { act.post("repair/${s.int("id")}") }, Modifier.weight(1f),
                                enabled = !act.busy && s.bool("canAffordRepair"))
                        }
                    }
                }
            }
        }
        Panel("Build a source") {
            data.objects("availableSources").forEach { a ->
                Heading(a.str("label"))
                Muted("${a.str("outputFormatted")} · ${a.str("upkeepFormatted")} upkeep · ${a.int("build_days")} days" +
                    (a.get("limit")?.takeIf { !it.isJsonNull }?.let { " · ${a.int("owned")} of ${a.int("limit")} built" } ?: ""))
                ActButton(if (a.bool("atLimit")) "Limit reached" else "Build for ${a.str("costFormatted")}", { building = a }, Modifier.fillMaxWidth(),
                    enabled = !act.busy && a.bool("canAfford") && !a.bool("atLimit"))
            }
        }
        building?.let { a ->
            InputDialog("Build ${a.str("label")}", "Name", a.str("label"), help = "${a.str("costFormatted")}, ready in ${a.int("build_days")} days.",
                confirmLabel = "Build", onDismiss = { building = null }) { name ->
                building = null
                act.post("build", mapOf("source_type" to a.str("type"), "name" to name))
            }
        }
    }
}
