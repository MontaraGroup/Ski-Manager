import { useState } from 'react';
import { Button, Chip } from '@heroui/react';
import { Checkbox } from '@/components/Checkbox';
import { Icon } from '@/components/Icon';
import { CsrfInput, Empty, Page, Panel, PostButton } from '@/components/Page';
import { Select } from '@/components/Select';

export type AllianceDirectoryItem = {
  id: number;
  name: string;
  tag: string;
  motto: string;
  founder_name: string;
  crest_icon: string;
  crest_color: string;
  level: number;
  member_count: number;
  max_members: number;
  valuation: number;
  valuationText: string;
  pass_tier: number;
  passTierName: string;
  passVisitorPct: number;
  min_reputation: number;
  is_recruiting: boolean;
  isFull: boolean;
  meetsRep: boolean;
  canJoin: boolean;
  canApply: boolean;
};

export type AllianceDirectoryUser = {
  cash: number;
  cashText: string;
  rep: number;
  canFound: boolean;
};

export type AllianceDirectoryMyApplication = {
  id: number;
  alliance_id: number;
  created_at: string;
} | null;

export type AllianceDirectoryPageProps = {
  alliances: AllianceDirectoryItem[];
  user: AllianceDirectoryUser;
  myApplication: AllianceDirectoryMyApplication;
  foundingFee: number;
  foundingFeeText: string;
};

const CREST_ICONS = [
  { value: 'fa-mountain-sun', label: 'Mountain Sun' },
  { value: 'fa-snowflake', label: 'Snowflake' },
  { value: 'fa-shield-halved', label: 'Shield' },
  { value: 'fa-crown', label: 'Crown' },
  { value: 'fa-award', label: 'Medal' },
  { value: 'fa-person-skiing', label: 'Ski Racer' },
  { value: 'fa-bolt', label: 'Lightning Bolt' },
];

const CREST_COLORS = [
  { value: '#3b82f6', label: 'Alpine Blue' },
  { value: '#10b981', label: 'Emerald Green' },
  { value: '#f59e0b', label: 'Sunset Amber' },
  { value: '#8b5cf6', label: 'Royal Purple' },
  { value: '#ef4444', label: 'Summit Crimson' },
  { value: '#0f172a', label: 'Carbon Slate' },
];

