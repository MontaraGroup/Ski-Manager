import { useState } from 'react';
import { Button, Chip, Modal } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput, Empty, Figure, Page, Panel } from '@/components/Page';

export type Candidate = {
  id: number;
  name: string;
  trait: string;
  traitLabel: string;
  dailyText: string;
  diffText?: string;
  morale: number;
  level: number;
};

export type HireRole = {
  key: string;
  name: string;
  icon: string;
  color?: string;
  desc: string;
  effect: string;
  dailyText: string;
  have: number;
  assigned: number;
  idle: number;
  posts: number;
  free: number;
  verdict: 'needed' | 'useful' | 'enough' | 'none';
  why: string;
  group: 'slopes' | 'guests';
};

export type StaffHirePageProps = {
  stats: {
    cashText: string;
    cashNegative: boolean;
    staffCount: number;
    payrollText: string;
  };
  roles: HireRole[];
  candidates: Record<string, Candidate[]>;
  maxGroomerLevel: number;
};

export default function StaffHirePage({ stats, roles, candidates, maxGroomerLevel }: StaffHirePageProps) {
  const [filter, setFilter] = useState<'all' | 'useful' | 'slopes' | 'guests'>('all');
  const [activeModalRole, setActiveModalRole] = useState<string | null>(null);

  const neededRoles = roles.filter((r) => r.verdict === 'needed');

  const filteredRoles = roles.filter((r) => {
    if (filter === 'all') return true;
    if (filter === 'useful') return r.verdict === 'needed' || r.verdict === 'useful';
    return r.group === filter;
  });

  const activeRoleData = activeModalRole ? roles.find((r) => r.key === activeModalRole) : null;
  const activeCandidates = activeModalRole ? candidates[activeModalRole] || [] : [];

  return (
    <Page
      title="Hire staff"
      icon="fa-user-plus"
      iconTone="text-accent"
      back="/staff"
      subtitle="Pick from today's candidates. No hiring fee: you pay their daily salary from tonight."
      width="max-w-5xl"
    >
      {/* Overview stats */}
      <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <Figure
          label="Available cash"
          value={stats.cashText}
          tone={stats.cashNegative ? 'text-danger' : 'text-success'}
        />
        <Figure label="Current staff" value={stats.staffCount} />
        <Figure label="Daily payroll" value={`${stats.payrollText} / day`} tone="text-danger" />
      </div>

      {/* Urgent needs banner */}
      <Panel
        title={
          <span className="flex items-center gap-2">
            <Icon
              name={neededRoles.length > 0 ? 'fa-circle-exclamation' : 'fa-circle-check'}
              className={neededRoles.length > 0 ? 'text-danger' : 'text-success'}
            />
            {neededRoles.length > 0 ? 'Urgent staffing needs' : 'Every key post is covered'}
          </span>
        }
      >
        {neededRoles.length === 0 ? (
          <p className="text-sm text-muted">
            Nothing is missing right now. Build more lifts, slopes, or facilities to open new posts.
          </p>
        ) : (
          <div className="divide-y divide-border">
            {neededRoles.map((r) => {
              const cands = candidates[r.key] || [];
              return (
                <div key={r.key} className="flex flex-wrap items-center justify-between gap-3 py-3">
                  <div className="flex items-center gap-3">
                    <div className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-danger/10">
                      <Icon name={r.icon} className="text-base text-danger" />
                    </div>
                    <div>
                      <div className="font-semibold text-sm">{r.name}</div>
                      <div className="text-xs text-muted">{r.why}</div>
                    </div>
                  </div>
                  <div className="flex items-center gap-4">
                    <span className="text-sm font-semibold tabular-nums">{r.dailyText} / day</span>
                    {cands.length > 0 ? (
                      <Button
                        variant="primary"
                        size="sm"
                        className="min-h-11 sm:min-h-8 text-xs font-semibold"
                        onPress={() => setActiveModalRole(r.key)}
                      >
                        <Icon name="fa-user-plus" className="mr-1" />
                        Hire ({cands.length})
                      </Button>
                    ) : (
                      <span className="text-xs text-muted">None left today</span>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </Panel>

      {/* Filter tabs */}
      <div className="flex flex-wrap items-center justify-between gap-2 pt-2">
        <h2 className="text-lg font-semibold">Available roles</h2>
        <div className="flex flex-wrap items-center gap-1">
          <Button
            variant={filter === 'all' ? 'primary' : 'secondary'}
            size="sm"
            className="min-h-11 sm:min-h-8 text-xs font-medium"
            onPress={() => setFilter('all')}
          >
            All
          </Button>
          <Button
            variant={filter === 'useful' ? 'primary' : 'secondary'}
            size="sm"
            className="min-h-11 sm:min-h-8 text-xs font-medium"
            onPress={() => setFilter('useful')}
          >
            Worth hiring
          </Button>
          <Button
            variant={filter === 'slopes' ? 'primary' : 'secondary'}
            size="sm"
            className="min-h-11 sm:min-h-8 text-xs font-medium"
            onPress={() => setFilter('slopes')}
          >
            Slopes & lifts
          </Button>
          <Button
            variant={filter === 'guests' ? 'primary' : 'secondary'}
            size="sm"
            className="min-h-11 sm:min-h-8 text-xs font-medium"
            onPress={() => setFilter('guests')}
          >
            Guests & buildings
          </Button>
        </div>
      </div>

      {/* Role list grid */}
      <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
        {filteredRoles.map((r) => {
          const cands = candidates[r.key] || [];
          const dim = r.verdict === 'enough' || r.verdict === 'none';
          return (
            <div
              key={r.key}
              className={`flex flex-col justify-between rounded-xl border bg-surface p-4 ${
                r.verdict === 'needed' ? 'border-danger/40' : 'border-border'
              }`}
            >
              <div>
                <div className="flex items-start justify-between gap-3 mb-2">
                  <div className="flex items-center gap-2.5">
                    <div className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-default">
                      <Icon name={r.icon} className="text-base text-accent" />
                    </div>
                    <div>
                      <h3 className="font-semibold text-sm">{r.name}</h3>
                      <p className="text-xs text-muted mt-0.5">{r.effect}</p>
                    </div>
                  </div>
                  <Chip
                    variant="soft"
                    color={
                      r.verdict === 'needed'
                        ? 'danger'
                        : r.verdict === 'useful'
                        ? 'success'
                        : 'default'
                    }
                    size="sm"
                  >
                    {r.verdict === 'needed'
                      ? 'Needed'
                      : r.verdict === 'useful'
                      ? 'Useful'
                      : r.verdict === 'enough'
                      ? 'Covered'
                      : 'No posts'}
                  </Chip>
                </div>

                <div className="my-2 text-xs flex items-center gap-1.5 text-muted">
                  <Icon
                    name={
                      r.verdict === 'needed'
                        ? 'fa-circle-exclamation'
                        : r.verdict === 'useful'
                        ? 'fa-circle-check'
                        : 'fa-circle-minus'
                    }
                    className={
                      r.verdict === 'needed'
                        ? 'text-danger'
                        : r.verdict === 'useful'
                        ? 'text-success'
                        : 'text-muted'
                    }
                  />
                  <span>{r.why}</span>
                </div>
              </div>

              <div className="flex items-center justify-between border-t border-border pt-3 mt-2">
                <div className="flex items-center gap-3 text-xs tabular-nums text-muted">
                  <div>
                    <span className="text-muted">Salary:</span>{' '}
                    <span className="font-semibold text-foreground">{r.dailyText}/day</span>
                  </div>
                  <div>
                    <span className="text-muted">Staff:</span>{' '}
                    <span className="font-semibold text-foreground">
                      {r.have} ({r.assigned} assigned)
                    </span>
                  </div>
                  {r.key !== 'manager' && (
                    <div>
                      <span className="text-muted">Posts:</span>{' '}
                      <span className="font-semibold text-foreground">{r.posts}</span>
                    </div>
                  )}
                </div>

                {cands.length > 0 ? (
                  <Button
                    variant={dim ? 'secondary' : 'primary'}
                    size="sm"
                    className="min-h-11 sm:min-h-8 text-xs font-semibold"
                    onPress={() => setActiveModalRole(r.key)}
                  >
                    <Icon name="fa-user-plus" className="mr-1" />
                    Candidates ({cands.length})
                  </Button>
                ) : (
                  <Button variant="secondary" size="sm" className="min-h-11 sm:min-h-8 text-xs" isDisabled>
                    None left today
                  </Button>
                )}
              </div>
            </div>
          );
        })}
      </div>

      {/* Candidate Selection Modal */}
      <Modal.Backdrop
        isOpen={Boolean(activeModalRole)}
        onOpenChange={(open) => !open && setActiveModalRole(null)}
        isKeyboardDismissDisabled={false}
      >
        <Modal.Container>
          <Modal.Dialog className="max-w-md w-full">
            <Modal.Header>
              <Modal.Heading>{activeRoleData?.name} candidates</Modal.Heading>
            </Modal.Header>
            <Modal.Body>
              <p className="text-xs text-muted mb-3">
                Standard salary is {activeRoleData?.dailyText} a day. New candidates arrive every game day.
              </p>
              {activeCandidates.length === 0 ? (
                <Empty icon="fa-user-slash" title="No candidates left today">
                  All available candidates for this role have been hired or passed over today. Check back tomorrow.
                </Empty>
              ) : (
                <div className="divide-y divide-border">
                  {activeCandidates.map((c) => (
                    <div key={c.id} className="flex items-center justify-between gap-3 py-3">
                      <div>
                        <div className="font-semibold text-sm flex items-center gap-2">
                          <span>{c.name}</span>
                          <span className="rounded-full bg-default px-2 py-0.5 text-[11px] font-normal text-muted">
                            {c.traitLabel}
                          </span>
                        </div>
                        <div className="flex items-center gap-2 text-xs text-muted mt-1 tabular-nums">
                          <span>{c.dailyText} / day</span>
                          {c.diffText && <span>({c.diffText})</span>}
                          <span>·</span>
                          <span>Morale {c.morale}%</span>
                          {activeRoleData?.key === 'groomer' && (
                            <>
                              <span>·</span>
                              <span>Level {c.level}</span>
                            </>
                          )}
                        </div>
                      </div>

                      <form action="/staff/hire" method="post">
                        <CsrfInput />
                        <input type="hidden" name="candidate" value={c.id} />
                        <Button type="submit" variant="primary" size="sm">
                          Hire
                        </Button>
                      </form>
                    </div>
                  ))}
                </div>
              )}
            </Modal.Body>
            <Modal.Footer>
              <Button variant="secondary" onPress={() => setActiveModalRole(null)}>
                Close
              </Button>
            </Modal.Footer>
          </Modal.Dialog>
        </Modal.Container>
      </Modal.Backdrop>

      {/* How staff works */}
      <Panel title="How staff works">
        <ul className="list-disc pl-5 space-y-1.5 text-xs text-muted leading-relaxed">
          <li>Candidates arrive each game day with unique traits, asking salaries, and starting morale.</li>
          <li>New hires are automatically assigned to the first open post for their role. Reassign them anytime on the roster.</li>
          <li>Salaries are deducted every night, regardless of whether staff are currently assigned or idle.</li>
          <li>Working staff maintain morale better than idle staff. Below 10% morale, employees may resign.</li>
          <li>
            Only groomer operators gain operational benefits from training (restoring additional slope condition up to Level {maxGroomerLevel}).
          </li>
        </ul>
      </Panel>
    </Page>
  );
}
