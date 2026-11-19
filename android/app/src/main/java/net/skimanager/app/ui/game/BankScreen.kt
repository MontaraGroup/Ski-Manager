package net.skimanager.app.ui.game

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.AccountBalance
import androidx.compose.material.icons.filled.AttachMoney
import androidx.compose.material.icons.filled.BusinessCenter
import androidx.compose.material.icons.filled.LocationCity
import androidx.compose.material.icons.filled.Warning
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
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
import net.skimanager.app.ui.common.StatusBadge
import net.skimanager.app.ui.theme.LocalSkiColors

@Composable
fun BankScreen(app: AppState) {
    var reloadCount by remember { mutableStateOf(0) }
    var borrowProduct by remember { mutableStateOf<JsonObject?>(null) }
    var repayLoan by remember { mutableStateOf<JsonObject?>(null) }
    var actionError by remember { mutableStateOf<String?>(null) }
    var actionSuccess by remember { mutableStateOf<String?>(null) }
    val scope = rememberCoroutineScope()

    fun act(endpoint: String, body: Map<String, String>, reload: () -> Unit) {
        actionError = null
        actionSuccess = null
        scope.launch {
            when (val r = app.api.post(endpoint, body)) {
                is ApiResult.Ok -> {
                    actionSuccess = r.message ?: "Transaction complete."
                    reload()
                }
                is ApiResult.Failed -> {
                    actionError = r.message
                }
                is ApiResult.Offline -> {
                    actionError = "Offline: cannot connect to bank servers."
                }
            }
        }
    }

    Loaded(key = reloadCount, load = { app.api.get("bank") }) { data, reload ->
        val stats = data.obj("stats")
        val loans = data.objects("loans")
        val products = data.objects("products")
        val freeSlots = stats.int("freeSlots")
        val maxLoans = stats.int("maxLoans")

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
                    Figure("Total debt", stats.str("totalDebtText"), "Principal + interest")
                }
                Column(Modifier.weight(1f)) {
                    Figure("Early payoff", stats.str("totalPayoffText"), "Cost to settle today")
                }
            }

            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                val loadPct = stats.str("loadPct").takeIf { it.isNotEmpty() }
                val paymentNote = if (loadPct != null) "$loadPct% of daily income" else "Deducted at midnight tick"
                Column(Modifier.weight(1f)) {
                    Figure("Daily payment", stats.str("dailyPaymentsText"), paymentNote)
                }
                val slotNote = if (stats.bool("hasExtraSlot")) "Includes extra loan boost" else "$freeSlots of $maxLoans free"
                Column(Modifier.weight(1f)) {
                    Figure("Active loans", "${stats.int("activeCount")} of $maxLoans", slotNote)
                }
            }

            Panel("Active loans") {
                if (loans.isEmpty()) {
                    EmptyText("No active loans. Your resort is currently debt-free.")
                } else {
                    loans.forEach { loan ->
                        ActiveLoanCard(
                            loan = loan,
                            onPayoff = { repayLoan = loan },
                        )
                    }
                }
            }

            Panel("Borrow capital") {
                val subtitle = if (freeSlots > 0) {
                    "$freeSlots loan slot${if (freeSlots > 1) "s" else ""} available for new financing."
                } else {
                    "All $maxLoans loan slots in use. Repay an active loan to unlock borrowing."
                }
                Text(subtitle, color = LocalSkiColors.current.muted)

                products.forEach { prod ->
                    LoanProductCard(
                        prod = prod,
                        canBorrow = freeSlots > 0,
                        onBorrow = { borrowProduct = prod },
                    )
                }
            }
        }

        borrowProduct?.let { p ->
            AlertDialog(
                onDismissRequest = { borrowProduct = null },
                shape = MaterialTheme.shapes.extraLarge,
                title = { Text("Borrow ${p.str("name")}?", fontWeight = FontWeight.Bold) },
                text = {
                    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        Text("You will receive ${p.str("amountText")} immediately into your resort treasury.")
                        Text("Daily payment: ${p.str("dailyPaymentText")} / day for ${p.int("days")} days.")
                        Text("Total repayment: ${p.str("totalRepayText")} (${p.str("interestText")} fee).", color = LocalSkiColors.current.muted)
                    }
                },
                confirmButton = {
                    TextButton({
                        val target = borrowProduct
                        borrowProduct = null
                        if (target != null) {
                            act("bank/borrow", mapOf("type" to target.str("type"))) { reload(); reloadCount++ }
                        }
                    }, Modifier.heightIn(min = 48.dp)) { Text("Borrow ${p.str("amountText")}", fontWeight = FontWeight.Bold) }
                },
                dismissButton = {
                    TextButton({ borrowProduct = null }, Modifier.heightIn(min = 48.dp)) { Text("Cancel") }
                },
            )
        }

        repayLoan?.let { l ->
            AlertDialog(
                onDismissRequest = { repayLoan = null },
                shape = MaterialTheme.shapes.extraLarge,
                title = { Text("Settle ${l.str("name")} early?", fontWeight = FontWeight.Bold) },
                text = {
                    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        Text("Pay ${l.str("payoffText")} now to clear the remaining balance.")
                        if (l.int("saving") > 0) {
                            Text(
                                "You will save ${l.str("savingText")} in unearned interest by settling today.",
                                color = MaterialTheme.colorScheme.primary,
                                fontWeight = FontWeight.Medium,
                            )
                        }
                    }
                },
                confirmButton = {
                    TextButton({
                        val target = repayLoan
                        repayLoan = null
                        if (target != null) {
                            act("bank/${target.int("id")}/repay", emptyMap()) { reload(); reloadCount++ }
                        }
                    }, Modifier.heightIn(min = 48.dp)) { Text("Pay ${l.str("payoffText")}", fontWeight = FontWeight.Bold) }
                },
                dismissButton = {
                    TextButton({ repayLoan = null }, Modifier.heightIn(min = 48.dp)) { Text("Cancel") }
                },
            )
        }
    }
}

