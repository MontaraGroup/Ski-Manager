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
import net.skimanager.app.ui.common.Panel
import net.skimanager.app.ui.common.PrimaryButton
import net.skimanager.app.ui.common.Row2
import net.skimanager.app.ui.common.ScreenColumn
import net.skimanager.app.ui.theme.LocalSkiColors

@Composable
fun GroomingScreen(app: AppState) {
    var reloadCount by remember { mutableStateOf(0) }
    var groomSingleSlope by remember { mutableStateOf<JsonObject?>(null) }
    var groomAllDialog by remember { mutableStateOf(false) }
    var actionError by remember { mutableStateOf<String?>(null) }
    var actionSuccess by remember { mutableStateOf<String?>(null) }
    val scope = rememberCoroutineScope()

    fun act(endpoint: String, body: Map<String, String>, reload: () -> Unit) {
        actionError = null
        actionSuccess = null
        scope.launch {
            when (val r = app.api.post(endpoint, body)) {
                is ApiResult.Ok -> {
                    actionSuccess = r.message ?: "Grooming run completed."
                    reload()
                }
                is ApiResult.Failed -> {
                    actionError = r.message
                }
                is ApiResult.Offline -> {
                    actionError = "Offline: cannot connect to grooming dispatch."
                }
            }
        }
    }

    Loaded(key = reloadCount, load = { app.api.get("grooming") }) { data, reload ->
        val overview = data.obj("overview")
        val batch = data.obj("batch")
        val slopes = data.objects("slopes")
        val machines = data.objects("machines")
        val operators = data.objects("operators")
        val costs = data.obj("costs")
        val runsLeft = overview.int("runsLeft")
        val capacity = overview.int("capacity")

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

            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                Column(Modifier.weight(1f)) {
                    Figure(
                        "Average condition",
                        "${overview.int("avgCondition")}%",
                        "+${overview.str("nightlyRestorePerSlope")}% night / -${overview.str("avgDecay")}% decay",
                    )
                }
                Column(Modifier.weight(1f)) {
                    val runsNote = if (capacity == 0) "No working fleet" else "$runsLeft of $capacity remaining"
                    Figure(
                        "Daytime runs",
                        "$runsLeft left",
                        runsNote,
                    )
                }
            }

            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                Column(Modifier.weight(1f)) {
                    val fleetNote = "+${overview.int("restore")}% condition per run"
                    Figure(
                        "Fleet & crew",
                        "${overview.int("activeMachines")} active",
                        fleetNote,
                    )
                }
                Column(Modifier.weight(1f)) {
                    Figure(
                        "Fleet costs",
                        costs.str("totalText"),
                        "Fuel ${costs.str("fuelText")} · Crew ${costs.str("crewText")}",
                    )
                }
            }

            if (batch.bool("available") && runsLeft > 0) {
                Panel("Daytime grooming run") {
                    Text(
                        "Send available groomers for an extra daytime run on the ${batch.int("count")} slopes with the lowest snow condition.",
                        color = LocalSkiColors.current.muted,
                    )
                    PrimaryButton(
                        "Groom ${batch.int("count")} slopes for ${batch.str("costText")}",
                        { groomAllDialog = true },
                        Modifier.fillMaxWidth(),
                    )
                }
            }

            Panel("Slopes (${slopes.size})") {
                if (slopes.isEmpty()) {
                    EmptyText("No slopes built yet. Create trails on the Trail Map.")
                } else {
                    slopes.forEach { slope ->
                        SlopeGroomCard(
                            slope = slope,
                            runsLeft = runsLeft,
                            restore = overview.int("restore"),
                            onGroom = { groomSingleSlope = slope },
                        )
                    }
                }
            }

            if (machines.isNotEmpty() || operators.isNotEmpty()) {
                Panel("Fleet & operators") {
                    if (machines.isNotEmpty()) {
                        Text("Machines", fontWeight = FontWeight.SemiBold, style = MaterialTheme.typography.titleSmall)
                        machines.forEach { m ->
                            Row2(
                                m.str("name"),
                                "${m.str("stateLabel")} · ${m.int("condition_pct")}% condition",
                            )
                        }
                    }
                    if (operators.isNotEmpty()) {
                        Text("Operators", fontWeight = FontWeight.SemiBold, style = MaterialTheme.typography.titleSmall, modifier = Modifier.padding(top = 8.dp))
                        operators.forEach { op ->
                            Row2(op.str("name"), "Skill ${op.int("skill")} · ${op.str("salaryText")} / day")
                        }
                    }
                }
            }
        }

        if (groomAllDialog) {
            AlertDialog(
                onDismissRequest = { groomAllDialog = false },
                title = { Text("Groom ${batch.int("count")} slopes?") },
                text = {
                    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        Text("Send out groomers on daytime extra runs for ${batch.str("costText")}.")
                        Text("Each groomed slope gains +${overview.int("restore")}% condition immediately.", color = LocalSkiColors.current.muted)
                    }
                },
                confirmButton = {
                    TextButton({
                        groomAllDialog = false
                        act("grooming/all", emptyMap()) { reload(); reloadCount++ }
                    }, Modifier.heightIn(min = 48.dp)) { Text("Groom all (${batch.str("costText")})") }
                },
                dismissButton = {
                    TextButton({ groomAllDialog = false }, Modifier.heightIn(min = 48.dp)) { Text("Cancel") }
                },
            )
        }

        groomSingleSlope?.let { s ->
            AlertDialog(
                onDismissRequest = { groomSingleSlope = null },
                title = { Text("Groom ${s.str("name")}?") },
                text = {
                    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        Text("Grooming costs ${s.str("extraCostText")} for daytime operation.")
                        Text("Adds +${overview.int("restore")}% snow condition immediately.", color = MaterialTheme.colorScheme.primary, fontWeight = FontWeight.Medium)
                    }
                },
                confirmButton = {
                    TextButton({
                        val target = groomSingleSlope
                        groomSingleSlope = null
                        if (target != null) {
                            act("grooming/single", mapOf("slope_id" to target.int("id").toString())) { reload(); reloadCount++ }
                        }
                    }, Modifier.heightIn(min = 48.dp)) { Text("Groom now (${s.str("extraCostText")})") }
                },
                dismissButton = {
                    TextButton({ groomSingleSlope = null }, Modifier.heightIn(min = 48.dp)) { Text("Cancel") }
                },
            )
        }
    }
}

