package net.skimanager.app

import android.app.Application
import com.google.android.gms.games.PlayGamesSdk

class SkiManagerApp : Application() {
    override fun onCreate() {
        super.onCreate()
        PlayGamesSdk.initialize(this)
    }
}
