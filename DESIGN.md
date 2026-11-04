# Ski Manager design direction

Direction for every UI change on ski-manager.net: the in-game React pages, the PHP pages still being migrated, and the public pages. It is the source the antislop rules check against (`~/.claude/skills/antislop`).

Stack: HeroUI v3, pinned to **3.2.7** (`@heroui/react` and `@heroui/styles`), Tailwind CSS v4, React 19. Upgrade HeroUI only on purpose, in its own PR.

## Identity

Ski Manager is a ski resort management game played on a real trail map. The player runs the resort in season, day by day: lifts, runs, snow, staff and money. The UI is the resort office: dense with numbers, calm, readable at a glance in the evening after work.

- **What it should feel like:** a well-run resort's operations board. Clear, practical, a little alpine.
- **What it should not feel like:** a SaaS dashboard template, a crypto app, a neon "gaming" UI.
- **Identity motif:** trail-difficulty signs. The real North American trail-sign shapes are the game's visual vocabulary:
  - green circle ● (easy)
  - blue square ■ (intermediate)
  - black diamond ◆ (advanced)
  - double black diamond ◆◆ (expert)

  Use them wherever difficulty, grade or level is shown. The same family includes the snow report board on the home page (light paper panel, dark header strip, big tabular numbers) for weather and snow data.

## Dials

| Surface | ENERGY | RHYTHM | MOTION |
| --- | --- | --- | --- |
| In-game pages | 1 | 2 | 1 |
| Public pages (home, guides) | 2 | 2 | 1 |
| Map (`/map`) | 2 | 1 | 2 |

- **ENERGY 1 in game:** the numbers are the content, and decoration competes with them.
- **RHYTHM 2:** a consistent page frame, with sections that vary by what they hold (a table, a form, a gauge). Not every section is a card grid.
- **MOTION 1:** state feedback only (hover, press, open, close). Nothing loops. The map is the exception: pan, zoom and progressive image loading are its job.

## Themes

Two themes, both fully supported, chosen by the player (navbar toggle, saved in `localStorage`). Dark is the default: players mostly play in the evening, and the trail map reads better on a dark frame.

| Theme | Name shown to players | HeroUI selector |
| --- | --- | --- |
| Dark | Carbon Cloud | `[data-theme="dark"]` |
| Light | Winter Alpine | `[data-theme="light"]` |

The current site uses `data-theme="carboncloud"` and `data-theme="winter"`. When the HeroUI theme lands, map the stored values once (`carboncloud` becomes `dark`, `winter` becomes `light`) and use HeroUI's selectors from then on. Never set `color-scheme` outside the theme blocks: an inline `color-scheme` broke the light theme once (antislop audit 001, item 1).

## Color tokens

These override HeroUI's default theme variables, which go in `[data-theme="dark"]` and `[data-theme="light"]`. Every pair was measured at WCAG AA or better (text 4.5:1, large text 3:1). Values marked "measured" are the ratio against the theme's `--background` and `--surface`.

### Carbon Cloud (dark)

| Token | Value | Use | Measured |
| --- | --- | --- | --- |
| `--background` | `oklch(17% 0.015 260)` | page | |
| `--surface` | `oklch(22% 0.02 260)` | cards, panels | |
| `--surface-secondary` | `oklch(25% 0.02 260)` | nested panels, table headers | |
| `--foreground` | `oklch(90% 0.02 240)` | body text | 14.2 / 12.9 |
| `--muted` | `oklch(72% 0.02 250)` | secondary text, labels | 7.7 / 7.0 |
| `--accent` | `oklch(52% 0.18 245)` | primary button fill, selected state | white text 4.95 |
| `--accent-foreground` | `var(--snow)` | text on accent | |
| `--link` | `oklch(70% 0.15 245)` | links, blue text on panels | 7.2 / 6.6 |
| `--success` | `oklch(72% 0.18 155)` | cash, income, open | 8.3 / 7.5 |
| `--success-foreground` | `var(--eclipse)` | text on success fills | 7.7 |
| `--warning` | `oklch(75% 0.15 65)` | maintenance due, low snow | 8.3 / 7.5 |
| `--warning-foreground` | `var(--eclipse)` | | 7.7 |
| `--danger` | `oklch(66% 0.18 25)` | broken, debt, delete | 5.7 / 5.1 |
| `--danger-foreground` | `var(--eclipse)` | dark text on danger fills (white fails on this lightness) | 5.2 |
| `--accent-soft-foreground` | `var(--link)` | text on secondary buttons and soft accent fills | 5.5 on `--default` |

