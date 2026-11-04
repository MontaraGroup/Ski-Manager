# React + HeroUI migration: handoff

For the next agent (Antigravity) continuing the move of the in-game UI from PHP views to React islands with HeroUI. Read this, then `DESIGN.md` (design rules and tokens), then `CLAUDE.md` (server access and deploys).

Status on 2026-11-04: Phases 1, 2 and 3a are merged and live on ski-manager.net. The next step is Phase 3b.

## What's done

| Phase | What | Where |
| --- | --- | --- |
| 1 | Vite + React 19 + TypeScript + HeroUI 3.2.7 build, island mounting, `/api/v1` with `GET /api/v1/me`, admin `/ui-kit` reference page | `vite.config.mts`, `resources/`, `app/Libraries/Vite.php`, `app/Libraries/Islands.php`, `app/Controllers/Api/V1/` |
| 2 | Signed-in shell: navbar, stats bar, phone dock, notifications, search, theme toggle, flash messages as toasts. Guests keep the PHP navbar | `app/Libraries/Shell.php`, `resources/js/islands/AppShell.tsx`, `StatsBar.tsx`, `MobileDock.tsx` |
| 3a | `/weather`, `/finances`, `/leaderboard`, `/activity`, `/achievements`, `/notifications` | `resources/js/islands/*Page.tsx` and their controllers |

## What's left

| Phase | Pages | Notes |
| --- | --- | --- |
| 3b | staff and hire, equipment, snowmaking, grooming, the six building pages (`Buildings::show/*`), parking, scenic lifts, night skiing, tickets, ski lessons, marketing, morale, insurance, compliance, environment, government, real estate, bank | The most forms. Ship it in batches of about 4 to 6 pages per PR. |
| 3c | alliances, tournaments, vote, roadmap, support chat, contact | Live updates and chat. |
| 3d | dashboard (drag-and-drop widgets, customize mode), resort analysis | Heavy state. |
| 3e | `/map` (2,900-line canvas engine in `app/Views/resort_map/index.php`) | Keep the canvas engine as plain JS inside one component; move only the drawers and toolbars to HeroUI. Riskiest, so it goes last. |
| 3f | admin panel | Low risk, few users. |
| 4 | Client-side navigation (optional) | One catch-all route serving the shell. Delete the remaining PHP views and the old component CSS in `public/css/input.css`. |
| 5 | Hardening | Playwright tests for core flows, an antislop pass on every page, a bundle-size budget. |

## How a page is moved (follow the Phase 3a pattern)

1. **Controller:** build a `$page` array with everything the page shows.
   - **Format on the server:** use `currency()`, `temp()`, `snow()`, `speed()` and `number_format()`, because players can choose imperial units. Send strings like `cashText`, not raw numbers to format in JS.
   - **Logic in the old view:** move it into the controller unchanged. Fixing the game math is separate work.
   - **Icons from the database:** pass them only after `preg_match('/^[a-z0-9 -]+$/i', ...)`.
2. **View:** replace the view with one island:
   ```php
   <?= $this->extend('layouts/main') ?>
   <?= $this->section('title') ?>Weather<?= $this->endSection() ?>
   <?= $this->section('content') ?>
   <?= island('WeatherPage', $page, '<p class="max-w-5xl mx-auto p-4 lg:p-8 text-sm">Loading the forecast…</p>') ?>
   <?= $this->endSection() ?>
   ```
3. **Component:** create `resources/js/islands/<Name>Page.tsx` with a default export and its props typed. Register it in `ISLANDS` in `resources/js/main.tsx` (lazy import, so each page is its own chunk).
4. **Shared components:** use the ones in `resources/js/components/Page.tsx`:
   - `Page` (title row with a back link), `Panel`, `Figure`, `Empty` (empty state with the reason and the next action).
   - `PostButton`: a normal form post with the CSRF field. Give it `confirm={{ title, body, confirmLabel }}` for destructive actions.
5. **Actions:** keep them as ordinary form posts to the existing routes. The controller redirects back with `->with('success' | 'error', ...)`, and the shell shows the message as a toast. Only add `/api/v1` endpoints when a page needs to update without a reload.
6. **Build:** run `npm run build:islands` (`tsc` + `vite build`) and commit `public/build`. CI fails the deploy if the committed build is stale, and the build is reproducible.

## Rules and gotchas (learned the hard way)

- **Font Awesome:**
  - The FA kit replaces `<i>` with `<svg>` in the DOM, which React can't reconcile.
  - Always use `<Icon name="fa-..." />` from `components/Icon.tsx`, never a raw `<i>`.
- **Class name collisions:**
  - The old site CSS and HeroUI share 15 class names: `alert avatar badge card checkbox dropdown input kbd label link menu radio select tabs toast`.
  - `resources/css/islands.css` resets them with `all: revert-layer` inside `[data-island]`, `[data-island-layer]` and `.toast-region`.
  - If a new HeroUI component looks wrong, check for another name collision and add it to that list.
