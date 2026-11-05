import { useState } from 'react';
import { Button } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Page, Panel } from '@/components/Page';

export type ActivityEntry = {
  id: number;
  user_id: number;
  username: string;
  game_day: number;
  category: string;
  message: string;
  icon: string;
  timeFormatted: string;
};

export type AdminActivityPageProps = {
  logs: ActivityEntry[];
  total: number;
  page: number;
  perPage: number;
  totalPages: number;
  filter: string;
  userFilter: string;
};

export default function AdminActivityPage({
  logs,
  total,
  page,
  perPage,
  totalPages,
  filter,
  userFilter,
}: AdminActivityPageProps) {
  const [keyword, setKeyword] = useState(filter);
  const [user, setUser] = useState(userFilter);

  const getPageUrl = (targetPage: number) => {
    const params = new URLSearchParams();
    params.set('page', String(targetPage));
    if (filter) params.set('filter', filter);
    if (userFilter) params.set('user', userFilter);
    return `/admin/activity?${params.toString()}`;
  };

  return (
    <Page
      title="Activity Log"
      icon="fa-clock-rotate-left"
      iconTone="text-accent"
      back="/admin"
      width="max-w-6xl"
      subtitle={`Global real-time tracking across player actions and automation ticks · ${total.toLocaleString()} total logged events`}
    >
      <div className="space-y-4">
        {/* Search & Filter Toolbar */}
        <Panel>
          <form method="get" action="/admin/activity" className="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div className="flex-1 space-y-1">
              <label className="text-xs font-semibold text-muted">Message Keyword</label>
              <div className="relative">
                <Icon
                  name="fa-magnifying-glass"
                  className="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-muted"
                />
                <input
                  type="text"
                  name="filter"
                  value={keyword}
                  onChange={(e) => setKeyword(e.target.value)}
                  placeholder="Filter by keyword (e.g. ticket, groom, loan)..."
                  className="w-full rounded-xl border border-border bg-surface-raised py-2 pl-9 pr-3 text-xs text-foreground placeholder:text-muted focus:border-accent focus:outline-hidden"
                />
              </div>
            </div>

            <div className="w-full sm:w-44 space-y-1">
              <label className="text-xs font-semibold text-muted">Filter by User ID</label>
              <input
                type="number"
                name="user"
                value={user}
                onChange={(e) => setUser(e.target.value)}
                placeholder="User ID..."
                className="w-full rounded-xl border border-border bg-surface-raised px-3 py-2 text-xs font-mono text-foreground placeholder:text-muted focus:border-accent focus:outline-hidden"
              />
            </div>

            <div className="flex items-center gap-2">
              <Button type="submit" variant="primary" size="sm" className="font-bold text-xs">
                <Icon name="fa-filter" className="mr-1" />
                Filter
              </Button>

              {(filter || userFilter) && (
                <a
                  href="/admin/activity"
                  className="inline-flex h-8 items-center rounded-lg border border-border bg-surface px-2.5 text-xs font-medium text-muted hover:text-foreground"
                >
                  Clear
                </a>
              )}
            </div>
          </form>
        </Panel>

        {/* Activity Table */}
        <Panel>
          {logs.length === 0 ? (
            <div className="py-8 text-center text-xs text-muted">
              No activity logs matched current filters.
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs">
                <thead className="border-b border-border text-[11px] font-semibold text-muted">
                  <tr>
                    <th className="pb-2 w-28">Timestamp</th>
                    <th className="pb-2 w-32">Player</th>
                    <th className="pb-2 w-16">Day</th>
                    <th className="pb-2 w-24">Category</th>
                    <th className="pb-2">Message</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border/60">
                  {logs.map((log) => (
                    <tr key={log.id} className="hover:bg-surface-raised/40 transition-colors">
                      <td className="py-2.5 font-mono text-muted text-[11px] whitespace-nowrap">
                        {log.timeFormatted}
                      </td>
                      <td className="py-2.5 font-bold">
                        <a
                          href={`/admin/user/${log.user_id}`}
                          className="text-accent hover:underline"
                        >
                          {log.username}
                        </a>
                      </td>
                      <td className="py-2.5">
                        <span className="rounded bg-surface-raised px-1.5 py-0.5 font-mono text-[10px] font-bold text-muted">
                          D{log.game_day}
                        </span>
                      </td>
                      <td className="py-2.5">
                        <span className="rounded border border-border px-1.5 py-0.5 text-[10px] font-semibold text-muted">
                          {log.category}
                        </span>
                      </td>
                      <td className="py-2.5 text-foreground leading-relaxed">
                        <div className="flex items-center gap-2">
                          <Icon name={log.icon} className="text-accent text-xs shrink-0" />
                          <span className="truncate max-w-xl" title={log.message}>
                            {log.message}
                          </span>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}

          {/* Pagination Controls */}
          {totalPages > 1 && (
            <div className="flex items-center justify-between border-t border-border pt-3 mt-3 text-xs text-muted">
              <span>
                Showing page <strong className="font-mono text-foreground">{page}</strong> of{' '}
                <strong className="font-mono text-foreground">{totalPages}</strong>
              </span>

              <div className="flex items-center gap-1.5">
                {page > 1 && (
                  <a
                    href={getPageUrl(page - 1)}
                    className="inline-flex items-center gap-1 rounded-lg border border-border bg-surface px-2.5 py-1 text-xs font-semibold text-foreground hover:bg-surface-raised"
                  >
                    <Icon name="fa-chevron-left" className="text-[10px]" />
                    Previous
                  </a>
                )}

                {page < totalPages && (
                  <a
                    href={getPageUrl(page + 1)}
                    className="inline-flex items-center gap-1 rounded-lg border border-border bg-surface px-2.5 py-1 text-xs font-semibold text-foreground hover:bg-surface-raised"
                  >
                    Next
                    <Icon name="fa-chevron-right" className="text-[10px]" />
                  </a>
                )}
              </div>
            </div>
          )}
        </Panel>
      </div>
    </Page>
  );
}