### Winter Alpine (light)

| Token | Value | Use | Measured |
| --- | --- | --- | --- |
| `--background` | `oklch(96% 0.008 260)` | page | |
| `--surface` | `oklch(99.5% 0.002 260)` | cards, panels | |
| `--surface-secondary` | `oklch(93% 0.01 260)` | nested panels, table headers | |
| `--foreground` | `oklch(28% 0.04 260)` | body text | 13.0 / 14.4 |
| `--muted` | `oklch(47% 0.03 260)` | secondary text, labels | 6.1 / 6.7 |
| `--accent` | `oklch(48% 0.2 260)` | primary button fill | white text 6.4 |
| `--link` | `oklch(48% 0.2 260)` | links | 6.1 / 6.7 |
| `--success` | `oklch(48% 0.15 155)` | | 5.2 / 5.8, white text 5.5 |
| `--warning` | `oklch(50% 0.15 60)` | | 5.6 / 6.2, white text 5.9 |
| `--danger` | `oklch(52% 0.2 25)` | | 5.5 / 6.0, white text 5.8 |

In the light theme every status `-foreground` is `var(--snow)`.

### Palette rules

- **Core colors:** the blue accent and the three status colors (success, warning, danger), on neutral surfaces. No purple, magenta or teal "secondary" color: the old `--s` and `--a` tokens go away.
- **Status colors mean status.** Green means money in, or open; orange means attention needed; red means broken, debt or destructive. Never color a page header or card by page.
- **One focal accent per screen:** the primary action. Not every button is primary.
- **Muted text** uses `--muted`, never a lower opacity of `--foreground`. Anything below 4.5:1 is a bug.
- **Trail-sign colors** (`--sign-green`, `--sign-blue`, and `--foreground` for black) are only used for difficulty marks, always with the shape, so color is never the only signal. Per theme they are green `oklch(66% 0.17 150)` / `oklch(52% 0.15 150)` and blue `oklch(60% 0.17 258)` / `oklch(50% 0.17 258)` (dark / light), all 4.5:1 or more on the page.

## Typography

- **Font:** Inter, already loaded, weights 400 / 500 / 600 / 700. Reason: the game is mostly numbers and labels at small sizes. Inter stays legible at 12 to 14px and has tabular figures.
- **Numbers:** every column, KPI and price uses `font-variant-numeric: tabular-nums`, so digits line up. Monospace (`font-mono`) is only for codes and IDs, not for money or stats.
- **Scale:** 12 / 14 / 16 / 20 / 28 / 34-52 (the home page h1 is fluid).
- **Smallest text:** 12px. No 10 or 11px text, which includes the mobile dock labels.
- **Letter case:** sentence case for headings, buttons and labels. No uppercase wide-tracked labels. The one exception is the snow report board header, which mimics a real resort board.

## Shape, depth, surfaces

- **Radius:** HeroUI default `--radius: 0.5rem`, so fields are `0.75rem`. Cards and modals use `--radius * 1.5`. Pills are only for chips, switches and avatars.
- **Shadow:** HeroUI defaults. In the dark theme surfaces are flat (no shadow), and in the light theme they get the subtle `--surface-shadow`. Only overlays (modals, popovers, menus, toasts) lift. No colored glow anywhere.
- **Blur:** the sticky navbar only. Not on cards, modals, the dock or panels.
- **Gradients:** two only:
  - the dark overlay on the home hero photo, where it keeps text readable over the map
  - the season progress track
  
  No tinted gradient headers.

## Layout

- **In-game frame:** a sticky navbar with the stats bar (cash, weather, snow base, Génépis), the page title row with its primary action on the right, then content at most `max-w-7xl`.
- **Page body:** built from what the page decides. For example, staff is "who do I hire or assign", so the open posts come first, the roster second and the help last. Use a table for lists of things with the same fields, a form for settings, and gauges (HeroUI Meter) for condition and capacity. Cards only where the items really are separate objects.
- **Spacing:** the Tailwind 4px scale. Use 16px gutters on phones, 24px between sections, 8 to 12px inside groups. Sections are separated by space, not by boxes.
- **Responsive:**
  - Every page works at 375px wide with no horizontal scroll.
  - Tables collapse to stacked rows below `md`.
  - The mobile dock replaces the desktop nav below `md`.
