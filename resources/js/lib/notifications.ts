import { useCallback, useEffect, useRef, useState } from 'react';
import { toast } from '@heroui/react';
import type { Notice } from './shell';

type Live = { unread: number; list: { id: number | string; title: string; message: string; icon: string; is_read: number | string | boolean }[] };

/**
 * Unread count and latest notifications, refreshed from /api/notifications/live every 30 s
 * while the tab is visible. Notifications that arrive after the page loaded show as a toast.
 */
export function useNotifications(initial: { unread: number; latest: Notice[] }) {
  const [unread, setUnread] = useState(initial.unread);
  const [latest, setLatest] = useState(initial.latest);
  const seen = useRef(new Set(initial.latest.map((n) => n.id)));

  const refresh = useCallback(async () => {
    try {
      const res = await fetch('/api/notifications/live', { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
      if (!res.ok) return;
      const live = (await res.json()) as Live;
      const list: Notice[] = (live.list ?? []).slice(0, 5).map((n) => ({
        id: Number(n.id),
        title: String(n.title ?? ''),
        message: String(n.message ?? ''),
        icon: /^[a-z0-9 -]+$/i.test(String(n.icon ?? '')) ? String(n.icon) : 'fa-solid fa-bell',
        isRead: n.is_read === true || Number(n.is_read) === 1,
      }));
      for (const n of list) {
        if (!n.isRead && !seen.current.has(n.id)) {
          toast(n.title, { description: n.message, actionProps: { children: 'Open', onPress: () => window.location.assign(`/notifications/open/${n.id}`) } });
        }
        seen.current.add(n.id);
      }
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
    return () => {
      window.clearInterval(timer);
      document.removeEventListener('visibilitychange', tick);
    };
  }, [refresh]);

  const markAllRead = useCallback(async () => {
    const meta = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]');
    const res = await fetch('/notifications/read-all', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest', ...(meta ? { [meta.dataset.header || 'X-CSRF-TOKEN']: meta.content } : {}) },
    });
    if (!res.ok) throw new Error(`Could not mark them read (${res.status}). Try again.`);
    await refresh();
  }, [refresh]);

  return { unread, latest, markAllRead };
}
