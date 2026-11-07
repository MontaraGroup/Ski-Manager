import { Chip } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Empty, Figure, Page, Panel, PostButton } from '@/components/Page';

export type TournamentItem = {
  id: number;
  name: string;
  description: string;
  start_day: number;
  end_day: number;
  daysUntil: number;
  status: 'active' | 'upcoming' | 'ended';
  visitors_boost: number;
  reputation_boost: number;
  prize_pool: number;
  prizeText: string;
  icon: string;
  canCancel: boolean;
};

export type SpecialEventItem = {
  id: number;
  name: string;
  description: string;
  event_type: string;
  game_day: number;
  duration_days: number;
  active: boolean;
  icon: string;
};

export type TournamentTypeItem = {
  key: string;
  name: string;
  desc: string;
  icon: string;
  visitors: number;
  reputation: number;
  duration: number;
  cost: number;
  costText: string;
};

export type TournamentsPageProps = {
  gameDay: number;
  stats: {
    activeTournaments: number;
    upcomingTournaments: number;
    activeEvents: number;
    totalHosted: number;
  };
  tournaments: TournamentItem[];
  events: SpecialEventItem[];
  types: TournamentTypeItem[];
};

export default function TournamentsPage({
  gameDay,
  stats,
  tournaments,
  events,
  types,
}: TournamentsPageProps) {
  return (
    <Page
      title="Events & Tournaments"
      icon="fa-trophy"
      iconTone="text-warning"
      subtitle="Host competitions to attract visitors, drive ticket sales, and earn reputation"
      width="max-w-6xl"
      actions={
        <div className="flex items-center gap-1.5 self-center rounded-full border border-border px-3 py-1 text-xs font-semibold tabular-nums text-muted">
          <span>Day {gameDay}</span>
        </div>
      }
    >
      {/* 4 Summary stats */}
      <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <Figure
          label="Live now"
          value={stats.activeTournaments}
          tone="text-success"
          note="Active competitions"
        />
        <Figure
          label="Upcoming"
          value={stats.upcomingTournaments}
          tone="text-accent"
          note="Scheduled to run"
        />
        <Figure
          label="Active events"
          value={stats.activeEvents}
          tone="text-warning"
          note="Seasonal mountain events"
        />
        <Figure
          label="Total hosted"
          value={stats.totalHosted}
          note="All-time tournaments"
        />
      </div>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {/* Tournaments list + special events (2 cols) */}
        <div className="lg:col-span-2 space-y-6">
          {/* Your Tournaments */}
          <Panel
            title={
              <div className="flex items-center gap-2">
                <Icon name="fa-trophy" className="text-warning" />
                <span>Your tournaments</span>
              </div>
            }
          >
            {tournaments.length === 0 ? (
              <Empty
                icon="fa-trophy"
                title="No tournaments hosted yet"
              >
                Host an alpine slalom, downhill championship, or winter festival to attract thousands of visitors to your mountain.
              </Empty>
            ) : (
              <div className="space-y-3">
                {tournaments.map((t) => (
                  <div
                    key={t.id}
                    className={`flex flex-col gap-3 rounded-lg border bg-surface-secondary/40 p-4 sm:flex-row sm:items-center sm:justify-between ${
                      t.status === 'active' ? 'border-success/40' : 'border-border'
                    }`}
                  >
                    <div className="flex items-start gap-3 flex-1 min-w-0">
                      <div
                        className={`flex size-10 shrink-0 items-center justify-center rounded-lg border ${
                          t.status === 'active'
                            ? 'border-success/30 bg-success/10 text-success'
                            : t.status === 'upcoming'
                            ? 'border-accent/30 bg-accent-soft text-accent-soft-foreground'
                            : 'border-border bg-surface text-muted'
                        }`}
                      >
                        <Icon name={t.icon || 'fa-trophy'} className="text-lg" />
                      </div>

                      <div className="min-w-0 flex-1">
                        <div className="flex items-center gap-2">
                          <span className="font-semibold text-sm">{t.name}</span>
                          {t.status === 'active' ? (
                            <Chip size="sm" variant="soft" color="success">
                              Live
                            </Chip>
                          ) : t.status === 'upcoming' ? (
                            <Chip size="sm" variant="soft" color="accent">
                              In {t.daysUntil}d
                            </Chip>
                          ) : (
                            <Chip size="sm" variant="soft" color="default">
                              Ended
                            </Chip>
                          )}
                        </div>

                        <div className="mt-1 flex flex-wrap items-center gap-3 text-xs text-muted">
                          <span className="tabular-nums">
                            Day {t.start_day}–{t.end_day}
                          </span>
                          <span className="tabular-nums font-medium text-foreground">
                            +{t.visitors_boost.toLocaleString()} visitors
                          </span>
                          <span className="tabular-nums font-medium text-foreground">
                            +{t.reputation_boost} rep
                          </span>
                          <span>
                            Prize: <span className="tabular-nums font-medium text-foreground">{t.prizeText}</span>
                          </span>
                        </div>
                      </div>
                    </div>

                    {t.canCancel && (
                      <div className="self-end sm:self-center">
                        <PostButton
                          action={`/tournaments/cancel/${t.id}`}
                          variant="ghost"
                          size="sm"
                          className="size-11 sm:size-8 flex items-center justify-center"
                          confirm={{
                            title: `Cancel ${t.name}`,
                            body: 'Cancel this scheduled tournament? If it has not yet started, an 80% refund of the prize pool will be returned to your cash balance.',
                            confirmLabel: 'Cancel tournament',
                          }}
                        >
                          <Icon name="fa-xmark" className="text-danger" />
                        </PostButton>
                      </div>
                    )}
                  </div>
                ))}
              </div>
            )}
          </Panel>

          {/* Special Events */}
          {events.length > 0 && (
            <Panel
              title={
                <div className="flex items-center gap-2">
                  <Icon name="fa-calendar-days" className="text-accent" />
                  <span>Mountain special events</span>
                </div>
              }
            >
              <div className="space-y-3">
                {events.map((e) => (
                  <div
                    key={e.id}
                    className={`flex items-start gap-3 rounded-lg border bg-surface-secondary/40 p-3 ${
                      e.active ? 'border-warning/40 ring-1 ring-warning/30' : 'border-border'
                    }`}
                  >
                    <div
                      className={`flex size-10 shrink-0 items-center justify-center rounded-lg border ${
                        e.active
                          ? 'border-warning/30 bg-warning/10 text-warning'
                          : 'border-border bg-surface text-muted'
                      }`}
                    >
                      <Icon name={e.icon || 'fa-calendar'} className="text-lg" />
                    </div>

                    <div className="min-w-0 flex-1">
                      <div className="flex items-center gap-2">
                        <span className="font-semibold text-sm">{e.name}</span>
                        {e.active && (
                          <Chip size="sm" variant="soft" color="warning">
                            Active
                          </Chip>
                        )}
                      </div>
                      <p className="mt-0.5 text-xs text-muted">{e.description}</p>
                      <div className="mt-1 text-xs text-muted tabular-nums">
                        Day {e.game_day} · {e.duration_days} day{e.duration_days > 1 ? 's' : ''} duration
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </Panel>
          )}
        </div>

        {/* Host Tournament catalog (1 col) */}
        <div className="space-y-6">
          <Panel
            title={
              <div className="flex items-center gap-2">
                <Icon name="fa-flag-checkered" className="text-warning" />
                <span>Host a tournament</span>
              </div>
            }
          >
            <div className="space-y-3">
              {types.map((type) => (
                <div
                  key={type.key}
                  className="rounded-lg border border-border bg-surface-secondary/40 p-3 space-y-2.5"
                >
                  <div className="flex items-start gap-3">
                    <div className="flex size-9 shrink-0 items-center justify-center rounded-lg border border-warning/30 bg-warning/10 text-warning">
                      <Icon name={type.icon || 'fa-trophy'} className="text-base" />
                    </div>
                    <div className="min-w-0 flex-1">
                      <div className="font-semibold text-sm">{type.name}</div>
                      <div className="text-xs text-muted">{type.desc}</div>
                    </div>
                  </div>

                  <div className="flex flex-wrap items-center gap-1.5 text-xs">
                    <Chip size="sm" variant="secondary">
                      +{type.visitors.toLocaleString()} visitors
                    </Chip>
                    <Chip size="sm" variant="secondary">
                      +{type.reputation} rep
                    </Chip>
                    <Chip size="sm" variant="secondary">
                      {type.duration}d
                    </Chip>
                  </div>

                  <div className="flex items-center justify-between border-t border-border pt-2">
                    <span className="tabular-nums font-semibold text-sm text-foreground">
                      {type.costText}
                    </span>
                    <PostButton
                      action="/tournaments/host"
                      data={{ type: type.key }}
                      variant="primary"
                      size="sm"
                      className="min-h-11 sm:min-h-8 font-semibold text-xs"
                      confirm={{
                        title: `Host ${type.name}`,
                        body: `Host ${type.name} starting tomorrow for ${type.costText}? The event runs for ${type.duration} day${type.duration > 1 ? 's' : ''} and brings +${type.visitors.toLocaleString()} visitors.`,
                        confirmLabel: `Host for ${type.costText}`,
                      }}
                    >
                      <Icon name="fa-flag-checkered" className="mr-1 text-xs" />
                      Host
                    </PostButton>
                  </div>
                </div>
              ))}
            </div>
          </Panel>

          {/* How it works info panel */}
          <Panel title="How tournaments work">
            <ul className="space-y-1.5 text-xs text-muted list-disc pl-4">
              <li>The host pays the prize pool upfront from resort cash reserves.</li>
              <li>Competitions draw additional daily visitors beyond standard base ticket demand.</li>
              <li>Your resort earns permanent reputation bonuses upon successful completion.</li>
              <li>Events run over designated day schedules; unstarted events can be cancelled for an 80% refund.</li>
              <li>Completed tournaments yield Génépis bonuses at end of competition.</li>
            </ul>
          </Panel>
        </div>
      </div>
    </Page>
  );
}
