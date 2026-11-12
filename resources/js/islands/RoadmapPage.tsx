import { useEffect, useMemo, useState } from 'react';
import { Button, Chip } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { csrf, CsrfInput, Page, Panel } from '@/components/Page';
import { Select } from '@/components/Select';

export type RoadmapItem = {
  id: number;
  title: string;
  description: string;
  status: 'suggested' | 'planned' | 'in_progress' | 'completed' | string;
  category: string;
  upvotes: number;
  author: string;
  mine: boolean;
  voted: boolean;
  updatesUrl: string | null;
  created_at: string;
};

export type RoadmapColumn = {
  status: string;
  title: string;
  description: string;
  icon: string;
  items: RoadmapItem[];
};

export type RoadmapPageProps = {
  isAdmin: boolean;
  stats: { totalItems: number; totalVotes: number };
  columns: RoadmapColumn[];
  me: {
    signedIn: boolean;
    canVote: boolean;
    canSuggest: boolean;
    earnsRewards: boolean;
    weeklyRewardDone: boolean;
    suggestionsLeft: number;
  };
  rewards: { voteWeekly: number; ideaShipped: number; perWeek: number };
  releases: { url: string; label: string }[];
};

const CATEGORIES = [
  { id: 'all', label: 'All' },
  { id: 'gameplay', label: 'Gameplay' },
  { id: 'economy', label: 'Economy' },
  { id: 'quality_of_life', label: 'Quality of life' },
  { id: 'mobile', label: 'Mobile' },
];
const CATEGORY_LABEL: Record<string, string> = {
  gameplay: 'Gameplay',
  economy: 'Economy',
  quality_of_life: 'Quality of life',
  mobile: 'Mobile',
};
const STATUS_TONE: Record<string, string> = {
  in_progress: 'text-accent',
  planned: 'text-foreground',
  suggested: 'text-warning',
  completed: 'text-success',
};
const STATUS_OPTIONS = [
  { id: 'suggested', label: 'Idea' },
  { id: 'planned', label: 'Planned' },
  { id: 'in_progress', label: 'In progress' },
  { id: 'completed', label: 'Shipped' },
];

