import { useState } from 'react';
import { Button } from '@heroui/react';
import { CsrfInput, Empty, Figure, Page, Panel } from '@/components/Page';
import { SEASON_OPTIONS, type SeasonChoice, type SeasonVoteResults } from '@/lib/seasonVote';

export type AdminSeasonVotePageProps = {
  flagLevel: number;
  flagId: number | null;
  closesAt: string;
  closesLabel: string | null;
  open: boolean;
  closed: boolean;
  results: SeasonVoteResults & { comments: { username: string; choice: SeasonChoice; comment: string; date: string }[] };
};

const LEVELS = ['Off', 'Admins only', 'Everyone'];

export default function AdminSeasonVotePage({ flagLevel, closesAt, closesLabel, open, closed, results }: AdminSeasonVotePageProps) {
  const [date, setDate] = useState(closesAt);
  const status = open
    ? flagLevel === 1 ? `Open to admins only until ${closesLabel}.` : `Open until ${closesLabel}.`
    : closed ? `Closed on ${closesLabel}. Players see the results in Settings while the flag is on.`
    : flagLevel === 0 ? 'Off. Switch the flag on and set a close date to open it.'
    : 'No close date set, so the vote is closed.';

  return (
    <Page title="Season Format Vote" icon="fa-check-to-slot" back="/admin" subtitle="Shared season or each player at their own pace">
      <div className="flex flex-col gap-6">
        <Panel title="Status">
          <div className="flex flex-col gap-4">
            <p className="text-sm">{status}</p>
            <p className="text-sm text-muted">
              Flag <code>season_mode_vote</code>: {LEVELS[flagLevel] ?? flagLevel}.{' '}
              <a href="/admin/features" className="inline-flex min-h-11 items-center sm:min-h-0 font-medium text-accent-soft-foreground underline">Change it on Feature flags</a>
            </p>
            <form action="/admin/season-vote/settings" method="post" className="flex flex-col gap-2 sm:flex-row sm:items-end">
              <CsrfInput />
              <div className="flex flex-col gap-1">
                <label htmlFor="closes_at" className="text-sm font-medium">Votes close at</label>
                <input
                  id="closes_at"
                  type="datetime-local"
                  name="closes_at"
                  value={date}
                  onChange={(e) => setDate(e.target.value)}
                  className="min-h-11 rounded-lg border border-border bg-surface px-3 text-sm text-foreground"
                />
              </div>
              <Button type="submit" variant="primary" className="min-h-11">Save close date</Button>
            </form>
            <p className="text-xs text-muted">Leave the date empty and save to close the vote.</p>
          </div>
        </Panel>

        <Panel title="Results">
          <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
            {(['shared', 'individual'] as const).map((k) => (
              <Figure key={k} label={SEASON_OPTIONS[k].title} value={`${results[k].pct}%`} note={`${results[k].count} ${results[k].count === 1 ? 'vote' : 'votes'}`} />
            ))}
            <Figure label="Votes" value={String(results.votes)} note={`of ${results.eligible} accounts`} />
            <Figure label="Not voted" value={String(results.notVoted)} note="active, non-demo accounts" />
          </div>
          <p className="mt-3 text-xs text-muted">Counts only active, non-demo accounts. Snoozed accounts count as not voted.</p>
        </Panel>

        <Panel title={`Comments (${results.comments.length})`}>
          {results.comments.length === 0 ? (
            <Empty icon="fa-comment" title="No comments yet" />
          ) : (
            <ul className="flex flex-col divide-y divide-border">
              {results.comments.map((c, i) => (
                <li key={i} className="flex flex-col gap-1 py-3">
                  <span className="flex flex-wrap items-center gap-x-2 text-xs text-muted">
                    <span className="font-semibold text-foreground">{c.username}</span>
                    <span>{SEASON_OPTIONS[c.choice]?.title}</span>
                    <span>{c.date}</span>
                  </span>
                  <p className="whitespace-pre-line break-words text-sm">{c.comment}</p>
                </li>
              ))}
            </ul>
          )}
        </Panel>
      </div>
    </Page>
  );
}
