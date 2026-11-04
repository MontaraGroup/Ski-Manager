import { Disclosure } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Page, Panel } from '@/components/Page';

type Row = {
  id: number; rank: number; username: string; resortName: string; profit: string; profitPositive: boolean;
  skiers: string; cash: string; score: string | null; slopes: number; lifts: number; buildings: number; isMe: boolean;
};
type Props = {
  v2: boolean;
  subtitle: string;
  difficulty: string;
  me: (Row & { behind: string | null }) | null;
  meUnranked: boolean;
  meOutsideTop: boolean;
  players: Row[];
  shown: number;
  rankedCount: number;
  unranked: number;
  pastSeasons: { number: number; name: string; rows: { rank: number; username: string; resortName: string; cash: string; slopes: number; lifts: number }[] }[];
};

const BOARDS = [['easy', 'Easy'], ['standard', 'Standard'], ['hard', 'Hard'], ['all', 'All']] as const;

/** Gold, silver, bronze for the podium; the rank number is always shown next to it */
function Medal({ rank }: { rank: number }) {
  const tone = rank === 1 ? 'text-warning' : rank === 2 ? 'text-muted' : rank === 3 ? 'text-[#b45309]' : '';
  return rank <= 3 ? <Icon name="fa-medal" className={tone} /> : null;
}

