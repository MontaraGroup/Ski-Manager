import { useEffect, useMemo, useRef, useState } from 'react';
import { Button, toast } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Empty, Page, Panel, PostButton } from '@/components/Page';
import { dayLabel, markAllRead, markRead, onReadChange, timeAgo } from '@/lib/notifications';
import type { Notice } from '@/lib/shell';

type Category = { key: string; label: string; total: number; unread: number };
type Props = {
  filter: 'all' | 'unread';
  category: string | null;
  unreadCount: number;
  totalCount: number;
  categories: Category[];
  items: Notice[];
  nextCursor: number | null;
};

function href(filter: Props['filter'], category: string | null): string {
  const q = new URLSearchParams();
  if (filter === 'unread') q.set('filter', 'unread');
  if (category) q.set('category', category);
  const s = q.toString();
  return s ? `/notifications?${s}` : '/notifications';
}

const chip = (active: boolean) =>
  `flex min-h-11 items-center gap-1.5 rounded-lg px-3 text-sm font-medium sm:min-h-9 ${active ? 'bg-surface text-foreground shadow-sm' : 'text-muted hover:text-foreground'}`;

/** /notifications: full history by day, with status and category filters */
export default function NotificationsPage(props: Props) {
  const { filter, category } = props;
  const [items, setItems] = useState(props.items);
  const [cursor, setCursor] = useState(props.nextCursor);
  const [loading, setLoading] = useState(false);
  const [unread, setUnread] = useState(props.unreadCount);
  const [categories, setCategories] = useState(props.categories);
  const [status, setStatus] = useState('');
  const listRef = useRef<HTMLDivElement>(null);
  const itemsRef = useRef(items);
  itemsRef.current = items;
  const categoryLabel = categories.find((c) => c.key === category)?.label ?? null;
  const shownUnread = category ? categories.find((c) => c.key === category)?.unread ?? 0 : unread;

  // Reads done in the bell show here too
  useEffect(
    () =>
      onReadChange((c) => {
        setUnread(c.unread);
        if (c.id !== undefined) {
          const hit = itemsRef.current.find((n) => n.id === c.id && !n.isRead);
          if (hit) setCategories((cs) => cs.map((x) => (x.key === hit.category ? { ...x, unread: Math.max(0, x.unread - 1) } : x)));
          setItems((list) => list.map((n) => (n.id === c.id ? { ...n, isRead: true } : n)));
        } else if (c.all) {
          setItems((list) => list.map((n) => (!c.category || n.category === c.category ? { ...n, isRead: true } : n)));
          setCategories((cs) => cs.map((x) => (!c.category || x.key === c.category ? { ...x, unread: 0 } : x)));
        }
      }),
    [],
  );

  const groups = useMemo(() => {
    const out: { day: string; items: Notice[] }[] = [];
    for (const n of items) {
      const day = dayLabel(n.createdAt);
      if (out.length && out[out.length - 1].day === day) out[out.length - 1].items.push(n);
      else out.push({ day, items: [n] });
    }
    return out;
  }, [items]);

  const readOne = async (n: Notice) => {
    // Keyboard users go on to the next unread one; otherwise focus stays on this row's link
    const rows = Array.from(listRef.current?.querySelectorAll<HTMLElement>('[data-mark-read]') ?? []);
    const next = rows[rows.findIndex((el) => el.dataset.markRead === String(n.id)) + 1];
    const own = listRef.current?.querySelector<HTMLElement>(`[data-open="${n.id}"]`);
    try {
      await markRead(n.id);
      (next ?? own)?.focus();
    } catch (e) {
      toast.danger((e as Error).message);
    }
  };

  const readAll = async () => {
    try {
      await markAllRead(category);
      setStatus(categoryLabel ? `${categoryLabel} notifications marked as read.` : 'All notifications marked as read.');
      const h1 = document.querySelector<HTMLElement>('main h1, h1');
      if (h1) { h1.tabIndex = -1; h1.focus(); }
    } catch (e) {
      toast.danger((e as Error).message);
    }
  };

  const loadMore = async () => {
    if (!cursor) return;
    setLoading(true);
    try {
      const q = new URLSearchParams({ before: String(cursor) });
      if (filter === 'unread') q.set('filter', 'unread');
      if (category) q.set('category', category);
      const res = await fetch(`/notifications/feed?${q}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
      if (!res.ok) throw new Error(`Could not load more (${res.status}). Try again.`);
      const data = (await res.json()) as Props;
      const firstNew = data.items[0]?.id;
      setItems((list) => [...list, ...data.items]);
      setCursor(data.nextCursor);
      setStatus(`${data.items.length} more loaded.`);
      if (firstNew) requestAnimationFrame(() => listRef.current?.querySelector<HTMLElement>(`[data-open="${firstNew}"]`)?.focus());
    } catch (e) {
      toast.danger((e as Error).message);
    } finally {
      setLoading(false);
    }
  };

  const emptyTitle = filter === 'unread' ? (categoryLabel ? `No unread ${categoryLabel.toLowerCase()} notifications` : 'Nothing unread') : categoryLabel ? `No ${categoryLabel.toLowerCase()} notifications` : 'No notifications';

  return (
    <Page
      title="Notifications"
      icon="fa-bell"
      subtitle={unread > 0 ? `${unread} unread` : 'All caught up'}
      width="max-w-3xl"
      actions={
        shownUnread > 0 && (
          // Own line on phones so the title keeps its width
          <div className="basis-full sm:basis-auto">
            <Button size="sm" variant="secondary" onPress={readAll} className="min-h-11 sm:min-h-9">
              {categoryLabel ? `Mark ${categoryLabel.toLowerCase()} read` : 'Mark all read'}
            </Button>
          </div>
        )
      }
    >
      <p role="status" aria-live="polite" className="sr-only">{status}</p>

      <div className="flex flex-col gap-2">
        <nav aria-label="Show" className="flex w-fit gap-1 rounded-xl bg-default p-1">
          <a href={href('all', category)} aria-current={filter === 'all' ? 'page' : undefined} className={chip(filter === 'all')}>All</a>
          <a href={href('unread', category)} aria-current={filter === 'unread' ? 'page' : undefined} className={chip(filter === 'unread')}>
            Unread <span className="tabular-nums text-muted">{unread}</span>
          </a>
        </nav>
        <nav aria-label="Category" className="flex flex-wrap gap-1">
          <a href={href(filter, null)} aria-current={!category ? 'page' : undefined} className={`${chip(!category)} border border-border`}>Everything</a>
          {categories.filter((c) => c.total > 0 || c.key === category).map((c) => (
            <a key={c.key} href={href(filter, c.key)} aria-current={category === c.key ? 'page' : undefined} className={`${chip(category === c.key)} border border-border`}>
              {c.label}
              {c.unread > 0 && (
                <span className="rounded-full bg-accent-soft px-1.5 text-xs tabular-nums text-accent-soft-foreground">
                  {c.unread}<span className="sr-only"> unread</span>
                </span>
              )}
            </a>
          ))}
        </nav>
      </div>

      <div ref={listRef} className="flex flex-col gap-4">
        {items.length === 0 ? (
          <Panel>
            <Empty icon="fa-folder-open" title={emptyTitle}>
              {filter === 'unread' || category ? (
                <a href="/notifications" className="text-link underline">See all notifications</a>
              ) : (
                'Breakdowns, finished builds, VIP guests, achievements and game news show up here.'
              )}
            </Empty>
          </Panel>
        ) : (
          groups.map((g, i) => (
            <section key={g.day} aria-labelledby={`notif-day-${i}`} className="flex flex-col gap-1">
              <h2 id={`notif-day-${i}`} className="px-1 text-sm font-semibold text-muted">{g.day}</h2>
              <ul className="divide-y divide-border rounded-xl border border-border bg-surface">
                {g.items.map((n) => (
                  <Row key={n.id} n={n} onRead={() => readOne(n)} />
                ))}
              </ul>
            </section>
          ))
        )}
        {cursor && (
          <Button variant="secondary" onPress={loadMore} isPending={loading} className="min-h-11 self-center">
            Load older notifications
          </Button>
        )}
      </div>

      {props.totalCount > 0 && (
        <Panel title="Clean up">
          <p className="mb-3 text-sm text-muted">Read notifications are removed after 30 days and unread ones after 90.</p>
          <div className="flex flex-wrap gap-2">
            <PostButton
              action="/notifications/delete-read"
              size="sm"
              variant="tertiary"
              confirm={{ title: 'Clear read notifications?', body: 'Notifications you have already read are deleted. Unread ones stay.', confirmLabel: 'Clear read' }}
            >
              Clear read
            </PostButton>
            <PostButton
              action="/notifications/delete-all"
              size="sm"
              variant="danger"
              confirm={{ title: `Delete all ${props.totalCount} notifications?`, body: 'Every notification is deleted, read or not. This cannot be undone.', confirmLabel: 'Delete all' }}
            >
              Delete all
            </PostButton>
          </div>
        </Panel>
      )}
    </Page>
  );
}

function Row({ n, onRead }: { n: Notice; onRead: () => void }) {
  const tone = n.alert ? 'bg-warning/15 text-warning' : n.isRead ? 'bg-default text-muted' : 'bg-accent-soft text-accent-soft-foreground';
  return (
    <li className="flex items-start gap-1 p-1">
      <a href={`/notifications/open/${n.id}`} data-open={n.id} className="flex min-w-0 flex-1 items-start gap-3 rounded-lg p-2 hover:bg-default">
        <span className={`flex size-9 shrink-0 items-center justify-center rounded-full ${tone}`}>
          <Icon name={n.icon} />
        </span>
        <span className="min-w-0 flex-1">
          <span className="flex flex-wrap items-baseline gap-x-2">
            <span className={`break-words ${n.isRead ? '' : 'font-semibold'}`}>{n.title}</span>
            {!n.isRead && <span className="rounded-full bg-accent-soft px-2 text-xs font-medium text-accent-soft-foreground">New</span>}
          </span>
          <span className="block break-words text-sm text-muted">{n.message}</span>
          <span className="mt-1 block text-sm text-muted">
            <time dateTime={n.createdAt} title={n.time}>{timeAgo(n.createdAt)}</time>
            {n.linkLabel && <span> · Open {n.linkLabel}</span>}
          </span>
        </span>
      </a>
      {!n.isRead && (
        <Button isIconOnly variant="ghost" aria-label={`Mark read: ${n.title}`} data-mark-read={n.id} className="mt-1 size-11 shrink-0" onPress={onRead}>
          <Icon name="fa-check" />
        </Button>
      )}
    </li>
  );
}