export default function RoadmapPage({ isAdmin, stats, columns, me, rewards, releases }: RoadmapPageProps) {
  const [cols, setCols] = useState(columns);
  const [category, setCategory] = useState('all');
  const [sort, setSort] = useState<'votes' | 'newest'>('votes');
  const [busyId, setBusyId] = useState<number | null>(null);
  const [notice, setNotice] = useState<{ tone: 'success' | 'danger'; text: string } | null>(null);
  const [weeklyDone, setWeeklyDone] = useState(me.weeklyRewardDone);

  // Arriving from a notification or the dashboard (#item-12): bring that card into view
  useEffect(() => {
    const el = window.location.hash ? document.getElementById(window.location.hash.slice(1)) : null;
    el?.scrollIntoView({ block: 'center' });
  }, []);

  const myVotes = useMemo(() => cols.reduce((n, c) => n + c.items.filter((i) => i.voted).length, 0), [cols]);

  const vote = async (id: number) => {
    if (busyId !== null) return;
    setBusyId(id);
    try {
      const { field, token } = csrf();
      const fd = new FormData();
      fd.append(field, token);
      const res = await fetch(`/roadmap/vote/${id}`, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd });
      const data = await res.json().catch(() => null);
      if (!res.ok || !data?.success) {
        setNotice({ tone: 'danger', text: data?.message ?? 'Your vote did not go through. Try again.' });
        return;
      }
      setCols((prev) => prev.map((c) => ({
        ...c,
        items: c.items.map((it) => (it.id === id ? { ...it, voted: Boolean(data.voted), upvotes: data.upvotes } : it)),
      })));
      if (data.reward > 0) {
        setWeeklyDone(true);
        setNotice({ tone: 'success', text: data.message });
      } else {
        setNotice(null);
      }
    } finally {
      setBusyId(null);
    }
  };

  const sortItems = (items: RoadmapItem[]) =>
    [...items]
      .filter((it) => category === 'all' || it.category === category)
      .sort((a, b) => (sort === 'votes' ? b.upvotes - a.upvotes || b.id - a.id : b.id - a.id));

  return (
    <Page
      title="Roadmap"
      icon="fa-map-location-dot"
      subtitle="Vote on what gets built next. The most-voted ideas are reviewed first."
      width="max-w-6xl"
    >
      <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
        {/* Rules and rewards first on phones, beside the list on desktop */}
        <aside className="flex flex-col gap-4 lg:sticky lg:top-20 lg:order-last">
          <Panel title="How it works">
            <ul className="space-y-3 text-sm">
              <li className="flex gap-3">
                <Icon name="fa-check-to-slot" className="mt-0.5 text-accent" />
                <span>
                  <strong>Vote</strong> on any idea that isn't shipped yet. Your first vote each week on an idea you haven't voted on before earns{' '}
                  <strong>{rewards.voteWeekly} Génépis</strong>.
                  {me.earnsRewards && (
                    <span className={`mt-1 block text-xs font-semibold ${weeklyDone ? 'text-success' : 'text-muted'}`}>
                      {weeklyDone ? 'Earned this week' : 'Not earned yet this week'}
                    </span>
                  )}
                </span>
              </li>
              <li className="flex gap-3">
                <Icon name="fa-lightbulb" className="mt-0.5 text-warning" />
                <span>
                  <strong>Suggest</strong> up to {rewards.perWeek} ideas a week. They start in Ideas until we review them.
                </span>
              </li>
              <li className="flex gap-3">
                <Icon name="fa-rocket" className="mt-0.5 text-success" />
                <span>
                  If your idea <strong>ships</strong>, you earn <strong>{rewards.ideaShipped} Génépis</strong>.
                </span>
              </li>
              <li className="flex gap-3">
                <Icon name="fa-award" className="mt-0.5 text-accent" />
                <span>Voting and suggesting also unlock four Community achievements.</span>
              </li>
            </ul>
            {me.signedIn && !me.earnsRewards && me.canVote && (
              <p className="mt-3 text-xs text-muted">Demo accounts can vote but don't earn Génépis or achievements. Save your resort to earn them.</p>
            )}
            {!me.signedIn && (
              <a href="/login" className="mt-3 inline-flex min-h-11 items-center rounded-lg bg-accent px-4 text-sm font-semibold text-accent-foreground hover:opacity-90">
                Sign in to vote
              </a>
            )}
            <dl className="mt-4 grid grid-cols-3 gap-2 border-t border-border pt-3 text-center">
              <div>
                <dt className="text-xs text-muted">Ideas</dt>
                <dd className="font-mono text-lg font-semibold">{stats.totalItems}</dd>
              </div>
              <div>
                <dt className="text-xs text-muted">Votes</dt>
                <dd className="font-mono text-lg font-semibold">{stats.totalVotes}</dd>
              </div>
              <div>
                <dt className="text-xs text-muted">Yours</dt>
                <dd className="font-mono text-lg font-semibold">{myVotes}</dd>
              </div>
            </dl>
          </Panel>

          {me.canSuggest && <SuggestForm left={me.suggestionsLeft} perWeek={rewards.perWeek} />}
        </aside>

        <div className="flex min-w-0 flex-col gap-4">
          <div className="flex flex-wrap items-center gap-2">
            <div role="group" aria-label="Filter by category" className="flex flex-wrap gap-1.5">
              {CATEGORIES.map((c) => (
                <Button
                  key={c.id}
                  size="sm"
                  variant={category === c.id ? 'primary' : 'ghost'}
                  aria-pressed={category === c.id}
                  onPress={() => setCategory(c.id)}
                  className="min-h-11 rounded-full px-3"
                >
                  {c.label}
                </Button>
              ))}
            </div>
            <div className="ml-auto w-40">
              <Select
                aria-label="Sort ideas"
                value={sort}
                onChange={(v) => setSort(v === 'newest' ? 'newest' : 'votes')}
                options={[{ id: 'votes', label: 'Most votes' }, { id: 'newest', label: 'Newest' }]}
              />
            </div>
          </div>

          {notice && (
            <p role="status" className={`rounded-lg border px-3 py-2 text-sm ${notice.tone === 'success' ? 'border-success/40 bg-success/10 text-success' : 'border-danger/40 bg-danger/10 text-danger'}`}>
              {notice.text}
            </p>
          )}

          {cols.map((col) => {
            const items = sortItems(col.items);
            return (
              <section key={col.status} aria-labelledby={`col-${col.status}`} className="flex flex-col gap-2">
                <div className="flex items-baseline gap-2 border-b border-border pb-1.5">
                  <Icon name={col.icon} className={STATUS_TONE[col.status] ?? 'text-muted'} />
                  <h2 id={`col-${col.status}`} className="font-semibold">{col.title}</h2>
                  <span className="text-sm tabular-nums text-muted">{items.length}</span>
                  <p className="ml-auto hidden text-xs text-muted sm:block">{col.description}</p>
                </div>
                {items.length === 0 ? (
                  <p className="py-3 text-sm text-muted">Nothing here{category !== 'all' ? ' in this category' : ''} yet.</p>
                ) : (
                  <ul className="flex flex-col gap-2">
                    {items.map((it) => (
                      <li key={it.id}>
                        <ItemCard
                          item={it}
                          canVote={me.canVote}
                          busy={busyId === it.id}
                          onVote={() => vote(it.id)}
                          isAdmin={isAdmin}
                          releases={releases}
                        />
                      </li>
                    ))}
                  </ul>
                )}
              </section>
            );
          })}
        </div>
      </div>
    </Page>
  );
}

