import { createContext, useContext, useEffect, useMemo, useRef, useState, type ReactNode } from 'react';
import { AlertDialog, Button, Chip, SearchField, Spinner, buttonVariants, toast } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { csrf, CsrfInput, Empty, Page, Panel, PostButton } from '@/components/Page';
import { Select } from '@/components/Select';

export type RoadmapItem = {
  id: number;
  title: string;
  description: string;
  status: 'suggested' | 'planned' | 'in_progress' | 'in_beta' | 'completed' | string;
  category: string;
  upvotes: number;
  author: string;
  mine: boolean;
  voted: boolean;
  updatesUrl: string | null;
  created_at: string;
  /** Ideas an admin merged into this one: shown inside this card, their votes count here */
  merged?: MergedIdea[];
  /** Comments on this idea and the ideas merged into it */
  comments?: number;
};

export type MergedIdea = {
  id: number;
  title: string;
  description: string;
  author: string;
  mine: boolean;
};

export type RoadmapColumn = {
  status: string;
  title: string;
  description: string;
  icon: string;
  items: RoadmapItem[];
  /** "In beta" only: where players join the in-game beta program */
  link?: { url: string; label: string };
};

export type RoadmapPageProps = {
  isAdmin: boolean;
  stats: { totalItems: number; totalVotes: number };
  columns: RoadmapColumn[];
  me: {
    signedIn: boolean;
    canVote: boolean;
    canSuggest: boolean;
    canComment?: boolean;
    earnsRewards: boolean;
    weeklyRewardDone: boolean;
    suggestionsLeft: number;
  };
  comments?: CommentRules;
  rewards: { voteWeekly: number; ideaShipped: number; perWeek: number };
  releases: { url: string; label: string }[];
};

