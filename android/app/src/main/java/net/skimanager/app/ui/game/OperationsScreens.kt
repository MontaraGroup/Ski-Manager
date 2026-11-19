package net.skimanager.app.ui.game

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.LinearProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Switch
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
import androidx.compose.ui.text.input.KeyboardType
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
private fun Note(text: String?, failed: Boolean) {
    if (text != null) Text(text, color = if (failed) LocalSkiColors.current.danger else LocalSkiColors.current.success, fontWeight = FontWeight.Medium)
}

@Composable
fun TicketsScreen(app: AppState) {
    var note by remember { mutableStateOf<String?>(null) }
    var failed by remember { mutableStateOf(false) }
    Loaded("tickets", { app.api.get("tickets") }) { data, reload ->
        val stats = data.obj("stats")
        ScreenColumn {
            Figure("Ticket income today", stats.str("todayRevenueText"), "${stats.str("todayCountText")} sold")
            Note(note, failed)
            data.objects("tickets").forEach { ticket ->
                TicketCard(app, ticket, data.str("currencySymbol")) { result ->
                    failed = result !is ApiResult.Ok
                    note = outcome(result, "Saved.")
                    if (result is ApiResult.Ok) reload()
                }
            }
        }
    }
}

@Composable
private fun TicketCard(app: AppState, ticket: JsonObject, symbol: String, onDone: (ApiResult) -> Unit) {
    val scope = rememberCoroutineScope()
    var price by remember(ticket) { mutableStateOf(ticket.int("price").toString()) }
    var active by remember(ticket) { mutableStateOf(ticket.bool("active")) }
    var busy by remember { mutableStateOf(false) }
    Panel(ticket.str("name")) {
        Text(ticket.str("desc"), color = LocalSkiColors.current.muted)
        Row(
            Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Text("Suggested ${ticket.str("refPriceText")}", color = LocalSkiColors.current.muted, style = MaterialTheme.typography.bodySmall)
            StatusBadge("${ticket.int("mixPct")}% of skiers", MaterialTheme.colorScheme.primary, MaterialTheme.colorScheme.primary.copy(alpha = 0.12f))
        }
        Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(12.dp)) {
            OutlinedTextField(
                price, { price = it.filter(Char::isDigit).take(5) }, Modifier.weight(1f),
                shape = MaterialTheme.shapes.medium,
                label = { Text("Price ($symbol)") }, singleLine = true,
                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
            )
            Text("On sale", fontWeight = FontWeight.Medium)
            Switch(active, { active = it })
        }
        PrimaryButton("Save", {
            busy = true
            scope.launch {
                val form = mutableMapOf("price" to price)
                if (active) form["active"] = "1"
                val result = app.api.post("tickets/${ticket.int("id")}", form)
                busy = false
                onDone(result)
            }
        }, Modifier.fillMaxWidth(), enabled = !busy && price.isNotEmpty())
    }
}

