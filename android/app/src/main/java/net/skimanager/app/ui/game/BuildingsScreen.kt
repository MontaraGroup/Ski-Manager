package net.skimanager.app.ui.game

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.DirectionsBus
import androidx.compose.material.icons.filled.Hotel
import androidx.compose.material.icons.filled.LocalHospital
import androidx.compose.material.icons.filled.Restaurant
import androidx.compose.material.icons.filled.ShoppingBag
import androidx.compose.material.icons.filled.Storefront
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.FilterChip
import androidx.compose.material3.Icon
import androidx.compose.material3.LinearProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.google.gson.JsonObject
import kotlinx.coroutines.launch
import net.skimanager.app.ui.AppState
import net.skimanager.app.api.ApiResult
import net.skimanager.app.api.int
import net.skimanager.app.api.obj
import net.skimanager.app.api.objects
import net.skimanager.app.api.str
import net.skimanager.app.ui.common.EmptyText
import net.skimanager.app.ui.common.Figure
import net.skimanager.app.ui.common.Loaded
import net.skimanager.app.ui.common.OutlinedPrimaryButton
import net.skimanager.app.ui.common.Panel
import net.skimanager.app.ui.common.PrimaryButton
import net.skimanager.app.ui.common.Row2
import net.skimanager.app.ui.common.ScreenColumn
import net.skimanager.app.ui.common.SecondaryButton
import net.skimanager.app.ui.common.StatusBadge
import net.skimanager.app.ui.theme.LocalSkiColors

data class BuildingCategory(val key: String, val label: String, val icon: ImageVector)

private val BUILDING_CATEGORIES = listOf(
    BuildingCategory("hotel", "Hotels", Icons.Default.Hotel),
    BuildingCategory("restaurant", "Restaurants", Icons.Default.Restaurant),
    BuildingCategory("rental", "Rentals", Icons.Default.ShoppingBag),
    BuildingCategory("retail", "Retail", Icons.Default.Storefront),
    BuildingCategory("ski_patrol", "Ski Patrol", Icons.Default.LocalHospital),
    BuildingCategory("transportation", "Transit", Icons.Default.DirectionsBus),
)

