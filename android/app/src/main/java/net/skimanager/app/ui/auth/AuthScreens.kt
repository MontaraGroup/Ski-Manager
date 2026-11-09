package net.skimanager.app.ui.auth

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.selection.selectable
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material3.Checkbox
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.RadioButton
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.dp
import kotlinx.coroutines.launch
import net.skimanager.app.api.ApiResult
import net.skimanager.app.api.SERVER_ERROR
import net.skimanager.app.api.bool
import net.skimanager.app.api.str
import net.skimanager.app.ui.AppState
import net.skimanager.app.ui.common.OFFLINE_TEXT
import net.skimanager.app.ui.common.PrimaryButton
import net.skimanager.app.ui.common.ScreenColumn
import net.skimanager.app.ui.common.openWebsite
import net.skimanager.app.ui.theme.LocalSkiColors

private enum class Step { SignIn, SignUp, Code }

/** Sign in, sign up and the email code */
@Composable
fun AuthFlow(app: AppState) {
    var step by rememberSaveable { mutableStateOf(Step.SignIn) }
    var email by rememberSaveable { mutableStateOf("") }
    var notice by rememberSaveable { mutableStateOf<String?>(null) }

    when (step) {
        Step.SignIn -> SignIn(app, email, onEmail = { email = it }, onSignUp = { step = Step.SignUp }, onNeedsCode = { step = Step.Code; notice = null })
        Step.SignUp -> SignUp(app, onBack = { step = Step.SignIn }, onCreated = { created, mailNote -> email = created; notice = mailNote; step = Step.Code })
        Step.Code -> EmailCode(app, email, notice, onBack = { step = Step.SignIn })
    }
}

private fun messageOf(result: ApiResult): String = when (result) {
    is ApiResult.Offline -> OFFLINE_TEXT
    is ApiResult.Failed -> result.message
    is ApiResult.Ok -> SERVER_ERROR
}

@Composable
private fun Title(text: String) {
    Text(text, style = MaterialTheme.typography.headlineMedium, fontWeight = FontWeight.Bold)
}

@Composable
private fun ErrorText(text: String?) {
    if (text != null) Text(text, color = LocalSkiColors.current.danger, style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.Medium)
}

@Composable
private fun Field(
    label: String,
    value: String,
    onChange: (String) -> Unit,
    error: String? = null,
    password: Boolean = false,
    keyboard: KeyboardType = KeyboardType.Text,
) {
    OutlinedTextField(
        value, onChange, Modifier.fillMaxWidth(),
        shape = MaterialTheme.shapes.medium,
        label = { Text(label) },
        singleLine = true,
        isError = error != null,
        supportingText = error?.let { { Text(it) } },
        visualTransformation = if (password) PasswordVisualTransformation() else androidx.compose.ui.text.input.VisualTransformation.None,
        keyboardOptions = KeyboardOptions(keyboardType = if (password) KeyboardType.Password else keyboard),
    )
}

@Composable
private fun SignIn(app: AppState, email: String, onEmail: (String) -> Unit, onSignUp: () -> Unit, onNeedsCode: () -> Unit) {
    val scope = rememberCoroutineScope()
    val context = LocalContext.current
    var password by remember { mutableStateOf("") }
    var error by remember { mutableStateOf<String?>(null) }
    var busy by remember { mutableStateOf(false) }

    ScreenColumn {
        Title("Sign in to Ski Manager")
        app.signedOutNote?.let { Text(it, color = LocalSkiColors.current.warning) }
        Field("Email", email, onEmail, keyboard = KeyboardType.Email)
        Field("Password", password, { password = it }, password = true)
        ErrorText(error)
        PrimaryButton("Sign in", {
            busy = true
            error = null
            scope.launch {
                val result = app.api.post("auth/login", mapOf("email" to email.trim(), "password" to password))
                busy = false
                when {
                    result is ApiResult.Ok -> app.signIn(result.data!!.asJsonObject)
                    result is ApiResult.Failed && result.code == "activation_required" -> onNeedsCode()
                    else -> error = messageOf(result)
                }
            }
        }, Modifier.fillMaxWidth(), enabled = !busy && email.isNotBlank() && password.isNotEmpty())
        TextButton(onSignUp, Modifier.fillMaxWidth().heightIn(min = 48.dp)) { Text("Create an account") }
        TextButton({ openWebsite(context, "/login/magic-link") }, Modifier.fillMaxWidth().heightIn(min = 48.dp)) { Text("Forgot password?") }
    }
}

