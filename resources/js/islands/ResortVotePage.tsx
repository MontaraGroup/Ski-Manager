import { useEffect, useRef, useState } from 'react';
import { Button, Chip, SearchField, Spinner, toast } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Empty, Page, Panel } from '@/components/Page';
import { api, ApiError } from '@/lib/api';
import { areaFacts, type ResortVoteState, type SkiArea } from '@/lib/resortVote';

export type ResortVotePageProps = ResortVoteState;

type Search = { status: 'idle' } | { status: 'loading' } | { status: 'done'; q: string; results: SkiArea[] } | { status: 'error' };

export default function ResortVotePage(props: ResortVotePageProps) {
  const [state, setState] = useState<ResortVoteState>(props);
  const [query, setQuery] = useState('');
  const [search, setSearch] = useState<Search>({ status: 'idle' });
  const [busy, setBusy] = useState<number | 'retract' | null>(null);
  const seq = useRef(0);
  const { round, canVote, myPick, top, past } = state;
  const open = round.open;

  // Debounced search; a late answer to an older query is dropped
  useEffect(() => {
    const q = query.trim();
    if (q.length < 2) {
      setSearch({ status: 'idle' });
      return;
    }
    const id = ++seq.current;
    setSearch({ status: 'loading' });
    const t = window.setTimeout(async () => {
      try {
        const results = await api<SkiArea[]>(`resort-vote/search?q=${encodeURIComponent(q)}`);
        if (id === seq.current) setSearch({ status: 'done', q, results });
      } catch {
        if (id === seq.current) setSearch({ status: 'error' });
      }
    }, 250);
    return () => window.clearTimeout(t);
  }, [query]);

  async function send(path: string, body: unknown, key: number | 'retract', done: string) {
    setBusy(key);
    try {
      setState(await api<ResortVoteState>(path, { method: 'POST', body }));
      toast.success(done);
      if (key !== 'retract') setQuery('');
    } catch (e) {
      toast.danger(e instanceof ApiError && e.status !== 500 ? e.message : "Your vote didn't save. Try again.");
    } finally {
      setBusy(null);
    }
  }

  return (
    <Page
      title="Resort Vote"
      icon="fa-check-to-slot"
      subtitle={open ? `Pick the real North American resort we add next. ${round.label} voting closes at the end of ${round.closesLabel}.` : `Voting for ${round.label} is closed. The winner is picked by hand from the results.`}
    >
      <div className="flex flex-col gap-6">
        <Panel title="Your vote">
          <div className="flex flex-col gap-4">
            {myPick ? (
              <div className="flex flex-wrap items-center gap-3">
                <div className="min-w-0 flex-1">
                  <p className="font-semibold">{myPick.name}</p>
                  <p className="text-sm text-muted">{[myPick.place, areaFacts(myPick)].filter(Boolean).join(' · ')}</p>
                </div>
                {open && (
                  <Button variant="tertiary" className="min-h-11" isPending={busy === 'retract'} onPress={() => send('resort-vote/retract', {}, 'retract', 'Vote removed.')}>
                    Remove my vote
                  </Button>
                )}
              </div>
            ) : (
              <p className="text-sm text-muted">{open ? 'You have not voted this month.' : `You did not vote in ${round.label}.`}</p>
            )}

            {!canVote ? (
              <p role="status" className="rounded-lg border border-warning/40 bg-warning/10 p-3 text-sm">This account can't vote. Demo accounts don't take part.</p>
            ) : open ? (
              <div className="flex flex-col gap-2">
                <SearchField aria-label="Search ski resorts" value={query} onChange={setQuery}>
                  <SearchField.Group>
                    <SearchField.SearchIcon />
                    <SearchField.Input placeholder={myPick ? 'Search to change your vote' : `Search ${state.candidateCount.toLocaleString()} resorts by name, state or province`} />
                    <SearchField.ClearButton />
                  </SearchField.Group>
                </SearchField>
                <SearchResults search={search} myPickId={myPick?.id ?? null} busy={busy} onVote={(a) => send('resort-vote', { areaId: a.id }, a.id, `You voted for ${a.name}. You can change it until the end of ${round.closesLabel}.`)} />
              </div>
            ) : null}
          </div>
        </Panel>

        <Panel title={`Standings, ${round.label}`} actions={<span className="text-sm text-muted">{round.votes} {round.votes === 1 ? 'vote' : 'votes'}</span>}>
          {top.length === 0 ? (
            <Empty icon="fa-check-to-slot" title="No votes yet">
              {open ? 'Search for a resort above to cast the first vote.' : 'Nobody voted this month.'}
            </Empty>
          ) : (
            <ol className="flex flex-col divide-y divide-border">
              {top.map((r, i) => (
                <li key={r.id} className="flex items-center gap-3 py-3">
                  <span className="w-6 shrink-0 text-right text-sm font-semibold tabular-nums text-muted">{i + 1}</span>
                  <div className="min-w-0 flex-1">
                    <p className="flex flex-wrap items-center gap-2 font-medium">
                      {r.name}
                      {myPick?.id === r.id && <Chip size="sm" variant="soft" color="accent">Your vote</Chip>}
                    </p>
                    <p className="truncate text-sm text-muted">{r.place}</p>
                  </div>
                  <span className="shrink-0 text-right text-sm tabular-nums">
                    <span className="font-semibold">{r.votes}</span>
                    <span className="text-muted"> · {r.pct}%</span>
                  </span>
                </li>
              ))}
            </ol>
          )}
        </Panel>

        {past.length > 0 && (
          <Panel title="Past months">
            <ul className="flex flex-col divide-y divide-border">
              {past.map((p) => (
                <li key={p.id} className="flex flex-wrap items-center gap-x-3 gap-y-1 py-3">
                  <span className="w-32 shrink-0 text-sm font-medium">{p.label}</span>
                  <span className="min-w-0 flex-1 text-sm">
                    {p.winner ? (
                      <>
                        <Icon name="fa-trophy" className="mr-1.5 text-warning" aria-hidden="true" />
                        <span className="font-semibold">{p.winner.name}</span>
                        <span className="text-muted">, {p.winner.place}</span>
                      </>
                    ) : (
                      <span className="text-muted">Winner not picked yet</span>
                    )}
                  </span>
                  <span className="text-sm text-muted tabular-nums">{p.votes} {p.votes === 1 ? 'vote' : 'votes'}</span>
                </li>
              ))}
            </ul>
          </Panel>
        )}

        <p className="text-xs text-muted">
          A new vote opens on the 1st of every month. The most-voted resort is the expected pick; it then gets built by hand, so it shows up in the game later.{' '}
          <a href="https://openskimap.org" target="_blank" rel="noopener noreferrer" className="underline">{state.attribution}</a>
        </p>
      </div>
    </Page>
  );
}

