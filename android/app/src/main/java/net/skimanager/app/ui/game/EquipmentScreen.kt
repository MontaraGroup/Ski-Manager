package net.skimanager.app.ui.game

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
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
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextDecoration
import androidx.compose.ui.unit.dp
import com.google.gson.JsonObject
import kotlinx.coroutines.launch
import net.skimanager.app.ui.AppState
import net.skimanager.app.api.ApiResult
import net.skimanager.app.api.bool
import net.skimanager.app.api.int
import net.skimanager.app.api.obj
import net.skimanager.app.api.objects
import net.skimanager.app.api.str
import net.skimanager.app.ui.common.ChoiceRow
import net.skimanager.app.ui.common.EmptyText
import net.skimanager.app.ui.common.Figure
import net.skimanager.app.ui.common.Loaded
import net.skimanager.app.ui.common.Panel
import net.skimanager.app.ui.common.PrimaryButton
import net.skimanager.app.ui.common.Row2
import net.skimanager.app.ui.common.ScreenColumn
import net.skimanager.app.ui.theme.LocalSkiColors

@Composable
fun EquipmentScreen(app: AppState) {
    var reloadCount by remember { mutableStateOf(0) }
    var selectedSection by remember { mutableStateOf("fleet") }
    var shopTab by remember { mutableStateOf("groomer") }
    var buyItem by remember { mutableStateOf<JsonObject?>(null) }
    var repairItem by remember { mutableStateOf<JsonObject?>(null) }
    var sellItem by remember { mutableStateOf<JsonObject?>(null) }
    var actionError by remember { mutableStateOf<String?>(null) }
    var actionSuccess by remember { mutableStateOf<String?>(null) }
    val scope = rememberCoroutineScope()

    fun act(endpoint: String, body: Map<String, String>, reload: () -> Unit) {
        actionError = null
        actionSuccess = null
        scope.launch {
            when (val r = app.api.post(endpoint, body)) {
                is ApiResult.Ok -> {
                    actionSuccess = r.message ?: "Equipment updated."
                    reload()
                }
                is ApiResult.Failed -> {
                    actionError = r.message
                }
                is ApiResult.Offline -> {
                    actionError = "Offline: cannot connect to equipment shop."
                }
            }
        }
    }

    Loaded(key = reloadCount, load = { app.api.get("equipment") }) { data, reload ->
        val cash = data.int("cash")
        val cashText = data.str("cashText")
        val cashNegative = data.bool("cashNegative")
        val discount = data.int("discount")
        val stats = data.obj("stats")
        val equipment = data.objects("equipment")
        val catalogObj = data.obj("catalog")
        val groomersCatalog = catalogObj.objects("groomer")
        val snowmakersCatalog = catalogObj.objects("snowmaker")

        val groomersOwned = equipment.filter { it.str("equipment_type") == "groomer" }
        val snowmakersOwned = equipment.filter { it.str("equipment_type") == "snowmaker" }

        ScreenColumn {
            actionError?.let { err ->
                Card(
                    Modifier.fillMaxWidth(),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.errorContainer),
                ) {
                    Text(err, color = MaterialTheme.colorScheme.onErrorContainer, modifier = Modifier.padding(12.dp))
                }
            }

            actionSuccess?.let { msg ->
                Card(
                    Modifier.fillMaxWidth(),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.primaryContainer),
                ) {
                    Text(msg, color = MaterialTheme.colorScheme.onPrimaryContainer, modifier = Modifier.padding(12.dp))
                }
            }

            // Cash and telemetry overview
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                Text("Cash available", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                Text(
                    cashText,
                    color = if (cashNegative) MaterialTheme.colorScheme.error else MaterialTheme.colorScheme.primary,
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold,
                )
            }

            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                Column(Modifier.weight(1f)) {
                    val slopes = stats.int("slopes")
                    val groomCap = stats.int("groomCapacity")
                    val operators = stats.int("operators")
                    val groomers = stats.int("groomers")
                    Figure(
                        "Grooming tonight",
                        "$groomCap of $slopes slopes",
                        "$operators operators · $groomers groomers",
                    )
                }
                Column(Modifier.weight(1f)) {
                    val gunCap = stats.int("gunCapacity")
                    val runningGuns = stats.int("runningGuns")
                    val guns = stats.int("guns")
                    Figure(
                        "Snow guns today",
                        "$gunCap slopes covered",
                        "$runningGuns of $guns running",
                    )
                }
            }

            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                Column(Modifier.weight(1f)) {
                    Figure(
                        "Daily running cost",
                        "${stats.str("dailyCostText")} / day",
                        "Fuel for active fleet",
                    )
                }
                Column(Modifier.weight(1f)) {
                    val eqCount = stats.int("equipmentCount")
                    Figure(
                        "Fleet resale value",
                        stats.str("fleetValueText"),
                        "$eqCount machines owned",
                    )
                }
            }

            // Idle groomers or missing fleet notices
            val idleGroomers = stats.int("idleGroomers")
            if (idleGroomers > 0) {
                Card(
                    Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(12.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.tertiaryContainer),
                ) {
                    Text(
                        "$idleGroomers groomer(s) switched on without an operator. Hire operators in Staff to run them.",
                        color = MaterialTheme.colorScheme.onTertiaryContainer,
                        modifier = Modifier.padding(12.dp),
                        style = MaterialTheme.typography.bodySmall,
                    )
                }
            }

            // Section selection: Fleet vs Shop
            ChoiceRow(
                options = listOf(
                    "fleet" to "Fleet (${equipment.size})",
                    "shop" to "Shop (${groomersCatalog.size + snowmakersCatalog.size})",
                ),
                selected = selectedSection,
                onSelect = { selectedSection = it },
            )

            if (selectedSection == "fleet") {
                if (equipment.isEmpty()) {
                    EmptyText("No equipment in your fleet yet. Switch to the Shop tab to buy groomers or snow guns.")
                } else {
                    if (groomersOwned.isNotEmpty()) {
                        Text("Groomers", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                        groomersOwned.forEach { eq ->
                            EquipmentCard(
                                eq = eq,
                                cash = cash,
                                onToggle = {
                                    act("equipment/${eq.int("id")}/toggle", emptyMap()) { reload(); reloadCount++ }
                                },
                                onRepair = { repairItem = eq },
                                onSell = { sellItem = eq },
                            )
                        }
                    }

                    if (snowmakersOwned.isNotEmpty()) {
                        Text("Snow guns", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                        snowmakersOwned.forEach { eq ->
                            EquipmentCard(
                                eq = eq,
                                cash = cash,
                                onToggle = {
                                    act("equipment/${eq.int("id")}/toggle", emptyMap()) { reload(); reloadCount++ }
                                },
                                onRepair = { repairItem = eq },
                                onSell = { sellItem = eq },
                            )
                        }
                    }
                }
            } else {
                // Shop tab
                if (discount > 0) {
                    Card(
                        Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(12.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.primaryContainer),
                    ) {
                        Text(
                            "Alliance Bulk Procurement discount: -$discount% on all machinery",
                            color = MaterialTheme.colorScheme.onPrimaryContainer,
                            modifier = Modifier.padding(12.dp),
                            style = MaterialTheme.typography.bodySmall,
                            fontWeight = FontWeight.Medium,
                        )
                    }
                }

                ChoiceRow(
                    options = listOf(
                        "groomer" to "Groomers (${groomersCatalog.size})",
                        "snowmaker" to "Snow guns (${snowmakersCatalog.size})",
                    ),
                    selected = shopTab,
                    onSelect = { shopTab = it },
                )

                val currentCatalog = if (shopTab == "groomer") groomersCatalog else snowmakersCatalog
                currentCatalog.forEach { c ->
                    CatalogItemCard(
                        item = c,
                        cash = cash,
                        discount = discount,
                        onBuy = { buyItem = c },
                    )
                }
            }
        }

        // Buy Confirmation Dialog
        buyItem?.let { c ->
            val price = c.int("price")
            val canAfford = cash >= price
            AlertDialog(
                onDismissRequest = { buyItem = null },
                title = { Text("Buy ${c.str("name")}?") },
                text = {
                    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        Text(c.str("description"), style = MaterialTheme.typography.bodyMedium)
                        Row2("Brand", c.str("brand"))
                        Row2("Capacity", "${c.int("capacity")} slopes")
                        Row2("Running cost", "${c.str("runningCostText")} / day")
                        Row2("Price", c.str("priceText"), valueColor = MaterialTheme.colorScheme.primary)
                        if (!canAfford) {
                            Text(
                                "Insufficient cash: costs ${c.str("priceText")}, available $cashText.",
                                color = MaterialTheme.colorScheme.error,
                                style = MaterialTheme.typography.bodySmall,
                            )
                        }
                    }
                },
                confirmButton = {
                    TextButton(
                        onClick = {
                            val target = buyItem
                            buyItem = null
                            if (target != null) {
                                act("equipment/buy", mapOf("model" to target.str("model_key"))) { reload(); reloadCount++ }
                            }
                        },
                        modifier = Modifier.heightIn(min = 48.dp),
                        enabled = canAfford,
                    ) { Text("Buy (${c.str("priceText")})") }
                },
                dismissButton = {
                    TextButton({ buyItem = null }, Modifier.heightIn(min = 48.dp)) { Text("Cancel") }
                },
            )
        }

        // Repair Confirmation Dialog
        repairItem?.let { eq ->
            AlertDialog(
                onDismissRequest = { repairItem = null },
                title = { Text("Repair ${eq.str("name")}?") },
                text = {
                    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        Text("Restore ${eq.str("name")} to 100% condition.")
                        Row2("Current condition", "${eq.int("condition_pct")}%")
                        Row2("Repair cost", eq.str("repairCostText"), valueColor = MaterialTheme.colorScheme.primary)
                    }
                },
                confirmButton = {
                    TextButton(
                        onClick = {
                            val target = repairItem
                            repairItem = null
                            if (target != null) {
                                act("equipment/${target.int("id")}/repair", emptyMap()) { reload(); reloadCount++ }
                            }
                        },
                        modifier = Modifier.heightIn(min = 48.dp),
                        enabled = cash >= eq.int("repairCost"),
                    ) { Text("Repair (${eq.str("repairCostText")})") }
                },
                dismissButton = {
                    TextButton({ repairItem = null }, Modifier.heightIn(min = 48.dp)) { Text("Cancel") }
                },
            )
        }

        // Sell Confirmation Dialog
        sellItem?.let { eq ->
            AlertDialog(
                onDismissRequest = { sellItem = null },
                title = { Text("Sell ${eq.str("name")}?") },
                text = {
                    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        Text("Decommission and sell ${eq.str("name")} from your fleet.")
                        Row2("Resale refund", eq.str("resaleText"), valueColor = MaterialTheme.colorScheme.primary)
                    }
                },
                confirmButton = {
                    TextButton(
                        onClick = {
                            val target = sellItem
                            sellItem = null
                            if (target != null) {
                                act("equipment/${target.int("id")}/sell", emptyMap()) { reload(); reloadCount++ }
                            }
                        },
                        modifier = Modifier.heightIn(min = 48.dp),
                    ) { Text("Sell (${eq.str("resaleText")})") }
                },
                dismissButton = {
                    TextButton({ sellItem = null }, Modifier.heightIn(min = 48.dp)) { Text("Cancel") }
                },
            )
        }
    }
}

