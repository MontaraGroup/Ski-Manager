import { useState } from 'react';
import { Button, Chip, Meter } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Empty, Figure, Page, Panel, PostButton } from '@/components/Page';

export type StaffMember = {
  id: number;
  name: string;
  role: string;
  roleName: string;
  roleIcon: string;
  level: number;
  status: 'active' | 'training' | string;
  morale: number;
  salaryText: string;
  assignedTo: string | null;
  canTrain: boolean;
  trainCostText?: string;
};

export type StaffRoleSummary = {
  key: string;
  name: string;
  icon: string;
  count: number;
};

export type StaffPageProps = {
  stats: {
    total: number;
    dailyCostText: string;
    avgMorale: number;
    assigned: number;
    unassigned: number;
    lowMoraleCount: number;
  };
  roles: StaffRoleSummary[];
  staff: StaffMember[];
};

export default function StaffPage({ stats, roles, staff }: StaffPageProps) {
  const [selectedRole, setSelectedRole] = useState<string>('all');

  const filtered = selectedRole === 'all' ? staff : staff.filter((s) => s.role === selectedRole);

  return (
    <Page
      title="Staff"
      icon="fa-users"
      iconTone="text-accent"
      subtitle={`${stats.total} employees · ${stats.dailyCostText} a day`}
      width="max-w-6xl"
      actions={
        <div className="flex items-center gap-2">
          <a
            href="/morale"
            className="inline-flex min-h-11 items-center gap-1.5 rounded-lg border border-border px-3 text-sm font-medium hover:bg-default"
          >
            <Icon name="fa-face-smile" className="text-warning" />
            Morale
          </a>
          <a
            href="/staff/hire"
            className="inline-flex min-h-11 items-center gap-1.5 rounded-lg bg-accent px-3 text-sm font-medium text-accent-foreground hover:bg-accent-hover"
          >
            <Icon name="fa-user-plus" />
            Hire
          </a>
        </div>
      }
    >
      {staff.length === 0 ? (
        <Empty icon="fa-users" title="No staff hired yet">
          Your resort needs people to run. Ski patrollers, lift mechanics and groomer operators keep the mountain open.
          <div className="mt-4">
            <a
              href="/staff/hire"
              className="inline-flex min-h-11 items-center gap-1.5 rounded-lg bg-accent px-4 py-2 text-sm font-medium text-accent-foreground hover:bg-accent-hover"
            >
              <Icon name="fa-user-plus" />
              Hire first employee
            </a>
          </div>
        </Empty>
      ) : (
        <>
          {/* Key figures */}
          <div className="grid grid-cols-2 gap-3 md:grid-cols-5">
            <Figure label="Total staff" value={stats.total} />
            <Figure label="Daily salaries" value={stats.dailyCostText} tone="text-danger" />
            <Figure
              label="Average morale"
              value={`${stats.avgMorale}%`}
              tone={stats.avgMorale >= 70 ? 'text-success' : stats.avgMorale >= 40 ? 'text-warning' : 'text-danger'}
            />
            <Figure label="Assigned" value={stats.assigned} tone="text-success" />
            <Figure
              label="Unassigned"
              value={stats.unassigned}
              tone={stats.unassigned > 0 ? 'text-warning' : 'text-success'}
            />
          </div>

          {/* Warnings */}
          {stats.lowMoraleCount > 0 && (
            <div className="flex items-center justify-between rounded-xl border border-warning/40 bg-surface p-4 text-sm text-foreground">
              <div className="flex items-center gap-2">
                <Icon name="fa-face-frown" className="text-warning" />
                <span>
                  {stats.lowMoraleCount} staff {stats.lowMoraleCount === 1 ? 'has' : 'have'} low morale. Below 10% they may quit.
                </span>
              </div>
              <a href="/morale" className="text-link hover:underline font-medium">
                Boost morale →
              </a>
            </div>
          )}

          {stats.unassigned > 0 && (
            <div className="flex items-center justify-between rounded-xl border border-border bg-surface p-4 text-sm text-foreground">
              <div className="flex items-center gap-2">
                <Icon name="fa-circle-info" className="text-link" />
                <span>{stats.unassigned} unassigned staff. They collect a salary but perform no work until assigned to a post.</span>
              </div>
              <PostButton action="/staff/auto-assign" variant="primary" size="sm">
                Auto-assign now
              </PostButton>
            </div>
          )}

          {/* Filters & Actions row */}
          <div className="flex flex-wrap items-center justify-between gap-3 pt-2">
            <div className="flex flex-wrap items-center gap-1.5">
              <Button
                variant={selectedRole === 'all' ? 'primary' : 'secondary'}
                size="sm"
                onPress={() => setSelectedRole('all')}
              >
                All ({staff.length})
              </Button>
              {roles.map((r) => (
                <Button
                  key={r.key}
                  variant={selectedRole === r.key ? 'primary' : 'secondary'}
                  size="sm"
                  onPress={() => setSelectedRole(r.key)}
                >
                  <Icon name={r.icon} className="mr-1 text-xs" />
                  {r.name} ({r.count})
                </Button>
              ))}
            </div>

            <div className="flex items-center gap-2">
              <PostButton action="/staff/auto-assign" variant="secondary" size="sm">
                <Icon name="fa-wand-magic-sparkles" className="mr-1" />
                Auto-assign
              </PostButton>
              <PostButton
                action="/staff/clear-assignments"
                variant="ghost"
                size="sm"
                confirm={{
                  title: 'Clear all assignments?',
                  body: 'All staff will be unassigned from their current posts.',
                  confirmLabel: 'Clear all',
                }}
              >
                <Icon name="fa-eraser" className="mr-1" />
                Clear all
              </PostButton>
            </div>
          </div>

          {/* Staff Roster Grid */}
          <div className="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
            {filtered.map((member) => (
              <div
                key={member.id}
                className={`flex flex-col justify-between rounded-xl border bg-surface p-4 ${
                  member.morale < 40 ? 'border-danger/40' : 'border-border'
                }`}
              >
                <div>
                  <div className="flex items-start justify-between gap-2 mb-3">
                    <div className="flex items-center gap-2.5 min-w-0">
                      <div className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-default">
                        <Icon name={member.roleIcon} className="text-base text-accent" />
                      </div>
                      <div className="min-w-0">
                        <div className="truncate font-semibold text-sm">{member.name}</div>
                        <div className="flex items-center gap-1.5 text-xs text-muted">
                          <span>{member.roleName}</span>
                          <span>·</span>
                          <span>Level {member.level}</span>
                        </div>
                      </div>
                    </div>
                    <Chip
                      variant="soft"
                      color={member.status === 'active' ? 'success' : member.status === 'training' ? 'accent' : 'warning'}
                      size="sm"
                    >
                      {member.status}
                    </Chip>
                  </div>

                  {/* Morale gauge & salary */}
                  <div className="grid grid-cols-2 gap-2 text-xs mb-3">
                    <div className="rounded-lg bg-surface-secondary p-2">
                      <div className="flex justify-between mb-1">
                        <span className="text-muted">Morale</span>
                        <span
                          className={`font-semibold tabular-nums ${
                            member.morale >= 70 ? 'text-success' : member.morale >= 40 ? 'text-warning' : 'text-danger'
                          }`}
                        >
                          {member.morale}%
                        </span>
                      </div>
                      <Meter
                        value={member.morale}
                        color={member.morale >= 70 ? 'success' : member.morale >= 40 ? 'warning' : 'danger'}
                        aria-label="Morale"
                      />
                    </div>
                    <div className="rounded-lg bg-surface-secondary p-2 flex flex-col justify-between">
                      <div className="text-muted">Salary</div>
                      <div className="font-semibold tabular-nums">{member.salaryText} / day</div>
                    </div>
                  </div>

                  {/* Current assignment */}
                  <div className="text-xs mb-4">
                    {member.assignedTo ? (
                      <div className="flex items-center gap-1.5 text-success">
                        <Icon name="fa-circle-check" className="text-[11px]" />
                        <span className="truncate">{member.assignedTo}</span>
                      </div>
                    ) : (
                      <div className="flex items-center gap-1.5 text-warning">
                        <Icon name="fa-circle-exclamation" className="text-[11px]" />
                        <span>Unassigned (idle)</span>
                      </div>
                    )}
                  </div>
                </div>

                {/* Card actions */}
                <div className="flex items-center gap-2 border-t border-border pt-3">
                  {member.canTrain && member.trainCostText ? (
                    <PostButton
                      action={`/staff/train/${member.id}`}
                      variant="secondary"
                      size="sm"
                      className="flex-1"
                      confirm={{
                        title: `Train ${member.name}?`,
                        body: `Training to Level ${member.level + 1} costs ${member.trainCostText}. Groomer operators restore more slope condition each night as their level increases.`,
                        confirmLabel: `Train for ${member.trainCostText}`,
                      }}
                    >
                      <Icon name="fa-graduation-cap" className="mr-1" />
                      Train ({member.trainCostText})
                    </PostButton>
                  ) : (
                    <div className="flex-1 text-xs text-muted">
                      {member.role === 'groomer' ? 'Max level reached' : 'Training has no effect'}
                    </div>
                  )}

                  <PostButton
                    action={`/staff/fire/${member.id}`}
                    variant="ghost"
                    size="sm"
                    confirm={{
                      title: `Fire ${member.name}?`,
                      body: `Are you sure you want to fire ${member.name}? Their post (${member.assignedTo || 'none'}) will become vacant tonight.`,
                      confirmLabel: `Fire ${member.name}`,
                    }}
                  >
                    <Icon name="fa-user-minus" className="text-danger" />
                  </PostButton>
                </div>
              </div>
            ))}
          </div>
        </>
      )}
    </Page>
  );
}
