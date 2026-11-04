import { Chip } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Page, Panel, PostButton } from '@/components/Page';

export type AllianceViewAlliance = {
  id: number;
  name: string;
  tag: string;
  motto: string;
  description: string;
  level: number;
  treasury_cash: number;
  treasuryText: string;
  crest_icon: string;
  crest_color: string;
  pass_name: string;
  pass_tier: number;
  max_members: number;
  is_recruiting: boolean;
};

export type AllianceViewFounder = {
  id: number;
  username: string;
};

export type AllianceViewPass = {
  tier: number;
  name: string;
  visitorPct: number;
  desc: string;
};

export type AllianceViewMember = {
  user_id: number;
  username: string;
  role: 'founder' | 'officer' | 'member';
  resort_map: string;
  reputation: number;
  open_slopes: number;
  open_lifts: number;
};

export type AllianceViewPageProps = {
  alliance: AllianceViewAlliance;
  founder: AllianceViewFounder;
  pass: AllianceViewPass;
  members: AllianceViewMember[];
  isMember: boolean;
  canJoin: boolean;
};

export default function AllianceViewPage({
  alliance,
  founder,
  pass,
  members,
  isMember,
  canJoin,
}: AllianceViewPageProps) {
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
      back="/alliances"
      actions={
        <div className="flex flex-wrap items-center gap-2">
          <a
            href="/alliances/leaderboard"
            className="inline-flex min-h-[44px] items-center gap-1.5 rounded-lg border border-border px-3 text-sm font-medium hover:bg-default"
          >
            <Icon name="fa-ranking-star" className="text-warning" />
            Standings
          </a>
          {canJoin && (
            <PostButton
              action={`/alliances/join/${alliance.id}`}
              variant="primary"
              className="font-bold"
            >
              <Icon name="fa-handshake" className="mr-1.5" />
              Join Alliance
            </PostButton>
          )}
        </div>
      }
      width="max-w-6xl"
    >
      <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
        {/* Charter Overview */}
        <div className="md:col-span-2">
          <Panel title="Charter Overview" className="space-y-4">
            <p className="whitespace-pre-line text-sm leading-relaxed text-muted">
              {alliance.description || 'No charter overview written yet.'}
            </p>

            <div className="grid grid-cols-3 gap-3 border-t border-border pt-3 text-center">
              <div className="rounded-xl border border-border bg-surface-raised p-3">
                <div className="text-[11px] font-medium uppercase text-muted">Alliance Level</div>
                <div className="mt-0.5 text-lg font-bold text-accent">
                  Level {alliance.level}
                </div>
              </div>
              <div className="rounded-xl border border-border bg-surface-raised p-3">
                <div className="text-[11px] font-medium uppercase text-muted">Treasury Vault</div>
                <div className="mt-0.5 font-mono text-lg font-bold text-success">
                  {alliance.treasuryText}
                </div>
              </div>
              <div className="rounded-xl border border-border bg-surface-raised p-3">
                <div className="text-[11px] font-medium uppercase text-muted">Membership</div>
                <div className="mt-0.5 font-mono text-lg font-bold text-foreground">
                  {members.length} / {alliance.max_members}
                </div>
              </div>
            </div>
          </Panel>
        </div>

        {/* Syndicate Multi-Pass */}
        <div>
          <Panel
            title={
              <span className="font-mono text-xs font-bold uppercase text-warning">
                Syndicate Multi-Pass
              </span>
            }
            className="space-y-3 border-warning/30 bg-warning/5"
          >
            <h2 className="text-base font-bold text-foreground">{alliance.pass_name}</h2>
            <div className="text-xs font-semibold text-accent">
              Tier {alliance.pass_tier}: {pass.name} (+{pass.visitorPct}% visitors)
            </div>
            <p className="text-xs leading-relaxed text-muted">{pass.desc}</p>
            <div className="border-t border-border pt-2 text-[11px] text-muted">
              Founder: <strong className="text-foreground">{founder.username}</strong>
            </div>
          </Panel>
        </div>
      </div>

      {/* Allied Mountain Resorts */}
      <Panel
        title={
          <div className="flex items-center gap-2">
            <Icon name="fa-users" className="text-accent" />
            <span>Allied Mountain Resorts ({members.length})</span>
          </div>
        }
      >
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3">
          {members.map((m) => (
            <div
              key={m.user_id}
              className="flex items-center justify-between rounded-xl border border-border bg-surface-raised p-3 text-xs"
            >
              <div>
                <a
                  href={`/tour/${m.user_id}`}
                  className="font-bold text-foreground hover:text-accent hover:underline"
                >
                  {m.username}
                </a>
                <div className="text-[11px] text-muted">
                  {m.resort_map} &middot;{' '}
                  <span className="font-mono text-foreground">{m.open_slopes} Slopes</span>
                </div>
              </div>
              {m.role === 'founder' ? (
                <Chip size="sm" variant="soft" color="warning" className="font-mono font-bold capitalize">
                  Founder
                </Chip>
              ) : m.role === 'officer' ? (
                <Chip size="sm" variant="soft" color="accent" className="font-mono font-bold capitalize">
                  Officer
                </Chip>
              ) : null}
            </div>
          ))}
        </div>
      </Panel>
    </Page>
  );
}
