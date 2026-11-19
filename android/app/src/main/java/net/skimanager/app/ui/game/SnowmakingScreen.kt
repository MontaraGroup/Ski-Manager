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
import androidx.compose.foundation.rememberScrollState
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

@Composable
fun SnowmakingScreen(app: AppState) {
    var reloadCount by remember { mutableStateOf(0) }
    var repairGun by remember { mutableStateOf<JsonObject?>(null) }
    var sellGun by remember { mutableStateOf<JsonObject?>(null) }
    var actionError by remember { mutableStateOf<String?>(null) }
    var actionSuccess by remember { mutableStateOf<String?>(null) }
    val scope = rememberCoroutineScope()

    fun act(endpoint: String, body: Map<String, String>, reload: () -> Unit) {
        actionError = null
        actionSuccess = null
        scope.launch {
            when (val r = app.api.post(endpoint, body)) {
                is ApiResult.Ok -> {
                    actionSuccess = r.message ?: "Snowmaking updated."
                    reload()
                }
                is ApiResult.Failed -> {
                    actionError = r.message
                }
                is ApiResult.Offline -> {
                    actionError = "Offline: cannot connect to snowmaking systems."
                }
            }
        }
    }

    Loaded(key = reloadCount, load = { app.api.get("snowmaking") }) { data, reload ->
        val overview = data.obj("overview")
        val guns = data.objects("guns")
        val outlook = data.objects("outlook")

        ScreenColumn {
            actionError?.let { err ->
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    shape = MaterialTheme.shapes.medium,
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.error.copy(alpha = 0.12f)),
                    border = BorderStroke(1.dp, MaterialTheme.colorScheme.error.copy(alpha = 0.35f)),
                ) {
                    Text(err, color = MaterialTheme.colorScheme.error, fontWeight = FontWeight.SemiBold, modifier = Modifier.padding(14.dp))
                }
            }

            actionSuccess?.let { msg ->
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    shape = MaterialTheme.shapes.medium,
                    colors = CardDefaults.cardColors(containerColor = LocalSkiColors.current.success.copy(alpha = 0.12f)),
                    border = BorderStroke(1.dp, LocalSkiColors.current.success.copy(alpha = 0.35f)),
                ) {
                    Text(msg, color = LocalSkiColors.current.success, fontWeight = FontWeight.SemiBold, modifier = Modifier.padding(14.dp))
                }
            }

            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                Column(Modifier.weight(1f)) {
                    val coverageNote = if (overview.bool("thinBase")) "Thin snow base alert" else "Base: ${overview.str("snowBaseText")}"
                    Figure(
                        "Slope coverage",
                        "${overview.int("coveredCount")} of ${overview.int("openCount")}",
                        coverageNote,
                    )
                }
                Column(Modifier.weight(1f)) {
                    Figure(
                        "Snow guns",
                        "${overview.int("runningGunsCount")} of ${overview.int("totalGunsCount")}",
                        "Temp: ${overview.str("baseTempText")}",
                    )
                }
            }

            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                Column(Modifier.weight(1f)) {
                    Figure(
                        "Daily cost",
                        overview.str("totalOperatingCostText"),
                        "Equipment + energy + water",
                    )
                }
                Column(Modifier.weight(1f)) {
                    Figure(
                        "Gun run time",
                        "${overview.int("standardHours")} h / day",
                        "Standard gun window",
                    )
                }
            }

            if (guns.isNotEmpty()) {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                    PrimaryButton(
                        "Turn all on",
                        { act("snowmaking/toggle-all", mapOf("action" to "on")) { reload(); reloadCount++ } },
                        Modifier.weight(1f),
                    )
                    SecondaryButton(
                        "Turn all off",
                        { act("snowmaking/toggle-all", mapOf("action" to "off")) { reload(); reloadCount++ } },
                        Modifier.weight(1f),
                    )
                }
            }

            Panel("Snow guns (${guns.size})") {
                if (guns.isEmpty()) {
                    EmptyText("No snow guns installed. Acquire snow cannons under Equipment.")
                } else {
                    guns.forEach { gun ->
                        SnowGunCard(
                            gun = gun,
                            onToggle = {
                                act("snowmaking/${gun.int("id")}/toggle", emptyMap()) { reload(); reloadCount++ }
                            },
                            onRepair = { repairGun = gun },
                            onSell = { sellGun = gun },
                        )
                    }
                }
            }

            if (outlook.isNotEmpty()) {
                Panel("5-Day snowmaking forecast") {
                    Row(
                        Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                        horizontalArrangement = Arrangement.spacedBy(12.dp),
                    ) {
                        outlook.forEach { day ->
                            Card(
                                Modifier.padding(vertical = 4.dp),
                                shape = MaterialTheme.shapes.medium,
                                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceContainerHigh),
                                border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant),
                            ) {
                                Column(Modifier.padding(14.dp), verticalArrangement = Arrangement.spacedBy(4.dp)) {
                                    Text("Day ${day.int("day")}", fontWeight = FontWeight.SemiBold, style = MaterialTheme.typography.bodySmall)
                                    Text(day.str("tempText"), style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                                    day.str("snowfallText").takeIf { it.isNotEmpty() }?.let {
                                        Text(it, color = MaterialTheme.colorScheme.primary, style = MaterialTheme.typography.bodySmall, fontWeight = FontWeight.Medium)
                                    }
                                    val canRun = day.bool("canRun")
                                    StatusBadge(
                                        day.str("hoursText"),
                                        if (canRun) LocalSkiColors.current.success else LocalSkiColors.current.muted,
                                        (if (canRun) LocalSkiColors.current.success else LocalSkiColors.current.muted).copy(alpha = 0.12f),
                                    )
                                }
                            }
                        }
                    }
                }
            }
        }

        repairGun?.let { g ->
            AlertDialog(
                onDismissRequest = { repairGun = null },
                shape = MaterialTheme.shapes.extraLarge,
                title = { Text("Repair ${g.str("name")}?", fontWeight = FontWeight.Bold) },
                text = {
                    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        Text("Restore ${g.str("name")} to 100% condition for ${g.str("repairCostText")}.")
                    }
                },
                confirmButton = {
                    TextButton({
                        val target = repairGun
                        repairGun = null
                        if (target != null) {
                            act("snowmaking/${target.int("id")}/repair", emptyMap()) { reload(); reloadCount++ }
                        }
                    }, Modifier.heightIn(min = 48.dp)) { Text("Repair (${g.str("repairCostText")})", fontWeight = FontWeight.Bold) }
                },
                dismissButton = {
                    TextButton({ repairGun = null }, Modifier.heightIn(min = 48.dp)) { Text("Cancel") }
                },
            )
        }

        sellGun?.let { g ->
            AlertDialog(
                onDismissRequest = { sellGun = null },
                shape = MaterialTheme.shapes.extraLarge,
                title = { Text("Sell ${g.str("name")}?", fontWeight = FontWeight.Bold) },
                text = {
                    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        Text("Decommission and sell ${g.str("name")} for ${g.str("resaleText")} refund?")
                    }
                },
                confirmButton = {
                    TextButton({
                        val target = sellGun
                        sellGun = null
                        if (target != null) {
                            act("snowmaking/${target.int("id")}/sell", emptyMap()) { reload(); reloadCount++ }
                        }
                    }, Modifier.heightIn(min = 48.dp)) { Text("Sell (${g.str("resaleText")})", color = LocalSkiColors.current.danger, fontWeight = FontWeight.Bold) }
                },
                dismissButton = {
                    TextButton({ sellGun = null }, Modifier.heightIn(min = 48.dp)) { Text("Cancel") }
                },
            )
        }
    }
}

