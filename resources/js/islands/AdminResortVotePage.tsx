import { useState } from 'react';
import { Button, Chip } from '@heroui/react';
import { CsrfInput, Empty, Page, Panel, PostButton } from '@/components/Page';
import type { SkiArea, TallyRow, VoteRound } from '@/lib/resortVote';

type AdminRound = VoteRound & { status: 'open' | 'closed'; tally: TallyRow[]; isCurrent: boolean };

export type AdminResortVotePageProps = {
  flagLevel: number;
  flagId: number | null;
  currentId: number;
  rounds: AdminRound[];
  inGame: SkiArea[];
  season4: { name: string; votes: number }[];
  attribution: string;
};

const LEVELS = ['Off', 'Admins only', 'Everyone', 'Beta'];

export default function AdminResortVotePage({ flagLevel, rounds, inGame, season4, attribution }: AdminResortVotePageProps) {
  return (
    <Page title="Resort Vote" icon="fa-check-to-slot" back="/admin" subtitle="Players pick a North American resort each month. You pick the winner.">
      <div className="flex flex-col gap-6">
        <Panel title="Status">
          <p className="text-sm text-muted">
            Flag <code>resort_vote</code>: {LEVELS[flagLevel] ?? flagLevel}. While it is off, /vote shows the old Season 4 page.{' '}
            <a href="/admin/features" className="inline-flex min-h-11 items-center sm:min-h-0 font-medium text-accent-soft-foreground underline">Change it on Feature flags</a>
          </p>
          <p className="mt-2 text-sm text-muted">Each month opens on its own on the 1st and closes after its last day. No cron: the first visit after midnight rolls it over.</p>
        </Panel>

        {rounds.map((r) => <RoundPanel key={r.id} round={r} />)}

        <Panel title={`Playable resorts (${inGame.length})`}>
          {inGame.length === 0 ? (
            <Empty icon="fa-mountain" title="None marked" />
          ) : (
            <ul className="flex flex-col divide-y divide-border">
              {inGame.map((a) => (
                <li key={a.id} className="flex flex-wrap items-center gap-3 py-2">
                  <span className="min-w-0 flex-1 text-sm"><span className="font-medium">{a.name}</span> <span className="text-muted">{a.place}</span></span>
                  <PostButton action={`/admin/resort-vote/area/${a.id}/in-game`} data={{ in_game: 0 }} variant="tertiary" size="sm" className="min-h-11 sm:min-h-8">Put back on the ballot</PostButton>
                </li>
              ))}
            </ul>
          )}
          <p className="mt-2 text-xs text-muted">Playable resorts and past winners are left out of the search.</p>
        </Panel>

        {season4.length > 0 && (
          <Panel title="Old Season 4 vote (closed when the flag is on)">
            <ul className="flex flex-col divide-y divide-border">
              {season4.map((s) => (
                <li key={s.name} className="flex justify-between py-2 text-sm"><span>{s.name}</span><span className="tabular-nums">{s.votes}</span></li>
              ))}
            </ul>
          </Panel>
        )}

        <p className="text-xs text-muted">{attribution}</p>
      </div>
    </Page>
  );
}