function ItemCard({ item, canVote, busy, onVote, isAdmin, releases }: {
  item: RoadmapItem;
  canVote: boolean;
  busy: boolean;
  onVote: () => void;
  isAdmin: boolean;
  releases: RoadmapPageProps['releases'];
}) {
  const shipped = item.status === 'completed';
  return (
    <article id={`item-${item.id}`} className="flex scroll-mt-24 gap-3 rounded-xl border border-border bg-surface p-3 target:ring-2 target:ring-accent">
      {shipped || !canVote ? (
        <div className="flex min-h-11 min-w-14 shrink-0 flex-col items-center justify-center self-start rounded-lg bg-default px-2 text-sm tabular-nums" aria-label={`${item.upvotes} votes`}>
          <Icon name="fa-chevron-up" className="text-[10px] text-muted" />
          {item.upvotes}
        </div>
      ) : (
        <Button
          variant={item.voted ? 'primary' : 'outline'}
          onPress={onVote}
          isDisabled={busy}
          aria-pressed={item.voted}
          aria-label={`${item.voted ? 'Remove your vote for' : 'Vote for'} ${item.title}, ${item.upvotes} votes`}
          className="h-auto min-h-11 min-w-14 shrink-0 flex-col gap-0 self-start px-2 tabular-nums"
        >
          <Icon name="fa-chevron-up" className="text-[10px]" />
          {item.upvotes}
        </Button>
      )}
      <div className="min-w-0 flex-1 space-y-1">
        <div className="flex flex-wrap items-center gap-1.5">
          <h3 className="font-semibold leading-snug">{item.title}</h3>
          {item.mine && <Chip size="sm" color="accent" variant="soft">Your idea</Chip>}
        </div>
        {item.description && <p className="text-sm leading-relaxed text-muted">{item.description}</p>}
        <p className="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted">
          <span>{CATEGORY_LABEL[item.category] ?? item.category}</span>
          <span>Suggested by <span className="text-foreground">{item.author}</span></span>
          {shipped && item.updatesUrl && (
            <a href={item.updatesUrl} className="inline-flex min-h-11 items-center font-medium text-accent hover:underline">Release notes</a>
          )}
        </p>
        {isAdmin && <AdminControls item={item} releases={releases} />}
      </div>
    </article>
  );
}

