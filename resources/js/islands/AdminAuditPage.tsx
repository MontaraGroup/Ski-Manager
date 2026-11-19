import { useState, useMemo } from 'react';
import { Page, Panel } from '@/components/Page';

export type AuditLogEntry = {
  id: number;
  timeFormatted: string;
  admin_name: string;
  action: string;
  target_name: string | null;
  target_user_id: number | null;
  details: string;
};

export type AdminAuditPageProps = {
  logs: AuditLogEntry[];
};

export default function AdminAuditPage({ logs = [] }: AdminAuditPageProps) {
  const [filter, setFilter] = useState('');

  const filteredLogs = useMemo(() => {
    const q = filter.trim().toLowerCase();
    if (!q) return logs;
    return logs.filter(
      (l) =>
        l.admin_name?.toLowerCase().includes(q) ||
        l.action?.toLowerCase().includes(q) ||
        l.target_name?.toLowerCase().includes(q) ||
        l.details?.toLowerCase().includes(q) ||
        String(l.target_user_id || '').includes(q)
    );
  }, [logs, filter]);

  return (
    <Page
      title="Admin Audit Log"
      icon="fa-shield-halved"
      iconTone="text-secondary"
      back="/admin"
      width="max-w-5xl"
      subtitle="Record of administrative actions, user modifications, and security operations."
    >
      <Panel
        title={`Audit Trail (${filteredLogs.length}${filteredLogs.length !== logs.length ? ` of ${logs.length}` : ''})`}
        actions={
          <div className="relative">
            <input
              type="search"
              aria-label="Filter audit logs"
              placeholder="Filter actions, admins, targets…"
              value={filter}
              onChange={(e) => setFilter(e.target.value)}
              className="w-56 sm:w-72 rounded-md border border-edge bg-surface-2 px-3 py-1.5 text-xs text-foreground placeholder:text-muted focus:border-focus focus:outline-none"
            />
          </div>
        }
      >
        {filteredLogs.length === 0 ? (
          <p className="py-8 text-center text-sm text-muted">
            {logs.length === 0 ? 'No audit entries recorded yet.' : 'No audit entries match filter.'}
          </p>
        ) : (
          <div className="overflow-x-auto -mx-4 -my-4 sm:-mx-6 sm:-my-6">
            <table className="w-full text-left text-xs">
              <thead className="border-b border-edge bg-surface-2 text-muted uppercase tracking-wider font-semibold">
                <tr>
                  <th scope="col" className="px-4 py-3 sm:px-6">Time</th>
                  <th scope="col" className="px-4 py-3">Admin</th>
                  <th scope="col" className="px-4 py-3">Action</th>
                  <th scope="col" className="px-4 py-3">Target</th>
                  <th scope="col" className="px-4 py-3 sm:pr-6">Details</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-edge">
                {filteredLogs.map((l) => (
                  <tr key={l.id} className="hover:bg-surface-2/50 transition-colors">
                    <td className="whitespace-nowrap px-4 py-2.5 font-mono text-muted sm:px-6">
                      {l.timeFormatted}
                    </td>
                    <td className="whitespace-nowrap px-4 py-2.5 font-medium text-foreground">
                      {l.admin_name || 'System'}
                    </td>
                    <td className="whitespace-nowrap px-4 py-2.5">
                      <span className="inline-flex items-center rounded border border-edge bg-surface-2 px-2 py-0.5 font-mono text-[11px] text-foreground">
                        {l.action}
                      </span>
                    </td>
                    <td className="whitespace-nowrap px-4 py-2.5 text-muted">
                      {l.target_name ? (
                        <a
                          href={`/admin/user/${l.target_user_id}`}
                          className="inline-flex min-h-11 items-center sm:min-h-0 font-medium text-primary hover:underline"
                        >
                          {l.target_name} <span className="font-mono text-muted text-[11px]">#{l.target_user_id}</span>
                        </a>
                      ) : (
                        <span className="text-muted/60">–</span>
                      )}
                    </td>
                    <td className="px-4 py-2.5 text-muted sm:pr-6 max-w-xs sm:max-w-md truncate" title={l.details}>
                      {l.details || '–'}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </Panel>
    </Page>
  );
}
