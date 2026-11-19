package net.skimanager.app.ui

import android.content.Context
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import com.google.gson.JsonObject
import net.skimanager.app.api.Api
import net.skimanager.app.api.TokenStore
import net.skimanager.app.api.bool
import net.skimanager.app.api.str
import net.skimanager.app.feedback.Capture
import net.skimanager.app.feedback.FeedbackPrefs

class AppState(private val context: Context) {
    private val store = TokenStore(context)
    private val token0 = store.read()

    var signedIn by mutableStateOf(token0 != null)
        private set
    /** Why the player is back on the sign-in screen */
    var signedOutNote by mutableStateOf<String?>(null)
    var needsStart by mutableStateOf(false)

    private var token: String? = token0

    /** The screen captured by a shake, read once by the feedback screen */
    var feedbackCapture: Capture? = null

    /** Shaking the phone opens feedback. Settings can switch it off */
    var shakeToFeedback by mutableStateOf(FeedbackPrefs.shake(context))
        private set

    fun changeShakeToFeedback(on: Boolean) {
        FeedbackPrefs.setShake(context, on)
        shakeToFeedback = on
    }

    val api = Api(tokens = { token }, onSignedOut = {
        if (signedIn) forget("Your session has ended. Sign in again.")
    })

    /** data: { token, user, profileComplete } from sign-in or the email code. False if the answer had no token */
    fun signIn(data: JsonObject): Boolean {
        val newToken = data.str("token").ifBlank { return false }
        token = newToken
        store.write(newToken)
        needsStart = !data.bool("profileComplete")
        signedOutNote = null
        signedIn = true
        return true
    }

    fun forget(note: String? = null) {
        token = null
        store.clear()
        needsStart = false
        signedOutNote = note
        signedIn = false
    }
}
