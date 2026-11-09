# Ski Manager (`ski-manager.net`) — VPS & Codebase Guide for Claude

## 1. SSH & VPS Access

- **Host / IP**: `72.62.170.146`
- **SSH User**: `root`
- **SSH Key**: Uses the local default SSH key (`~/.ssh/id_ed25519` or `~/.ssh/id_rsa` — passwordless key auth is already configured on this Mac).
- **Quick SSH Command**:
  ```bash
  ssh root@72.62.170.146
  ```
- **Run a one-off remote command**:
  ```bash
  ssh root@72.62.170.146 'command_here'
  ```

---

## 2. Environments & Where Files Live

### Local Workspace (Mac)
- **Local Git Repo**: `/Users/324080/.gemini/antigravity/scratch/ski-manager-repo`
- **GitHub Remote**: `https://github.com/MontaraGroup/Ski-Manager.git` (`main` branch)

### Production & Staging on the VPS (Standalone Docker Compose Stack)
The VPS runs a standalone Docker Compose stack at `/srv/ski-manager` with OpenResty (Nginx), PHP-FPM 8.5, MySQL 9.7, and Wiki.js (PostgreSQL 17).

| Environment | Domain | Host Path on VPS | Path Inside `ski-php` Container | Database Name |
| :--- | :--- | :--- | :--- | :--- |
| **Production** | `https://ski-manager.net` | `/srv/ski-manager/sites/skiv2/ci4` | `/www/sites/skiv2/ci4` | `skimanager_v2` |
| **Staging** | `https://staging.ski-manager.net` | `/srv/ski-manager/sites/skiv2-staging/ci4` | `/www/sites/skiv2-staging/ci4` | `skimanager_staging` |

> **Important Note on Docker Path Mapping**:
> On the VPS host, files live under `/srv/ski-manager/sites/...`, which is bind-mounted into the `ski-php` and `ski-proxy` containers at `/www/sites/...`.

---

## 3. Docker Containers on the VPS

PHP and MySQL run inside Docker containers managed via `/srv/ski-manager/docker-compose.yml`:

- **`ski-php`** (`1panel-php-fpm:8.5.5`): Runs PHP 8.5 + CodeIgniter 4 CLI (`spark`).
  - Lint a PHP file:
    ```bash
    ssh root@72.62.170.146 'docker exec ski-php php -l /www/sites/skiv2/ci4/app/Controllers/Dashboard.php'
    ```
  - Clear CodeIgniter cache:
    ```bash
    ssh root@72.62.170.146 'docker exec ski-php php /www/sites/skiv2/ci4/spark cache:clear'
    ```
  - Run migrations:
    ```bash
    ssh root@72.62.170.146 'docker exec ski-php php /www/sites/skiv2/ci4/spark migrate'
    ```

- **`ski-mysql`** (`mysql:9.7.4`): Runs the MySQL database (also aliased as `1Panel-mysql-Uibp` on the `ski-network` bridge).
  - Read credentials from `/srv/ski-manager/sites/skiv2/ci4/.env` (`database.default.*`).
  - Run a SQL query on Production (`skimanager_v2`):
    ```bash
    ssh root@72.62.170.146 'docker exec ski-mysql mysql -uskiv2 -p"$(grep "database.default.password" /srv/ski-manager/sites/skiv2/ci4/.env | cut -d= -f2 | tr -d " ")" skimanager_v2 -e "SHOW TABLES;"'
    ```

- **`ski-proxy`** (`1panel/openresty:1.31.1.1-2-4-noble`): Host-network OpenResty reverse proxy handling SSL and HTTP routing.
- **`wikijs`** & **`wikijs-db`**: Documentation wiki running Wiki.js v3 on top of PostgreSQL 17.

---

## 4. Site Basics & Tech Stack

- **Framework**: CodeIgniter 4 (PHP 8.5)
- **Authentication**: CodeIgniter Shield (`users` table + `auth_identities` table for `email_password` hashes + `auth_groups_users` for roles like `admin` / `superadmin`) + custom `AdminSession` second-layer gate for `/admin`.
  - Primary admin user: `contact` (ID `2`, email `contact@ski-manager.net`).
- **Frontend Stack**:
  - Server-rendered PHP views in `app/Views/` extending `app/Views/layouts/main.php` (player layout) or `app/Views/layouts/admin.php` (admin panel layout).
  - **CSS**: Tailwind CSS v4 + DaisyUI v5 (`public/css/input.css` compiled via `npm run build` to `public/css/style.css`).
  - **Icons**: FontAwesome 6 (`fa-solid`, `fa-regular`, `fa-brands`).
  - **Interactive Trail Map (`/map`)**: Custom high-FPS HTML5 `<canvas>` pan/zoom map engine with progressive image loading (`_low.jpg` → `_med.jpg` → `.jpg`), SVG/Canvas lift & slope spline rendering, player Build Menu (`#buildDrawer`), and Admin Map Studio (`#adminEditorToolbar` + `#adminInspectorDrawer`) for drawing/editing presets on the mountain.
- **Game Loop & Cron**:
  - Game ticks run via `app/Controllers/Cron.php` / `spark` commands (advancing `game_day`, weather, snowfall, slope conditions, daily visitor traffic, ticket & building revenue, salaries, loans, and equipment maintenance).

---

## 5. Key File & Directory Structure