private data class Difficulty(val key: String, val label: String, val cash: String, val note: String)

private val DIFFICULTIES = listOf(
    Difficulty("easy", "Easy", "1,000,000 €", "Room for mistakes"),
    Difficulty("standard", "Standard", "500,000 €", "The intended game"),
    Difficulty("hard", "Hard", "200,000 €", "No room for error"),
)

@Composable
private fun DifficultyChoice(selected: String, onSelect: (String) -> Unit) {
    Text("Difficulty and starting budget", fontWeight = FontWeight.Bold, style = MaterialTheme.typography.titleMedium)
    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
        DIFFICULTIES.forEach { d ->
            val isSelected = d.key == selected
            androidx.compose.material3.Card(
                modifier = Modifier
                    .fillMaxWidth()
                    .selectable(isSelected, role = Role.RadioButton) { onSelect(d.key) },
                shape = MaterialTheme.shapes.medium,
                colors = androidx.compose.material3.CardDefaults.cardColors(
                    containerColor = if (isSelected) MaterialTheme.colorScheme.secondaryContainer else MaterialTheme.colorScheme.surfaceContainer,
                ),
                border = if (isSelected) androidx.compose.foundation.BorderStroke(2.dp, MaterialTheme.colorScheme.primary) else androidx.compose.foundation.BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant),
            ) {
                Row(
                    Modifier.fillMaxWidth().padding(14.dp),
                    verticalAlignment = Alignment.CenterVertically,
                ) {
                    RadioButton(isSelected, null, Modifier.padding(end = 12.dp))
                    Column(Modifier.weight(1f)) {
                        Text("${d.label}: ${d.cash}", fontWeight = FontWeight.Bold, style = MaterialTheme.typography.titleSmall)
                        Text(d.note, color = LocalSkiColors.current.muted, style = MaterialTheme.typography.bodySmall)
                    }
                }
            }
        }
    }
}

@Composable
private fun TermsBox(checked: Boolean, onChange: (Boolean) -> Unit, error: String?) {
    val context = LocalContext.current
    androidx.compose.material3.Card(
        shape = MaterialTheme.shapes.medium,
        colors = androidx.compose.material3.CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceContainer),
        border = androidx.compose.foundation.BorderStroke(1.dp, if (error != null) MaterialTheme.colorScheme.error else MaterialTheme.colorScheme.outlineVariant),
        modifier = Modifier.fillMaxWidth(),
    ) {
        Column(Modifier.padding(14.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
            Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                Checkbox(checked, onChange, Modifier.padding(end = 8.dp))
                Text("I agree to the Terms of Service and Privacy Policy", style = MaterialTheme.typography.bodyMedium, modifier = Modifier.weight(1f).clickable { onChange(!checked) })
            }
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                TextButton({ openWebsite(context, "/terms") }, Modifier.heightIn(min = 48.dp)) { Text("Terms of Service") }
                TextButton({ openWebsite(context, "/privacy") }, Modifier.heightIn(min = 48.dp)) { Text("Privacy Policy") }
            }
        }
    }
    ErrorText(error)
}

