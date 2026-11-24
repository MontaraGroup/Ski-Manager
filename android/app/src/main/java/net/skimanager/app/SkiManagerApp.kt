package net.skimanager.app

import android.app.Application
import com.google.android.gms.games.PlayGamesSdk
import com.posthog.android.PostHogAndroid
import com.posthog.PersonProfiles
import com.posthog.android.PostHogAndroidConfig

class SkiManagerApp : Application() {
    override fun onCreate() {
        super.onCreate()
        PlayGamesSdk.initialize(this)

        val apiKey = BuildConfig.POSTHOG_API_KEY
        val host = BuildConfig.POSTHOG_HOST
        // Analytics never stops the game from starting: without a key, PostHog stays off
        if (apiKey.isBlank() || host.isBlank()) return

        PostHogAndroid.setup(
            this,
            PostHogAndroidConfig(
                apiKey = apiKey,
                host = host,
            ).apply {
                personProfiles = PersonProfiles.IDENTIFIED_ONLY
                sessionReplay = true
                errorTrackingConfig.autoCapture = true
                logs.serviceName = "ski-manager-android"
                logs.environment = if (BuildConfig.DEBUG) "debug" else "production"
            }
        )
        PostHogLogCapture.applicationReady()
    }
}