@Composable
fun StaffScreen(app: AppState, onHire: () -> Unit) {
    val scope = rememberCoroutineScope()
    var note by remember { mutableStateOf<String?>(null) }
    var failed by remember { mutableStateOf(false) }
    var letGo by remember { mutableStateOf<JsonObject?>(null) }
    Loaded("staff", { app.api.get("staff") }) { data, reload ->
        val stats = data.obj("stats")
        val staff = data.objects("staff")
        val act: (String) -> Unit = { path ->
            scope.launch {
                val result = app.api.post(path)
                failed = result !is ApiResult.Ok
                note = outcome(result, "Done.")
                if (result is ApiResult.Ok) reload()
            }
        }
        ScreenColumn {
            Figure("Payroll per day", stats.str("dailyCostText"), "${stats.int("total")} staff · ${stats.int("assigned")} assigned · morale ${stats.int("avgMorale")}%")
            PrimaryButton("Hire staff", onHire, Modifier.fillMaxWidth())
            if (stats.int("unassigned") > 0) {
                SecondaryButton("Assign everyone to free posts", { act("staff/auto-assign") }, Modifier.fillMaxWidth())
            }
            Note(note, failed)
            if (staff.isEmpty()) EmptyText("No staff hired yet.")
            staff.forEach { member ->
                val morale = member.int("morale")
                Panel {
                    Row(
                        Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically,
                    ) {
                        Text(member.str("name"), fontWeight = FontWeight.Bold, style = MaterialTheme.typography.titleMedium)
                        StatusBadge(
                            member.str("roleName"),
                            MaterialTheme.colorScheme.primary,
                            MaterialTheme.colorScheme.primary.copy(alpha = 0.12f),
                        )
                    }
                    Text("Level ${member.int("level")} · Morale $morale%", color = LocalSkiColors.current.muted, style = MaterialTheme.typography.bodySmall)
                    LinearProgressIndicator(
                        progress = { (morale / 100f).coerceIn(0f, 1f) },
                        modifier = Modifier.fillMaxWidth().height(6.dp).clip(RoundedCornerShape(3.dp)),
                        color = when {
                            morale >= 70 -> LocalSkiColors.current.success
                            morale >= 40 -> LocalSkiColors.current.warning
                            else -> LocalSkiColors.current.danger
                        },
                        trackColor = MaterialTheme.colorScheme.surfaceVariant,
                    )
                    Row2("Pay per day", member.str("salaryText"))
                    Row2("Post", member.strOrNull("assignedTo") ?: "None yet")
                    Row(
                        Modifier.fillMaxWidth().padding(top = 4.dp),
                        horizontalArrangement = Arrangement.spacedBy(8.dp),
                    ) {
                        if (member.bool("canTrain")) {
                            SecondaryButton(
                                "Train for ${member.str("trainCostText")}",
                                { act("staff/${member.int("id")}/train") },
                                Modifier.weight(1f),
                            )
                        }
                        OutlinedPrimaryButton(
                            "Let go",
                            { letGo = member },
                            if (member.bool("canTrain")) Modifier.weight(1f) else Modifier.fillMaxWidth(),
                        )
                    }
                }
            }
        }
        letGo?.let { member ->
            AlertDialog(
                onDismissRequest = { letGo = null },
                shape = MaterialTheme.shapes.extraLarge,
                title = { Text("Let ${member.str("name")} go?", fontWeight = FontWeight.Bold) },
                text = { Text("They stop working for your resort today.") },
                confirmButton = {
                    TextButton({
                        letGo = null
                        act("staff/${member.int("id")}/fire")
                    }, Modifier.heightIn(min = 48.dp)) { Text("Let go", color = LocalSkiColors.current.danger, fontWeight = FontWeight.Bold) }
                },
                dismissButton = { TextButton({ letGo = null }, Modifier.heightIn(min = 48.dp)) { Text("Keep") } },
            )
        }
    }
}

@Composable
fun HireScreen(app: AppState) {
    val scope = rememberCoroutineScope()
    var note by remember { mutableStateOf<String?>(null) }
    var failed by remember { mutableStateOf(false) }
    Loaded("candidates", { app.api.get("staff/candidates") }) { data, reload ->
        val stats = data.obj("stats")
        val candidates = data.obj("candidates")
        ScreenColumn {
            Figure("Cash", stats.str("cashText"), "Payroll ${stats.str("payrollText")} per day")
            Note(note, failed)
            data.objects("roles").forEach { role ->
                val people = candidates.objects(role.str("key"))
                Panel(role.str("name")) {
                    Text(role.str("effect"), color = LocalSkiColors.current.muted)
                    Text(role.str("why"), color = LocalSkiColors.current.muted, style = MaterialTheme.typography.bodySmall)
                    if (people.isEmpty()) Text("No candidates today.", color = LocalSkiColors.current.muted)
                    people.forEach { person ->
                        Row(
                            Modifier.fillMaxWidth().padding(vertical = 4.dp),
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.spacedBy(8.dp),
                        ) {
                            Column(Modifier.weight(1f)) {
                                Row(
                                    verticalAlignment = Alignment.CenterVertically,
                                    horizontalArrangement = Arrangement.spacedBy(8.dp),
                                ) {
                                    Text(person.str("name"), fontWeight = FontWeight.SemiBold)
                                    StatusBadge(
                                        person.str("traitLabel"),
                                        LocalSkiColors.current.muted,
                                        LocalSkiColors.current.surfaceSecondary,
                                    )
                                }
                                Text(
                                    "${person.str("dailyText")} per day (${person.str("diffText")})",
                                    color = LocalSkiColors.current.muted,
                                    style = MaterialTheme.typography.bodySmall,
                                )
                            }
                            SecondaryButton(
                                "Hire",
                                {
                                    scope.launch {
                                        val result = app.api.post("staff/hire", mapOf("candidate" to person.int("id").toString()))
                                        failed = result !is ApiResult.Ok
                                        note = outcome(result, "Hired.")
                                        if (result is ApiResult.Ok) reload()
                                    }
                                },
                            )
                        }
                    }
                }
            }
        }
    }
}