@Composable
fun BuildingsScreen(app: AppState) {
    val scope = rememberCoroutineScope()
    var selectedType by remember { mutableStateOf("hotel") }
    var note by remember { mutableStateOf<String?>(null) }
    var failed by remember { mutableStateOf(false) }

    var buildTier by remember { mutableStateOf<JsonObject?>(null) }
    var upgradeBuilding by remember { mutableStateOf<JsonObject?>(null) }
    var demolishBuilding by remember { mutableStateOf<JsonObject?>(null) }

    Loaded("buildings/$selectedType", { app.api.get("buildings/$selectedType") }) { data, reload ->
        val def = data.obj("def")
        val stats = data.obj("stats")
        val domain = data.obj("domain")
        val buildings = data.objects("buildings")
        val catalog = data.objects("catalog")

        val act: (String, Map<String, String>) -> Unit = { path, params ->
            scope.launch {
                val result = app.api.post(path, params)
                failed = result !is ApiResult.Ok
                note = outcome(result, "Done.")
                if (result is ApiResult.Ok) reload()
            }
        }

        ScreenColumn {
            Row(
                Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                horizontalArrangement = Arrangement.spacedBy(8.dp),
            ) {
                BUILDING_CATEGORIES.forEach { category ->
                    FilterChip(
                        selected = selectedType == category.key,
                        onClick = {
                            selectedType = category.key
                            note = null
                        },
                        label = { Text(category.label, fontWeight = if (selectedType == category.key) FontWeight.Bold else FontWeight.Medium) },
                        leadingIcon = { Icon(category.icon, contentDescription = null, Modifier.size(18.dp)) },
                        modifier = Modifier.heightIn(min = 48.dp),
                        shape = MaterialTheme.shapes.small,
                    )
                }
            }

            if (note != null) {
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    shape = MaterialTheme.shapes.medium,
                    colors = CardDefaults.cardColors(containerColor = (if (failed) LocalSkiColors.current.danger else LocalSkiColors.current.success).copy(alpha = 0.12f)),
                    border = BorderStroke(1.dp, (if (failed) LocalSkiColors.current.danger else LocalSkiColors.current.success).copy(alpha = 0.35f)),
                ) {
                    Text(
                        note!!,
                        color = if (failed) LocalSkiColors.current.danger else LocalSkiColors.current.success,
                        fontWeight = FontWeight.SemiBold,
                        style = MaterialTheme.typography.bodyMedium,
                        modifier = Modifier.padding(14.dp),
                    )
                }
            }

            BuildingTelemetry(selectedType, stats, domain)

            BuildingAlerts(selectedType, stats, domain)

            Panel("Construct ${def.str("singular")}") {
                Text(def.str("desc"), color = LocalSkiColors.current.muted)
                catalog.forEach { tier ->
                    Card(
                        modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp),
                        shape = MaterialTheme.shapes.medium,
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceContainerHigh),
                        border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant),
                    ) {
                        Column(
                            Modifier.fillMaxWidth().padding(14.dp),
                            verticalArrangement = Arrangement.spacedBy(6.dp),
                        ) {
                            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                                Text(tier.str("name"), fontWeight = FontWeight.Bold, style = MaterialTheme.typography.titleMedium)
                                StatusBadge("Level ${tier.int("level")}", MaterialTheme.colorScheme.primary, MaterialTheme.colorScheme.primary.copy(alpha = 0.12f))
                            }
                            Row2("Capacity", "${tier.int("capacity")} units")
                            Row2("Revenue", "${tier.str("revenueText")} / day")
                            Row2("Upkeep", "${tier.str("upkeepText")} / day")
                            PrimaryButton(
                                "Build for ${tier.str("costText")}",
                                { buildTier = tier },
                                Modifier.fillMaxWidth().padding(top = 4.dp),
                            )
                        }
                    }
                }
            }

            Panel("Your ${def.str("label")}") {
                if (buildings.isEmpty()) {
                    EmptyText("No ${def.str("label").lowercase()} built yet.")
                } else {
                    buildings.forEach { b ->
                        BuildingItemCard(
                            b = b,
                            onToggle = { act("buildings/${b.int("id")}/toggle", emptyMap()) },
                            onUpgrade = { upgradeBuilding = b },
                            onDemolish = { demolishBuilding = b },
                        )
                    }
                }
            }
        }

        buildTier?.let { tier ->
            AlertDialog(
                onDismissRequest = { buildTier = null },
                shape = MaterialTheme.shapes.extraLarge,
                title = { Text("Construct ${tier.str("name")}?", fontWeight = FontWeight.Bold) },
                text = { Text("Constructing costs ${tier.str("costText")}. Daily upkeep will be ${tier.str("upkeepText")} / day.") },
                confirmButton = {
                    TextButton({
                        val t = buildTier
                        buildTier = null
                        if (t != null) {
                            act("buildings/build", mapOf("type" to selectedType, "level" to t.int("level").toString()))
                        }
                    }, Modifier.heightIn(min = 48.dp)) { Text("Construct", fontWeight = FontWeight.Bold) }
                },
                dismissButton = { TextButton({ buildTier = null }, Modifier.heightIn(min = 48.dp)) { Text("Cancel") } },
            )
        }

        upgradeBuilding?.let { b ->
            val next = b.obj("nextLevel")
            AlertDialog(
                onDismissRequest = { upgradeBuilding = null },
                shape = MaterialTheme.shapes.extraLarge,
                title = { Text("Upgrade ${b.str("name")}?", fontWeight = FontWeight.Bold) },
                text = { Text("Upgrade to ${next.str("name")} for ${next.str("costText")}?") },
                confirmButton = {
                    TextButton({
                        val target = upgradeBuilding
                        upgradeBuilding = null
                        if (target != null) {
                            act("buildings/${target.int("id")}/upgrade", emptyMap())
                        }
                    }, Modifier.heightIn(min = 48.dp)) { Text("Upgrade", fontWeight = FontWeight.Bold) }
                },
                dismissButton = { TextButton({ upgradeBuilding = null }, Modifier.heightIn(min = 48.dp)) { Text("Cancel") } },
            )
        }

        demolishBuilding?.let { b ->
            AlertDialog(
                onDismissRequest = { demolishBuilding = null },
                shape = MaterialTheme.shapes.extraLarge,
                title = { Text("Demolish ${b.str("name")}?", fontWeight = FontWeight.Bold) },
                text = { Text("Demolishing gives ${b.str("refundText")} salvage refund (25%). This cannot be undone.") },
                confirmButton = {
                    TextButton({
                        val target = demolishBuilding
                        demolishBuilding = null
                        if (target != null) {
                            act("buildings/${target.int("id")}/sell", emptyMap())
                        }
                    }, Modifier.heightIn(min = 48.dp)) { Text("Demolish", color = LocalSkiColors.current.danger, fontWeight = FontWeight.Bold) }
                },
                dismissButton = { TextButton({ demolishBuilding = null }, Modifier.heightIn(min = 48.dp)) { Text("Keep") } },
            )
        }
    }
}