function SearchResults({ search, myPickId, busy, onVote }: { search: Search; myPickId: number | null; busy: number | 'retract' | null; onVote: (a: SkiArea) => void }) {
  if (search.status === 'idle') return <p className="text-sm text-muted">Type at least 2 letters, for example "Stowe" or "Colorado".</p>;
  if (search.status === 'loading') return <p className="flex items-center gap-2 text-sm text-muted"><Spinner size="sm" /> Searching</p>;
  if (search.status === 'error') return <p className="text-sm text-danger">The search didn't load. Try again.</p>;
  if (search.results.length === 0) return <p className="text-sm text-muted">No resort matches "{search.q}". Resorts already in the game and past winners aren't listed.</p>;
  return (
    <ul aria-label="Matching resorts" className="flex flex-col divide-y divide-border rounded-lg border border-border">
      {search.results.map((a) => (
        <li key={a.id} className="flex flex-wrap items-center gap-3 px-3 py-2">
          <div className="min-w-0 flex-1">
            <p className="font-medium">{a.name}</p>
            <p className="text-sm text-muted">{[a.locality ? `${a.locality}, ${a.place}` : a.place, areaFacts(a)].filter(Boolean).join(' · ')}</p>
          </div>
          {myPickId === a.id ? (
            <Chip size="sm" variant="soft" color="accent">Your vote</Chip>
          ) : (
            <Button size="sm" variant="secondary" className="min-h-11 sm:min-h-8" isPending={busy === a.id} isDisabled={busy !== null} onPress={() => onVote(a)}>
              Vote for {a.name}
            </Button>
          )}
        </li>
      ))}
    </ul>
  );
}