function RoundPanel({ round }: { round: AdminRound }) {
  const tie = round.tally.length > 1 && round.tally[0].votes === round.tally[1].votes;
  const [pick, setPick] = useState<number | null>(round.winner?.id ?? round.tally[0]?.id ?? null);
  const status = round.open ? `Open until ${round.closesLabel}` : round.closedLabel ? `Closed ${round.closedLabel}` : 'Closed';

  return (
    <Panel
      title={
        <span className="flex flex-wrap items-center gap-2">
          {round.label}
          <Chip size="sm" variant="soft" color={round.open ? 'success' : 'default'}>{round.open ? 'Open' : 'Closed'}</Chip>
          {round.winner && <Chip size="sm" variant="soft" color="accent">Winner: {round.winner.name}</Chip>}
        </span>
      }
      actions={
        round.isCurrent ? (
          round.open ? (
            <PostButton
              action={`/admin/resort-vote/${round.id}/open`}
              data={{ open: 0 }}
              variant="secondary"
              size="sm"
              className="min-h-11 sm:min-h-8"
              confirm={{ title: `Close ${round.label} early?`, body: 'Players can no longer vote or change their vote this month. You can reopen it until the month ends.', confirmLabel: 'Close voting' }}
            >
              Close voting now
            </PostButton>
          ) : (
            <PostButton action={`/admin/resort-vote/${round.id}/open`} data={{ open: 1 }} variant="secondary" size="sm" className="min-h-11 sm:min-h-8">Reopen voting</PostButton>
          )
        ) : undefined
      }
    >
      <p className="mb-3 text-sm text-muted">{status}. {round.votes} {round.votes === 1 ? 'vote' : 'votes'} from active, non-demo accounts.</p>

      {round.tally.length === 0 ? (
        <Empty icon="fa-check-to-slot" title="No votes" />
      ) : (
        <form action={`/admin/resort-vote/${round.id}/winner`} method="post" className="flex flex-col gap-4">
          <CsrfInput />
          <fieldset>
            <legend className="sr-only">Votes for {round.label}{round.open ? '' : ', pick the winner'}</legend>
            <ol className="flex flex-col divide-y divide-border">
              {round.tally.map((t, i) => (
                <li key={t.id}>
                  <label className="flex min-h-11 items-center gap-3 py-2">
                    {!round.open && (
                      <input type="radio" name="area_id" value={t.id} checked={pick === t.id} onChange={() => setPick(t.id)} className="size-4 accent-[var(--accent)]" />
                    )}
                    <span className="w-6 shrink-0 text-right text-sm tabular-nums text-muted">{i + 1}</span>
                    <span className="min-w-0 flex-1 text-sm">
                      <span className="font-medium">{t.name}</span> <span className="text-muted">{t.place}</span>
                    </span>
                    <span className="shrink-0 text-sm tabular-nums"><span className="font-semibold">{t.votes}</span> <span className="text-muted">· {t.pct}%</span></span>
                  </label>
                </li>
              ))}
            </ol>
          </fieldset>
          {tie && !round.open && <p className="text-sm text-warning">The top two are tied. Pick one by hand.</p>}

          {!round.open && (
            <>
              <div className="flex flex-col gap-1">
                <label htmlFor={`note-${round.id}`} className="text-sm font-medium">Note (optional, admin only)</label>
                <textarea id={`note-${round.id}`} name="note" maxLength={500} rows={2} defaultValue={round.winnerNote ?? ''} className="rounded-lg border border-border bg-surface p-3 text-sm text-foreground" />
              </div>
              <label className="flex min-h-11 items-center gap-2 text-sm">
                <input type="checkbox" name="roadmap" value="1" defaultChecked={!round.roadmapItemId} disabled={!!round.roadmapItemId} className="size-4" />
                {round.roadmapItemId ? 'On the roadmap already (picking again renames the item)' : 'Add it to the roadmap as Planned'}
              </label>
              <label className="flex min-h-11 items-center gap-2 text-sm">
                <input type="checkbox" name="notify" value="1" defaultChecked={!round.votersNotified} disabled={round.votersNotified} className="size-4" />
                {round.votersNotified ? 'Voters were notified' : `Notify the ${round.votes} ${round.votes === 1 ? 'voter' : 'voters'}`}
              </label>
              <Button type="submit" variant="primary" className="min-h-11 self-start" isDisabled={pick === null}>
                {round.winner ? 'Save winner' : 'Pick winner'}
              </Button>
            </>
          )}
        </form>
      )}

      {round.winner && (
        <div className="mt-4 flex flex-wrap items-center gap-3 border-t border-border pt-4">
          <span className="min-w-0 flex-1 text-sm">When {round.winner.name} is built in Map Studio, mark it playable.</span>
          <PostButton action={`/admin/resort-vote/area/${round.winner.id}/in-game`} data={{ in_game: 1 }} variant="secondary" size="sm" className="min-h-11 sm:min-h-8">Mark playable</PostButton>
        </div>
      )}
    </Panel>
  );
}