@Composable
private fun SnowGunCard(
    gun: JsonObject,
    onToggle: () -> Unit,
    onRepair: () -> Unit,
    onSell: () -> Unit,
) {
    Card(
        Modifier.fillMaxWidth().padding(vertical = 4.dp),
        shape = MaterialTheme.shapes.medium,
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceContainerHigh),
        border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant),
    ) {
        Column(Modifier.padding(14.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                Text(gun.str("name"), fontWeight = FontWeight.Bold, style = MaterialTheme.typography.titleMedium)
                val stateTone = gun.str("stateTone")
                val toneColor = when (stateTone) {
                    "success" -> LocalSkiColors.current.success
                    "warning" -> LocalSkiColors.current.warning
                    "danger" -> LocalSkiColors.current.danger
                    else -> LocalSkiColors.current.muted
                }
                StatusBadge(
                    gun.str("stateLabel"),
                    toneColor,
                    toneColor.copy(alpha = 0.12f),
                )
            }

            val condition = gun.int("condition_pct").coerceIn(0, 100)
            LinearProgressIndicator(
                progress = { condition / 100f },
                modifier = Modifier.fillMaxWidth().height(6.dp).clip(RoundedCornerShape(3.dp)),
                color = when {
                    condition >= 70 -> LocalSkiColors.current.success
                    condition >= 40 -> LocalSkiColors.current.warning
                    else -> LocalSkiColors.current.danger
                },
                trackColor = MaterialTheme.colorScheme.surfaceVariant,
            )

            Row2("Condition", "$condition%")
            Row2("Temp threshold", gun.str("tempLimitText"))
            Row2("Daily operating cost", gun.str("operatingCostText"))
            Row2("Run window today", "${gun.int("hours")} hours")

            gun.str("crew").takeIf { it.isNotEmpty() }?.let {
                Row2("Assigned operator", it)
            }

            Row(
                Modifier.fillMaxWidth().padding(top = 4.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                val isRunning = gun.str("status") == "active"
                SecondaryButton(
                    if (isRunning) "Turn off" else "Turn on",
                    onToggle,
                    Modifier.weight(1f),
                    enabled = condition > 0,
                )
                if (condition < 100) {
                    SecondaryButton(
                        "Repair (${gun.str("repairCostText")})",
                        onRepair,
                        Modifier.weight(1f),
                    )
                }
                OutlinedPrimaryButton(
                    "Sell (${gun.str("resaleText")})",
                    onSell,
                    Modifier.weight(1f),
                )
            }
        }
    }
}
