package net.skimanager.app

import com.posthog.PostHog

object PostHogLogCapture {
    fun applicationReady() = capture("application ready", "application_ready")

    fun authenticationCompleted() = capture("authentication completed", "authentication_completed")

    fun profileStartSelected(difficulty: String) = capture(
        message = "profile start selected",
        event = "profile_start_selected",
        attributes = mapOf("difficulty" to difficulty),
    )

    private fun capture(message: String, event: String, attributes: Map<String, Any> = emptyMap()) {
        runCatching {
            PostHog.logger.info(message, attributes + ("event" to event))
        }
    }
}