/** /leaderboard: this season's resorts by profit, one board per difficulty, and past winners */
export default function LeaderboardPage(p: Props) {
  const podium = p.players.length >= 3 ? [p.players[1], p.players[0], p.players[2]] : [];
  return (
    <Page title="Leaderboard" icon="fa-trophy" iconTone="text-warning" subtitle={p.subtitle}>
      {p.v2 && (
        <nav aria-label="Difficulty board" className="flex w-fit flex-wrap gap-1 rounded-xl bg-default p-1">
          {BOARDS.map(([key, label]) => (
            <a
              key={key}
              href={`/leaderboard?difficulty=${key}`}
              aria-current={p.difficulty === key ? 'page' : undefined}
              className={`flex min-h-10 pointer-coarse:min-h-11 items-center rounded-lg px-4 text-sm font-medium ${p.difficulty === key ? 'bg-surface shadow-sm' : 'text-muted hover:text-foreground'}`}
            >
              {label}
            </a>
          ))}
        </nav>
      )}

      {p.me ? (
        <section aria-label="Your position" className="flex flex-wrap items-center gap-x-6 gap-y-1 rounded-xl border border-accent bg-accent-soft p-4 text-sm">
          <span>Your position: <b className="text-lg"><Medal rank={p.me.rank} /> #{p.me.rank}</b> of {p.rankedCount}</span>
          <span>Season profit: <b className={p.me.profitPositive ? 'text-success' : 'text-danger'}>{p.me.profit}</b></span>
          {p.me.behind && <span className="text-muted">{p.me.behind} behind the leader</span>}
        </section>
      ) : p.meUnranked && (
        <p role="status" className="rounded-xl border border-border bg-surface p-4 text-sm">
          You're not ranked yet. <a href="/map" className="font-semibold text-link underline">Build your first slope or lift</a> to join the leaderboard.
        </p>
      )}

      {podium.length === 3 && (
        <ol className="grid grid-cols-3 items-end gap-3" aria-label="Top three">
          {podium.map((r) => (
            <li key={r.id} className={`rounded-xl border bg-surface p-3 text-center sm:p-4 ${r.rank === 1 ? 'border-warning sm:py-6' : 'border-border'}`}>
              <div className="text-3xl"><Medal rank={r.rank} /><span className="sr-only">Rank {r.rank}</span></div>
              <div className="truncate font-semibold">{r.username}</div>
              <div className="text-sm font-semibold text-success sm:text-lg">{p.v2 ? r.profit : r.score}</div>
              <div className="text-sm text-muted">{r.slopes} slopes · {r.lifts} lifts</div>
            </li>
          ))}
        </ol>
      )}

      <Panel className="p-0">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead className="text-left text-muted">
              <tr className="border-b border-border">
                <th scope="col" className="p-3 font-medium">Rank</th>
                <th scope="col" className="p-3 font-medium">Resort manager</th>
                <th scope="col" className="p-3 font-medium">{p.v2 ? 'Season profit' : 'Net profit'}</th>
                <th scope="col" className="hidden p-3 font-medium sm:table-cell">Skiers today</th>
                <th scope="col" className="hidden p-3 font-medium md:table-cell">Resort</th>
                <th scope="col" className="hidden p-3 font-medium lg:table-cell">Cash</th>
                {!p.v2 && <th scope="col" className="p-3 font-medium">Score</th>}
              </tr>
            </thead>
            <tbody className="divide-y divide-border">
              {p.players.map((r) => <PlayerRow key={r.id} row={r} v2={p.v2} />)}
              {p.meOutsideTop && p.me && (
                <>
                  <tr aria-hidden="true"><td colSpan={7} className="py-1 text-center text-muted">…</td></tr>
                  <PlayerRow row={p.me} v2={p.v2} />
                </>
              )}
              {p.players.length === 0 && (
                <tr><td colSpan={7} className="py-8 text-center text-muted">No resorts on this board yet. Build one to be the first.</td></tr>
              )}
            </tbody>
          </table>
        </div>
      </Panel>
      <p className="text-sm text-muted">
        Showing the top {p.shown} of {p.rankedCount} ranked resort{p.rankedCount === 1 ? '' : 's'}.{' '}
        {p.unranked > 0 && <>{p.unranked} manager{p.unranked === 1 ? " hasn't" : "s haven't"} built anything yet this season. </>}
        Profit is income minus running costs since the season started; tied resorts share a rank.
      </p>

      {p.pastSeasons.length > 0 && (
        <section className="flex flex-col gap-3">
          <h2 className="flex items-center gap-2 text-lg font-semibold"><Icon name="fa-landmark" className="text-warning" />Hall of fame</h2>
          {p.pastSeasons.map((season, i) => (
            <Disclosure key={season.number} defaultExpanded={i === 0} className="rounded-xl border border-border bg-surface">
              <Disclosure.Heading>
                <Disclosure.Trigger className="flex min-h-11 w-full items-center justify-between px-4 font-semibold">
                  {season.name} · final top 10
                  <Disclosure.Indicator />
                </Disclosure.Trigger>
              </Disclosure.Heading>
              <Disclosure.Content>
                <div className="overflow-x-auto px-2 pb-3">
                  <table className="w-full text-sm">
                    <thead className="text-left text-muted">
                      <tr><th scope="col" className="p-2 font-medium">Rank</th><th scope="col" className="p-2 font-medium">Manager</th><th scope="col" className="p-2 font-medium">Final cash</th><th scope="col" className="hidden p-2 font-medium sm:table-cell">Resort</th></tr>
                    </thead>
                    <tbody className="divide-y divide-border">
                      {season.rows.map((r) => (
                        <tr key={r.rank + r.username}>
                          <td className="whitespace-nowrap p-2 font-semibold"><Medal rank={r.rank} /> {r.rank}</td>
                          <td className="p-2"><div className="font-semibold">{r.username}</div>{r.resortName && <div className="text-muted">{r.resortName}</div>}</td>
                          <td className="p-2 tabular-nums">{r.cash}</td>
                          <td className="hidden p-2 sm:table-cell">{r.slopes} slopes · {r.lifts} lifts</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </Disclosure.Content>
            </Disclosure>
          ))}
        </section>
      )}

      <a href="/vote" className="flex items-center gap-3 rounded-xl border border-border bg-surface p-4 hover:border-accent">
        <Icon name="fa-check-to-slot" className="text-2xl text-accent-soft-foreground" />
        <span className="flex-1">
          <span className="block font-semibold">Where should we ski next?</span>
          <span className="block text-sm text-muted">Vote for next season's resort.</span>
        </span>
        <span className="font-semibold text-link">Vote</span>
      </a>
    </Page>
  );
}

function PlayerRow({ row: r, v2 }: { row: Row; v2: boolean }) {
  return (
    <tr className={r.isMe ? 'bg-accent-soft' : ''}>
      <td className="whitespace-nowrap p-3 font-semibold"><Medal rank={r.rank} /> {r.rank}</td>
      <td className="p-3">
        <div className="flex min-w-0 items-center gap-2">
          <span aria-hidden="true" className="flex size-8 shrink-0 items-center justify-center rounded-full bg-default text-sm font-semibold">{r.username.slice(0, 2).toUpperCase()}</span>
          <div className="min-w-0">
            <div className="flex flex-wrap items-center gap-2 font-semibold">
              <span className="truncate">{r.username}</span>
              {r.isMe ? (
                <span className="rounded-full bg-accent px-2 text-sm text-accent-foreground">You</span>
              ) : (
                <a href={`/tour/${r.id}`} className="rounded-full bg-default px-2 text-sm font-normal hover:underline" aria-label={`Tour ${r.username}'s resort`}>Tour</a>
              )}
            </div>
            {r.resortName && <div className="truncate text-muted">{r.resortName}</div>}
          </div>
        </div>
      </td>
      <td className={`whitespace-nowrap p-3 font-semibold tabular-nums ${r.profitPositive ? 'text-success' : 'text-danger'}`}>{r.profit}</td>
      <td className="hidden whitespace-nowrap p-3 tabular-nums sm:table-cell">{r.skiers}</td>
      <td className="hidden whitespace-nowrap p-3 md:table-cell">{r.slopes} slopes · {r.lifts} lifts · {r.buildings} buildings</td>
      <td className="hidden whitespace-nowrap p-3 tabular-nums lg:table-cell">{r.cash}</td>
      {!v2 && <td className="p-3 font-semibold">{r.score}</td>}
    </tr>
  );
}
