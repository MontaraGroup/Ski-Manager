# Ski Manager for Android

Native Kotlin and Jetpack Compose app for [ski-manager.net](https://ski-manager.net). It plays the real game on real accounts through `/api/v1/app`. The server decides every rule and formats every number, and the app only shows them.

## Screens

Sign in, sign up, email code, dashboard, trail map (build and demolish), staff (hire, train, let go), lift tickets, finances, weather, notifications, leaderboard, settings, delete account. Other game pages open on the website from Settings.

## Layout

- `api/`: the HTTP client and the token store (encrypted with an Android Keystore key)
- `ui/auth/`: sign in, sign up, email code
- `ui/game/`: game screens
- `ui/common/`: loading, offline and error states, shared components
- `ui/theme/`: colors from `DESIGN.md`

## Build

Needs JDK 17 or later and the Android SDK (platform 36, build tools 36.0.0). Debug builds use staging, release builds use production.

```bash
cd android
./gradlew assembleDebug
./gradlew bundleRelease
```

The bundle lands in `app/build/outputs/bundle/release/app-release.aab`.

If AAPT2 fails with "Daemon startup failed" on Apple silicon, point Gradle at the SDK's copy:

```bash
./gradlew bundleRelease -Pandroid.aapt2FromMavenOverride=$ANDROID_HOME/build-tools/36.0.0/aapt2
```

To test the shrunk release code against staging, add `-PreleaseBaseUrl=https://staging.ski-manager.net`. Never upload that build.

The version lives in `gradle.properties` (`VERSION_CODE`, `VERSION_NAME`). Raise `VERSION_CODE` for every upload.

## Signing

Release builds are signed with an upload key that is never committed. Set these in `android/local.properties` (git-ignored), or as environment variables:

```properties
SKI_KEYSTORE_PATH=/absolute/path/to/skimanager-upload.jks
SKI_KEYSTORE_PASSWORD=...
SKI_KEY_ALIAS=upload
SKI_KEY_PASSWORD=...
```

The GitHub workflow needs the secrets `SKI_KEYSTORE_BASE64` (the keystore file in base64), `SKI_KEYSTORE_PASSWORD`, `SKI_KEY_ALIAS` and `SKI_KEY_PASSWORD`.

The key that used to be in this repo is public in git history. Never sign with it again.

See `PLAY_RELEASE.md` for publishing.
