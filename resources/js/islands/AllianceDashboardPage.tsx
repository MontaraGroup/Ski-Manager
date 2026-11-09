import { useState } from 'react';
import { Button, Chip } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput, Figure, Page, Panel, PostButton } from '@/components/Page';

export type AllianceProps = {
  id: number;
  name: string;
  tag: string;
  motto: string;
  level: number;
  xp: number;
  treasury_cash: number;
  treasuryText: string;
  crest_icon: string;
  crest_color: string;
  pass_name: string;
  pass_tier: number;
  max_members: number;
};

export type AllianceStatsProps = {
  memberCount: number;
  maxMembers: number;
  totalSlopes: number;
  totalLifts: number;
  totalSlopeKm: number;
  combinedValuationText: string;
};

export type AlliancePassNextTier = {
  tier: number;
  name: string;
  req_level: number;
  cost: number;
  costText: string;
  visitor_pct: number;
  canAfford: boolean;
  meetsLevel: boolean;
};

export type AlliancePassProps = {
  tier: number;
  tierName: string;
  visitorPct: number;
  sisterBonus: number;
  totalBoost: number;
  desc: string;
  nextTier: AlliancePassNextTier | null;
};

export type AlliancePerkProps = {
  key: string;
  name: string;
  desc: string;
  icon: string;
  curLevel: number;
  curEffect: string | null;
  isMax: boolean;
  nextLevel: number;
  nextCost: number;
  nextCostText: string;
  nextEffect: string;
  nextReqLevel: number;
  canAfford: boolean;
  meetsLevel: boolean;
};

export type AllianceMemberProps = {
  membership_id: number;
  user_id: number;
  username: string;
  role: 'founder' | 'officer' | 'member';
  resort_map: string;
  reputation: number;
  open_slopes: number;
  open_lifts: number;
  cash: number;
  cashText: string;
  donated_cash: number;
  donatedText: string;
  isCurrentUser: boolean;
  canManage: boolean;
};

export type AllianceApplicationProps = {
  id: number;
  user_id: number;
  username: string;
  reputation: number;
  message: string;
  cashText: string;
};

export type AllianceActivityProps = {
  id: number;
  message: string;
  timeAgoText: string;
};

export type AllianceUserProps = {
  cash: number;
  cashText: string;
  rep: number;
  role: 'founder' | 'officer' | 'member';
  isLeader: boolean;
  isFounder: boolean;
};

export type AllianceDashboardPageProps = {
  alliance: AllianceProps;
  stats: AllianceStatsProps;
  pass: AlliancePassProps;
  perks: AlliancePerkProps[];
  members: AllianceMemberProps[];
  applications: AllianceApplicationProps[];
  activities: AllianceActivityProps[];
  user: AllianceUserProps;
};

