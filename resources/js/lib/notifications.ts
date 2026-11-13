import { useCallback, useEffect, useRef, useState } from 'react';
import { toast } from '@heroui/react';
import type { Notice } from './shell';

/**
 * The bell and the /notifications page change the same rows. Each change is broadcast on window
 * so the other one updates without a reload: { unread, id } for one, { unread, all, category } for many.
 */
export type ReadChange = { unread: number; id?: number; all?: boolean; category?: string | null };
const EVENT = 'skim:notifications-read';

export function onReadChange(fn: (change: ReadChange) => void): () => void {
  const listener = (e: Event) => fn((e as CustomEvent<ReadChange>).detail);
  window.addEventListener(EVENT, listener);
  return () => window.removeEventListener(EVENT, listener);
}

async function post(path: string, body?: Record<string, string>): Promise<{ unread: number }> {
  const meta = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]');
  const headers: Record<string, string> = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
  if (meta) headers[meta.dataset.header || 'X-CSRF-TOKEN'] = meta.content;
  const res = await fetch(path, {
    method: 'POST',
    credentials: 'same-origin',
    headers,
    body: body ? new URLSearchParams(body) : undefined,
  });
  if (!res.ok) throw new Error(`That did not save (${res.status}). Try again.`);
  const data = (await res.json()) as { unread?: number; csrf_hash?: string };
  if (meta && data.csrf_hash) meta.content = data.csrf_hash;
  return { unread: Number(data.unread) || 0 };
}

export async function markRead(id: number): Promise<number> {
  const { unread } = await post(`/notifications/${id}/read`);
  window.dispatchEvent(new CustomEvent<ReadChange>(EVENT, { detail: { unread, id } }));
  return unread;
}

/** category null marks every category */
export async function markAllRead(category: string | null = null): Promise<number> {
  const { unread } = await post('/notifications/read-all', category ? { category } : undefined);
  window.dispatchEvent(new CustomEvent<ReadChange>(EVENT, { detail: { unread, all: true, category } }));
  return unread;
}

/** "3 min ago", "5 h ago", "Yesterday, 14:05", then the date */
export function timeAgo(iso: string, now = Date.now()): string {
  const then = new Date(iso);
  if (Number.isNaN(then.getTime())) return '';
  const minutes = Math.round((now - then.getTime()) / 60000);
  if (minutes < 1) return 'Just now';
  if (minutes < 60) return `${minutes} min ago`;
  const hours = Math.floor(minutes / 60);
  const clock = then.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
  if (hours < 12) return `${hours} h ago`;
  if (dayLabel(iso, now) === 'Today') return `Today, ${clock}`;
  if (dayLabel(iso, now) === 'Yesterday') return `Yesterday, ${clock}`;
  return `${then.toLocaleDateString(undefined, { month: 'short', day: 'numeric' })}, ${clock}`;
}

/** Heading for the day a notification arrived on, in the player's time zone */
export function dayLabel(iso: string, now = Date.now()): string {
  const then = new Date(iso);
  const start = (d: Date) => new Date(d.getFullYear(), d.getMonth(), d.getDate()).getTime();
  const days = Math.round((start(new Date(now)) - start(then)) / 86400000);
  if (days === 0) return 'Today';
  if (days === 1) return 'Yesterday';
  return then.toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric' });
}

/**
 * Bell state: unread count and latest notifications, refreshed from /api/notifications/live every 30 s
 * while the tab is visible. New ones show as a toast and are announced to screen readers.
 */
export function useNotifications(initial: { unread: number; latest: Notice[] }) {
  const [unread, setUnread] = useState(initial.unread);
  const [latest, setLatest] = useState(initial.latest);
  const [announcement, setAnnouncement] = useState('');
  const seen = useRef(new Set(initial.latest.map((n) => n.id)));

  const refresh = useCallback(async () => {
    try {
      const res = await fetch('/api/notifications/live', { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
      if (!res.ok) return;
      const live = (await res.json()) as { unread: number; items?: Notice[] };
      const list = live.items ?? [];
      const fresh = list.filter((n) => !n.isRead && !seen.current.has(n.id));
      for (const n of fresh) {
        toast(n.title, { description: n.message, actionProps: { children: 'Open', onPress: () => window.location.assign(`/notifications/open/${n.id}`) } });
      }
      for (const n of list) seen.current.add(n.id);
      if (fresh.length) setAnnouncement(fresh.length === 1 ? `New notification: ${fresh[0].title}` : `${fresh.length} new notifications`);
      setUnread(Number(live.unread) || 0);
      setLatest(list);
    } catch {
      // offline or signed out: keep what is shown
    }
  }, []);

  useEffect(() => {
    const tick = () => { if (!document.hidden) refresh(); };
    const timer = window.setInterval(tick, 30000);
    document.addEventListener('visibilitychange', tick);
    const off = onReadChange((c) => {
      setUnread(c.unread);
      if (c.id !== undefined) setLatest((l) => l.map((n) => (n.id === c.id ? { ...n, isRead: true } : n)));
      else refresh();
    });
    return () => {
      window.clearInterval(timer);
      document.removeEventListener('visibilitychange', tick);
      off();
    };
  }, [refresh]);

  return { unread, latest, announcement };
}
