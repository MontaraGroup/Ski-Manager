# Ski Manager for Android (Material Design 3 Expressive)

Native Android implementation of **[ski-manager.net](https://ski-manager.net)** built with Kotlin and Jetpack Compose utilizing Google's latest **Material Design 3 Expressive** design system.

---

## Highlights

- **Material 3 Expressive UI**:
  - Expressive pill navigation indicators, dynamic shape scales (`RoundedCornerShape(28.dp)`), high-contrast surfaces, and alpine tonal color palettes.
  - Tactile haptic feedback (`HapticFeedbackType`) across day advancement, trail inspections, and daily streak rewards.
  - Smooth spring transitions and animated content changes.
- **Top Snapshot Bar**:
  - Real-time balances, daily skiers, net daily profit, weather/snow temperature, and Génépis counter.
  - Dynamic server status badge: `🟢 Online · ski-manager.net` / `🟡 Syncing...` / `⚪️ Offline (Cached)`.
- **Modular Dashboard**:
  - Director greeting banner with Season 1 & 2 progress meter.
  - Daily Bonus streak claim banner with instant rewards.
  - High-emphasis pill "Advance Game Day" action with full resort economics.
  - Modular cards for mountain conditions, operations, and village status.
- **Authentic Leaflet Mountain Trail Map**:
  - High-resolution `ParkCity.jpg` backdrop with 73 Leaflet CRS database segments and sectors.
  - Smooth pinch-to-zoom and pan canvas with tap inspector and construction options.
- **Commercial Village**:
  - Hotels, mountain lodges, après-ski bistros, equipment rentals, first aid clinics, and parking facilities with 5-star upgrade paths.
- **Operations & Commercial Bank**:
  - Dynamic lift ticket pricing sliders with visitor elasticity modeling.
  - Staff management across 5 roles (Ski Patrol, Groomers, Mechanics, Instructors, Administration).
  - Commercial bank loan borrowing & principal repayment.
- **Online & Offline Dual-Engine**:
  - Works 100% offline with atomic local persistence to `ski_manager_resort_v4.json`.
  - Connects to `https://ski-manager.net/api/...` for global leaderboards and background telemetry synchronization.

---

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
