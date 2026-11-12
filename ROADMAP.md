# Ski Manager Roadmap

This plan is a guide, not a promise. Quarters show order of work and will move as players vote and priorities change.
Last updated 2026-11-12. Player ideas and votes come from the [/roadmap](https://ski-manager.net/roadmap) page.

Tags: `[votes: N]` comes from /roadmap · `[beta]` ships behind a beta switch first · `[PR open]` a branch already exists · `proposed` not yet agreed (changes game rules or the economy).

## Shipped recently (since mid-September 2026)

- **Whole site in React + HeroUI**: every player and admin page moved to one component library; DaisyUI removed.
- **Native Android app**: real accounts, trail map, tickets, staff, buildings, grooming, snowmaking, bank and equipment, in Material 3 Expressive.
- **Trail map rework**: buildable runs are shown, the first build is guided, and the map fits phones.
- **Beta program**: players can opt in to new features one at a time and send feedback.
- **Faster pages**: about 58 fewer database queries per page, and a WebP map that loads faster.
- **Enforced Content Security Policy**: browsers now block scripts the site did not approve.
- **Try Demo Mountain**: an instant sandbox that converts to a real account.
- **Better first days**: the tutorial explains the nightly settle, and new players see tonight's estimate.
- **Season votes**: the Season 4 mountain vote and the site-wide season format vote.
- **Monitoring**: Sentry errors, session replay, and alerts when the nightly tick or database backup is missed.

## Q4 2026 (rest of)

### Player features
- **Fix the 28 reported bugs (BUGS.md)** `[PR open]`: closes free morale boosts, free marketing, reputation farming, building race conditions and broken alliance buttons.
  - Branches: fix/server-bugs, fix/web-bugs, fix/simulation-bugs.
  - BUG-28 (unbuilt Génépis boosts) is on fix/audit-bugs-28, which is not pushed (unconfirmed).
- **Resort Alliances cooperative challenges** (in progress on /roadmap): alliance tournaments, shared equipment leasing and joint marketing.

### Tech & infrastructure
- **Merge the open clean-up branch** `[PR open]`: chore/retire-feature-flags removes old switches, including economy_v2 and demo_v2.
  - It conflicts with fix/simulation-bugs on one line in GameTick. Merge fix/simulation-bugs first.
- **Change the Wiki.js database password**: it still uses a password that was exposed; needs a short wiki restart.
- **Check Google Tag Manager under the enforced CSP**: confirm no blocked scripts on pages that load it.

### Mobile apps
- **Android bug fixes** `[PR open]`: fix/android-api-bugs stops the lock-out on the start screen, fixes grooming operator details and unactivated-account sign-in.
- **Keep the Play target current**: the app targets API 36 (compileSdk 37), so it already meets Google Play's requirement. Recheck when Play announces the next one.

## Q1 2027

### Player features
- **Change season and weather on your own resort** `[votes: 2]` `proposed`: the most-voted idea.
  - Weather is shared by all players and drives snow, visitors and income, so this changes game rules.
  - Needs a decision first: for example a sandbox mode only, or a paid Génépis "skip weather" boost.
- **Interactive 3D trail map and fall-line viewer** `[votes: 1]` `[beta]` (in progress on /roadmap): see slope steepness, grooming routes and lift queues.

### Tech & infrastructure
- **CSP nonces (phase 2)**: replace `unsafe-inline` scripts with per-request nonces so injected scripts cannot run.
- **WebP for every resort map**: create WebP copies when a new mountain is added, not only for Park City.

### Mobile apps
- **Push notifications** `[votes: 1]`: blizzard, breakdown and daily check-in alerts, on Android first.

## Q2 2027

### Player features
- **Dynamic alpine weather and avalanche control** `[votes: 0]` `proposed`: multi-day storms, snowpack build-up and ski patrol avalanche work.
- **Trail map revamp, part 2**: follow-up to the first-build work, deferred in November 2026.

### Tech & infrastructure
- **Guest sessions**: let visitors browse without starting a server session, to cut session files and database load.

### Mobile apps
- **iOS app catch-up**: the SwiftUI app (version 2.0.0) has not changed since 2026-10-22. Bring it level with Android's screens and the token API.

## Q3 2027

### Player features
- **Luxury VIP chalets and celebrity guests** `[votes: 0]` `proposed`: high-end lodging, fine dining reservations and VIP reputation.

### Tech & infrastructure
- **Review the feature-flag list again**: retire flags for features that have been on for everyone for a season.

### Mobile apps
- **Push notifications on iOS**: same alerts as Android.

## Q4 2027

### Player features
- **Night skiing power cost balancing** `[votes: 0]` `proposed`: change floodlight running costs at off-peak hours.

### Tech & infrastructure
- **Yearly security and performance audit**: repeat the November 2026 backend audit and compare query counts per page.

### Mobile apps
- **Play target update**: move to the API level Google Play requires for 2028 (unconfirmed until Google announces it).

## Not planned / parked

- Ideas with no votes and no owner stay on /roadmap until they gain votes.