@Composable
private fun BuildingTelemetry(type: String, stats: JsonObject, domain: JsonObject) {
    when (type) {
        "hotel" -> {
            Figure("Hotel rating", "${domain.str("starRating")} / 5", "Resort hotel average")
            Figure("Tonight's occupancy", "${domain.int("occupancyRate")}%", "${domain.int("occupiedBeds")} of ${stats.int("totalCapacity")} beds")
            Figure("Daily revenue", stats.str("totalRevenueText"), "Net: ${stats.str("netProfitText")} / day")
            Figure("Front desk staff", "${domain.int("receptionists")} staff", if (domain.int("receptionists") >= stats.int("openCount")) "Fully staffed" else "Need more receptionists")
            Figure("Active properties", "${stats.int("openCount")} of ${stats.int("totalCount")} open")
        }
        "rental" -> {
            Figure("Rental shops", "${stats.int("openCount")} of ${stats.int("totalCount")} open")
            Figure("Gear capacity", "${stats.int("totalCapacity")} sets", "${domain.int("totalSetsInUse")} currently rented")
            Figure("Daily revenue", stats.str("totalRevenueText"), "Net: ${stats.str("netProfitText")} / day")
            Figure("Utilization", "${domain.int("utilization")}%", "Rental gear in use")
            Figure("Daily upkeep", stats.str("totalUpkeepText"), "Maintenance & inventory")
        }
        "restaurant" -> {
            Figure("Dining venues", "${stats.int("openCount")} of ${stats.int("totalCount")} open")
            Figure("Total seats", "${stats.int("totalCapacity")} seats", "Across all venues")
            Figure("Food quality", "${domain.int("foodQuality")}%", "Rating boost factor")
            Figure("Kitchen chefs", "${domain.int("chefCount")} chefs", "Avg level ${domain.str("avgChefLevel")}")
            Figure("Daily revenue", stats.str("totalRevenueText"), "Net: ${stats.str("netProfitText")} / day")
        }
        "retail" -> {
            Figure("Stores", "${stats.int("openCount")} of ${stats.int("totalCount")} open")
            Figure("Product lines", "${domain.int("totalProducts")} products", "Merchandise lines")
            Figure("Daily sales", stats.str("totalRevenueText"), "Net: ${stats.str("netProfitText")} / day")
            Figure("Profit margin", "${domain.int("avgMargin")}%", "Average margin")
            Figure("Upkeep cost", stats.str("totalUpkeepText"), "Daily operating cost")
        }
        "ski_patrol" -> {
            Figure("Safety score", "${domain.int("safetyScore")} / 100", "Risk level: ${domain.str("riskLevel")}")
            Figure("Slope coverage", "${domain.int("coverageRatio")}%", "${stats.int("totalCapacity")} of ${domain.int("slopeCount")} slopes")
            Figure("Patrol on duty", "${domain.int("patrolCount")} patrollers", "Active responders")
            Figure("Medics standby", "${domain.int("medicCount")} medics", "Clinic personnel")
            Figure("Daily upkeep", stats.str("totalUpkeepText"), "${stats.int("openCount")} active stations")
        }
        "transportation" -> {
            Figure("Active routes", "${stats.int("openCount")} of ${stats.int("totalCount")} active")
            Figure("Daily capacity", "${stats.int("totalCapacity")} riders", "System passenger cap")
            Figure("Fare revenue", stats.str("totalRevenueText"), "Net: ${stats.str("netProfitText")} / day")
            Figure("Network score", "${domain.int("networkScore")} / 100", "Connectivity rating")
            Figure("Operating cost", stats.str("totalUpkeepText"), "Fuel and maintenance")
        }
    }
}