Inside `/srv/ski-manager/sites/skiv2/ci4` (and local `/Users/324080/.gemini/antigravity/scratch/ski-manager-repo`):

```text
├── app/
│   ├── Config/
│   │   ├── Routes.php              # All URL routes (player routes, /map APIs, /admin routes)
│   │   ├── Filters.php             # Auth, CSRF, AdminAuthFilter, MaintenanceFilter
│   │   └── AuthGroups.php          # Shield groups/permissions
│   ├── Controllers/
│   │   ├── Dashboard.php           # /dashboard executive overview, KPI & cashflow calculations, widget layout AJAX
│   │   ├── ResortMap.php           # /map interactive trail map, build facility/slope/lift endpoints, admin preset drawing APIs
│   │   ├── Resort.php              # /resort operations (open/close resort, ticket pricing, lifts & slopes management)
│   │   ├── Finances.php            # /finances ledger & transactions
│   │   ├── Staff.php               # /staff hiring, training, morale
│   │   ├── Grooming.php            # /grooming trail maintenance
│   │   ├── Snowmaking.php          # /snowmaking snow guns & coverage
│   │   ├── Buildings.php           # /buildings hotels, restaurants, lodges
│   │   ├── TerrainParks.php        # /terrain-parks freestyle parks
│   │   ├── Parking.php             # /parking lots & capacity
│   │   ├── Equipment.php           # /equipment snowcats & fleet
│   │   ├── Weather.php             # /weather forecast & snowpack
│   │   ├── Cron.php                # Daily game tick processor
│   │   └── Admin/                  # /admin controllers (AdminAuth, Users, Resorts, GameSettings, Logs, etc.)
│   ├── Filters/
│   │   └── AdminAuthFilter.php     # Protects /admin routes (checks Shield group + AdminSession)
│   ├── Helpers/
│   │   └── game_helper.php         # Global helpers: currency(), temp(), snow(), speed(), getGameDay(), getSeasonLength(), featureEnabled()
│   ├── Libraries/
│   │   └── AdminSession.php        # Secondary password/session verification for /admin
│   ├── Models/                     # CI4 models (FinanceModel,WeatherModel, DashboardWidgetModel, PresetLiftModel, PresetSlopeModel, etc.)
│   └── Views/
│       ├── layouts/
│       │   ├── main.php            # Main player navbar, sidebar, theme & global scripts
│       │   └── admin.php           # Admin panel layout
│       ├── dashboard/
│       │   └── index.php           # /dashboard view (KPI cards, modular container-query widgets, drag-and-drop customize mode)
│       ├── resort_map/
│       │   └── index.php           # /map view & canvas engine, Build Menu drawer, Admin Map Studio toolbar & inspector
│       └── admin/                  # Admin panel views
├── public/
│   ├── index.php                   # CodeIgniter front controller (DocumentRoot points to public/)
│   ├── css/
│   │   ├── input.css               # Source CSS (Tailwind v4 + custom theme rules)
│   │   └── style.css               # Compiled production CSS bundle
│   └── img/                        # Resort trail maps (e.g., ParkCity.jpg, ParkCity-Big.jpg, *_low.jpg, *_med.jpg) & assets
├── writable/
│   ├── cache/                      # CI4 cache files
│   ├── logs/                       # CI4 error logs (log-YYYY-MM-DD.log)
│   └── session/                    # User session files (keep owned by ubuntu:ubuntu or 0777)
├── ios/                            # Native SwiftUI iOS companion app
└── android/                        # Native Jetpack Compose Android companion app
```

---

## 6. Standard Deployment Workflow

1. **Edit files locally** in `/Users/324080/.gemini/antigravity/scratch/ski-manager-repo`.
2. **If editing CSS (`public/css/input.css`)**, rebuild the stylesheet before deploying:
   ```bash
   npm run build
   ```
3. **Deploy to Production & Staging via `scp` or `git`**:
   ```bash
   # Option A: Direct SCP of modified file(s)
   scp app/Views/dashboard/index.php root@72.62.170.146:/srv/ski-manager/sites/skiv2/ci4/app/Views/dashboard/index.php
   scp app/Views/dashboard/index.php root@72.62.170.146:/srv/ski-manager/sites/skiv2-staging/ci4/app/Views/dashboard/index.php

   # Option B: Commit & push to GitHub, then pull on VPS
   git add -A && git commit -m "Describe change" && git push origin main
   ssh root@72.62.170.146 'cd /srv/ski-manager/sites/skiv2/ci4 && git pull origin main && cd /srv/ski-manager/sites/skiv2-staging/ci4 && git pull origin main'
   ```
4. **Lint & Clear Cache on the VPS**:
   ```bash
   ssh root@72.62.170.146 'docker exec ski-php php /www/sites/skiv2/ci4/spark cache:clear && docker exec ski-php php /www/sites/skiv2-staging/ci4/spark cache:clear'
   ```
5. **Check Error Logs if Needed**:
   ```bash
   ssh root@72.62.170.146 'tail -n 80 /srv/ski-manager/sites/skiv2/ci4/writable/logs/log-$(date +%F).log'
   ```

<!-- entire-agent:begin -->
Read .entire/agent-guide.md for this repository's workflow, source inspection, and verification guidance.
@.entire/agent-guide.md
<!-- entire-agent:end -->