@Composable
private fun SignUp(app: AppState, onBack: () -> Unit, onCreated: (String, String?) -> Unit) {
    val scope = rememberCoroutineScope()
    var username by rememberSaveable { mutableStateOf("") }
    var email by rememberSaveable { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var confirm by remember { mutableStateOf("") }
    var difficulty by rememberSaveable { mutableStateOf("standard") }
    var terms by rememberSaveable { mutableStateOf(false) }
    var fields by remember { mutableStateOf(emptyMap<String, String>()) }
    var error by remember { mutableStateOf<String?>(null) }
    var busy by remember { mutableStateOf(false) }

    ScreenColumn {
        Title("Create your account")
        Field("Username", username, { username = it }, fields["username"])
        Field("Email", email, { email = it }, fields["email"], keyboard = KeyboardType.Email)
        Field("Password", password, { password = it }, fields["password"], password = true)
        Field("Confirm password", confirm, { confirm = it }, fields["password_confirm"], password = true)
        DifficultyChoice(difficulty) { difficulty = it }
        TermsBox(terms, { terms = it }, fields["terms"])
        ErrorText(error)
        PrimaryButton("Continue", {
            busy = true
            error = null
            fields = emptyMap()
            scope.launch {
                val form = mutableMapOf(
                    "username" to username.trim(), "email" to email.trim(), "password" to password,
                    "password_confirm" to confirm, "difficulty" to difficulty, "resort_map" to "ParkCity",
                )
                if (terms) form["terms"] = "on"
                val result = app.api.post("auth/register", form)
                busy = false
                when {
                    result is ApiResult.Ok -> {
                        val data = result.data!!.asJsonObject
                        onCreated(data.str("email"), if (data.bool("mailSent")) null else result.message)
                    }
                    result is ApiResult.Failed && result.fields.isNotEmpty() -> fields = result.fields
                    else -> error = messageOf(result)
                }
            }
        }, Modifier.fillMaxWidth(), enabled = !busy)
        TextButton(onBack, Modifier.fillMaxWidth().heightIn(min = 48.dp)) { Text("I already have an account") }
    }
}

@Composable
private fun EmailCode(app: AppState, email: String, startNotice: String?, onBack: () -> Unit) {
    val scope = rememberCoroutineScope()
    var code by rememberSaveable { mutableStateOf("") }
    var message by remember { mutableStateOf(startNotice) }
    var busy by remember { mutableStateOf(false) }

    ScreenColumn {
        Title("Confirm your email")
        Text("We sent a 6-digit code to $email.")
        Field("6-digit code", code, { code = it.filter(Char::isDigit).take(6) }, keyboard = KeyboardType.Number)
        ErrorText(message)
        PrimaryButton("Confirm", {
            busy = true
            message = null
            scope.launch {
                val result = app.api.post("auth/verify", mapOf("email" to email, "code" to code))
                busy = false
                if (result is ApiResult.Ok) app.signIn(result.data!!.asJsonObject) else message = messageOf(result)
            }
        }, Modifier.fillMaxWidth(), enabled = !busy && code.length == 6)
        TextButton({
            scope.launch {
                val result = app.api.post("auth/resend", mapOf("email" to email))
                message = if (result is ApiResult.Ok) null else messageOf(result)
            }
        }, Modifier.fillMaxWidth().heightIn(min = 48.dp)) { Text("Send a new code") }
        TextButton(onBack, Modifier.fillMaxWidth().heightIn(min = 48.dp)) { Text("Back to sign in") }
    }
}

/** Only for accounts without a chosen start. New sign-ups choose in the form above */
@Composable
fun ChooseStart(app: AppState) {
    val scope = rememberCoroutineScope()
    var difficulty by rememberSaveable { mutableStateOf("standard") }
    var terms by rememberSaveable { mutableStateOf(false) }
    var error by remember { mutableStateOf<String?>(null) }
    var busy by remember { mutableStateOf(false) }

    ScreenColumn {
        Title("Choose your start")
        DifficultyChoice(difficulty) { difficulty = it }
        TermsBox(terms, { terms = it }, null)
        ErrorText(error)
        PrimaryButton("Start playing", {
            busy = true
            error = null
            scope.launch {
                val form = mutableMapOf("difficulty" to difficulty, "resort_map" to "ParkCity")
                if (terms) form["terms"] = "on"
                val result = app.api.post("profile", form)
                busy = false
                if (result is ApiResult.Ok) app.needsStart = false else error = messageOf(result)
            }
        }, Modifier.fillMaxWidth(), enabled = !busy)
    }
}
