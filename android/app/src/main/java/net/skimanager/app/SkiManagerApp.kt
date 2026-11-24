package net.skimanager.app

import android.app.Application
import com.google.android.gms.games.PlayGamesSdk
import com.posthog.android.PostHogAndroid
import com.posthog.android.PostHogAndroidConfig

class SkiManagerApp : Application() {
    override fun onCreate() {
        super.onCreate()
        PlayGamesSdk.initialize(this)

        val apiKey = BuildConfig.POSTHOG_API_KEY
        val host = BuildConfig.POSTHOG_HOST
        if (apiKey.isBlank() || host.isBlank()) {
            if (BuildConfig.DEBUG) {
                val missingVariable = if (apiKey.isBlank()) "POSTHOG_API_KEY" else "POSTHOG_HOST"
                throw IllegalStateException(
                    "$missingVariable variable required by PostHog is missing or un-configured, this causes events to be silently missed. This error stops appearing once $missingVariable is configured"
                )
            }
            return
        }

        PostHogAndroid.setup(
            this,
            PostHogAndroidConfig(
                apiKey = apiKey,
                host = host,
            ).apply {
                sessionReplay = true
                errorTrackingConfig.autoCapture = true
                logs.serviceName = "ski-manager-android"
                logs.environment = if (BuildConfig.DEBUG) "debug" else "production"
            }
        )
        PostHogLogCapture.applicationReady()
    }
}
