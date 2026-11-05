import { Button } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Page, Panel } from '@/components/Page';

export type SuspectPlayer = {
  id: number;
  username: string;
  cashFormatted: string;
  difficulty: string;
  times_flagged: number;
  last_income: string;
};

export type RecentAdminEdit = {
  id: number;
  username: string;
  cashFormatted: string;
  latest: string;
  timeFormatted: string;
};

export type AdminSuspiciousPageProps = {
  suspects: SuspectPlayer[];
  recent: RecentAdminEdit[];
};

export default function AdminSuspiciousPage({ suspects, recent }: AdminSuspiciousPageProps) {
  return (
    <Page
      title="Suspicious Activity"
      icon="fa-triangle-exclamation"
      iconTone="text-warning"
      back="/admin"
      subtitle="Automated anomaly detection for abnormal liquidity and manual administrative adjustments."
    >
      <div className="space-y-6">
        {/* High Cash Outliers */}
        <Panel
          title={
            <div className="flex items-center gap-2">
              <Icon name="fa-coins" className="text-warning" />
              <span>High Cash Accounts ({suspects.length})</span>
            </div>
          }
        >
          <p className="text-xs text-muted mb-3">
            Players exceeding statistical cash thresholds for their difficulty level ($5M Easy, $3M Standard, $2M Hard).
          </p>

          {suspects.length === 0 ? (
            <div className="flex items-center gap-2 py-4 text-xs font-semibold text-emerald-400">
              <Icon name="fa-circle-check" />
              <span>No suspicious accounts detected above current thresholds.</span>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs">
                <thead className="border-b border-border text-[11px] font-semibold text-muted">
                  <tr>
                    <th className="pb-2">Player</th>
                    <th className="pb-2">Cash Balance</th>
                    <th className="pb-2">Difficulty</th>
                    <th className="pb-2">Flags</th>
                    <th className="pb-2">Latest Income Event</th>
                    <th className="pb-2 text-right">Inspect</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border/60">
                  {suspects.map((s) => (
                    <tr key={s.id} className="hover:bg-surface-raised/40 transition-colors">
                      <td className="py-2.5 font-bold text-foreground">
                        <a href={`/admin/user/${s.id}`} className="text-accent hover:underline">
                          {s.username}
                        </a>
                      </td>
                      <td className="py-2.5 font-mono font-bold text-warning">
                        {s.cashFormatted}
                      </td>
                      <td className="py-2.5">
                        <span className="rounded bg-surface-raised px-1.5 py-0.5 text-[10px] font-semibold text-muted uppercase">
                          {s.difficulty}
                        </span>
                      </td>
                      <td className="py-2.5 font-mono text-muted">
                        {s.times_flagged > 0 ? (
                          <span className="rounded bg-red-600/15 px-1.5 py-0.5 font-bold text-red-400">
                            {s.times_flagged}x
                          </span>
                        ) : (
                          '0'
                        )}
                      </td>
                      <td className="py-2.5 text-muted max-w-xs truncate text-[11px]">
                        {s.last_income}
                      </td>
                      <td className="py-2.5 text-right">
                        <a
                          href={`/admin/user/${s.id}`}
                          aria-label={`Inspect ${s.username}`}
                          className="inline-flex size-7 items-center justify-center rounded-lg text-muted hover:bg-surface-raised hover:text-foreground"
                        >
                          <Icon name="fa-eye" className="text-xs" />
                        </a>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </Panel>

        {/* Recent Admin Cash Edits */}
        <Panel
          title={
            <div className="flex items-center gap-2">
              <Icon name="fa-user-shield" className="text-accent" />
              <span>Recent Manual Cash Adjustments</span>
            </div>
          }
        >
          {recent.length === 0 ? (
            <p className="py-4 text-xs text-muted">No recent admin balance interventions recorded.</p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs">
                <thead className="border-b border-border text-[11px] font-semibold text-muted">
                  <tr>
                    <th className="pb-2">Player</th>
                    <th className="pb-2">Current Cash</th>
                    <th className="pb-2">Action Details</th>
                    <th className="pb-2 text-right">Timestamp</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border/60">
                  {recent.map((r, i) => (
                    <tr key={i} className="hover:bg-surface-raised/40 transition-colors">
                      <td className="py-2.5 font-bold text-foreground">
                        <a href={`/admin/user/${r.id}`} className="text-accent hover:underline">
                          {r.username}
                        </a>
                      </td>
                      <td className="py-2.5 font-mono font-bold text-foreground">
                        {r.cashFormatted}
                      </td>
                      <td className="py-2.5 text-muted max-w-sm truncate text-[11px]">
                        {r.latest}
                      </td>
                      <td className="py-2.5 text-right font-mono text-muted text-[11px]">
                        {r.timeFormatted}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </Panel>
      </div>
    </Page>
  );
}
