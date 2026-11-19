package net.skimanager.app.ui.game

import androidx.activity.compose.LocalActivity
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import kotlinx.coroutines.launch
import net.skimanager.app.games.PlayGamesState
import net.skimanager.app.ui.AppState
import net.skimanager.app.ui.common.Panel

/** Settings: the player's Google Play Games account, with its achievements and leaderboard */
@Composable
fun PlayGamesPanel(app: AppState) {
    val activity = LocalActivity.current ?: return
    val scope = rememberCoroutineScope()
    var busy by remember { mutableStateOf(false) }
    Panel("Google Play Games") {
        if (PlayGamesState.signedIn) {
            Muted("Signed in as ${PlayGamesState.playerName ?: "a Play Games player"}. Your achievements and resort score show in Play Games.")
            ButtonRow {
                ActButton("Achievements", { PlayGamesState.openAchievements(activity) }, Modifier.weight(1f))
                if (PlayGamesState.hasLeaderboard(activity)) {
                    ActButton("Leaderboard", { PlayGamesState.openLeaderboard(activity) }, Modifier.weight(1f))
                }
            }
        } else {
            Muted("Sign in to keep your achievements and resort score in Play Games.")
            ActButton("Sign in to Play Games", {
                busy = true
                scope.launch {
                    PlayGamesState.signIn(activity)
                    PlayGamesState.sync(activity, app.api)
                    busy = false
                }
            }, Modifier.fillMaxWidth(), enabled = !busy)
        }
    }
}
