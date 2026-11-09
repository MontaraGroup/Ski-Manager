package net.skimanager.app.ui

import androidx.compose.foundation.layout.padding
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.AccountBalance
import androidx.compose.material.icons.filled.Landscape
import androidx.compose.material.icons.filled.Menu
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.NavigationBar
import androidx.compose.material3.NavigationBarItem
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.TopAppBarDefaults
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.LocalContext
import androidx.navigation.NavGraph.Companion.findStartDestination
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.currentBackStackEntryAsState
import androidx.navigation.compose.rememberNavController
import net.skimanager.app.ui.auth.AuthFlow
import net.skimanager.app.ui.auth.ChooseStart
import net.skimanager.app.ui.game.DashboardScreen
import net.skimanager.app.ui.game.FinancesScreen
import net.skimanager.app.ui.game.LeaderboardScreen
import net.skimanager.app.ui.game.MoreScreen
import net.skimanager.app.ui.game.NotificationsScreen
import net.skimanager.app.ui.game.SettingsScreen
import net.skimanager.app.ui.game.WeatherScreen
import java.io.File

private data class Tab(val route: String, val label: String, val icon: ImageVector)

private val TABS = listOf(
    Tab("dashboard", "Dashboard", Icons.Filled.Landscape),
    Tab("finances", "Finances", Icons.Filled.AccountBalance),
    Tab("more", "More", Icons.Filled.Menu),
)

private val TITLES = mapOf(
    "dashboard" to "Dashboard", "finances" to "Finances", "more" to "More", "weather" to "Weather",
    "notifications" to "Notifications", "leaderboard" to "Leaderboard", "settings" to "Settings",
)

/** The save file the offline version of the app left behind */
private const val OLD_SAVE = "ski_manager_resort_v4.json"

@Composable
fun App() {
    val context = LocalContext.current
    val app = remember { AppState(context.applicationContext) }
    val oldSave = remember { File(context.filesDir, OLD_SAVE) }
    var showUpdateNote by remember { mutableStateOf(oldSave.exists()) }

    when {
        !app.signedIn -> Screen { AuthFlow(app) }
        app.needsStart -> Screen { ChooseStart(app) }
        else -> GameShell(app)
    }

    if (showUpdateNote) {
        AlertDialog(
            onDismissRequest = {},
            text = { Text("This version plays on your ski-manager.net account. Progress from the old offline game isn't carried over.") },
            confirmButton = {
                TextButton({
                    oldSave.delete()
                    showUpdateNote = false
                }) { Text("Continue") }
            },
        )
    }
}

@Composable
private fun Screen(content: @Composable () -> Unit) {
    Scaffold(containerColor = MaterialTheme.colorScheme.background) { padding ->
        androidx.compose.foundation.layout.Box(Modifier.padding(padding)) { content() }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun GameShell(app: AppState) {
    val nav = rememberNavController()
    val entry by nav.currentBackStackEntryAsState()
    val route = entry?.destination?.route ?: "dashboard"
    val isTab = TABS.any { it.route == route }

    Scaffold(
        containerColor = MaterialTheme.colorScheme.background,
        topBar = {
            TopAppBar(
                title = { Text(TITLES[route].orEmpty()) },
                navigationIcon = {
                    if (!isTab) IconButton({ nav.popBackStack() }) { Icon(Icons.AutoMirrored.Filled.ArrowBack, "Back") }
                },
                colors = TopAppBarDefaults.topAppBarColors(containerColor = MaterialTheme.colorScheme.background),
            )
        },
        bottomBar = {
            NavigationBar(containerColor = MaterialTheme.colorScheme.surface) {
                TABS.forEach { tab ->
                    NavigationBarItem(
                        selected = route == tab.route,
                        onClick = {
                            nav.navigate(tab.route) {
                                popUpTo(nav.graph.findStartDestination().id) { saveState = true }
                                launchSingleTop = true
                                restoreState = true
                            }
                        },
                        icon = { Icon(tab.icon, null) },
                        label = { Text(tab.label) },
                    )
                }
            }
        },
    ) { padding ->
        NavHost(nav, "dashboard", Modifier.padding(padding)) {
            composable("dashboard") { DashboardScreen(app) }
            composable("finances") { FinancesScreen(app) }
            composable("more") { MoreScreen { nav.navigate(it) } }
            composable("weather") { WeatherScreen(app) }
            composable("notifications") { NotificationsScreen(app) }
            composable("leaderboard") { LeaderboardScreen(app) }
            composable("settings") { SettingsScreen(app) }
        }
    }
}