- **Overlays:** popovers, modals and drawers render into `#island-layer` (via `UNSAFE_PortalProvider` in `main.tsx`). The toast region goes straight into `<body>`.
- **Alert dialogs:** HeroUI's `AlertDialog` ignores Escape by default. `PostButton` sets `isKeyboardDismissDisabled={false}`, so do the same anywhere else.
- **Tap targets:** 44px on touch screens. Use `pointer-coarse:min-h-11` on custom links and buttons; HeroUI buttons get it from `islands.css`.
- **Contrast:**
  - Use `text-muted`, `text-success`, `text-danger`, `text-warning`, `text-link` and `text-accent-soft-foreground`.
  - Never use opacity for secondary text. All tokens are measured in `DESIGN.md`.
- **Text rules:**
  - No em dashes, no decorative emoji, no endless animations.
  - No invented numbers: every figure comes from game data or the rule classes.
  - A pill that computed "estimated visitors" as `lifts × 80 + slopes × 40` was removed for that reason.
- **Bundle size:**
  - The JS on a signed-in page is about 152 KB gzipped. Load heavy overlays on first open, like `MenuButton` and `SearchDialog` do.
  - The islands CSS is 471 KB (44 KB gzipped) because it imports all of HeroUI. Importing per component is a known to-do.
- **Cloudflare Rocket Loader:** it rewrites script tags. `App\Libraries\Vite` adds `data-cfasync="false"` to the module scripts; keep that.
- **Bundle tags in `<head>`:** `<head>` is printed before the layout body. `Islands::headTags(auth()->loggedIn())` forces the bundle tags for signed-in pages, because the shell islands are mounted later in the layout.
- **Tables:** these pages use plain semantic `<table>` markup with HeroUI tokens. HeroUI's `Table` is a React Aria grid, heavier than read-only lists need.
- **Leave alone:**
  - `app/Views/terrain_parks/index.php`, until its own rework: an earlier terrain-parks branch rewrote it.
  - The `feature/heroui-v3` branch. Its `public/css/heroui-adapter.css` forces dark mode with `!important` and conflicts with `DESIGN.md`; don't use it.

## Testing each batch

1. **Lint:** run `npm run build:islands`, and lint every changed PHP file in the `PHP` container (`docker exec PHP php -l ...`).
2. **Deploy to staging** first:
   - Back up the files you overwrite (`tar czf /root/stg-<name>-<timestamp>.tgz ...`).
   - Then clear the cache (`spark cache:clear`).
   - Staging (`/opt/1panel/www/sites/skiv2-staging/ci4`) is a checkout of the old `staging` branch with files copied over it, so compare its copy with the branch base before overwriting. If they differ, do a 3-way merge with `git merge-file`, and never deploy a file with conflict markers.
3. **Throwaway player:** create one with a small CLI script in `writable/` that boots CodeIgniter, as earlier sessions did. Sign in with curl, fetch each page, and check for 200, valid island props, and no `Whoops` or `ErrorException`. Delete the player and the script afterwards.
4. **Check in a browser:** contrast in both themes (`data-theme="carboncloud"` and `"winter"`), 375px width (no horizontal overflow, 44px controls), keyboard order and focus ring, and Escape on dialogs.
   - A local copy of the signed-in HTML served with the real `style.css` and `public/build` works well for this.
   - The test browser only paints frames on screenshots, so animation timing looks stuck there. Check dialog logic with animations turned off.
5. **Error log:** check `writable/logs/log-<date>.log` for new errors.

## Repositories and deploys

- **Two GitHub repositories:**
  - `Montara-Group/Ski-Manager` (git remote `origin`) is where the branches and PRs go.
  - `MontaraGroup/Ski-Manager` (remote `upstream`) pulls from it with PRs from `Montara-Group/main`.
- **Production deploy:** merging into `main` runs `.github/workflows/production-release.yml`, which resets production to `main` and runs `scripts/deploy.sh` (composer, npm build, `spark migrate`, cache clear).
- **Entire trails:** the owner wanted every change to go through an Entire trail first. Lately they have asked for GitHub PRs instead, so ask which one they want.

## Open issues found along the way (not fixed)

- **Mobile API:** `/api/auth` and `/api/sync` (used by the mobile apps) authenticate nobody. `auth` returns a random token for any username and never stores it; `sync` echoes the cash the client sends.
- **Review findings never fixed:** they are still in the live code:
  - The demo cleanup can delete an account just claimed through `/demo/claim`. Recheck under a row lock.
  - Sentry Session Replay records unmasked text, and the production DSN is hardcoded in `layouts/main.php` and the `env` template.
  - `composer.lock` needs PHP 8.4.1 while `composer.json` allows 8.2.
  - `AgentTracing` and `agent:test` are speculative code with nothing using them.
  - The staff role descriptions hardcode the game tick's numbers.
- **Finances:** `/finances` estimates its per-day rows. With economy v2 the totals come from the last game day, and the remainder shows as "other".
- **Night skiing:** the page shows a revenue boost that the game tick never applies.
- **Duplicate messages:** some PHP views not yet moved still print success and error messages themselves as well as through the layout.