@Composable
private fun ActiveLoanCard(
    loan: JsonObject,
    onPayoff: () -> Unit,
) {
    Card(
        Modifier.fillMaxWidth().padding(vertical = 4.dp),
        shape = MaterialTheme.shapes.medium,
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceContainerHigh),
        border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant),
    ) {
        Column(Modifier.padding(14.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                Text(loan.str("name"), fontWeight = FontWeight.Bold, style = MaterialTheme.typography.titleMedium)
                StatusBadge(
                    "${loan.int("days_remaining")}d remaining",
                    MaterialTheme.colorScheme.primary,
                    MaterialTheme.colorScheme.primary.copy(alpha = 0.12f),
                )
            }

            val paidPct = loan.int("paid_pct").coerceIn(0, 100)
            LinearProgressIndicator(
                progress = { paidPct / 100f },
                modifier = Modifier.fillMaxWidth().height(6.dp).clip(RoundedCornerShape(3.dp)),
                color = MaterialTheme.colorScheme.primary,
                trackColor = MaterialTheme.colorScheme.surfaceVariant,
            )

            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Text("$paidPct% repaid", color = LocalSkiColors.current.muted, style = MaterialTheme.typography.bodySmall)
                val daysTotal = loan.int("days_total")
                val daysPaid = daysTotal - loan.int("days_remaining")
                Text("$daysPaid of $daysTotal days", color = LocalSkiColors.current.muted, style = MaterialTheme.typography.bodySmall)
            }

            Row2("Principal", loan.str("principalText"))
            Row2("Remaining balance", loan.str("remainingText"))
            Row2("Daily installment", "${loan.str("dailyPaymentText")} / day")
            Row2("Early payoff cost", loan.str("payoffText"))

            val saving = loan.int("saving")
            if (saving > 0) {
                Card(
                    Modifier.fillMaxWidth(),
                    shape = MaterialTheme.shapes.small,
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.primary.copy(alpha = 0.08f)),
                    border = BorderStroke(1.dp, MaterialTheme.colorScheme.primary.copy(alpha = 0.25f)),
                ) {
                    Text(
                        "Early payoff discount: save ${loan.str("savingText")} in interest.",
                        modifier = Modifier.padding(horizontal = 10.dp, vertical = 6.dp),
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.primary,
                        fontWeight = FontWeight.Medium,
                    )
                }
            }

            val canPay = loan.bool("canPay")
            if (canPay) {
                PrimaryButton(
                    "Pay off early for ${loan.str("payoffText")}",
                    onPayoff,
                    Modifier.fillMaxWidth(),
                )
            } else {
                val needed = loan.str("cashNeededText")
                PrimaryButton(
                    if (needed.isNotEmpty()) "Need $needed more cash to pay off" else "Insufficient funds",
                    {},
                    Modifier.fillMaxWidth(),
                    enabled = false,
                )
            }
        }
    }
}

@Composable
private fun LoanProductCard(
    prod: JsonObject,
    canBorrow: Boolean,
    onBorrow: () -> Unit,
) {
    val isEmergency = prod.bool("isEmergency")
    Card(
        Modifier.fillMaxWidth().padding(vertical = 4.dp),
        shape = MaterialTheme.shapes.medium,
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceContainerHigh),
        border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant),
    ) {
        Column(Modifier.padding(14.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp), verticalAlignment = Alignment.CenterVertically) {
                    Icon(
                        loanIcon(prod.str("type")),
                        contentDescription = null,
                        modifier = Modifier.size(20.dp),
                        tint = if (isEmergency) MaterialTheme.colorScheme.error else MaterialTheme.colorScheme.primary,
                    )
                    Text(prod.str("name"), fontWeight = FontWeight.Bold, style = MaterialTheme.typography.titleMedium)
                }
                if (isEmergency) {
                    StatusBadge("EMERGENCY", MaterialTheme.colorScheme.error, MaterialTheme.colorScheme.error.copy(alpha = 0.15f))
                }
            }

            Text(
                prod.str("amountText"),
                style = MaterialTheme.typography.headlineSmall,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onSurface,
            )

            Row2("Term length", "${prod.int("days")} days")
            Row2("Daily installment", "${prod.str("dailyPaymentText")} / day")
            Row2("Total repayment", "${prod.str("totalRepayText")} (${prod.str("interestText")} fee)")

            PrimaryButton(
                if (canBorrow) "Borrow ${prod.str("amountText")}" else "No loan slots available",
                onBorrow,
                Modifier.fillMaxWidth(),
                enabled = canBorrow,
            )
        }
    }
}

private fun loanIcon(type: String): ImageVector = when (type) {
    "emergency" -> Icons.Default.Warning
    "small" -> Icons.Default.AttachMoney
    "medium" -> Icons.Default.BusinessCenter
    "large" -> Icons.Default.AccountBalance
    "mega" -> Icons.Default.LocationCity
    else -> Icons.Default.AttachMoney
}
