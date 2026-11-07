import { useState } from 'react';
import { Meter } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Empty, Figure, Page, Panel, PostButton } from '@/components/Page';

type State = 'ready' | 'progress' | 'claimed';
type Item = { id: number; name: string; description: string; icon: string; reward: string | null; state: State; pct: number; progress: string; unlocks: string | null };
type Props = {
  completed: number;
  total: number;
  readyCount: number;
  readyReward: string;
  earned: string;
  lockedFeatures: { feature: string; name: string; description: string; ready: boolean; pct: number }[];
  groups: { category: string; done: number; items: Item[] }[];
};
type Filter = 'all' | State;

/** /achievements: goals that pay cash and unlock features; ready ones can be claimed here */
export default function AchievementsPage(p: Props) {
  const [filter, setFilter] = useState<Filter>('all');
  const counts: Record<Filter, number> = { all: p.total, ready: p.readyCount, progress: p.total - p.completed, claimed: p.completed - p.readyCount };
  const groups = p.groups.map((g) => ({ ...g, items: g.items.filter((a) => filter === 'all' || a.state === filter) })).filter((g) => g.items.length > 0);

  return (
    <Page
      title="Achievements"
      icon="fa-award"
      iconTone="text-warning"
      subtitle="Goals that pay cash rewards; some unlock new parts of the game. Progress updates every night and when you open this page."
    >
      <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div className="rounded-xl border border-border bg-surface p-4">
          <Meter value={p.completed} maxValue={Math.max(1, p.total)} color="warning">
            <span className="text-sm text-muted">Completed</span>
            <Meter.Output className="sr-only" />
            <div className="w-full text-2xl font-semibold tabular-nums">{p.completed} <span className="text-sm font-normal text-muted">of {p.total}</span></div>
            <Meter.Track><Meter.Fill /></Meter.Track>
          </Meter>
        </div>
        <div className={`rounded-xl border bg-surface p-4 ${p.readyCount ? 'border-warning' : 'border-border'}`}>
          <div className="text-sm text-muted">Ready to claim</div>
          <div className="text-2xl font-semibold tabular-nums text-warning">{p.readyReward}</div>
          {p.readyCount > 0 ? (
            <PostButton action="/achievements/claim-all" variant="primary" size="sm" className="mt-2 min-h-11 sm:min-h-8 font-semibold text-xs">Claim all {p.readyCount}</PostButton>
          ) : (
            <div className="text-sm text-muted">Nothing waiting</div>
          )}
        </div>
        <Figure label="Rewards earned" value={p.earned} tone="text-success" note="All seasons" />
      </div>

      {p.lockedFeatures.length > 0 && (
        <Panel title={<span className="flex items-center gap-2"><Icon name="fa-lock" className="text-link" />Features still to unlock</span>}>
          <p className="mb-3 text-sm text-muted">Claim the achievement to open the feature.</p>
          <ul className="grid grid-cols-1 gap-2 sm:grid-cols-2">
            {p.lockedFeatures.map((f) => (
              <li key={f.feature} className="flex items-center gap-3 rounded-lg bg-default p-3">
                <div className="min-w-0 flex-1">
                  <div className="truncate text-sm font-semibold">{f.feature}</div>
                  <div className="truncate text-sm text-muted">{f.name}: {f.description}</div>
                </div>
                {f.ready ? <span className="rounded-full bg-warning px-2 text-sm text-warning-foreground">Claim to unlock</span> : <span className="text-sm tabular-nums text-muted">{f.pct}%</span>}
              </li>
            ))}
          </ul>
        </Panel>
      )}

      <div role="group" aria-label="Show achievements" className="flex w-fit flex-wrap gap-1 rounded-xl bg-default p-1">
        {(['all', 'ready', 'progress', 'claimed'] as Filter[]).map((f) => (
          <button
            key={f}
            type="button"
            aria-pressed={filter === f}
            onClick={() => setFilter(f)}
            className={`flex min-h-10 pointer-coarse:min-h-11 items-center gap-2 rounded-lg px-4 text-sm font-medium ${filter === f ? 'bg-surface shadow-sm' : 'text-muted hover:text-foreground'}`}
          >
            {{ all: 'All', ready: 'Ready', progress: 'In progress', claimed: 'Done' }[f]}
            <span className="tabular-nums text-muted">{counts[f]}</span>
          </button>
        ))}
      </div>

      {groups.length === 0 ? (
        <Panel><Empty icon="fa-award" title="Nothing here">{filter === 'ready' ? 'No rewards are waiting. Finished goals appear here to claim.' : 'No achievements match this filter.'}</Empty></Panel>
      ) : groups.map((g) => (
        <section key={g.category} className="flex flex-col gap-2">
          <h2 className="text-lg font-semibold">{g.category} <span className="text-sm font-normal text-muted">{g.done}/{p.groups.find((x) => x.category === g.category)?.items.length}</span></h2>
          <ul className="grid grid-cols-1 gap-2 md:grid-cols-2">
            {g.items.map((a) => <AchievementCard key={a.id} a={a} />)}
          </ul>
        </section>
      ))}
    </Page>
  );
}

function AchievementCard({ a }: { a: Item }) {
  return (
    <li className={`rounded-xl border bg-surface p-3 ${a.state === 'ready' ? 'border-warning' : 'border-border'}`}>
      <div className="flex items-start gap-3">
        <span className={`flex size-10 shrink-0 items-center justify-center rounded-lg ${a.state === 'progress' ? 'bg-default text-muted' : 'bg-warning-soft text-warning'}`}>
          <Icon name={a.icon} />
        </span>
        <div className="min-w-0 flex-1">
          <div className="flex items-baseline justify-between gap-2">
            <span className="text-sm font-semibold">{a.name}</span>
            {a.reward && <span className="shrink-0 text-sm font-semibold text-warning">{a.reward}</span>}
          </div>
          <p className="text-sm text-muted">{a.description}</p>
          {a.state === 'progress' && (
            <Meter value={a.pct} color="accent" size="sm" aria-label={`${a.name}: ${a.progress}`} className="mt-2">
              <Meter.Track className="flex-1"><Meter.Fill /></Meter.Track>
              <span className="shrink-0 text-sm tabular-nums text-muted">{a.progress}</span>
            </Meter>
          )}
          <div className="mt-2 flex flex-wrap items-center gap-2">
            {a.unlocks && (
              <span className="flex items-center gap-1 rounded-full bg-default px-2 text-sm">
                <Icon name={a.state === 'claimed' ? 'fa-lock-open' : 'fa-lock'} />
                {a.state === 'claimed' ? 'Unlocked' : 'Unlocks'}: {a.unlocks}
              </span>
            )}
            {a.state === 'ready' && <PostButton action={`/achievements/claim/${a.id}`} variant="primary" size="sm" className="ml-auto min-h-11 sm:min-h-8 font-semibold text-xs">Claim</PostButton>}
            {a.state === 'claimed' && <span className="ml-auto flex items-center gap-1 text-sm text-success"><Icon name="fa-check" />Claimed</span>}
          </div>
        </div>
      </div>
    </li>
  );
}
