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
};
export type NavSection = { name: string; icon: string; items: NavItem[] };

export type Stats = {
  cash: string;
  temp: string;
  tempIcon: string;
  weather: string;
  snow: string;
  gameDay: number;
  seasonLength: number;
  genepis: number;
  stars: number;
};

export type Notice = { id: number; title: string; message: string; icon: string; isRead: boolean };

export type Flash = { success?: string; error?: string; warning?: string; errors?: string[] };

export type ShellProps = {
  path: string;
  user: { username: string; isAdmin: boolean };
  nav: NavSection[];
  bonusDue: boolean;
  search: { name: string; url: string; icon: string }[];
  stats: Stats;
  notifications: { unread: number; latest: Notice[] };
  flash: Flash;
  seasonVote?: SeasonVoteState;
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
