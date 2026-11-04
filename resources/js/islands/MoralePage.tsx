import { Chip, Meter } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Empty, Figure, Page, Panel, PostButton } from '@/components/Page';

export type BoostItem = {
  key: string;
  name: string;
  desc: string;
  icon: string;
  morale: number;
  cost: number;
  costText: string | null;
  confirmBody: string;
};

export type StaffMoraleItem = {
  id: number;
  name: string;
  role: string;
  roleLabel: string;
  morale: number;
  salaryText: string;
};

export type MoralePageProps = {
  stats: {
    avgMorale: number;
    totalStaff: number;
    highMoraleCount: number;
    lowMoraleCount: number;
  };
  boosts: BoostItem[];
  staff: StaffMoraleItem[];
};

export default function MoralePage({ stats, boosts, staff }: MoralePageProps) {
  const avgTone =
    stats.avgMorale >= 70
      ? 'text-success'
      : stats.avgMorale >= 40
      ? 'text-warning'
      : 'text-danger';

  const meterColor =
    stats.avgMorale >= 70 ? 'success' : stats.avgMorale >= 40 ? 'warning' : 'danger';

  return (
    <Page
      title="Staff morale"
      icon="fa-face-smile"
      iconTone="text-accent"
      subtitle="Keep your team happy to maximize productivity"
      back="/staff"
      width="max-w-6xl"
    >
      {/* Morale Overview */}
      <div className="rounded-xl border border-border bg-surface p-6">
        <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
          <div className="text-center md:text-left space-y-2">
            <div className="text-xs uppercase tracking-wider text-muted font-medium">Average morale</div>
            <div className={`text-5xl font-bold tabular-nums ${avgTone}`}>
              {stats.avgMorale}%
            </div>
            <div className="w-56 max-w-full">
              <Meter
                value={stats.avgMorale}
                color={meterColor}
                aria-label="Average morale"
              />
            </div>
          </div>

          <div className="grid grid-cols-3 gap-4 text-center">
            <div className="rounded-lg bg-surface-secondary p-3 border border-border">
              <div className="text-2xl font-bold tabular-nums">{stats.totalStaff}</div>
              <div className="text-xs text-muted mt-0.5">Total staff</div>
            </div>
            <div className="rounded-lg bg-surface-secondary p-3 border border-border">
              <div className="text-2xl font-bold tabular-nums text-success">{stats.highMoraleCount}</div>
              <div className="text-xs text-muted mt-0.5">Happy (80%+)</div>
            </div>
            <div className="rounded-lg bg-surface-secondary p-3 border border-border">
              <div className="text-2xl font-bold tabular-nums text-danger">{stats.lowMoraleCount}</div>
              <div className="text-xs text-muted mt-0.5">Unhappy (&lt;50%)</div>
            </div>
          </div>
        </div>
      </div>

      {/* Morale Status Alerts */}
      {stats.avgMorale < 50 && stats.totalStaff > 0 && (
        <div className="rounded-xl border border-danger/40 bg-danger/10 p-4 text-sm flex items-start gap-3">
          <Icon name="fa-triangle-exclamation" className="text-danger mt-0.5 text-base shrink-0" />
          <div>
            <div className="font-semibold text-foreground">Morale is critically low</div>
            <div className="text-muted">Unhappy staff work slower and may quit. Take action now with morale boosts or raises.</div>
          </div>
        </div>
      )}
      {stats.avgMorale >= 50 && stats.avgMorale < 70 && stats.totalStaff > 0 && (
        <div className="rounded-xl border border-warning/40 bg-warning/10 p-4 text-sm flex items-start gap-3">
          <Icon name="fa-circle-exclamation" className="text-warning mt-0.5 text-base shrink-0" />
          <div>
            <div className="font-semibold text-foreground">Morale could be better</div>
            <div className="text-muted">Staff spirits are moderate. Consider boosting team morale to reach higher productivity.</div>
          </div>
        </div>
      )}

      {/* 2-Column Layout */}
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {/* Left column: Boost Morale Actions */}
        <div className="lg:col-span-2 space-y-3">
          <h2 className="text-lg font-bold text-foreground flex items-center gap-2">
            <Icon name="fa-hand-holding-heart" className="text-accent" />
            Boost morale
          </h2>

          <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
            {boosts.map((boost) => (
              <div
                key={boost.key}
                className="rounded-xl border border-border bg-surface p-4 flex flex-col justify-between gap-3"
              >
                <div className="flex items-start gap-3">
                  <div className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-surface-secondary text-accent">
                    <Icon name={boost.icon} className="text-base" />
                  </div>
                  <div className="min-w-0 flex-1">
                    <div className="font-semibold text-sm text-foreground">{boost.name}</div>
                    <p className="text-xs text-muted mt-0.5 leading-relaxed">{boost.desc}</p>
                  </div>
                </div>

                <div className="flex items-center justify-between pt-2 border-t border-border">
                  <Chip size="sm" variant="soft" color="success">
                    +{boost.morale} morale
                  </Chip>

                  <PostButton
                    action="/morale/boost"
                    data={{ action: boost.key }}
                    variant="primary"
                    size="sm"
                    disabled={stats.totalStaff === 0}
                    confirm={{
                      title: boost.name,
                      body: boost.confirmBody,
                      confirmLabel: boost.costText ? `Spend ${boost.costText}` : 'Apply boost',
                    }}
                  >
                    {boost.costText ? (
                      <>
                        <Icon name="fa-coins" className="mr-1" />
                        {boost.costText}
                      </>
                    ) : (
                      <>
                        <Icon name="fa-check" className="mr-1" />
                        Apply
                      </>
                    )}
                  </PostButton>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Right column: Staff Roster & Effects */}
        <div className="space-y-4">
          <Panel
            title={
              <span className="flex items-center gap-2">
                <Icon name="fa-users" className="text-accent" />
                Staff morale
              </span>
            }
          >
            {staff.length === 0 ? (
              <Empty icon="fa-users" title="No staff hired">
                <a href="/staff/hire" className="text-link underline hover:no-underline">
                  Hire staff
                </a>{' '}
                first.
              </Empty>
            ) : (
              <div className="divide-y divide-border -mx-4 -mb-4 px-4 pb-4 max-h-[380px] overflow-y-auto">
                {staff.map((s) => {
                  const sColor =
                    s.morale >= 70 ? 'success' : s.morale >= 40 ? 'warning' : 'danger';
                  const sTone =
                    s.morale >= 70 ? 'text-success' : s.morale >= 40 ? 'text-warning' : 'text-danger';

                  return (
                    <div key={s.id} className="py-2.5 flex items-center justify-between gap-3">
                      <div className="min-w-0 flex-1">
                        <div className="text-sm font-medium truncate">{s.name}</div>
                        <div className="text-xs text-muted truncate">{s.roleLabel}</div>
                      </div>
                      <div className="flex items-center gap-2 shrink-0">
                        <div className="w-16">
                          <Meter value={s.morale} color={sColor} aria-label={`${s.name} morale`} />
                        </div>
                        <span className={`text-xs font-semibold tabular-nums w-8 text-right ${sTone}`}>
                          {s.morale}%
                        </span>
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </Panel>

          {/* Morale Effects Guide */}
          <Panel
            title={
              <span className="flex items-center gap-2">
                <Icon name="fa-circle-info" className="text-accent" />
                Morale effects
              </span>
            }
          >
            <ul className="text-xs text-muted space-y-2">
              <li className="flex items-start gap-2">
                <span className="font-semibold text-success shrink-0 w-16">80-100%</span>
                <span>Staff work faster, +10% efficiency</span>
              </li>
              <li className="flex items-start gap-2">
                <span className="font-semibold text-accent shrink-0 w-16">60-79%</span>
                <span>Normal performance</span>
              </li>
              <li className="flex items-start gap-2">
                <span className="font-semibold text-warning shrink-0 w-16">40-59%</span>
                <span>Slower work, -10% efficiency</span>
              </li>
              <li className="flex items-start gap-2">
                <span className="font-semibold text-danger shrink-0 w-16">0-39%</span>
                <span>Risk of quitting, -25% efficiency</span>
              </li>
              <li className="pt-2 border-t border-border flex items-center gap-2">
                <Icon name="fa-arrow-down" className="text-danger text-[10px]" />
                <span>Morale drops 2% per day naturally</span>
              </li>
              <li className="flex items-center gap-2">
                <Icon name="fa-arrow-down" className="text-danger text-[10px]" />
                <span>Low pay, overwork, and storms lower it faster</span>
              </li>
            </ul>
          </Panel>
        </div>
      </div>
    </Page>
  );
}