export default function AllianceDashboardPage({
  alliance,
  stats,
  pass,
  perks,
  members,
  applications,
  activities,
  user,
}: AllianceDashboardPageProps) {
  const [donateAmount, setDonateAmount] = useState<number | ''>('');

  const quickAmounts = [10000, 50000, 100000];

  return (
    <Page
      title={alliance.name}
      icon={alliance.crest_icon || 'fa-shield'}
      subtitle={
        <span className="flex items-center gap-2">
          <span
            className="inline-flex size-5 items-center justify-center rounded text-[10px] font-bold text-white"
            style={{ backgroundColor: alliance.crest_color }}
          >
            <Icon name={alliance.crest_icon} />
          </span>
          <span className="font-mono font-bold">[{alliance.tag}]</span>
          {alliance.motto ? <span>&middot; &ldquo;{alliance.motto}&rdquo;</span> : null}
        </span>
      }
      back="/dashboard"
      actions={
        <div className="flex flex-wrap items-center gap-2">
          <a
            href="/alliances/leaderboard"
            className="inline-flex min-h-[44px] items-center gap-1.5 rounded-lg border border-border px-3 text-sm font-medium hover:bg-default"
          >
            <Icon name="fa-ranking-star" className="text-warning" />
            Standings
          </a>
          <PostButton
            action="/alliances/leave"
            variant="ghost"
            className="text-danger"
            confirm={{
              title: user.isFounder ? 'Leave or Disband Alliance?' : 'Leave Alliance?',
              body: user.isFounder
                ? 'Are you sure you want to leave? As the founder, leaving will disband the alliance if no successor is appointed.'
                : 'Are you sure you want to leave this alliance? You will lose access to the syndicate multi-pass and cooperative perks.',
              confirmLabel: 'Confirm Leave',
            }}
          >
            <Icon name="fa-right-from-bracket" className="mr-1.5" />
            Leave
          </PostButton>
        </div>
      }
      width="max-w-7xl"
    >
      {/* 4 Overview Figures */}
      <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <Figure
          label="Alliance Level"
          value={`Level ${alliance.level}`}
          tone="text-accent"
          note={<span className="font-mono">{alliance.xp.toLocaleString()} Total XP</span>}
        />
        <Figure
          label="Treasury Vault"
          value={alliance.treasuryText}
          tone="text-success"
          note="Shared syndicate capital"
        />
        <Figure
          label="Network Scale"
          value={`${stats.memberCount} / ${stats.maxMembers}`}
          note={`${stats.totalSlopes} Slopes &middot; ${stats.totalLifts} Lifts`}
        />
        <Figure
          label="Trail Network"
          value={`${stats.totalSlopeKm} km`}
          tone="text-info"
          note={`${stats.combinedValuationText} Valuation`}
        />
      </div>

      {/* Syndicate Multi-Pass Card */}
      <Panel
        title={
          <div className="flex items-center gap-2">
            <span className="inline-flex items-center rounded-full bg-warning/15 px-2 py-0.5 font-mono text-xs font-bold uppercase text-warning">
              Syndicate Multi-Pass
            </span>
            <span className="text-base font-semibold">{alliance.pass_name}</span>
          </div>
        }
        className="border-warning/30 bg-warning/5"
      >
        <div className="space-y-4">
          <div className="flex flex-wrap items-center justify-between gap-3">
            <div>
              <div className="text-sm font-medium">
                Tier {pass.tier}: {pass.tierName}
              </div>
              <p className="mt-0.5 text-xs text-muted">{pass.desc}</p>
            </div>
            <div className="flex items-center gap-4 text-xs font-mono">
              <div>
                <span className="text-muted">Base Pass:</span>{' '}
                <strong className="text-success">+{pass.visitorPct}%</strong>
              </div>
              <div>
                <span className="text-muted">Sister Bonus:</span>{' '}
                <strong className="text-success">+{pass.sisterBonus}%</strong>
              </div>
              <div className="rounded-lg bg-success/15 px-2 py-1 text-sm font-bold text-success">
                +{pass.totalBoost}% visitors
              </div>
            </div>
          </div>

          {pass.nextTier ? (
            <div className="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-border bg-surface p-3 text-xs">
              <div>
                <div className="font-semibold text-foreground">
                  Next Upgrade: Tier {pass.nextTier.tier} &middot; {pass.nextTier.name} (+
                  {pass.nextTier.visitor_pct}% visitor boost)
                </div>
                <div className="mt-0.5 text-muted">
                  Required: Level {pass.nextTier.req_level} &middot; Cost:{' '}
                  <span className="font-mono font-medium text-foreground">
                    {pass.nextTier.costText}
                  </span>
                </div>
              </div>
              {user.isLeader && (
                <PostButton
                  action="/alliances/upgrade-pass"
                  variant="primary"
                  size="sm"
                  disabled={!pass.nextTier.canAfford || !pass.nextTier.meetsLevel}
                >
                  <Icon name="fa-arrow-up" className="mr-1.5" />
                  Upgrade Multi-Pass ({pass.nextTier.costText})
                </PostButton>
              )}
            </div>
          ) : (
            <div className="text-xs text-muted">
              Maximum syndicate multi-pass tier achieved.
            </div>
          )}
        </div>
      </Panel>

      {/* Cooperative Perk Tech Tree */}
      <Panel
        title={
          <div className="flex items-center gap-2">
            <Icon name="fa-microchip" className="text-accent" />
            <span>Syndicate Research Tree</span>
          </div>
        }
      >
        <div className="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
          {perks.map((perk) => (
            <div
              key={perk.key}
              className="flex flex-col justify-between rounded-lg border border-border bg-surface-raised p-3.5 text-xs"
            >
              <div className="space-y-2">
                <div className="flex items-start justify-between gap-2">
                  <div className="flex items-center gap-2">
                    <span className="flex size-7 items-center justify-center rounded bg-accent/10 text-accent">
                      <Icon name={perk.icon} />
                    </span>
                    <span className="font-semibold text-foreground">{perk.name}</span>
                  </div>
                  <Chip
                    size="sm"
                    variant="soft"
                    color={perk.isMax ? 'success' : perk.curLevel > 0 ? 'accent' : 'default'}
                    className="font-mono font-bold"
                  >
                    Lvl {perk.curLevel} / 3
                  </Chip>
                </div>
                <p className="text-muted">{perk.desc}</p>
                {perk.curEffect && (
                  <div className="rounded bg-success/10 px-2 py-1 text-success">
                    <Icon name="fa-check" className="mr-1" />
                    {perk.curEffect}
                  </div>
                )}
              </div>

              <div className="mt-3 border-t border-border pt-2">
                {perk.isMax ? (
                  <span className="font-medium text-success">Fully Researched</span>
                ) : (
                  <div className="flex items-center justify-between gap-2">
                    <div className="min-w-0">
                      <div className="truncate font-medium text-foreground">
                        {perk.nextEffect}
                      </div>
                      <div className="text-[11px] text-muted">
                        Req Lvl {perk.nextReqLevel} &middot;{' '}
                        <span className="font-mono">{perk.nextCostText}</span>
                      </div>
                    </div>
                    {user.isLeader && (
                      <PostButton
                        action="/alliances/unlock-perk"
                        data={{ perk_key: perk.key }}
                        size="sm"
                        variant="secondary"
                        disabled={!perk.canAfford || !perk.meetsLevel}
                      >
                        Research
                      </PostButton>
                    )}
                  </div>
                )}
              </div>
            </div>
          ))}
        </div>
      </Panel>

      {/* Allied Mountain Resorts Roster */}
      <Panel
        title={
          <div className="flex items-center gap-2">
            <Icon name="fa-users" className="text-accent" />
            <span>Allied Mountain Resorts ({members.length} / {stats.maxMembers})</span>
          </div>
        }
      >
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead>
              <tr className="border-b border-border text-muted">
                <th className="pb-2 font-medium">Director</th>
                <th className="pb-2 font-medium">Resort &amp; Slopes</th>
                <th className="pb-2 font-medium">Role</th>
                <th className="pb-2 font-medium">Reputation</th>
                <th className="pb-2 font-medium text-right">Donated</th>
                {user.isLeader && <th className="pb-2 text-right font-medium">Manage</th>}
              </tr>
            </thead>
            <tbody className="divide-y divide-border">
              {members.map((m) => (
                <tr key={m.membership_id} className="hover:bg-default/40">
                  <td className="py-2.5 font-semibold">
                    <a
                      href={`/tour/${m.user_id}`}
                      className="text-accent hover:underline"
                    >
                      {m.username}
                    </a>
                    {m.isCurrentUser && (
                      <span className="ml-1.5 text-[10px] text-muted">(You)</span>
                    )}
                  </td>
                  <td className="py-2.5 text-muted">
                    {m.resort_map} &middot;{' '}
                    <span className="font-mono text-foreground">{m.open_slopes} Slopes</span> &middot;{' '}
                    <span className="font-mono text-foreground">{m.open_lifts} Lifts</span>
                  </td>
                  <td className="py-2.5">
                    <Chip
                      size="sm"
                      variant="soft"
                      color={
                        m.role === 'founder'
                          ? 'warning'
                          : m.role === 'officer'
                            ? 'accent'
                            : 'default'
                      }
                      className="font-mono capitalize"
                    >
                      {m.role}
                    </Chip>
                  </td>
                  <td className="py-2.5 font-mono">{m.reputation.toLocaleString()}</td>
                  <td className="py-2.5 text-right font-mono text-success">{m.donatedText}</td>
                  {user.isLeader && (
                    <td className="py-2.5 text-right">
                      {m.canManage ? (
                        <div className="flex items-center justify-end gap-1">
                          {user.isFounder && (
                            <PostButton
                              action={`/alliances/transfer-founder/${m.membership_id}`}
                              size="sm"
                              variant="ghost"
                              confirm={{
                                title: 'Transfer Alliance Leadership?',
                                body: `Are you sure you want to transfer founder leadership to ${m.username}? You will become an officer.`,
                                confirmLabel: 'Transfer Leadership',
                              }}
                            >
                              Transfer
                            </PostButton>
                          )}
                          {m.role === 'officer' ? (
                            <PostButton
                              action={`/alliances/demote/${m.membership_id}`}
                              size="sm"
                              variant="ghost"
                            >
                              Demote
                            </PostButton>
                          ) : (
                            <PostButton
                              action={`/alliances/promote/${m.membership_id}`}
                              size="sm"
                              variant="ghost"
                            >
                              Promote
                            </PostButton>
                          )}
                          <PostButton
                            action={`/alliances/kick/${m.membership_id}`}
                            size="sm"
                            variant="ghost"
                            className="text-danger"
                            confirm={{
                              title: `Remove ${m.username}?`,
                              body: `Are you sure you want to remove ${m.username} from the alliance?`,
                              confirmLabel: 'Remove Member',
                            }}
                          >
                            Kick
                          </PostButton>
                        </div>
                      ) : (
                        <span className="text-[11px] text-muted">&mdash;</span>
                      )}
                    </td>
                  )}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </Panel>

      {/* Bottom Grid: Treasury Vault Deposit + Pending Applications + Activity Feed */}
      <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
        {/* Vault Deposit */}
        <Panel
          title={
            <div className="flex items-center gap-2">
              <Icon name="fa-vault" className="text-success" />
              <span>Vault Contribution</span>
            </div>
          }
        >
          <p className="text-xs text-muted">
            Pool funds to finance pass upgrades and perk research. Your personal balance:{' '}
            <strong className="font-mono text-foreground">{user.cashText}</strong>
          </p>
          <form action="/alliances/donate" method="post" className="mt-3 space-y-3">
            <CsrfInput />
            <div>
              <div className="flex items-center gap-1.5">
                <input
                  type="number"
                  name="amount"
                  min={1000}
                  max={user.cash}
                  step={1000}
                  placeholder="Amount (€)"
                  value={donateAmount}
                  onChange={(e) =>
                    setDonateAmount(e.target.value === '' ? '' : Number(e.target.value))
                  }
                  required
                  className="w-full rounded-lg border border-border bg-surface px-3 py-2 font-mono text-xs text-foreground focus:border-accent focus:outline-none"
                />
              </div>
              <div className="mt-2 flex flex-wrap gap-1.5">
                {quickAmounts.map((q) => (
                  <Button
                    key={q}
                    type="button"
                    variant="ghost"
                    size="sm"
                    onPress={() => setDonateAmount(q)}
                    isDisabled={user.cash < q}
                    className="font-mono text-[11px] px-2 py-1 min-h-7"
                  >
                    +{q.toLocaleString()} €
                  </Button>
                ))}
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  onPress={() => setDonateAmount(user.cash)}
                  isDisabled={user.cash <= 0}
                  className="font-mono text-[11px] font-semibold text-accent px-2 py-1 min-h-7"
                >
                  Max
                </Button>
              </div>
            </div>
            <Button
              type="submit"
              variant="primary"
              size="sm"
              className="w-full"
              isDisabled={!donateAmount || donateAmount < 1000 || donateAmount > user.cash}
            >
              Deposit into Vault
            </Button>
          </form>
        </Panel>

        {/* Pending Applications (if leader) or Syndicate Mission */}
        {user.isLeader && applications.length > 0 ? (
          <Panel
            title={
              <div className="flex items-center gap-2">
                <Icon name="fa-envelope-open-text" className="text-accent" />
                <span>Pending Applications ({applications.length})</span>
              </div>
            }
          >
            <div className="space-y-3">
              {applications.map((app) => (
                <div
                  key={app.id}
                  className="space-y-2 rounded-lg border border-border bg-surface-raised p-3 text-xs"
                >
                  <div className="flex items-center justify-between">
                    <span className="font-bold text-foreground">{app.username}</span>
                    <span className="font-mono text-muted">{app.reputation} Rep</span>
                  </div>
                  {app.message && (
                    <p className="italic text-muted">&ldquo;{app.message}&rdquo;</p>
                  )}
                  <div className="flex items-center justify-between border-t border-border pt-2">
                    <span className="font-mono text-[11px] text-muted">
                      Personal: {app.cashText}
                    </span>
                    <div className="flex items-center gap-1.5">
                      <PostButton
                        action="/alliances/review-application"
                        data={{ application_id: app.id, action: 'reject' }}
                        size="sm"
                        variant="ghost"
                        className="text-danger"
                      >
                        Reject
                      </PostButton>
                      <PostButton
                        action="/alliances/review-application"
                        data={{ application_id: app.id, action: 'accept' }}
                        size="sm"
                        variant="primary"
                      >
                        Accept
                      </PostButton>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </Panel>
        ) : (
          <Panel
            title={
              <div className="flex items-center gap-2">
                <Icon name="fa-bullhorn" className="text-accent" />
                <span>Recruitment Policy</span>
              </div>
            }
          >
            <div className="space-y-2 text-xs text-muted">
              <p>
                Your syndicate is currently welcoming new partner resorts. All approved member
                slopes count toward combined trail capacity and visitor volume.
              </p>
              <div className="rounded-lg bg-surface-raised p-2.5">
                <span className="font-semibold text-foreground">Capacity: </span>
                <span className="font-mono">
                  {members.length} / {alliance.max_members} resorts enrolled
                </span>
              </div>
            </div>
          </Panel>
        )}

        {/* Activity Feed */}
        <Panel
          title={
            <div className="flex items-center gap-2">
              <Icon name="fa-list-check" className="text-muted" />
              <span>Syndicate Ledger</span>
            </div>
          }
        >
          {activities.length === 0 ? (
            <p className="text-xs text-muted">No recent syndicate activity recorded.</p>
          ) : (
            <ul className="space-y-2 text-xs">
              {activities.slice(0, 6).map((act, idx) => (
                <li
                  key={act.id || idx}
                  className="flex items-start justify-between gap-2 border-b border-border/50 pb-1.5 last:border-b-0"
                >
                  <span className="text-foreground">{act.message}</span>
                  <span className="shrink-0 text-[10px] text-muted">{act.timeAgoText}</span>
                </li>
              ))}
            </ul>
          )}
        </Panel>
      </div>
    </Page>
  );
}