- **Tap targets:** at least 44px on touch screens (`@media (pointer: coarse)`), for every button, link outside running text and menu row.

## Components (HeroUI v3)

| Need | HeroUI component | Notes |
| --- | --- | --- |
| Actions | `Button` (`primary`, `secondary`, `tertiary`, `danger`) | One primary per view. Labels name the action: "Hire Lucas", "Repair for 2,400 €", not "Submit" |
| Status labels | `Chip` | Open / Closed / Broken / Active. Text always, color second |
| Counts on icons | `Badge` | Notification count only |
| Messages | `Toast` for results of an action; `Alert` for a state on the page (low cash, closed resort) | Replaces session flash blocks |
| Panels | `Surface`, `Card` | Card only for separate objects (one building, one lift) |
| Lists | `Table` | Sortable where players compare (leaderboard, staff) |
| Condition, capacity | `Meter` | Lift condition, snow base, staff morale |
| Progress over time | `ProgressBar` | Season day, construction |
| Settings | `Switch`, `Checkbox`, `RadioGroup`, `Select`, `NumberField`, `TextField` | Every field has a visible `Label` |
| Choices | `Tabs`, `ToggleButtonGroup` | Filters on lists |
| Overlays | `Modal` (confirm, forms), `Drawer` (build menu on the map), `Popover`, `Tooltip` | Confirm costs before spending: "Build Blue run for 48,000 €?" |
| Menus | `Dropdown`, `Menu` | Account menu, nav sections |
| Loading | `Skeleton` with `--skeleton-animation: none`, `Spinner` inside buttons | No shimmer loops |
| Empty | `EmptyState` | Says why it's empty and gives the one action that fills it: "No staff yet. Hire a ski patroller to open your first slope." |

Not in HeroUI, kept as site components: the stats bar, the snow report board, trail-sign difficulty marks and the map canvas.

## States

Every view that shows data has all of these:

- **Loading:** a skeleton shaped like the real content, or a spinner in the button that was pressed.
- **Empty:** the cause and the next action.
- **Error:** what failed and what to do. "Could not hire: you need 3,200 € more." Never just "Error".
- **Permission:** a page the player hasn't unlocked says how to unlock it.
- **Disabled controls** say why, in a tooltip or helper text: "Needs a mechanic".

## Motion

- **Allowed:**
  - HeroUI's own state transitions: press scale, overlay open and close, toast in and out. They stay at 200ms or less.
  - Map pan and zoom.
- **Not allowed:** pulse, ping, bounce, floating or any loop. A live or new state is shown with a static dot or chip.
- **Reduced motion:** everything respects `prefers-reduced-motion: reduce`.

## Copy

- **Voice:** plain, specific, and in the game's units. Talk like a resort manager, not like a marketing page.
- **Money:** always through `currency()`, in euros. Distances and temperatures through `snow()`, `temp()` and `speed()`.
- **Numbers:** every number shown comes from game data or the rule classes (Economy, StaffEffects, Grooming, Snowmaking, Weather). No invented stats, no example numbers in the UI.
- **Banned:**
  - em dashes
  - decorative emoji
  - "AI powered", "seamless", "ultimate", "powerful"
  - generic CTAs ("Get started", "Learn more")
- **Messages:** say what happened and what changed: "Hired Lucas Martin as ski patrol. Salary 180 € a day."

## Accessibility

- WCAG 2.2 AA: contrast as above, a visible focus ring (HeroUI `--focus`, 2px), and full keyboard use. Modals trap focus and close on Escape.
- Every icon-only control has an `aria-label`; decorative icons have `aria-hidden="true"`.
- The page has landmarks (`header`, `nav`, `main`, `footer`), one `h1`, and no skipped heading levels.
- Images and the map have sizes set, so nothing shifts while loading. The hero image is not lazy-loaded.
- Third-party scripts are `defer` or `async`.

## How changes are checked

Before a page ships:
1. Run the antislop Delivery Gate.
2. Check contrast in both themes at 375px and desktop.
3. Click through every control, checking keyboard-only too.
4. Check the console for errors.
