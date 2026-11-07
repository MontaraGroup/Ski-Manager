import { Chip, Meter } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Figure, Page, Panel, PostButton } from '@/components/Page';

export type ResortCandidate = {
  key: string;
  name: string;
  location: string;
  tagline: string;
  desc: string;
  icon: string;
  specs: {
    vertical: string;
    acres: string;
    summit: string;
    snowfall: string;
    difficulty: {
      green: number;
      blue: number;
      black: number;
      double_black: number;
    };
  };
  perk: {
    title: string;
    badge: string;
    icon: string;
    desc: string;
  };
  votes: number;
  percentage: number;
  isLeading: boolean;
  isUserVote: boolean;
};

export type RecentVote = {
  username: string;
  resort_key: string;
  resortName: string;
  timeAgoText: string;
};

export type VotePageProps = {
  season: {
    number: number;
    day: number;
    length: number;
    daysRemaining: number;
  };
  stats: {
    totalVotes: number;
    leadingKey: string | null;
    leadingName: string | null;
    candidateCount: number;
  };
  userVote: {
    hasVoted: boolean;
    resortKey: string | null;
    resortName: string | null;
  };
  options: ResortCandidate[];
  recentVotes: RecentVote[];
};

export default function VotePage({
  season,
  stats,
  userVote,
  options,
  recentVotes,
}: VotePageProps) {
  return (
    <Page
      title="Vote for Next Season's Mountain"
      icon="fa-check-to-slot"
      iconTone="text-accent"
      subtitle="Cast your ballot to choose the next iconic mountain destination for Season 4"
      width="max-w-6xl"
      actions={
        userVote.hasVoted ? (
          <PostButton
            action="/vote/retract"
            variant="tertiary"
            size="sm"
            className="min-h-11 sm:min-h-8 font-semibold text-xs"
            confirm={{
              title: 'Retract your vote',
              body: `Retract your active ballot for ${userVote.resortName}? You can vote again at any time before Season ${season.number} ends.`,
              confirmLabel: 'Retract vote',
            }}
          >
            <Icon name="fa-rotate-left" className="mr-1.5 text-xs" />
            Retract vote
          </PostButton>
        ) : undefined
      }
    >
      {/* Voted notification banner */}
      {userVote.hasVoted && (
        <div className="flex items-center gap-2.5 rounded-lg border border-accent/30 bg-accent-soft p-3 text-xs text-accent-soft-foreground font-medium">
          <Icon name="fa-circle-check" className="text-sm shrink-0" />
          <span>
            You voted for <strong>{userVote.resortName}</strong>. You may switch candidate or retract your ballot until season end.
          </span>
        </div>
      )}

      {/* 4 Summary stats */}
      <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <Figure
          label="Total ballots cast"
          value={stats.totalVotes.toLocaleString()}
          note="Community participation"
        />
        <Figure
          label="Current leader"
          value={stats.leadingName || 'Tied'}
          tone="text-success"
          note="Top destination"
        />
        <Figure
          label="Season timeline"
          value={`Day ${season.day} / ${season.length}`}
          note={`${season.daysRemaining} days until voting closes`}
        />
        <Figure
          label="Candidates"
          value={stats.candidateCount}
          note="Iconic resort nominees"
        />
      </div>

      {/* Resort candidates grid */}
      <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
        {options.map((opt) => (
          <Panel
            key={opt.key}
            className={`flex flex-col justify-between space-y-4 ${
              opt.isUserVote
                ? 'border-accent/40 ring-1 ring-accent/30'
                : opt.isLeading
                ? 'border-success/30'
                : ''
            }`}
          >
            <div>
              {/* Header row */}
              <div className="flex items-start justify-between gap-3 mb-2">
                <div className="flex items-center gap-3">
                  <div className="flex size-11 shrink-0 items-center justify-center rounded-xl border border-border bg-surface text-xl">
                    <Icon name={opt.icon || 'fa-mountain'} className="text-accent" />
                  </div>
                  <div>
                    <h3 className="font-bold text-base leading-tight">{opt.name}</h3>
                    <div className="text-xs text-muted font-medium">{opt.location}</div>
                  </div>
                </div>

                <div className="flex flex-col items-end gap-1">
                  {opt.isUserVote && (
                    <Chip size="sm" variant="soft" color="accent">
                      Your vote
                    </Chip>
                  )}
                  {opt.isLeading && (
                    <Chip size="sm" variant="soft" color="success">
                      Leading
                    </Chip>
                  )}
                </div>
              </div>

              <div className="text-xs font-semibold text-accent mb-2">{opt.tagline}</div>
              <p className="text-xs text-muted mb-4 leading-relaxed">{opt.desc}</p>

              {/* Vote progress meter */}
              <div className="mb-4 rounded-lg border border-border bg-surface-secondary/40 p-3">
                <div className="flex justify-between text-xs mb-1.5 font-medium">
                  <span>Ballot standing</span>
                  <span className="tabular-nums">
                    {opt.percentage}% ({opt.votes.toLocaleString()} vote{opt.votes === 1 ? '' : 's'})
                  </span>
                </div>
                <Meter
                  value={opt.percentage}
                  maxValue={100}
                  color={opt.isLeading ? 'success' : 'accent'}
                  aria-label={`${opt.name} vote percentage`}
                />
              </div>

              {/* Specifications */}
              <dl className="grid grid-cols-2 gap-2 text-xs mb-4">
                <div className="rounded-lg border border-border bg-surface-secondary/30 p-2 text-center">
                  <dt className="text-muted">Vertical drop</dt>
                  <dd className="font-semibold tabular-nums mt-0.5">{opt.specs.vertical}</dd>
                </div>
                <div className="rounded-lg border border-border bg-surface-secondary/30 p-2 text-center">
                  <dt className="text-muted">Skiable acres</dt>
                  <dd className="font-semibold tabular-nums mt-0.5">{opt.specs.acres}</dd>
                </div>
                <div className="rounded-lg border border-border bg-surface-secondary/30 p-2 text-center">
                  <dt className="text-muted">Summit elevation</dt>
                  <dd className="font-semibold tabular-nums mt-0.5">{opt.specs.summit}</dd>
                </div>
                <div className="rounded-lg border border-border bg-surface-secondary/30 p-2 text-center">
                  <dt className="text-muted">Annual snowfall</dt>
                  <dd className="font-semibold tabular-nums mt-0.5">{opt.specs.snowfall}</dd>
                </div>
              </dl>

              {/* Difficulty breakdown */}
              <div className="mb-4">
                <div className="text-xs uppercase tracking-wider text-muted font-medium mb-1.5">
                  Trail difficulty balance
                </div>
                <div className="flex gap-1.5 text-xs">
                  <span className="rounded bg-success/15 px-2 py-0.5 font-semibold text-success tabular-nums">
                    {opt.specs.difficulty.green}% Beginner
                  </span>
                  <span className="rounded bg-accent/15 px-2 py-0.5 font-semibold text-accent tabular-nums">
                    {opt.specs.difficulty.blue}% Intermediate
                  </span>
                  <span className="rounded bg-default px-2 py-0.5 font-semibold text-foreground tabular-nums">
                    {opt.specs.difficulty.black}% Advanced
                  </span>
                  {opt.specs.difficulty.double_black > 0 && (
                    <span className="rounded bg-danger/15 px-2 py-0.5 font-semibold text-danger tabular-nums">
                      {opt.specs.difficulty.double_black}% Expert
                    </span>
                  )}
                </div>
              </div>

              {/* Unique Mountain Perk */}
              <div className="rounded-lg border border-border bg-surface-secondary/40 p-3 mb-4 space-y-1">
                <div className="flex items-center gap-1.5 font-semibold text-xs text-foreground">
                  <Icon name={opt.perk.icon || 'fa-sparkles'} className="text-accent" />
                  <span>{opt.perk.title}</span>
                  <span className="text-muted font-normal">· {opt.perk.badge}</span>
                </div>
                <p className="text-xs text-muted">{opt.perk.desc}</p>
              </div>
            </div>

            {/* Voting action */}
            <PostButton
              action="/vote/cast"
              data={{ resort: opt.key }}
              variant={opt.isUserVote ? 'secondary' : 'primary'}
              size="md"
              disabled={opt.isUserVote}
              className="w-full min-h-11 font-semibold text-xs"
              confirm={
                !opt.isUserVote
                  ? {
                      title: `Vote for ${opt.name}`,
                      body: `Cast your official ballot for ${opt.name} as the destination for Season 4?`,
                      confirmLabel: `Vote for ${opt.name}`,
                    }
                  : undefined
              }
            >
              <Icon name={opt.isUserVote ? 'fa-check' : 'fa-check-to-slot'} className="mr-1.5" />
              {opt.isUserVote ? 'Active Ballot' : `Vote for ${opt.name}`}
            </PostButton>
          </Panel>
        ))}
      </div>

      {/* Recent community votes */}
      {recentVotes.length > 0 && (
        <Panel
          title={
            <div className="flex items-center gap-2">
              <Icon name="fa-users" className="text-accent" />
              <span>Recent community ballots</span>
            </div>
          }
        >
          <div className="divide-y divide-border">
            {recentVotes.map((rv, idx) => (
              <div
                key={idx}
                className="flex items-center justify-between py-2 text-xs first:pt-0 last:pb-0"
              >
                <div className="flex items-center gap-2">
                  <span className="font-semibold text-foreground">{rv.username}</span>
                  <span className="text-muted">voted for</span>
                  <span className="font-medium text-accent">{rv.resortName}</span>
                </div>
                {rv.timeAgoText && (
                  <span className="text-muted tabular-nums">{rv.timeAgoText}</span>
                )}
              </div>
            ))}
          </div>
        </Panel>
      )}
    </Page>
  );
}
