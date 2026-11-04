import { StrictMode, Suspense, lazy, type ComponentType } from 'react';
import { createRoot } from 'react-dom/client';
import { UNSAFE_PortalProvider } from 'react-aria';
import { Spinner, Toast } from '@heroui/react';
import { IslandErrorBoundary } from './components/IslandErrorBoundary';
import '../css/islands.css';

/**
 * React islands inside the PHP pages. A view calls island('Name', $props) (island_helper.php),
 * which prints a mount point plus its props as JSON; this file mounts the matching component.
 * Each island is its own chunk, so a page only downloads the islands it uses.
 *
 * To add one: create resources/js/islands/Name.tsx with a default export, and list it here.
 */
const ISLANDS: Record<string, () => Promise<{ default: ComponentType<any> }>> = {
  AppShell: () => import('./islands/AppShell'),
  StatsBar: () => import('./islands/StatsBar'),
  MobileDock: () => import('./islands/MobileDock'),
  UiKit: () => import('./islands/UiKit'),
  ActivityPage: () => import('./islands/ActivityPage'),
  AchievementsPage: () => import('./islands/AchievementsPage'),
  FinancesPage: () => import('./islands/FinancesPage'),
  LeaderboardPage: () => import('./islands/LeaderboardPage'),
  NotificationsPage: () => import('./islands/NotificationsPage'),
  WeatherPage: () => import('./islands/WeatherPage'),
  StaffPage: () => import('./islands/StaffPage'),
  StaffHirePage: () => import('./islands/StaffHirePage'),
  EquipmentPage: () => import('./islands/EquipmentPage'),
  SnowmakingPage: () => import('./islands/SnowmakingPage'),
  GroomingPage: () => import('./islands/GroomingPage'),
  ParkingPage: () => import('./islands/ParkingPage'),
  ScenicLiftsPage: () => import('./islands/ScenicLiftsPage'),
  TicketsPage: () => import('./islands/TicketsPage'),
  MarketingPage: () => import('./islands/MarketingPage'),
  NightSkiingPage: () => import('./islands/NightSkiingPage'),
  SkiLessonsPage: () => import('./islands/SkiLessonsPage'),
  MoralePage: () => import('./islands/MoralePage'),
  BuildingPage: () => import('./islands/BuildingPage'),
};

function readProps(el: HTMLElement): Record<string, unknown> {
  const script = el.nextElementSibling;
  if (!(script instanceof HTMLScriptElement) || script.type !== 'application/json') return {};
  try {
    return JSON.parse(script.textContent || '{}');
  } catch (err) {
    console.error(`Island ${el.dataset.island}: props are not valid JSON`, err);
    return {};
  }
}

/**
 * Popovers, modals and menus render here instead of straight into <body>. The layer
 * carries data-island-layer, which islands.css uses to keep the site's older CSS classes
 * (.card, .menu, .modal ...) off HeroUI elements, the same as inside an island.
 */
function overlayLayer(): HTMLElement {
  let layer = document.getElementById('island-layer');
  if (!layer) {
    layer = document.createElement('div');
    layer.id = 'island-layer';
    layer.dataset.islandLayer = '';
    document.body.appendChild(layer);
  }
  return layer;
}

function mount(el: HTMLElement) {
  const name = el.dataset.island ?? '';
  const load = ISLANDS[name];
  if (!load || el.dataset.mounted) return;
  el.dataset.mounted = 'true';
  const Island = lazy(load);
  createRoot(el).render(
    <StrictMode>
      <UNSAFE_PortalProvider getContainer={overlayLayer}>
        <IslandErrorBoundary name={name}>
          <Suspense fallback={<Spinner aria-label="Loading" />}>
            <Island {...readProps(el)} />
          </Suspense>
        </IslandErrorBoundary>
      </UNSAFE_PortalProvider>
    </StrictMode>,
  );
}

// One toast region per page, shared by every island (toast() from @heroui/react)
function mountToasts() {
  const host = document.createElement('div');
  overlayLayer().appendChild(host);
  createRoot(host).render(<Toast.Provider placement="bottom end" />);
}

function boot() {
  mountToasts();
  document.querySelectorAll<HTMLElement>('[data-island]').forEach(mount);
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
else boot();
