import { Icon } from '@/components/Icon';
import { Empty, Page, Panel, PostButton } from '@/components/Page';

type Item = { id: number; title: string; message: string; icon: string; isRead: boolean; time: string };
type Props = { filter: 'all' | 'unread'; unreadCount: number; totalCount: number; items: Item[] };

/** /notifications: every alert, with filters and clean-up actions */
export default function NotificationsPage({ filter, unreadCount, totalCount, items }: Props) {
  const tabs: { key: Props['filter']; label: string; href: string; count: number }[] = [
    { key: 'all', label: 'All', href: '/notifications', count: totalCount },
    { key: 'unread', label: 'Unread', href: '/notifications?filter=unread', count: unreadCount },
  ];
  return (
    <Page
      title="Notifications"
      icon="fa-bell"
      subtitle="Weather, events, staff and resort alerts."
      width="max-w-4xl"
      actions={totalCount > 0 && (
        <div className="flex flex-wrap gap-2">
          {unreadCount > 0 && <PostButton action="/notifications/read-all" size="sm">Mark all read</PostButton>}
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
            confirm={{ title: `Delete all ${totalCount} notifications?`, body: 'Every notification is deleted, read or not. This cannot be undone.', confirmLabel: 'Delete all' }}
          >
            Delete all
          </PostButton>
        </div>
      )}
    >
      <nav aria-label="Filter notifications" className="flex w-fit gap-1 rounded-xl bg-default p-1">
        {tabs.map((t) => (
          <a
            key={t.key}
            href={t.href}
            aria-current={filter === t.key ? 'page' : undefined}
            className={`flex min-h-10 pointer-coarse:min-h-11 items-center gap-2 rounded-lg px-4 text-sm font-medium ${filter === t.key ? 'bg-surface shadow-sm' : 'text-muted hover:text-foreground'}`}
          >
            {t.label}
            <span className="tabular-nums text-muted">{t.count}</span>
          </a>
        ))}
      </nav>

      <Panel>
        {items.length === 0 ? (
          <Empty icon="fa-folder-open" title={filter === 'unread' ? 'Nothing unread' : 'No notifications'}>
            {filter === 'unread' ? (
              <a href="/notifications" className="text-link underline">See all notifications</a>
            ) : (
              'Weather changes, events and staff news show up here.'
            )}
          </Empty>
        ) : (
          <ul className="divide-y divide-border">
            {items.map((n) => (
              <li key={n.id}>
                <a href={`/notifications/open/${n.id}`} className="flex items-start gap-3 rounded-lg px-1 py-3 hover:bg-default">
                  <span className={`flex size-9 shrink-0 items-center justify-center rounded-full ${n.isRead ? 'bg-default text-muted' : 'bg-accent-soft text-accent-soft-foreground'}`}>
                    <Icon name={n.icon} />
                  </span>
                  <span className="min-w-0 flex-1">
                    <span className={`block ${n.isRead ? '' : 'font-semibold'}`}>{n.title}</span>
                    <span className="block text-sm text-muted">{n.message}</span>
                    <span className="mt-1 block text-sm text-muted">{n.time}</span>
                  </span>
                  {!n.isRead && <span className="mt-1 shrink-0 rounded-full bg-accent-soft px-2 text-sm text-accent-soft-foreground">New</span>}
                </a>
              </li>
            ))}
          </ul>
        )}
      </Panel>
    </Page>
  );
}