export default function AllianceDirectoryPage({
  alliances,
  user,
  myApplication,
  foundingFee,
  foundingFeeText,
}: AllianceDirectoryPageProps) {
  const [foundModalOpen, setFoundModalOpen] = useState(false);
  const [applyTarget, setApplyTarget] = useState<AllianceDirectoryItem | null>(null);

  return (
    <Page
      title="Resort Alliances & Syndicates"
      icon="fa-handshake"
      iconTone="text-accent"
      subtitle="Band together with fellow directors to co-brand a multi-pass, pool capital, and unlock cooperative fleet discounts."
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
          <Button
            variant="primary"
            onPress={() => setFoundModalOpen(true)}
            className="min-h-[44px] font-bold"
          >
            <Icon name="fa-plus" className="mr-1.5" />
            Found an Alliance
          </Button>
        </div>
      }
      width="max-w-7xl"
    >
      {/* Pending Application Notice */}
      {myApplication && (
        <div className="flex items-center gap-2.5 rounded-xl border border-warning/40 bg-warning/10 p-3 text-xs text-foreground">
          <Icon name="fa-hourglass-half" className="text-warning" />
          <span>
            You have a pending application with an alliance. Leadership will review your credentials
            shortly.
          </span>
        </div>
      )}

      {/* 3 Syndicate Pillars Cards */}
      <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
        <Panel className="space-y-2">
          <div className="flex size-8 items-center justify-center rounded-lg bg-accent/10 text-accent">
            <Icon name="fa-id-badge" />
          </div>
          <h2 className="text-sm font-bold text-foreground">Syndicate Multi-Pass</h2>
          <p className="text-xs leading-relaxed text-muted">
            Co-brand a shared mountain pass that drives +5% to +20% cross-mountain visitors and extra
            price tolerance across all sister resorts.
          </p>
        </Panel>
        <Panel className="space-y-2">
          <div className="flex size-8 items-center justify-center rounded-lg bg-success/10 text-success">
            <Icon name="fa-truck-monster" />
          </div>
          <h2 className="text-sm font-bold text-foreground">Bulk Equipment Procurement</h2>
          <p className="text-xs leading-relaxed text-muted">
            Pool capital into the shared vault to research cooperative purchasing agreements, slashing
            groomer and snowmaker costs by up to 20%.
          </p>
        </Panel>
        <Panel className="space-y-2">
          <div className="flex size-8 items-center justify-center rounded-lg bg-info/10 text-info">
            <Icon name="fa-ranking-star" />
          </div>
          <h2 className="text-sm font-bold text-foreground">Seasonal Alliance Trophy</h2>
          <p className="text-xs leading-relaxed text-muted">
            Compete on the global Alliance Leaderboard. Carry your syndicate tag and crest on your
            Director Accreditation Pass and public profile.
          </p>
        </Panel>
      </div>

      {/* Active Alliances Directory Table */}
      <Panel
        title={
          <div className="flex items-center gap-2">
            <span>Active Alliances</span>
            <Chip size="sm" variant="soft" className="font-mono">
              {alliances.length} Registered
            </Chip>
          </div>
        }
      >
        {alliances.length === 0 ? (
          <Empty
            icon="fa-mountain-sun"
            title="No alliances formed yet"
          >
            <p>Be the first ski director to establish a mountain syndicate and recruit partner resorts!</p>
            <div className="mt-3">
              <Button
                variant="primary"
                onPress={() => setFoundModalOpen(true)}
              >
                Found the First Alliance
              </Button>
            </div>
          </Empty>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead>
                <tr className="border-b border-border text-muted">
                  <th className="pb-2 font-medium">Alliance</th>
                  <th className="pb-2 font-medium">Level &amp; Pass</th>
                  <th className="pb-2 font-medium">Members</th>
                  <th className="pb-2 font-medium">Founder</th>
                  <th className="pb-2 font-medium">Total Valuation</th>
                  <th className="pb-2 font-medium">Requirements</th>
                  <th className="pb-2 text-right font-medium">Action</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {alliances.map((a) => (
                  <tr key={a.id} className="hover:bg-default/40">
                    <td className="py-2.5">
                      <div className="flex items-center gap-2.5">
                        <span
                          className="flex size-7 shrink-0 items-center justify-center rounded-lg text-xs font-bold text-white"
                          style={{ backgroundColor: a.crest_color }}
                        >
                          <Icon name={a.crest_icon} />
                        </span>
                        <div>
                          <div className="flex items-center gap-1.5 font-bold">
                            <a
                              href={`/alliances/view/${a.id}`}
                              className="text-foreground hover:text-accent hover:underline"
                            >
                              {a.name}
                            </a>
                            <span className="font-mono text-[11px] text-muted">[{a.tag}]</span>
                          </div>
                          {a.motto && (
                            <div className="line-clamp-1 text-[11px] italic text-muted">
                              &ldquo;{a.motto}&rdquo;
                            </div>
                          )}
                        </div>
                      </div>
                    </td>
                    <td className="py-2.5">
                      <Chip size="sm" variant="soft" color="accent" className="font-mono font-bold">
                        Lvl {a.level}
                      </Chip>
                      <div className="mt-0.5 text-[11px] text-muted">
                        {a.passTierName} (+{a.passVisitorPct}%)
                      </div>
                    </td>
                    <td className="py-2.5 font-mono">
                      <span className={a.isFull ? 'font-bold text-danger' : 'text-foreground'}>
                        {a.member_count} / {a.max_members}
                      </span>
                    </td>
                    <td className="py-2.5 text-muted">{a.founder_name}</td>
                    <td className="py-2.5 font-mono font-semibold text-success">{a.valuationText}</td>
                    <td className="py-2.5">
                      {a.min_reputation > 0 ? (
                        <Chip
                          size="sm"
                          variant="soft"
                          color={a.meetsRep ? 'default' : 'danger'}
                          className="font-mono"
                        >
                          {a.min_reputation}+ Rep
                        </Chip>
                      ) : (
                        <span className="text-xs text-muted">None</span>
                      )}
                    </td>
                    <td className="py-2.5 text-right">
                      {a.isFull ? (
                        <span className="rounded bg-default px-2 py-1 text-xs text-muted">
                          Full
                        </span>
                      ) : !a.meetsRep ? (
                        <span
                          className="rounded bg-danger/10 px-2 py-1 text-xs text-danger"
                          title={`Requires ${a.min_reputation} reputation`}
                        >
                          Low Rep
                        </span>
                      ) : a.canJoin ? (
                        <PostButton action={`/alliances/join/${a.id}`} size="sm" variant="primary">
                          Join
                        </PostButton>
                      ) : (
                        <Button
                          size="sm"
                          variant="secondary"
                          onPress={() => setApplyTarget(a)}
                        >
                          Apply
                        </Button>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </Panel>

      {/* Found Alliance Modal */}
      {foundModalOpen && (
        <div
          role="dialog"
          aria-modal="true"
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        >
          <div className="w-full max-w-lg rounded-xl border border-border bg-surface p-6 shadow-xl">
            <div className="mb-4 flex items-center justify-between border-b border-border pb-3">
              <div>
                <h3 className="text-base font-bold text-foreground">Charter a New Alliance</h3>
                <p className="text-xs text-muted">
                  Creation Fee: {foundingFeeText} (Your balance: {user.cashText})
                </p>
              </div>
              <Button
                type="button"
                variant="ghost"
                isIconOnly
                size="sm"
                onPress={() => setFoundModalOpen(false)}
                aria-label="Close modal"
                className="size-8 rounded-full text-muted hover:text-foreground"
              >
                <Icon name="fa-xmark" />
              </Button>
            </div>

            <form action="/alliances/create" method="post" className="space-y-3.5 text-xs">
              <CsrfInput />

              <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div className="sm:col-span-2">
                  <label className="mb-1 block font-medium text-foreground">Alliance Name</label>
                  <input
                    type="text"
                    name="name"
                    required
                    minLength={3}
                    maxLength={30}
                    placeholder="e.g. Summit Alpine Syndicate"
                    className="w-full min-h-11 sm:min-h-9 rounded-lg border border-border bg-surface px-3 py-2 text-base sm:text-sm text-foreground focus:border-accent focus:outline-none"
                  />
                </div>
                <div>
                  <label className="mb-1 block font-medium text-foreground">Tag Ticker</label>
                  <input
                    type="text"
                    name="tag"
                    required
                    minLength={3}
                    maxLength={5}
                    placeholder="SUMMT"
                    className="w-full min-h-11 sm:min-h-9 rounded-lg border border-border bg-surface px-3 py-2 font-mono uppercase text-base sm:text-sm text-foreground focus:border-accent focus:outline-none"
                  />
                </div>
              </div>

              <div>
                <label className="mb-1 block font-medium text-foreground">Charter Motto</label>
                <input
                  type="text"
                  name="motto"
                  maxLength={100}
                  placeholder="e.g. United by fresh powder and alpine elevation."
                  className="w-full min-h-11 sm:min-h-9 rounded-lg border border-border bg-surface px-3 py-2 text-base sm:text-sm text-foreground focus:border-accent focus:outline-none"
                />
              </div>

              <div>
                <label className="mb-1 block font-medium text-foreground">Charter Description</label>
                <textarea
                  name="description"
                  rows={2}
                  maxLength={500}
                  placeholder="Describe your syndicate vision and cooperative standards..."
                  className="w-full rounded-lg border border-border bg-surface px-3 py-2 text-base sm:text-sm text-foreground focus:border-accent focus:outline-none"
                />
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label className="mb-1 block font-medium text-foreground">Crest Icon</label>
                  <Select
                    name="crest_icon"
                    aria-label="Crest Icon"
                    defaultValue="fa-mountain-sun"
                    options={CREST_ICONS.map((i) => ({ id: i.value, label: i.label }))}
                  />
                </div>
                <div>
                  <label className="mb-1 block font-medium text-foreground">Crest Color</label>
                  <Select
                    name="crest_color"
                    aria-label="Crest Color"
                    defaultValue="#3b82f6"
                    options={CREST_COLORS.map((c) => ({ id: c.value, label: c.label }))}
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 rounded-lg border border-border bg-surface-raised p-3">
                <div>
                  <label className="mb-1 block font-medium text-foreground">
                    Min Reputation Requirement
                  </label>
                  <input
                    type="number"
                    name="min_reputation"
                    defaultValue={0}
                    min={0}
                    step={5}
                    className="w-full min-h-11 sm:min-h-9 rounded-lg border border-border bg-surface px-3 py-1.5 font-mono text-base sm:text-sm text-foreground focus:border-accent focus:outline-none"
                  />
                </div>
                <div className="flex flex-col justify-center pt-2">
                  <Checkbox
                    name="is_recruiting"
                    value="1"
                    defaultChecked
                    className="font-medium text-foreground"
                  >
                    Open Recruitment
                  </Checkbox>
                  <span className="text-xs text-muted pl-7">
                    Allow direct joining without approval
                  </span>
                </div>
              </div>

              <div className="flex items-center justify-end gap-2 border-t border-border pt-3">
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  onPress={() => setFoundModalOpen(false)}
                >
                  Cancel
                </Button>
                <Button
                  type="submit"
                  variant="primary"
                  size="sm"
                  isDisabled={user.cash < foundingFee}
                  className="font-bold"
                >
                  Charter Alliance ({foundingFeeText})
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Apply to Alliance Modal */}
      {applyTarget && (
        <div
          role="dialog"
          aria-modal="true"
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        >
          <div className="w-full max-w-md rounded-xl border border-border bg-surface p-6 shadow-xl">
            <div className="mb-4 flex items-center justify-between border-b border-border pb-3">
              <div>
                <h3 className="text-base font-bold text-foreground">
                  Apply to {applyTarget.name}
                </h3>
                <p className="text-xs text-muted">
                  Alliance leadership will review your credentials before admitting your mountain.
                </p>
              </div>
              <Button
                type="button"
                variant="ghost"
                isIconOnly
                size="sm"
                onPress={() => setApplyTarget(null)}
                aria-label="Close modal"
                className="size-8 rounded-full text-muted hover:text-foreground"
              >
                <Icon name="fa-xmark" />
              </Button>
            </div>

            <form
              action={`/alliances/join/${applyTarget.id}`}
              method="post"
              className="space-y-3.5 text-xs"
            >
              <CsrfInput />
              <div>
                <label className="mb-1 block font-medium text-foreground">Introduction Note</label>
                <textarea
                  name="message"
                  rows={4}
                  required
                  placeholder="Introduce your resort, slope capacity, and why you want to join..."
                  className="w-full rounded-lg border border-border bg-surface px-3 py-2 text-base sm:text-xs text-foreground focus:border-accent focus:outline-none"
                />
              </div>

              <div className="flex items-center justify-end gap-2 border-t border-border pt-3">
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  onPress={() => setApplyTarget(null)}
                >
                  Cancel
                </Button>
                <Button type="submit" variant="primary" size="sm">
                  Submit Application
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}
    </Page>
  );
}