@Composable
private fun BuildingAlerts(type: String, stats: JsonObject, domain: JsonObject) {
    if (type == "hotel" && domain.int("receptionists") < stats.int("openCount")) {
        Card(
            modifier = Modifier.fillMaxWidth(),
            shape = MaterialTheme.shapes.medium,
            colors = CardDefaults.cardColors(containerColor = LocalSkiColors.current.warning.copy(alpha = 0.12f)),
            border = BorderStroke(1.dp, LocalSkiColors.current.warning.copy(alpha = 0.35f)),
        ) {
            Column(Modifier.padding(14.dp), verticalArrangement = Arrangement.spacedBy(4.dp)) {
                Text("Understaffed reception desks", fontWeight = FontWeight.SemiBold, color = LocalSkiColors.current.warning)
                Text(
                    "Some hotels lack a receptionist. Hire receptionists from Staff to boost check-in speed and revenue.",
                    color = LocalSkiColors.current.muted,
                    style = MaterialTheme.typography.bodySmall,
                )
            }
        }
    }
    if (type == "ski_patrol" && domain.int("coverageRatio") < 100) {
        Card(
            modifier = Modifier.fillMaxWidth(),
            shape = MaterialTheme.shapes.medium,
            colors = CardDefaults.cardColors(containerColor = LocalSkiColors.current.warning.copy(alpha = 0.12f)),
            border = BorderStroke(1.dp, LocalSkiColors.current.warning.copy(alpha = 0.35f)),
        ) {
            Column(Modifier.padding(14.dp), verticalArrangement = Arrangement.spacedBy(4.dp)) {
                Text("Insufficient slope coverage", fontWeight = FontWeight.SemiBold, color = LocalSkiColors.current.warning)
                Text(
                    "${domain.int("uncoveredSlopes")} slope(s) have no patrol coverage. Uncovered slopes carry liability risks.",
                    color = LocalSkiColors.current.muted,
                    style = MaterialTheme.typography.bodySmall,
                )
            }
        }
    }
    if (type == "ski_patrol" && domain.int("patrolCount") == 0 && stats.int("totalCount") > 0) {
        Card(
            modifier = Modifier.fillMaxWidth(),
            shape = MaterialTheme.shapes.medium,
            colors = CardDefaults.cardColors(containerColor = LocalSkiColors.current.danger.copy(alpha = 0.12f)),
            border = BorderStroke(1.dp, LocalSkiColors.current.danger.copy(alpha = 0.35f)),
        ) {
            Column(Modifier.padding(14.dp), verticalArrangement = Arrangement.spacedBy(4.dp)) {
                Text("Patrol stations unmanned", fontWeight = FontWeight.SemiBold, color = LocalSkiColors.current.danger)
                Text(
                    "Stations are built without ski patrol staff. Hire ski patrol immediately to respond to emergencies.",
                    color = LocalSkiColors.current.muted,
                    style = MaterialTheme.typography.bodySmall,
                )
            }
        }
    }
}

@Composable
private fun BuildingItemCard(
    b: JsonObject,
    onToggle: () -> Unit,
    onUpgrade: () -> Unit,
    onDemolish: () -> Unit,
) {
    val isOpen = b.str("status") == "open"
    val conditionPct = b.int("condition_pct")
    val nextLevel = b.obj("nextLevel")
    val hasNextLevel = nextLevel.size() > 0

    Card(
        modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp),
        shape = MaterialTheme.shapes.medium,
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceContainerHigh),
        border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant),
    ) {
        Column(
            Modifier.fillMaxWidth().padding(14.dp),
            verticalArrangement = Arrangement.spacedBy(8.dp),
        ) {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                Column(Modifier.weight(1f).padding(end = 8.dp)) {
                    Text(b.str("name"), fontWeight = FontWeight.Bold, style = MaterialTheme.typography.titleMedium)
                    Text("Level ${b.int("level")}", color = LocalSkiColors.current.muted, style = MaterialTheme.typography.bodySmall)
                }
                StatusBadge(
                    text = if (isOpen) "OPEN" else "CLOSED",
                    color = if (isOpen) LocalSkiColors.current.success else LocalSkiColors.current.danger,
                    backgroundColor = (if (isOpen) LocalSkiColors.current.success else LocalSkiColors.current.danger).copy(alpha = 0.12f),
                )
            }

            Row2("Capacity", "${b.int("capacity")} units")
            Row2("Revenue", "${b.str("revenueText")} / day")
            Row2("Upkeep", "${b.str("upkeepText")} / day")
            Row2("Condition", "$conditionPct%")

            LinearProgressIndicator(
                progress = { (conditionPct / 100f).coerceIn(0f, 1f) },
                modifier = Modifier.fillMaxWidth().height(6.dp).clip(RoundedCornerShape(3.dp)),
                color = when {
                    conditionPct >= 60 -> LocalSkiColors.current.success
                    conditionPct >= 30 -> LocalSkiColors.current.warning
                    else -> LocalSkiColors.current.danger
                },
                trackColor = MaterialTheme.colorScheme.surfaceVariant,
            )

            Row(
                Modifier.fillMaxWidth().padding(top = 4.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                SecondaryButton(
                    if (isOpen) "Close" else "Open",
                    onToggle,
                    Modifier.weight(1f),
                )
                if (hasNextLevel) {
                    SecondaryButton(
                        "Upgrade (${nextLevel.str("costText")})",
                        onUpgrade,
                        Modifier.weight(1f),
                    )
                }
                OutlinedPrimaryButton(
                    "Demolish",
                    onDemolish,
                    Modifier.weight(1f),
                )
            }
        }
    }
}