@Composable
private fun SlopeGroomCard(
    slope: JsonObject,
    runsLeft: Int,
    restore: Int,
    onGroom: () -> Unit,
) {
    Card(
        Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = LocalSkiColors.current.surfaceSecondary),
    ) {
        Column(Modifier.padding(14.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                Text(slope.str("name"), fontWeight = FontWeight.SemiBold, style = MaterialTheme.typography.titleSmall)
                val statusText = when {
                    slope.bool("groomed_today") -> "Groomed today"
                    slope.bool("isTonight") -> "Tonight's plan"
                    else -> slope.str("sectorName")
                }
                Text(
                    statusText,
                    color = if (slope.bool("groomed_today")) MaterialTheme.colorScheme.primary else LocalSkiColors.current.muted,
                    style = MaterialTheme.typography.bodySmall,
                    fontWeight = FontWeight.Medium,
                )
            }

            val condition = slope.int("condition_pct").coerceIn(0, 100)
            LinearProgressIndicator(
                progress = { condition / 100f },
                modifier = Modifier.fillMaxWidth().height(6.dp).clip(RoundedCornerShape(3.dp)),
                color = when {
                    condition >= 70 -> MaterialTheme.colorScheme.primary
                    condition >= 40 -> MaterialTheme.colorScheme.tertiary
                    else -> MaterialTheme.colorScheme.error
                },
            )

            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Text("$condition% condition", color = LocalSkiColors.current.muted, style = MaterialTheme.typography.bodySmall)
                Text("-${slope.int("decay")}% / day", color = LocalSkiColors.current.muted, style = MaterialTheme.typography.bodySmall)
            }

            Row2("Length", slope.str("lengthText"))
            Row2("Visitor draw", "${slope.int("draw_pct")}%")

            val canGroom = slope.bool("isGroomable") && runsLeft > 0
            if (canGroom) {
                PrimaryButton(
                    "Groom now (${slope.str("extraCostText")})",
                    onGroom,
                    Modifier.fillMaxWidth(),
                )
            } else if (slope.bool("groomed_today")) {
                Text(
                    "Groomed today: +$restore% condition restored",
                    color = MaterialTheme.colorScheme.primary,
                    style = MaterialTheme.typography.bodySmall,
                    modifier = Modifier.padding(top = 4.dp),
                )
            }
        }
    }
}
