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

class AppState(context: Context) {
    private val store = TokenStore(context)
    private val token0 = store.read()

    var signedIn by mutableStateOf(token0 != null)
        private set
    /** Why the player is back on the sign-in screen */
    var signedOutNote by mutableStateOf<String?>(null)
    var needsStart by mutableStateOf(false)

    private var token: String? = token0

    val api = Api(tokens = { token }, onSignedOut = {
        if (signedIn) forget("Your session has ended. Sign in again.")
    })

    /** data: { token, user, profileComplete } from sign-in or the email code */
    fun signIn(data: JsonObject) {
        token = data.str("token")
        store.write(data.str("token"))
        needsStart = !data.bool("profileComplete")
        signedOutNote = null
        signedIn = true
    }

    fun forget(note: String? = null) {
        token = null
        store.clear()
        needsStart = false
        signedOutNote = note
        signedIn = false
    }
}
