package net.skimanager.app.games

import android.annotation.SuppressLint
import android.app.Activity
import android.content.Context
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import com.google.android.gms.games.PlayGames
import com.google.android.gms.tasks.Task
import kotlinx.coroutines.suspendCancellableCoroutine
import net.skimanager.app.api.Api
import net.skimanager.app.api.ApiResult
import net.skimanager.app.api.objects
import net.skimanager.app.api.str
import kotlin.coroutines.resume

/**
 * Google Play Games: sign-in, achievements and the leaderboard. Play Games signs the player in by
 * itself when the app starts (PlayGamesSdk.initialize in SkiManagerApp); this checks the result,
 * then mirrors the game from GET play-games. Achievement and leaderboard IDs come from
 * res/values/play_games.xml; anything missing there is skipped, so the app works before Play
 * Console has them.
 */
object PlayGamesState {
    var signedIn by mutableStateOf(false)
        private set
    var playerName by mutableStateOf<String?>(null)
        private set

    /** Whether Play Games signed this player in, and their Play Games name */
    suspend fun refresh(activity: Activity) {
        val auth = PlayGames.getGamesSignInClient(activity).isAuthenticated.awaitOrNull()
        signedIn = auth?.isAuthenticated == true
        playerName = if (signedIn) PlayGames.getPlayersClient(activity).currentPlayer.awaitOrNull()?.displayName else null
    }

    /** The Play Games sign-in screen, for players who weren't signed in automatically */
    suspend fun signIn(activity: Activity) {
        PlayGames.getGamesSignInClient(activity).signIn().awaitOrNull()
        refresh(activity)
    }

    /** Unlocks completed achievements and submits the resort score. Safe to run on every launch */
    suspend fun sync(activity: Activity, api: Api) {
        if (!signedIn) return
        val data = (api.get("play-games") as? ApiResult.Ok)?.dataObject() ?: return
        val achievements = PlayGames.getAchievementsClient(activity)
        data.objects("achievements").forEach { a ->
            val id = idFor(activity, "achievement_", slug(a.str("name"))) ?: idFor(activity, "achievement_", slug(a.str("key")))
            if (id != null) achievements.unlock(id)
        }
        val score = data.get("leaderboardScore")?.takeIf { it.isJsonPrimitive }?.asLong
        val board = leaderboardId(activity)
        if (score != null && board != null) PlayGames.getLeaderboardsClient(activity).submitScore(board, score)
    }

    fun openAchievements(activity: Activity) {
        PlayGames.getAchievementsClient(activity).achievementsIntent.addOnSuccessListener { activity.startActivityForResult(it, REQUEST_UI) }
    }

    fun openLeaderboard(activity: Activity) {
        val board = leaderboardId(activity) ?: return
        PlayGames.getLeaderboardsClient(activity).getLeaderboardIntent(board).addOnSuccessListener { activity.startActivityForResult(it, REQUEST_UI) }
    }

    fun hasLeaderboard(context: Context) = leaderboardId(context) != null

    private fun leaderboardId(context: Context) = idFor(context, "leaderboard_", "resort_score")

    /** "First Fortune" or "cash_100k" as Play Console names resources: lower case, underscores */
    private fun slug(text: String) = text.lowercase().replace(Regex("[^a-z0-9]+"), "_").trim('_')

    // Play Console's exported IDs are matched by name, so they can be added without code changes; res/raw/keep.xml keeps them
    @SuppressLint("DiscouragedApi")
    private fun idFor(context: Context, prefix: String, name: String): String? {
        if (name.isEmpty()) return null
        val res = context.resources.getIdentifier(prefix + name, "string", context.packageName)
        return if (res == 0) null else context.getString(res).takeIf { it.isNotBlank() }
    }

    private const val REQUEST_UI = 9001
}

private suspend fun <T> Task<T>.awaitOrNull(): T? = suspendCancellableCoroutine { cont ->
    addOnCompleteListener { task -> cont.resume(if (task.isSuccessful) task.result else null) }
}
