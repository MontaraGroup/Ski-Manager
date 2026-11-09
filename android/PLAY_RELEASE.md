# Releasing on Google Play

Package: `net.skimanager.app`. Target API 36, which Play requires for new apps and updates ([Play Console Help](https://support.google.com/googleplay/android-developer/answer/11926878)).

## Signing

The app uses Play App Signing: Google holds the app signing key, and you sign uploads with the upload key.

1. Wait for Google to approve the upload key reset. Until then, uploads signed with the new key are refused.
2. Keep `skimanager-upload.jks` and its password backed up outside this repo. Losing them means another reset.
3. `public/.well-known/assetlinks.json` lists Play's app signing fingerprints from **Setup > App signing**. It only changes if those do.

## Each release

1. Raise `VERSION_CODE` in `android/gradle.properties`. It must beat every code already uploaded. Check under **Test and release > App bundle explorer**.
2. Build with `./gradlew bundleRelease`, or use the artifact from the GitHub workflow.
3. Upload `app-release.aab` to the internal testing track first, install from there, and sign in.
4. Promote to production.

## Data safety answers

Based on what this version of the app does. Check them against the current code and the website before submitting.

| Question | Answer |
|---|---|
| Collects or shares user data? | Collects. Not shared with third parties by the app |
| Personal info | Email address and username: collected, required, for account management and app functionality |
| App activity | In-game actions (buildings, staff, prices): collected, required, for app functionality |
| Identifiers, location, contacts, photos, financial info, health | Not collected by the app |
| Crash logs, analytics | Not collected by the app; it has no analytics or crash SDK |
| Encrypted in transit | Yes, HTTPS only |
| Can users request deletion? | Yes, in the app under Settings > Delete account, and on the website under Settings |

The server also logs IP addresses for rate limits and security, and the website has its own analytics. Check whether your privacy policy covers both.

**Account deletion URL** for the form: `https://ski-manager.net/settings`. It requires signing in.

**Privacy policy URL:** `https://ski-manager.net/privacy`

**Ads:** none. **In-app purchases:** none.

## Store listing assets you need to supply

- App icon: 512 x 512 PNG
- Feature graphic: 1024 x 500 PNG or JPEG
- Phone screenshots: at least 2, from a real build
- Short description (up to 80 characters) and full description (up to 4000)
- Content rating questionnaire and target audience answers