@Composable
private fun EquipmentCard(
    eq: JsonObject,
    cash: Int,
    onToggle: () -> Unit,
    onRepair: () -> Unit,
    onSell: () -> Unit,
) {
    Card(
        Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = LocalSkiColors.current.surfaceSecondary),
    ) {
        Column(Modifier.padding(14.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                Column(Modifier.weight(1f).padding(end = 8.dp)) {
                    Text(eq.str("name"), fontWeight = FontWeight.SemiBold, style = MaterialTheme.typography.titleSmall)
                    val capType = if (eq.str("equipment_type") == "groomer") "grooms" else "covers"
                    Text(
                        "${eq.str("brand")} · $capType ${eq.int("capacity")} slopes",
                        color = LocalSkiColors.current.muted,
                        style = MaterialTheme.typography.bodySmall,
                    )
                }
                val stateTone = eq.str("stateTone")
                val toneColor = when (stateTone) {
                    "success" -> MaterialTheme.colorScheme.primary
                    "warning" -> MaterialTheme.colorScheme.tertiary
                    "danger" -> MaterialTheme.colorScheme.error
                    else -> LocalSkiColors.current.muted
                }
                Text(
                    eq.str("stateLabel"),
                    color = toneColor,
                    style = MaterialTheme.typography.bodySmall,
                    fontWeight = FontWeight.Medium,
                )
            }

            val condition = eq.int("condition_pct").coerceIn(0, 100)
            LinearProgressIndicator(
                progress = { condition / 100f },
                modifier = Modifier.fillMaxWidth().height(6.dp).clip(RoundedCornerShape(3.dp)),
                color = when {
                    condition >= 60 -> MaterialTheme.colorScheme.primary
                    condition >= 30 -> MaterialTheme.colorScheme.tertiary
                    else -> MaterialTheme.colorScheme.error
                },
            )

            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Text("Condition: $condition%", color = LocalSkiColors.current.muted, style = MaterialTheme.typography.bodySmall)
                val crew = eq.str("crew")
                val crewLabel = if (crew.isNotEmpty()) crew else if (eq.str("equipment_type") == "groomer") "Any available operator" else "Automated"
                Text(crewLabel, color = LocalSkiColors.current.muted, style = MaterialTheme.typography.bodySmall)
            }

            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Text("Running cost", color = LocalSkiColors.current.muted, style = MaterialTheme.typography.bodySmall)
                Text("${eq.str("fuelCostText")} / day", style = MaterialTheme.typography.bodySmall, fontWeight = FontWeight.Medium)
            }

            val isBroken = eq.str("state") == "broken"
            val isActive = eq.str("status") == "active"
            val repairCost = eq.int("repairCost")

            Row(
                Modifier.fillMaxWidth().padding(top = 4.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                if (!isBroken) {
                    TextButton(onToggle, Modifier.weight(1f).heightIn(min = 48.dp)) {
                        Text(if (isActive) "Switch off" else "Switch on")
                    }
                }

                if (condition < 100) {
                    TextButton(
                        onRepair,
                        Modifier.weight(1f).heightIn(min = 48.dp),
                        enabled = cash >= repairCost,
                    ) {
                        Text("Repair (${eq.str("repairCostText")})")
                    }
                }

                TextButton(onSell, Modifier.weight(1f).heightIn(min = 48.dp)) {
                    Text("Sell (${eq.str("resaleText")})", color = MaterialTheme.colorScheme.error)
                }
            }
        }
    }
}