function SuggestForm({ left, perWeek }: { left: number; perWeek: number }) {
  return (
    <Panel title="Suggest an idea">
      {left <= 0 ? (
        <p className="text-sm text-muted">You've used your {perWeek} suggestions for this week. You can suggest more in a few days.</p>
      ) : (
        <form action="/roadmap/suggest" method="post" className="space-y-3">
          <CsrfInput />
          <div>
            <label htmlFor="rm-title" className="mb-1 block text-sm font-medium">Title</label>
            <input
              id="rm-title"
              name="title"
              required
              minLength={5}
              maxLength={100}
              placeholder="Helicopter skiing tours"
              className="min-h-11 w-full rounded-lg border border-border bg-field px-3 text-sm focus-visible:outline-2 focus-visible:outline-accent"
            />
          </div>
          <div>
            <label htmlFor="rm-desc" className="mb-1 block text-sm font-medium">What it does and why</label>
            <textarea
              id="rm-desc"
              name="description"
              required
              minLength={10}
              maxLength={1000}
              rows={3}
              className="w-full rounded-lg border border-border bg-field px-3 py-2 text-sm focus-visible:outline-2 focus-visible:outline-accent"
            />
          </div>
          <Select
            name="category"
            aria-label="Category"
            defaultValue="gameplay"
            options={CATEGORIES.filter((c) => c.id !== 'all')}
          />
          <Button type="submit" variant="primary" className="min-h-11 w-full">Suggest idea</Button>
          <p className="text-xs text-muted">{left} of {perWeek} suggestions left this week. Keep it to one idea per suggestion.</p>
        </form>
      )}
    </Panel>
  );
}

function AdminControls({ item, releases }: { item: RoadmapItem; releases: RoadmapPageProps['releases'] }) {
  const [status, setStatus] = useState(item.status);
  const [release, setRelease] = useState(item.updatesUrl ?? '');
  const [state, setState] = useState<'idle' | 'saving' | 'error'>('idle');

  const save = async () => {
    setState('saving');
    const { field, token } = csrf();
    const fd = new FormData();
    fd.append(field, token);
    fd.append('id', String(item.id));
    fd.append('status', status);
    if (release) fd.append('updates_url', release);
    const res = await fetch('/roadmap/admin/status', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd });
    if (res.ok) window.location.reload();
    else setState('error');
  };

  return (
    <div className="mt-2 flex flex-wrap items-center gap-2 border-t border-dashed border-border pt-2">
    <form onSubmit={(e) => { e.preventDefault(); save(); }} className="contents">
      <div className="w-36">
        <Select aria-label={`Status of ${item.title}`} value={status} onChange={(v) => setStatus(String(v))} options={STATUS_OPTIONS} />
      </div>
      {status === 'completed' && releases.length > 0 && (
        <div className="min-w-0 flex-1">
          <Select
            aria-label="Release it shipped in"
            value={release}
            onChange={(v) => setRelease(String(v))}
            options={[{ id: '', label: 'No release link' }, ...releases.map((r) => ({ id: r.url, label: r.label }))]}
          />
        </div>
      )}
      <Button type="submit" size="sm" variant="secondary" isDisabled={state === 'saving'} className="min-h-11">Save</Button>
      {state === 'error' && <span role="alert" className="text-xs text-danger">Not saved</span>}
    </form>
    <form
      action={`/roadmap/delete/${item.id}`}
      method="post"
      className="ml-auto"
      onSubmit={(e) => { if (!window.confirm('Delete this idea and all its votes?')) e.preventDefault(); }}
    >
      <CsrfInput />
      <Button type="submit" size="sm" variant="ghost" className="min-h-11 text-danger">Delete</Button>
    </form>
    </div>
  );
}
