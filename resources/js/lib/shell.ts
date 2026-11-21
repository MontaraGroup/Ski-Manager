// Props the PHP layout passes to the shell islands (App\Libraries\Shell::props)
import type { SeasonVoteState } from './seasonVote';

export type NavItem = {
  href: string;
  url: string;
  label: string;
  icon: string;
  locked: boolean;
  flagOff: boolean;
  external: boolean;
  bonusDue: boolean;
  /** Not opened yet by this player: shows a "New" mark */
  isNew?: boolean;
  /** Visible because the page is in beta */
  beta: boolean;
};
export type NavSection = { name: string; icon: string; items: NavItem[] };
/** A public page in the visitors' navbar */
export type PublicLink = { href: string; url: string; label: string; icon: string };

export type Stats = {
  cash: string;
  temp: string;
  tempIcon: string;
  weather: string;
  snow: string;
  gameDay: number;
  seasonLength: number;
  /** Server Unix time in seconds, and the zone the resort clock runs in */
  now: number;
  timezone: string;
  genepis: number;
  stars: number;
  liveSkiers?: number | null;
};

/** One notification as NotificationFeed::item() sends it */
export type Notice = {
  id: number;
  title: string;
  message: string;
  icon: string;
  isRead: boolean;
  time: string;
  category: string;
  alert: boolean;
  link: string | null;
  linkLabel: string | null;
  createdAt: string;
};

export type Flash = { success?: string; info?: string; error?: string; warning?: string; errors?: string[] };

export type DemoBarState = {
  cash: string;
  lifts: number;
  runs: number;
  buildings: number;
  prompts: { key: string; title: string; body: string }[];
};

export type ShellProps = {
  path: string;
  user: { username: string; isAdmin: boolean } | null;
  /** The game menu; empty for visitors */
  nav: NavSection[];
  /** The account menu's pages (Settings, Activity Log, Support, Wiki); empty for visitors */
  account: NavItem[];
  /** The visitors' navbar */
  publicNav: PublicLink[];
  bonusDue: boolean;
  search: { name: string; url: string; icon: string }[];
  stats?: Stats | null;
  notifications?: { unread: number; latest: Notice[] } | null;
  flash: Flash;
  demo?: DemoBarState | null;
  seasonVote?: SeasonVoteState | null;
};

/** The two site themes (DESIGN.md): stored the same way the head script reads them */
export type Theme = 'carboncloud' | 'winter';

export function currentTheme(): Theme {
  return document.documentElement.getAttribute('data-theme') === 'winter' ? 'winter' : 'carboncloud';
}

export function applyTheme(theme: Theme) {
  document.documentElement.setAttribute('data-theme', theme);
  try {
    localStorage.setItem('theme', theme);
  } catch {
    // private mode: the choice lasts for this page only
  }
}

export function isActive(path: string, url: string): boolean {
  return path === url || (url !== '/' && path.startsWith(url + '/'));
}

/** The menu section holding the current page, if any: its button and drawer group are marked current */
export function currentSection(nav: NavSection[], path: string): string | null {
  return nav.find((s) => s.items.some((it) => isActive(path, it.url)))?.name ?? null;
}

/** A menu item's states in words (locked, beta, new…), for screen readers; the marks that show them are hidden */
export function itemStates(item: NavItem): string {
  return [
    item.locked && 'locked, unlock it through achievements',
    item.flagOff && 'switched off for players',
    item.beta && 'beta',
    item.bonusDue && 'bonus ready',
    item.isNew && 'new',
    item.external && 'opens in a new tab',
  ].filter(Boolean).join(', ');
}

/** What a screen reader reads for a menu item: its label, then its states */
export function itemText(item: NavItem): string {
  const states = itemStates(item);
  return states ? `${item.label}, ${states}` : item.label;
}