const CATEGORIES = [
  { id: 'all', label: 'All categories' },
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
// Stage icons: blue while it's being built, green once it's live, neutral before that
const STATUS_TONE: Record<string, string> = {
  in_beta: 'text-link',
  in_progress: 'text-link',
  planned: 'text-foreground',
  suggested: 'text-muted',
  completed: 'text-success',
};
const STATUS_OPTIONS = [
  { id: 'suggested', label: 'Idea' },
  { id: 'planned', label: 'Planned' },
  { id: 'in_progress', label: 'In progress' },
  { id: 'in_beta', label: 'In beta' },
  { id: 'completed', label: 'Shipped' },
];

type CommentRules = { maxLength: number; perDay: number; editMinutes: number };
const DEFAULT_COMMENT_RULES: CommentRules = { maxLength: 500, perDay: 10, editMinutes: 15 };

/** Text buttons and links outside running text: compact for a mouse, 44px for fingers */
const TAP = 'inline-flex min-h-9 pointer-coarse:min-h-11 items-center';
const LINK = `${TAP} gap-1.5 font-medium text-link hover:underline`;

// ---------------------------------------------------------------------------------------------
// Confirm dialog shared by every card (merge, split out, delete comment)

type ConfirmRequest = { title: string; body: ReactNode; confirmLabel: string; danger?: boolean };
const ConfirmContext = createContext<(req: ConfirmRequest) => Promise<boolean>>(async () => false);

function ConfirmProvider({ children }: { children: ReactNode }) {
  const [req, setReq] = useState<(ConfirmRequest & { resolve: (ok: boolean) => void }) | null>(null);
  const ask = (r: ConfirmRequest) => new Promise<boolean>((resolve) => setReq({ ...r, resolve }));
  const close = (ok: boolean) => {
    req?.resolve(ok);
    setReq(null);
  };
  return (
    <ConfirmContext.Provider value={ask}>
      {children}
      {/* Escape means Cancel (DESIGN.md: dialogs close on Escape) */}
      <AlertDialog.Backdrop isOpen={req !== null} onOpenChange={(o) => !o && close(false)} isKeyboardDismissDisabled={false}>
        <AlertDialog.Container>
          <AlertDialog.Dialog>
            <AlertDialog.Header>
              <AlertDialog.Heading>{req?.title}</AlertDialog.Heading>
            </AlertDialog.Header>
            <AlertDialog.Body>{req?.body}</AlertDialog.Body>
            <AlertDialog.Footer>
              <Button variant="tertiary" onPress={() => close(false)}>Cancel</Button>
              <Button variant={req?.danger ? 'danger' : 'primary'} onPress={() => close(true)}>{req?.confirmLabel}</Button>
            </AlertDialog.Footer>
          </AlertDialog.Dialog>
        </AlertDialog.Container>
      </AlertDialog.Backdrop>
    </ConfirmContext.Provider>
  );
}

// ---------------------------------------------------------------------------------------------

/** Lower-case words of 4+ letters, for search and for spotting ideas like the one being typed */
function words(text: string) {
  return text.toLowerCase().split(/[^\p{L}\p{N}]+/u).filter((w) => w.length >= 4);
}

function matchesQuery(item: RoadmapItem, query: string) {
  if (!query) return true;
  const hay = [item.title, item.description, item.author, ...(item.merged ?? []).flatMap((m) => [m.title, m.description])]
    .join(' ')
    .toLowerCase();
  return query.toLowerCase().split(/\s+/).filter(Boolean).every((q) => hay.includes(q));
}

export default function RoadmapPage({ isAdmin, stats, columns, me, rewards, releases, comments }: RoadmapPageProps) {
  const [cols, setCols] = useState(columns);
  const [stage, setStage] = useState('all');
  const [category, setCategory] = useState('all');
  const [sort, setSort] = useState<'votes' | 'newest'>('votes');
  const [query, setQuery] = useState('');
  const [busyId, setBusyId] = useState<number | null>(null);
  const [weeklyDone, setWeeklyDone] = useState(me.weeklyRewardDone);
  const [suggestTitle, setSuggestTitle] = useState('');
  const suggestRef = useRef<HTMLDivElement>(null);

  // Arriving from a notification or the dashboard (#item-12): bring that card into view.
  // A merged idea sits in its main card's collapsed list, so open that list first.
  useEffect(() => {
    const el = window.location.hash ? document.getElementById(window.location.hash.slice(1)) : null;
    el?.closest('details')?.setAttribute('open', '');
    el?.scrollIntoView({ block: 'center' });
  }, []);

  // Showing one idea from the suggest form: clear the filters that may hide it, then go to it
  const [jumpTo, setJumpTo] = useState<number | null>(null);
  const showItem = (id: number) => {
    setQuery('');
    setCategory('all');
    setStage('all');
    setJumpTo(id);
  };
  useEffect(() => {
    if (jumpTo === null) return;
    window.location.hash = `item-${jumpTo}`;
    document.getElementById(`item-${jumpTo}`)?.scrollIntoView({ block: 'center' });
    setJumpTo(null);
  }, [jumpTo]);

  const allItems = useMemo(() => cols.flatMap((c) => c.items), [cols]);
  const mergeTargets = useMemo(
    () => cols.flatMap((c) => c.items.map((it) => ({ id: it.id, title: it.title, column: c.title }))),
    [cols],
  );
  const myVotes = useMemo(() => allItems.filter((i) => i.voted).length, [allItems]);

  // Category and search narrow every stage; the stage tabs show what's left in each
  const filtered = useMemo(
    () =>
      cols.map((c) => ({
        ...c,
        items: c.items
          .filter((it) => (category === 'all' || it.category === category) && matchesQuery(it, query.trim()))
          .sort((a, b) => (sort === 'votes' ? b.upvotes - a.upvotes || b.id - a.id : b.id - a.id)),
      })),
    [cols, category, query, sort],
  );
  const filtering = category !== 'all' || query.trim() !== '';
  const shownTotal = filtered.reduce((n, c) => n + c.items.length, 0);
  const visibleCols = filtered.filter((c) => (stage === 'all' ? !filtering || c.items.length > 0 : c.status === stage));

  const vote = async (id: number) => {
    if (busyId !== null) return;
    setBusyId(id);
    try {
      const res = await postForm(`/roadmap/vote/${id}`, {});
      const data = await res.json().catch(() => null);
      if (!res.ok || !data?.success) {
        toast.danger(data?.message ?? 'Your vote did not go through. Try again.');
        return;
      }
      setCols((prev) => prev.map((c) => ({
        ...c,
        items: c.items.map((it) => (it.id === id ? { ...it, voted: Boolean(data.voted), upvotes: data.upvotes } : it)),
      })));
      if (data.reward > 0) {
        setWeeklyDone(true);
        toast.success(data.message);
      }
    } catch {
      toast.danger('Your vote did not go through. Check your connection and try again.');
    } finally {
      setBusyId(null);
    }
  };

  const openSuggest = (title = '') => {
    if (title) setSuggestTitle(title);
    suggestRef.current?.scrollIntoView({ block: 'start', behavior: 'smooth' });
    suggestRef.current?.querySelector<HTMLInputElement>('#rm-title')?.focus({ preventScroll: true });
  };

  const headerAction = me.canSuggest ? (
    <Button variant="primary" className="min-h-11" onPress={() => openSuggest()}>
      <Icon name="fa-lightbulb" />
      Suggest an idea
    </Button>
  ) : !me.signedIn ? (
    <a href="/login" className={buttonVariants({ variant: 'primary', className: 'min-h-11' })}>Sign in to vote</a>
  ) : null;

  return (
    <ConfirmProvider>
      <Page
        title="Roadmap"
        icon="fa-map-location-dot"
        back={null}
        subtitle="Vote on what gets built next. The most-voted ideas are reviewed first."
        actions={headerAction}
        width="max-w-6xl"
      >
        <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
          <div className="flex min-w-0 flex-col gap-4">
            <div className="flex flex-col gap-3">
              <nav aria-label="Stage" className="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
                <div className="flex w-max gap-1 rounded-xl bg-default p-1">
                  <StageTab label="All" count={shownTotal} active={stage === 'all'} onPress={() => setStage('all')} />
                  {filtered.map((c) => (
                    <StageTab key={c.status} label={c.title} count={c.items.length} active={stage === c.status} onPress={() => setStage(c.status)} />
                  ))}
                </div>
              </nav>
              <div className="grid grid-cols-2 gap-2 sm:flex sm:items-center">
                <SearchField aria-label="Search ideas" value={query} onChange={setQuery} className="col-span-2 sm:min-w-0 sm:flex-1">
                  <SearchField.Group>
                    <SearchField.SearchIcon />
                    <SearchField.Input placeholder="Search ideas" />
                    <SearchField.ClearButton />
                  </SearchField.Group>
                </SearchField>
                <Select
                  aria-label="Category"
                  className="sm:w-44"
                  value={category}
                  onChange={(v) => setCategory(String(v))}
                  options={CATEGORIES}
                />
                <Select
                  aria-label="Sort ideas"
                  className="sm:w-36"
                  value={sort}
                  onChange={(v) => setSort(v === 'newest' ? 'newest' : 'votes')}
                  options={[{ id: 'votes', label: 'Most votes' }, { id: 'newest', label: 'Newest' }]}
                />
              </div>
              {filtering && (
                <p role="status" className="flex flex-wrap items-center gap-x-3 text-sm text-muted">
                  {shownTotal === 1 ? '1 idea matches' : `${shownTotal} ideas match`}
                  <button
                    type="button"
                    className={`${TAP} font-medium text-link hover:underline`}
                    onClick={() => { setQuery(''); setCategory('all'); }}
                  >
                    Clear filters
                  </button>
                </p>
              )}
            </div>

            {shownTotal === 0 && filtering ? (
              <Empty icon="fa-magnifying-glass" title={query.trim() ? `No ideas match "${query.trim()}"` : 'No ideas in this category yet'}>
                {me.canSuggest ? (
                  <>
                    <p>Nobody has suggested it yet.</p>
                    <Button variant="secondary" className="mt-3 min-h-11" onPress={() => openSuggest(query.trim())}>Suggest it</Button>
                  </>
                ) : (
                  'Try fewer words or another category.'
                )}
              </Empty>
            ) : (
              visibleCols.map((col) => (
                <section key={col.status} aria-labelledby={`col-${col.status}`} className="flex flex-col gap-2">
                  <header className="border-b border-border pb-2">
                    <div className="flex flex-wrap items-center gap-x-2">
                      <Icon name={col.icon} className={STATUS_TONE[col.status] ?? 'text-muted'} />
                      <h2 id={`col-${col.status}`} className="text-lg font-semibold">{col.title}</h2>
                      <span className="text-sm tabular-nums text-muted">{col.items.length}</span>
                      {col.link && (
                        <a href={col.link.url} className={`${LINK} ml-auto text-sm`}>
                          <Icon name="fa-flask" />
                          {col.link.label}
                        </a>
                      )}
                    </div>
                    <p className="text-sm text-muted">{col.description}</p>
                  </header>
                  {col.items.length === 0 ? (
                    <p className="py-3 text-sm text-muted">{filtering ? 'No ideas here match your filters.' : 'Nothing here yet.'}</p>
                  ) : (
                    <ul className="flex flex-col gap-2">
                      {col.items.map((it) => (
                        <li key={it.id}>
                          <ItemCard
                            item={it}
                            me={me}
                            busy={busyId === it.id}
                            onVote={() => vote(it.id)}
                            isAdmin={isAdmin}
                            releases={releases}
                            mergeTargets={mergeTargets}
                            betaUrl={cols.find((c) => c.status === 'in_beta')?.link?.url ?? null}
                            comments={{ canComment: Boolean(me.canComment), signedIn: me.signedIn, rules: comments ?? DEFAULT_COMMENT_RULES }}
                          />
                        </li>
                      ))}
                    </ul>
                  )}
                </section>
              ))
            )}
          </div>

          {/* After the list on phones, so ideas come first; beside it on desktop */}
          <aside className="flex flex-col gap-4">
            {me.canSuggest && (
              <div ref={suggestRef} className="scroll-mt-24">
                <SuggestForm
                  left={me.suggestionsLeft}
                  perWeek={rewards.perWeek}
                  title={suggestTitle}
                  onTitle={setSuggestTitle}
                  items={allItems}
                  onShow={showItem}
                />
              </div>
            )}
            <Panel title="How it works">
              <ul className="space-y-3 text-sm">
                <li className="flex gap-3">
                  <Icon name="fa-check-to-slot" className="mt-0.5 w-4 text-link" />
                  <span>
                    <strong>Vote</strong> on any idea that isn't shipped yet. Your first vote each week on an idea you haven't voted on before earns{' '}
                    <strong>{rewards.voteWeekly} Génépis</strong>.
                    {me.earnsRewards && (
                      <span className={`mt-1 flex items-center gap-1.5 text-xs font-semibold ${weeklyDone ? 'text-success' : 'text-muted'}`}>
                        <Icon name={weeklyDone ? 'fa-circle-check' : 'fa-circle'} style={weeklyDone ? 'fa-solid' : 'fa-regular'} />
                        {weeklyDone ? 'Earned this week' : 'Not earned yet this week'}
                      </span>
                    )}
                  </span>
                </li>
                <li className="flex gap-3">
                  <Icon name="fa-lightbulb" className="mt-0.5 w-4 text-muted" />
                  <span>
                    <strong>Suggest</strong> up to {rewards.perWeek} ideas a week. They start in Ideas until we review them.
                  </span>
                </li>
                <li className="flex gap-3">
                  <Icon name="fa-rocket" className="mt-0.5 w-4 text-success" />
                  <span>
                    If your idea <strong>ships</strong>, you earn <strong>{rewards.ideaShipped} Génépis</strong>.
                  </span>
                </li>
                <li className="flex gap-3">
                  <Icon name="fa-award" className="mt-0.5 w-4 text-muted" />
                  <span>Voting and suggesting also unlock four Community achievements.</span>
                </li>
              </ul>
              {me.signedIn && !me.canVote && (
                <p className="mt-3 text-sm text-muted">Finish setting up your resort to vote, suggest and comment.</p>
              )}
              {me.signedIn && !me.earnsRewards && me.canVote && (
                <p className="mt-3 text-sm text-muted">Demo accounts can vote but don't earn Génépis or achievements. Save your resort to earn them.</p>
              )}
              <dl className={`mt-4 grid gap-2 border-t border-border pt-3 text-center ${me.signedIn ? 'grid-cols-3' : 'grid-cols-2'}`}>
                <div>
                  <dt className="text-xs text-muted">Ideas</dt>
                  <dd className="text-lg font-semibold tabular-nums">{stats.totalItems}</dd>
                </div>
                <div>
                  <dt className="text-xs text-muted">Votes</dt>
                  <dd className="text-lg font-semibold tabular-nums">{stats.totalVotes}</dd>
                </div>
                {me.signedIn && (
                  <div>
                    <dt className="text-xs text-muted">Your votes</dt>
                    <dd className="text-lg font-semibold tabular-nums">{myVotes}</dd>
                  </div>
                )}
              </dl>
            </Panel>
          </aside>
        </div>
      </Page>
    </ConfirmProvider>
  );
}

function StageTab({ label, count, active, onPress }: { label: string; count: number; active: boolean; onPress: () => void }) {
  return (
    <button
      type="button"
      aria-pressed={active}
      onClick={onPress}
      className={`flex min-h-10 pointer-coarse:min-h-11 shrink-0 cursor-pointer items-center gap-1.5 whitespace-nowrap rounded-lg px-3 text-sm font-medium focus-visible:outline-2 focus-visible:outline-focus ${active ? 'bg-surface text-foreground shadow-sm' : 'text-muted hover:text-foreground'}`}
    >
      {label}
      <span className="tabular-nums text-muted">{count}</span>
    </button>
  );
}

type MergeTarget = { id: number; title: string; column: string };

type CommentAccess = { canComment: boolean; signedIn: boolean; rules: CommentRules };

/** The vote count, and the vote toggle when the player can vote on this idea */
function VoteControl({ item, me, busy, onVote }: { item: RoadmapItem; me: RoadmapPageProps['me']; busy: boolean; onVote: () => void }) {
  const box = 'flex min-h-14 w-14 shrink-0 flex-col items-center justify-center gap-0.5 self-start rounded-lg px-1 text-sm font-semibold tabular-nums';
  const votes = `${item.upvotes} ${item.upvotes === 1 ? 'vote' : 'votes'}`;
  if (item.status === 'completed') {
    return (
      <div className={`${box} bg-default`} role="img" aria-label={`Shipped, ${votes}`}>
        <Icon name="fa-circle-check" className="text-success" />
        {item.upvotes}
      </div>
    );
  }
  if (!me.signedIn) {
    return (
      <a href="/login" className={`${box} border border-border hover:bg-default`} aria-label={`Sign in to vote for ${item.title}, ${votes}`}>
        <Icon name="fa-chevron-up" className="text-xs text-muted" />
        {item.upvotes}
      </a>
    );
  }
  if (!me.canVote) {
    return (
      <div className={`${box} bg-default`} role="img" aria-label={votes} title="Finish setting up your resort to vote">
        <Icon name="fa-chevron-up" className="text-xs text-muted" />
        {item.upvotes}
      </div>
    );
  }
  return (
    <Button
      variant={item.voted ? 'primary' : 'outline'}
      onPress={onVote}
      isPending={busy}
      aria-pressed={item.voted}
      aria-label={`${item.voted ? 'Remove your vote for' : 'Vote for'} ${item.title}, ${votes}`}
      className={`${box} h-auto`}
    >
      {busy ? <Spinner size="sm" color="current" /> : <Icon name={item.voted ? 'fa-check' : 'fa-chevron-up'} className="text-xs" />}
      {item.upvotes}
    </Button>
  );
}

/** A long description shows three lines until the player opens it */
function Description({ id, text }: { id: string; text: string }) {
  const [open, setOpen] = useState(false);
  const long = text.length > 220 || text.split('\n').length > 3;
  return (
    <div>
      <p id={id} className={`whitespace-pre-line break-words text-sm leading-relaxed text-muted ${long && !open ? 'line-clamp-3' : ''}`}>{text}</p>
      {long && (
        <button type="button" aria-expanded={open} aria-controls={id} onClick={() => setOpen(!open)} className={`${TAP} cursor-pointer text-sm font-medium text-link hover:underline`}>
          {open ? 'Show less' : 'Show more'}
        </button>
      )}
    </div>
  );
}

function ItemCard({ item, me, busy, onVote, isAdmin, releases, mergeTargets, betaUrl, comments }: {
  item: RoadmapItem;
  me: RoadmapPageProps['me'];
  busy: boolean;
  onVote: () => void;
  isAdmin: boolean;
  releases: RoadmapPageProps['releases'];
  mergeTargets: MergeTarget[];
  betaUrl: string | null;
  comments: CommentAccess;
}) {
  const [thread, setThread] = useState(false);
  const [opened, setOpened] = useState(false);
  const [count, setCount] = useState(item.comments ?? 0);
  const threadId = `comments-${item.id}`;
  return (
    <article id={`item-${item.id}`} className="flex scroll-mt-24 gap-3 rounded-xl border border-border bg-surface p-3 target:ring-2 target:ring-focus sm:p-4">
      <VoteControl item={item} me={me} busy={busy} onVote={onVote} />
      <div className="min-w-0 flex-1 space-y-1.5">
        <div className="flex flex-wrap items-center gap-x-2 gap-y-1">
          <h3 className="font-semibold leading-snug break-words">{item.title}</h3>
          {item.mine && <Chip size="sm" color="accent" variant="soft">Your idea</Chip>}
        </div>
        {item.description && <Description id={`desc-${item.id}`} text={item.description} />}
        <div className="flex flex-wrap items-center gap-x-4 text-sm text-muted">
          <span>{CATEGORY_LABEL[item.category] ?? item.category}</span>
          <span>by <span className="text-foreground">{item.author}</span></span>
          <button
            type="button"
            aria-expanded={thread}
            aria-controls={threadId}
            onClick={() => { setThread(!thread); setOpened(true); }}
            className={`${TAP} cursor-pointer gap-1.5 font-medium hover:text-foreground ${thread ? 'text-foreground' : ''}`}
          >
            <Icon name="fa-comment" style={count > 0 ? 'fa-solid' : 'fa-regular'} />
            {count === 0 ? 'Comment' : count === 1 ? '1 comment' : `${count} comments`}
          </button>
          {item.status === 'in_beta' && betaUrl && <a href={betaUrl} className={LINK}>Try it in the beta</a>}
          {item.status === 'completed' && item.updatesUrl && <a href={item.updatesUrl} className={LINK}>Release notes</a>}
        </div>
        {item.merged && item.merged.length > 0 && <MergedList ideas={item.merged} isAdmin={isAdmin} />}
        {/* Mounted on first open, then only hidden, so a half-written comment survives closing */}
        {opened && <CommentThread id={threadId} hidden={!thread} itemId={item.id} title={item.title} onCount={setCount} access={comments} />}
        {isAdmin && <AdminControls item={item} releases={releases} mergeTargets={mergeTargets} />}
      </div>
    </article>
  );
}

function MergedList({ ideas, isAdmin }: { ideas: MergedIdea[]; isAdmin: boolean }) {
  const confirm = useContext(ConfirmContext);
  const [busyId, setBusyId] = useState<number | null>(null);

  const splitOut = async (idea: MergedIdea) => {
    const ok = await confirm({
      title: 'Split out this idea?',
      body: `"${idea.title}" becomes its own idea again and gets back its own votes.`,
      confirmLabel: 'Split out',
    });
    if (!ok) return;
    setBusyId(idea.id);
    const res = await postForm('/roadmap/admin/unmerge', { id: String(idea.id) }).catch(() => null);
    if (res?.ok) window.location.reload();
    else {
      toast.danger(`"${idea.title}" was not split out. Try again.`);
      setBusyId(null);
    }
  };

  return (
    <details className="group rounded-lg border border-border bg-surface-secondary text-sm">
      <summary className="flex min-h-10 pointer-coarse:min-h-11 cursor-pointer list-none items-center gap-2 rounded-lg px-3 font-medium focus-visible:outline-2 focus-visible:outline-focus">
        <Icon name="fa-object-group" className="text-muted" />
        {ideas.length === 1 ? '1 similar idea grouped here' : `${ideas.length} similar ideas grouped here`}
        <Icon name="fa-chevron-down" className="ml-auto text-xs text-muted group-open:rotate-180" />
      </summary>
      <ul className="divide-y divide-border border-t border-border">
        {ideas.map((m) => (
          <li key={m.id} id={`item-${m.id}`} className="scroll-mt-24 space-y-1 px-3 py-2 target:bg-accent-soft">
            <div className="flex flex-wrap items-center gap-1.5">
              <span className="font-medium">{m.title}</span>
              {m.mine && <Chip size="sm" color="accent" variant="soft">Your idea</Chip>}
            </div>
            {m.description && <p className="whitespace-pre-line break-words leading-relaxed text-muted">{m.description}</p>}
            <div className="flex flex-wrap items-center gap-2 text-muted">
              <span>by <span className="text-foreground">{m.author}</span></span>
              {isAdmin && (
                <Button size="sm" variant="ghost" className="ml-auto min-h-9 pointer-coarse:min-h-11" isPending={busyId === m.id} isDisabled={busyId !== null} onPress={() => splitOut(m)}>
                  Split out
                </Button>
              )}
            </div>
          </li>
        ))}
      </ul>
    </details>
  );
}

type RoadmapComment = {
  id: number;
  author: string;
  body: string;
  created: string;
  edited: boolean;
  mine: boolean;
  canEdit: boolean;
  canDelete: boolean;
};

/** "Nov 19" style date for a comment, from the server's "Y-m-d H:i:s" */
function commentDate(created: string) {
  const d = new Date(created.replace(' ', 'T'));
  return Number.isNaN(d.getTime()) ? '' : d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
}

const FIELD = 'w-full rounded-lg border border-border bg-field px-3 py-2 text-sm focus-visible:outline-2 focus-visible:outline-focus';

/** Comments under an idea: loaded when the thread is opened */
function CommentThread({ id, hidden, itemId, title, onCount, access }: {
  id: string;
  hidden: boolean;
  itemId: number;
  title: string;
  onCount: (n: number | ((n: number) => number)) => void;
  access: CommentAccess;
}) {
  const confirm = useContext(ConfirmContext);
  const [list, setList] = useState<RoadmapComment[] | null>(null);
  const [loadFailed, setLoadFailed] = useState(false);
  const [draft, setDraft] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [editing, setEditing] = useState<{ id: number; body: string } | null>(null);
  const { maxLength, editMinutes } = access.rules;

  const load = async () => {
    setLoadFailed(false);
    const res = await fetch(`/roadmap/comments/${itemId}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).catch(() => null);
    const data = res ? await res.json().catch(() => null) : null;
    if (data?.success) {
      setList(data.comments);
      onCount(data.comments.length);
    } else setLoadFailed(true);
  };

  useEffect(() => {
    load();
    // Loaded once when the thread opens
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const send = async (url: string, fields: Record<string, string>) => {
    setBusy(true);
    setError(null);
    try {
      const res = await postForm(url, fields);
      const data = await res.json().catch(() => null);
      if (!res.ok || !data?.success) {
        setError(data?.message ?? 'That did not go through. Try again.');
        return null;
      }
      return data;
    } catch {
      setError('That did not go through. Check your connection and try again.');
      return null;
    } finally {
      setBusy(false);
    }
  };

  const post = async () => {
    const body = draft.trim();
    if (!body) return;
    const data = await send(`/roadmap/comments/${itemId}`, { body });
    if (!data) return;
    setList((prev) => [...(prev ?? []), data.comment]);
    onCount(data.count);
    setDraft('');
  };

  const saveEdit = async () => {
    if (!editing || !editing.body.trim()) return;
    const data = await send(`/roadmap/comment/${editing.id}/edit`, { body: editing.body.trim() });
    if (!data) return;
    setList((prev) => (prev ?? []).map((c) => (c.id === editing.id ? data.comment : c)));
    setEditing(null);
  };

  const remove = async (c: RoadmapComment) => {
    const ok = await confirm({ title: 'Delete this comment?', body: 'It is removed for everyone. This cannot be undone.', confirmLabel: 'Delete comment', danger: true });
    if (!ok) return;
    const data = await send(`/roadmap/comment/${c.id}/delete`, {});
    if (!data) return;
    setList((prev) => (prev ?? []).filter((x) => x.id !== c.id));
    onCount((n) => Math.max(0, n - 1));
  };

  return (
    <div id={id} hidden={hidden} className="space-y-3 rounded-lg bg-surface-secondary p-3 text-sm">
      {list === null && !loadFailed && (
        <p role="status" className="flex items-center gap-2 text-muted"><Spinner size="sm" color="current" /> Loading comments</p>
      )}
      {loadFailed && (
        <p role="alert" className="flex flex-wrap items-center gap-x-2 text-danger">
          Comments did not load.
          <button type="button" className={`${TAP} cursor-pointer font-medium underline`} onClick={load}>Try again</button>
        </p>
      )}
      {list !== null && list.length === 0 && <p className="text-muted">No comments yet.{access.canComment ? ' Start the discussion.' : ''}</p>}
      {list !== null && list.length > 0 && (
        <ul className="space-y-3">
          {list.map((c) => (
            <li key={c.id} className="space-y-1">
              <p className="flex flex-wrap items-baseline gap-x-2 text-xs text-muted">
                <span className="text-sm font-semibold text-foreground">{c.author}</span>
                <span>{commentDate(c.created)}</span>
                {c.edited && <span>edited</span>}
              </p>
              {editing?.id === c.id ? (
                <form onSubmit={(e) => { e.preventDefault(); saveEdit(); }} className="space-y-2">
                  <textarea
                    aria-label="Edit your comment"
                    value={editing.body}
                    maxLength={maxLength}
                    rows={3}
                    onChange={(e) => setEditing({ id: c.id, body: e.target.value })}
                    onKeyDown={(e) => { if (e.key === 'Escape') setEditing(null); }}
                    className={FIELD}
                  />
                  <div className="flex gap-2">
                    <Button type="submit" size="sm" variant="secondary" isPending={busy} isDisabled={!editing.body.trim()} className="min-h-9 pointer-coarse:min-h-11">Save</Button>
                    <Button size="sm" variant="ghost" onPress={() => setEditing(null)} className="min-h-9 pointer-coarse:min-h-11">Cancel</Button>
                  </div>
                </form>
              ) : (
                <p className="whitespace-pre-line break-words leading-relaxed">{c.body}</p>
              )}
              {editing?.id !== c.id && (c.canEdit || c.canDelete) && (
                <div className="flex gap-1">
                  {c.canEdit && (
                    <Button size="sm" variant="ghost" className="min-h-9 pointer-coarse:min-h-11" isDisabled={busy} onPress={() => setEditing({ id: c.id, body: c.body })}>Edit</Button>
                  )}
                  {c.canDelete && (
                    <Button size="sm" variant="ghost" className="min-h-9 pointer-coarse:min-h-11 text-danger" isDisabled={busy} onPress={() => remove(c)}>Delete</Button>
                  )}
                </div>
              )}
            </li>
          ))}
        </ul>
      )}
      {access.canComment ? (
        <form onSubmit={(e) => { e.preventDefault(); post(); }} className="space-y-2 border-t border-border pt-3">
          <textarea
            aria-label={`Comment on ${title}`}
            value={draft}
            maxLength={maxLength}
            rows={2}
            placeholder="What would make this work for your resort?"
            onChange={(e) => setDraft(e.target.value)}
            className={FIELD}
          />
          <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
            <Button type="submit" size="sm" variant="secondary" isPending={busy} isDisabled={!draft.trim()} className="min-h-9 pointer-coarse:min-h-11">Post comment</Button>
            <span className="text-xs tabular-nums text-muted">{draft.length}/{maxLength}</span>
            <span className="text-xs text-muted">You can edit a comment for {editMinutes} minutes.</span>
          </div>
        </form>
      ) : (
        <p className="border-t border-border pt-3 text-muted">
          {access.signedIn ? 'Finish setting up your resort with a full account to comment.' : <><a href="/login" className="font-medium text-link hover:underline">Sign in</a> to comment.</>}
        </p>
      )}
      {error && <p role="alert" className="text-danger">{error}</p>}
    </div>
  );
}

async function postForm(url: string, fields: Record<string, string>) {
  const { field, token } = csrf();
  const fd = new FormData();
  fd.append(field, token);
  Object.entries(fields).forEach(([k, v]) => fd.append(k, v));
  return fetch(url, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd });
}

function SuggestForm({ left, perWeek, title, onTitle, items, onShow }: {
  left: number;
  perWeek: number;
  title: string;
  onTitle: (t: string) => void;
  items: RoadmapItem[];
  onShow: (id: number) => void;
}) {
  // Ideas that share words with the title being typed: voting for one beats a duplicate
  const similar = useMemo(() => {
    const typed = new Set(words(title));
    if (typed.size === 0) return [];
    return items
      .map((it) => ({ it, hits: words(it.title).filter((w) => typed.has(w)).length }))
      .filter((x) => x.hits > 0)
      .sort((a, b) => b.hits - a.hits || b.it.upvotes - a.it.upvotes)
      .slice(0, 3)
      .map((x) => x.it);
  }, [title, items]);

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
              value={title}
              onChange={(e) => onTitle(e.target.value)}
              placeholder="Helicopter skiing tours"
              aria-describedby={similar.length > 0 ? 'rm-similar' : undefined}
              className={`min-h-11 ${FIELD}`}
            />
          </div>
          {similar.length > 0 && (
            <div id="rm-similar" className="rounded-lg bg-surface-secondary p-3 text-sm">
              <p className="font-medium">Already on the roadmap? Vote for it instead:</p>
              <ul className="mt-1">
                {similar.map((it) => (
                  <li key={it.id}>
                    <a
                      href={`#item-${it.id}`}
                      onClick={(e) => { e.preventDefault(); onShow(it.id); }}
                      className={`${TAP} text-left text-link hover:underline`}
                    >
                      {it.title}
                    </a>
                  </li>
                ))}
              </ul>
            </div>
          )}
          <div>
            <label htmlFor="rm-desc" className="mb-1 block text-sm font-medium">What it does and why</label>
            <textarea id="rm-desc" name="description" required minLength={10} maxLength={1000} rows={3} className={FIELD} />
          </div>
          <div>
            <span id="rm-cat-label" className="mb-1 block text-sm font-medium">Category</span>
            <Select name="category" aria-labelledby="rm-cat-label" defaultValue="gameplay" options={CATEGORIES.filter((c) => c.id !== 'all')} />
          </div>
          <Button type="submit" variant="secondary" className="min-h-11 w-full">Suggest idea</Button>
          <p className="text-xs text-muted">{left} of {perWeek} suggestions left this week. One idea per suggestion.</p>
        </form>
      )}
    </Panel>
  );
}

