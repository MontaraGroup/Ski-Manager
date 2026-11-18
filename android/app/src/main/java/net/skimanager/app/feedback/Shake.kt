package net.skimanager.app.feedback

import android.content.Context
import android.hardware.Sensor
import android.hardware.SensorEvent
import android.hardware.SensorEventListener
import android.hardware.SensorManager
import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.rememberUpdatedState
import androidx.compose.ui.platform.LocalContext
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.LifecycleEventObserver
import androidx.lifecycle.compose.LocalLifecycleOwner
import kotlin.math.sqrt

/** The player's choice to open feedback by shaking the phone. On unless they switched it off */
object FeedbackPrefs {
    private fun prefs(context: Context) = context.getSharedPreferences("feedback", Context.MODE_PRIVATE)
    fun shake(context: Context): Boolean = prefs(context).getBoolean("shake", true)
    fun setShake(context: Context, on: Boolean) = prefs(context).edit().putBoolean("shake", on).apply()
}

/**
 * Calls onShake after two firm shakes within a second, at most every 3 seconds. Listens only while
 * the app is in front and enabled is true. The accelerometer needs no permission.
 */
@Composable
fun ShakeEffect(enabled: Boolean, onShake: () -> Unit) {
    val context = LocalContext.current
    val lifecycle = LocalLifecycleOwner.current.lifecycle
    val callback = rememberUpdatedState(onShake)
    DisposableEffect(enabled, lifecycle) {
        val sensors = context.getSystemService(SensorManager::class.java)
        val accel = sensors?.getDefaultSensor(Sensor.TYPE_ACCELEROMETER)
        if (!enabled || sensors == null || accel == null) return@DisposableEffect onDispose {}
        var firstAt = 0L
        var lastFired = 0L
        val listener = object : SensorEventListener {
            override fun onSensorChanged(event: SensorEvent) {
                val (x, y, z) = event.values
                val g = sqrt(x * x + y * y + z * z) / SensorManager.GRAVITY_EARTH
                if (g < 2.7f) return
                val now = System.currentTimeMillis()
                when {
                    now - lastFired < 3000 -> Unit
                    firstAt != 0L && now - firstAt in 250..1000 -> {
                        firstAt = 0L
                        lastFired = now
                        callback.value()
                    }
                    now - firstAt > 1000 -> firstAt = now
                }
            }

            override fun onAccuracyChanged(sensor: Sensor?, accuracy: Int) = Unit
        }
        val observer = LifecycleEventObserver { _, e ->
            when (e) {
                Lifecycle.Event.ON_RESUME -> sensors.registerListener(listener, accel, SensorManager.SENSOR_DELAY_UI)
                Lifecycle.Event.ON_PAUSE -> sensors.unregisterListener(listener)
                else -> Unit
            }
        }
        lifecycle.addObserver(observer)
        if (lifecycle.currentState.isAtLeast(Lifecycle.State.RESUMED)) sensors.registerListener(listener, accel, SensorManager.SENSOR_DELAY_UI)
        onDispose {
            lifecycle.removeObserver(observer)
            sensors.unregisterListener(listener)
        }
    }
}
