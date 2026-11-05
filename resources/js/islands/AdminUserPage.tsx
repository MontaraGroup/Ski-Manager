import { useState } from 'react';
import { Button } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput, Figure, Page, Panel, PostButton } from '@/components/Page';

export type UserMeta = {
  id: number;
  username: string;
  active: boolean;
  isOwner: boolean;
  createdFormatted: string;
  email: string;
};

export type FinanceMeta = {
  cashRaw: number;
  cashFormatted: string;
  reputation: number;
  difficulty: string;
  units: string;
  resortMap: string;
  resortOpen: boolean;
};

export type GenepisMeta = {
  balance: number;
  balanceFormatted: string;
};

export type UserStats = {
  staffCount: number;
  buildingCount: number;
  itemCount: number;
  loanCount: number;
  achievementCount: number;
  facilityCount: number;
};

export type StaffItem = {
  id: number;
  role: string;
  name: string;
  salaryFormatted: string;
  morale: number;
};

export type ItemRow = {
  id: number;
  name: string;
  type: string;
  lengthFormatted: string;
  status: string;
};

export type BuildingRow = {
  id: number;
  name: string;
  type: string;
  level: number;
};

export type LoanRow = {
  id: number;
  type: string;
  remainingFormatted: string;
  daysRemaining: number;
};

export type AchievementRow = {
  key: string;
};

export type TutorialMeta = {
  step: number;
} | null;

export type LogEntry = {
  icon: string;
  message: string;
  game_day: number;
};

export type AdminUserPageProps = {
  user: UserMeta;
  finance: FinanceMeta;
  genepis: GenepisMeta;
  stats: UserStats;
  staff: StaffItem[];
  items: ItemRow[];
  buildings: BuildingRow[];
  loans: LoanRow[];
  achievements: AchievementRow[];
  tutorial: TutorialMeta;
  logs: LogEntry[];
};