function AdminControls({ item, releases, mergeTargets }: {
  item: RoadmapItem;
  releases: RoadmapPageProps['releases'];
  mergeTargets: MergeTarget[];
}) {
  const confirm = useContext(ConfirmContext);
  const [status, setStatus] = useState(item.status);
  const [release, setRelease] = useState(item.updatesUrl ?? '');
  const [saving, setSaving] = useState(false);
  const [into, setInto] = useState('');
  const targets = mergeTargets.filter((t) => t.id !== item.id);

  const merge = async () => {
    const target = targets.find((t) => String(t.id) === into);
    if (!target) return;
    const n = item.merged?.length ?? 0;
    const grouped = n === 0 ? '' : n === 1 ? ' The idea grouped under it moves too.' : ` The ${n} ideas grouped under it move too.`;
    const ok = await confirm({
      title: 'Merge these ideas?',
      body: `"${item.title}" goes into "${target.title}". Its votes count toward "${target.title}", once per player.${grouped}`,
      confirmLabel: 'Merge',
    });
    if (!ok) return;
    const res = await postForm('/roadmap/admin/merge', { id: String(item.id), into }).catch(() => null);
    if (res?.ok) {
      window.location.hash = `item-${target.id}`;
      window.location.reload();
      return;
    }
    const data = res ? await res.json().catch(() => null) : null;
    toast.danger(data?.message ?? 'Not merged. Try again.');
  };

  const save = async () => {
    setSaving(true);
    const res = await postForm('/roadmap/admin/status', release ? { id: String(item.id), status, updates_url: release } : { id: String(item.id), status }).catch(() => null);
    if (res?.ok) {
      window.location.reload();
      return;
    }
    const data = res ? await res.json().catch(() => null) : null;
    toast.danger(data?.message ?? 'Status not saved. Try again.');
    setSaving(false);
  };

  return (
    <details className="group text-sm">
      <summary className={`${TAP} cursor-pointer list-none gap-1.5 rounded-lg font-medium text-muted hover:text-foreground focus-visible:outline-2 focus-visible:outline-focus`}>
        <Icon name="fa-sliders" />
        Manage
        <Icon name="fa-chevron-down" className="text-xs group-open:rotate-180" />
      </summary>
      <div className="mt-1 flex flex-col gap-3 rounded-lg border border-dashed border-border p-3">
        <form onSubmit={(e) => { e.preventDefault(); save(); }} className="flex flex-wrap items-center gap-2">
          <Select aria-label={`Stage of ${item.title}`} className="w-36" value={status} onChange={(v) => setStatus(String(v))} options={STATUS_OPTIONS} />
          {status === 'completed' && releases.length > 0 && (
            <Select
              aria-label="Release it shipped in"
              className="min-w-0 flex-1"
              value={release}
              onChange={(v) => setRelease(String(v))}
              options={[{ id: '', label: 'No release link' }, ...releases.map((r) => ({ id: r.url, label: r.label }))]}
            />
          )}
          <Button type="submit" size="sm" variant="secondary" isPending={saving} className="min-h-9 pointer-coarse:min-h-11">Save stage</Button>
        </form>
        {targets.length > 0 && (
          <form onSubmit={(e) => { e.preventDefault(); merge(); }} className="flex flex-wrap items-center gap-2">
            <Select
              aria-label={`Merge ${item.title} into another idea`}
              className="min-w-0 flex-1"
              value={into}
              onChange={(v) => setInto(String(v))}
              options={[{ id: '', label: 'Merge into another idea' }, ...targets.map((t) => ({ id: t.id, label: `${t.title} (${t.column})` }))]}
            />
            <Button type="submit" size="sm" variant="secondary" isDisabled={!into} className="min-h-9 pointer-coarse:min-h-11">Merge</Button>
          </form>
        )}
        <PostButton
          action={`/roadmap/delete/${item.id}`}
          variant="ghost"
          size="sm"
          className="self-start [&_button]:text-danger"
          confirm={{
            title: 'Delete this idea?',
            body: `"${item.title}" and all its votes and comments are removed. This cannot be undone.${item.merged?.length ? ' Ideas grouped under it become separate ideas again.' : ''}`,
            confirmLabel: 'Delete idea',
          }}
        >
          Delete idea
        </PostButton>
      </div>
    </details>
  );
}