@Composable
private fun CatalogItemCard(
    item: JsonObject,
    cash: Int,
    discount: Int,
    onBuy: () -> Unit,
) {
    Card(
        Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = LocalSkiColors.current.surfaceSecondary),
    ) {
        Column(Modifier.padding(14.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                Column(Modifier.weight(1f).padding(end = 8.dp)) {
                    Text(item.str("name"), fontWeight = FontWeight.SemiBold, style = MaterialTheme.typography.titleSmall)
                    Text(item.str("brand"), color = LocalSkiColors.current.muted, style = MaterialTheme.typography.bodySmall)
                }
                val owned = item.int("owned")
                if (owned > 0) {
                    Text(
                        "Owned: $owned",
                        color = MaterialTheme.colorScheme.primary,
                        style = MaterialTheme.typography.bodySmall,
                        fontWeight = FontWeight.Medium,
                    )
                }
            }

            Text(item.str("description"), color = LocalSkiColors.current.muted, style = MaterialTheme.typography.bodySmall)

            val isGroomer = item.str("equipment_type") == "groomer"
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Text(if (isGroomer) "Grooms per night" else "Slope coverage", color = LocalSkiColors.current.muted, style = MaterialTheme.typography.bodySmall)
                Text("${item.int("capacity")} slopes", style = MaterialTheme.typography.bodySmall, fontWeight = FontWeight.Medium)
            }

            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Text("Running cost", color = LocalSkiColors.current.muted, style = MaterialTheme.typography.bodySmall)
                Text("${item.str("runningCostText")} / day", style = MaterialTheme.typography.bodySmall, fontWeight = FontWeight.Medium)
            }

            if (isGroomer) {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                    Text("Crew requirement", color = LocalSkiColors.current.muted, style = MaterialTheme.typography.bodySmall)
                    Text("1 operator", style = MaterialTheme.typography.bodySmall, fontWeight = FontWeight.Medium)
                }
            } else {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                    Text("Operating range", color = LocalSkiColors.current.muted, style = MaterialTheme.typography.bodySmall)
                    Text(item.str("maxTempText"), style = MaterialTheme.typography.bodySmall, fontWeight = FontWeight.Medium)
                }
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                    Text("Daily utilities", color = LocalSkiColors.current.muted, style = MaterialTheme.typography.bodySmall)
                    Text("${item.str("energyKwhText")} · ${item.str("waterLitersText")}", style = MaterialTheme.typography.bodySmall, fontWeight = FontWeight.Medium)
                }
            }

            val price = item.int("price")
            val canAfford = cash >= price

            Row(
                Modifier.fillMaxWidth().padding(top = 6.dp),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically,
            ) {
                Column {
                    if (discount > 0) {
                        Text(
                            item.str("costText"),
                            style = MaterialTheme.typography.bodySmall.copy(textDecoration = TextDecoration.LineThrough),
                            color = LocalSkiColors.current.muted,
                        )
                    }
                    Text(
                        item.str("priceText"),
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold,
                        color = MaterialTheme.colorScheme.primary,
                    )
                }

                PrimaryButton(
                    text = if (canAfford) "Buy" else "Short cash",
                    onClick = onBuy,
                    enabled = canAfford,
                )
            }
        }
    }
}