export default function AdminUserPage({
  user,
  finance,
  genepis,
  stats,
  staff = [],
  items = [],
  buildings = [],
  loans = [],
  achievements = [],
  tutorial,
  logs = [],
}: AdminUserPageProps) {
  const [cashVal, setCashVal] = useState<number | string>(finance?.cashRaw ?? 0);
  const [genepisVal, setGenepisVal] = useState<number | string>(genepis?.balance ?? 0);

  return (
    <Page
      title={user.username}
      icon="fa-user-gear"
      iconTone="text-primary"
      back="/admin"
      width="max-w-6xl"
      subtitle={`User #${user.id} • Registered ${user.createdFormatted}`}
      actions={
        !user.isOwner ? (
          <div className="flex flex-wrap items-center gap-2">
            {!user.active ? (
              <PostButton
                action={`/admin/unban/${user.id}`}
                variant="secondary"
                size="sm"
              >
                <Icon name="fa-unlock" className="mr-1 text-success" />
                Unban
              </PostButton>
            ) : (
              <PostButton
                action={`/admin/ban/${user.id}`}
                variant="danger"
                size="sm"
                confirm={{
                  title: `Ban ${user.username}?`,
                  body: `This will prevent ${user.username} from logging in until unbanned.`,
                  confirmLabel: 'Ban User',
                }}
              >
                <Icon name="fa-ban" className="mr-1" />
                Ban
              </PostButton>
            )}

            <PostButton
              action={`/admin/reset/${user.id}`}
              variant="secondary"
              size="sm"
              confirm={{
                title: `Reset ${user.username}'s progress?`,
                body: `This will clear resort items, staff, buildings, loans, and reset cash to defaults for ${user.username}. This cannot be undone.`,
                confirmLabel: 'Reset Everything',
              }}
            >
              <Icon name="fa-rotate-left" className="mr-1" />
              Reset
            </PostButton>

            <PostButton
              action={`/admin/delete/${user.id}`}
              variant="danger"
              size="sm"
              confirm={{
                title: `Delete ${user.username}?`,
                body: `Permanently delete ${user.username} and all records. This action cannot be reversed.`,
                confirmLabel: 'Delete Permanently',
              }}
            >
              <Icon name="fa-trash" className="mr-1" />
              Delete
            </PostButton>

            <a
              href={`/admin/impersonate/${user.id}`}
              onClick={(e) => {
                if (!window.confirm(`Log in and switch session to ${user.username}?`)) {
                  e.preventDefault();
                }
              }}
              className="inline-flex h-8 items-center gap-1.5 rounded-md border border-edge bg-surface-2 px-3 text-xs font-medium text-foreground hover:bg-surface-3 transition-colors"
            >
              <Icon name="fa-user-secret" />
              Impersonate
            </a>
          </div>
        ) : (
          <span className="inline-flex items-center rounded border border-warning/30 bg-warning/10 px-2.5 py-1 text-xs font-semibold text-warning">
            Owner Account
          </span>
        )
      }
    >
      <div className="space-y-6">
        {/* Top Cards: Cash, Genepis, Resort Stats, Account Profile */}
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {/* Cash Adjuster */}
          <Panel
            title={
              <span className="flex items-center gap-2">
                <Icon name="fa-coins" className="text-warning" />
                Resort Cash
              </span>
            }
          >
            <Figure
              label="Current Balance"
              value={finance?.cashFormatted ?? '$0'}
            />
            <form action="/admin/cash" method="post" className="mt-4 flex items-center gap-2">
              <CsrfInput />
              <input type="hidden" name="user_id" value={user.id} />
              <input
                type="number"
                name="cash"
                aria-label="New Cash Balance"
                value={cashVal}
                onChange={(e) => setCashVal(e.target.value)}
                className="w-full rounded-md border border-edge bg-surface-2 px-2.5 py-1.5 text-xs text-foreground focus:border-focus focus:outline-none"
                required
              />
              <Button size="sm" variant="primary" type="submit" className="shrink-0">
                Set
              </Button>
            </form>
          </Panel>

          {/* Genepis Adjuster */}
          <Panel
            title={
              <span className="flex items-center gap-2">
                <Icon name="fa-seedling" className="text-success" />
                Genepis Balance
              </span>
            }
          >
            <Figure
              label="Premium Currency"
              value={genepis?.balanceFormatted ?? '0'}
            />
            <form action="/admin/genepis" method="post" className="mt-4 flex items-center gap-2">
              <CsrfInput />
              <input type="hidden" name="user_id" value={user.id} />
              <input
                type="number"
                name="genepis"
                aria-label="New Genepis Balance"
                value={genepisVal}
                onChange={(e) => setGenepisVal(e.target.value)}
                className="w-full rounded-md border border-edge bg-surface-2 px-2.5 py-1.5 text-xs text-foreground focus:border-focus focus:outline-none"
                required
              />
              <Button size="sm" variant="primary" type="submit" className="shrink-0">
                Set
              </Button>
            </form>
          </Panel>

          {/* Resort Metadata */}
          <Panel
            title={
              <span className="flex items-center gap-2">
                <Icon name="fa-mountain" className="text-primary" />
                Resort Details
              </span>
            }
          >
            <dl className="space-y-2 text-xs">
              <div className="flex justify-between border-b border-edge/60 pb-1.5">
                <dt className="text-muted">Map</dt>
                <dd className="font-medium text-foreground">{finance?.resortMap || 'N/A'}</dd>
              </div>
              <div className="flex justify-between border-b border-edge/60 pb-1.5">
                <dt className="text-muted">Difficulty</dt>
                <dd className="font-medium capitalize text-foreground">{finance?.difficulty || 'Standard'}</dd>
              </div>
              <div className="flex justify-between border-b border-edge/60 pb-1.5">
                <dt className="text-muted">Reputation</dt>
                <dd className="font-semibold text-primary">{finance?.reputation ?? 50}%</dd>
              </div>
              <div className="flex justify-between">
                <dt className="text-muted">Status</dt>
                <dd>
                  <span
                    className={`inline-flex rounded px-1.5 py-0.5 text-[11px] font-semibold ${
                      finance?.resortOpen ? 'bg-success/15 text-success' : 'bg-danger/15 text-danger'
                    }`}
                  >
                    {finance?.resortOpen ? 'Open' : 'Closed'}
                  </span>
                </dd>
              </div>
            </dl>
          </Panel>

          {/* User Account Info */}
          <Panel
            title={
              <span className="flex items-center gap-2">
                <Icon name="fa-id-card" className="text-muted" />
                Account Info
              </span>
            }
          >
            <dl className="space-y-2 text-xs">
              <div className="flex justify-between border-b border-edge/60 pb-1.5">
                <dt className="text-muted">User ID</dt>
                <dd className="font-mono text-foreground">#{user.id}</dd>
              </div>
              <div className="flex justify-between border-b border-edge/60 pb-1.5 truncate">
                <dt className="text-muted">Email</dt>
                <dd className="font-medium text-foreground truncate max-w-[140px]" title={user.email}>
                  {user.email}
                </dd>
              </div>
              <div className="flex justify-between border-b border-edge/60 pb-1.5">
                <dt className="text-muted">Joined</dt>
                <dd className="text-foreground">{user.createdFormatted}</dd>
              </div>
              <div className="flex justify-between">
                <dt className="text-muted">Status</dt>
                <dd>
                  <span
                    className={`inline-flex rounded px-1.5 py-0.5 text-[11px] font-semibold ${
                      user.active ? 'bg-success/15 text-success' : 'bg-danger/15 text-danger'
                    }`}
                  >
                    {user.active ? 'Active' : 'Banned'}
                  </span>
                </dd>
              </div>
            </dl>
          </Panel>
        </div>

        {/* Stats Summary Strip */}
        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
          <div className="rounded-lg border border-edge bg-surface-2 p-3 text-center">
            <div className="text-xs text-muted">Lifts & Slopes</div>
            <div className="mt-1 text-lg font-bold tabular-nums text-foreground">{stats?.itemCount ?? items.length}</div>
          </div>
          <div className="rounded-lg border border-edge bg-surface-2 p-3 text-center">
            <div className="text-xs text-muted">Staff Hired</div>
            <div className="mt-1 text-lg font-bold tabular-nums text-foreground">{stats?.staffCount ?? staff.length}</div>
          </div>
          <div className="rounded-lg border border-edge bg-surface-2 p-3 text-center">
            <div className="text-xs text-muted">Buildings</div>
            <div className="mt-1 text-lg font-bold tabular-nums text-foreground">{stats?.buildingCount ?? buildings.length}</div>
          </div>
          <div className="rounded-lg border border-edge bg-surface-2 p-3 text-center">
            <div className="text-xs text-muted">Active Loans</div>
            <div className="mt-1 text-lg font-bold tabular-nums text-foreground">{stats?.loanCount ?? loans.length}</div>
          </div>
          <div className="rounded-lg border border-edge bg-surface-2 p-3 text-center">
            <div className="text-xs text-muted">Achievements</div>
            <div className="mt-1 text-lg font-bold tabular-nums text-foreground">{stats?.achievementCount ?? achievements.length}</div>
          </div>
          <div className="rounded-lg border border-edge bg-surface-2 p-3 text-center">
            <div className="text-xs text-muted">Facilities</div>
            <div className="mt-1 text-lg font-bold tabular-nums text-foreground">{stats?.facilityCount ?? 0}</div>
          </div>
        </div>

        {/* Lifts & Slopes Table + Staff Roster */}
        <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
          {/* Lifts & Slopes */}
          <Panel
            title={
              <span className="flex items-center gap-2">
                <Icon name="fa-cable-car" className="text-primary" />
                Lifts & Slopes ({items.length})
              </span>
            }
          >
            {items.length === 0 ? (
              <p className="py-6 text-center text-xs text-muted">No lifts or slopes built yet.</p>
            ) : (
              <div className="overflow-x-auto max-h-64 -mx-4 -my-4 sm:-mx-6 sm:-my-6">
                <table className="w-full text-left text-xs">
                  <thead className="sticky top-0 border-b border-edge bg-surface-2 text-muted uppercase tracking-wider font-semibold">
                    <tr>
                      <th scope="col" className="px-4 py-2.5 sm:px-6">Name</th>
                      <th scope="col" className="px-4 py-2.5">Type</th>
                      <th scope="col" className="px-4 py-2.5">Length</th>
                      <th scope="col" className="px-4 py-2.5 sm:pr-6">Status</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-edge">
                    {items.map((it) => (
                      <tr key={it.id} className="hover:bg-surface-2/40">
                        <td className="px-4 py-2 font-medium text-foreground sm:px-6">{it.name}</td>
                        <td className="px-4 py-2 capitalize text-muted">{it.type}</td>
                        <td className="px-4 py-2 font-mono text-muted">{it.lengthFormatted}</td>
                        <td className="px-4 py-2 sm:pr-6">
                          <span
                            className={`inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase ${
                              it.status === 'open'
                                ? 'bg-success/15 text-success'
                                : 'bg-warning/15 text-warning'
                            }`}
                          >
                            {it.status}
                          </span>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </Panel>

          {/* Staff Roster */}
          <Panel
            title={
              <span className="flex items-center gap-2">
                <Icon name="fa-users" className="text-secondary" />
                Staff Roster ({staff.length})
              </span>
            }
          >
            {staff.length === 0 ? (
              <p className="py-6 text-center text-xs text-muted">No staff members hired.</p>
            ) : (
              <div className="overflow-x-auto max-h-64 -mx-4 -my-4 sm:-mx-6 sm:-my-6">
                <table className="w-full text-left text-xs">
                  <thead className="sticky top-0 border-b border-edge bg-surface-2 text-muted uppercase tracking-wider font-semibold">
                    <tr>
                      <th scope="col" className="px-4 py-2.5 sm:px-6">Name</th>
                      <th scope="col" className="px-4 py-2.5">Role</th>
                      <th scope="col" className="px-4 py-2.5">Salary</th>
                      <th scope="col" className="px-4 py-2.5 sm:pr-6">Morale</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-edge">
                    {staff.map((s) => (
                      <tr key={s.id} className="hover:bg-surface-2/40">
                        <td className="px-4 py-2 font-medium text-foreground sm:px-6">{s.name}</td>
                        <td className="px-4 py-2 capitalize text-muted">{s.role}</td>
                        <td className="px-4 py-2 font-mono text-muted">{s.salaryFormatted}/day</td>
                        <td className="px-4 py-2 sm:pr-6">
                          <div className="flex items-center gap-1.5">
                            <div className="h-1.5 w-12 rounded-full bg-surface-3 overflow-hidden">
                              <div
                                className={`h-full rounded-full ${
                                  s.morale > 70 ? 'bg-success' : s.morale > 40 ? 'bg-warning' : 'bg-danger'
                                }`}
                                style={{ width: `${Math.min(100, Math.max(0, s.morale))}%` }}
                              />
                            </div>
                            <span className="font-mono text-[11px] text-muted">{s.morale}%</span>
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </Panel>
        </div>

        {/* Buildings & Loans */}
        <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
          {/* Buildings */}
          <Panel
            title={
              <span className="flex items-center gap-2">
                <Icon name="fa-building" className="text-warning" />
                Buildings ({buildings.length})
              </span>
            }
          >
            {buildings.length === 0 ? (
              <p className="py-6 text-center text-xs text-muted">No buildings constructed.</p>
            ) : (
              <div className="overflow-x-auto max-h-56 -mx-4 -my-4 sm:-mx-6 sm:-my-6">
                <table className="w-full text-left text-xs">
                  <thead className="sticky top-0 border-b border-edge bg-surface-2 text-muted uppercase tracking-wider font-semibold">
                    <tr>
                      <th scope="col" className="px-4 py-2.5 sm:px-6">Name</th>
                      <th scope="col" className="px-4 py-2.5">Type</th>
                      <th scope="col" className="px-4 py-2.5 sm:pr-6">Level</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-edge">
                    {buildings.map((b) => (
                      <tr key={b.id} className="hover:bg-surface-2/40">
                        <td className="px-4 py-2 font-medium text-foreground sm:px-6">{b.name}</td>
                        <td className="px-4 py-2 capitalize text-muted">{b.type}</td>
                        <td className="px-4 py-2 font-mono text-foreground sm:pr-6">Lv. {b.level}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </Panel>

          {/* Active Loans */}
          <Panel
            title={
              <span className="flex items-center gap-2">
                <Icon name="fa-landmark" className="text-danger" />
                Active Loans ({loans.length})
              </span>
            }
          >
            {loans.length === 0 ? (
              <p className="py-6 text-center text-xs text-muted">No active loans outstanding.</p>
            ) : (
              <div className="divide-y divide-edge">
                {loans.map((l) => (
                  <div key={l.id} className="flex items-center justify-between py-2 text-xs">
                    <div className="flex items-center gap-2">
                      <Icon name="fa-hand-holding-dollar" className="text-danger text-sm" />
                      <span className="font-medium text-foreground">{l.type}</span>
                    </div>
                    <div className="flex items-center gap-4">
                      <span className="font-mono font-semibold text-foreground">{l.remainingFormatted}</span>
                      <span className="text-muted">{l.daysRemaining} days left</span>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </Panel>
        </div>

        {/* Achievements + Tutorial */}
        <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
          {/* Achievements */}
          <Panel
            title={
              <span className="flex items-center gap-2">
                <Icon name="fa-trophy" className="text-warning" />
                Achievements Earned ({achievements.length})
              </span>
            }
          >
            {achievements.length === 0 ? (
              <p className="py-6 text-center text-xs text-muted">No achievements unlocked yet.</p>
            ) : (
              <div className="flex flex-wrap gap-1.5">
                {achievements.map((a, i) => (
                  <span
                    key={i}
                    className="inline-flex items-center gap-1 rounded-md border border-edge bg-surface-2 px-2.5 py-1 text-xs font-medium text-foreground"
                  >
                    <Icon name="fa-medal" className="text-warning text-[11px]" />
                    {a.key}
                  </span>
                ))}
              </div>
            )}
          </Panel>

          {/* Tutorial */}
          <Panel
            title={
              <span className="flex items-center gap-2">
                <Icon name="fa-graduation-cap" className="text-secondary" />
                Tutorial Progress
              </span>
            }
            actions={
              tutorial && (
                <PostButton
                  action={`/admin/reset-tutorial/${user.id}`}
                  variant="secondary"
                  size="sm"
                  confirm={{
                    title: 'Reset Tutorial Progress?',
                    body: `Reset tutorial step back to step 1 for ${user.username}?`,
                    confirmLabel: 'Reset Tutorial',
                  }}
                >
                  Reset Tutorial
                </PostButton>
              )
            }
          >
            {tutorial ? (
              <div className="flex items-center justify-between text-xs">
                <span className="text-muted">Current onboarding step:</span>
                <span className="rounded bg-surface-3 px-2 py-1 font-mono font-semibold text-foreground">
                  Step {tutorial.step}
                </span>
              </div>
            ) : (
              <p className="py-4 text-xs text-muted">Tutorial either completed or not initiated.</p>
            )}
          </Panel>
        </div>

        {/* Activity Log (last 50) */}
        <Panel
          title={
            <span className="flex items-center gap-2">
              <Icon name="fa-clock-rotate-left" className="text-muted" />
              Recent Activity Log (Last {logs.length})
            </span>
          }
        >
          {logs.length === 0 ? (
            <p className="py-6 text-center text-xs text-muted">No activity records found for this user.</p>
          ) : (
            <div className="divide-y divide-edge max-h-80 overflow-y-auto -mx-4 -my-4 sm:-mx-6 sm:-my-6 px-4 sm:px-6">
              {logs.map((log, idx) => (
                <div key={idx} className="flex items-center gap-3 py-2 text-xs">
                  <span className="w-5 text-center text-muted">
                    <Icon name={`fa-${log.icon || 'circle-dot'}`} />
                  </span>
                  <span className="flex-1 text-foreground truncate" title={log.message}>
                    {log.message}
                  </span>
                  <span className="shrink-0 rounded bg-surface-2 px-1.5 py-0.5 font-mono text-[11px] text-muted">
                    Day {log.game_day}
                  </span>
                </div>
              ))}
            </div>
          )}
        </Panel>
      </div>
    </Page>
  );
}
