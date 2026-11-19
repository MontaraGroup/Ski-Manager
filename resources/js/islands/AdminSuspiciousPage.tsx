import { Page } from '@/components/Page';
import { ConfirmForm, Field } from './admin/ConfirmForm';

export type SuspectReason = { signal: string; points: number; detail: string };

export type Suspect = {
  id: number;
  username: string;
  score: number;
  reasons: SuspectReason[];
  cashFormatted: string;
  difficulty: string;
  adminEdits: number;
  review: { decision: 'flagged' | 'cleared'; when: string } | null;
};

export type AdminSuspiciousPageProps = { players: Suspect[] };

/** Players whose cash, Génépis or activity the game's own records can't explain (App\Libraries\Suspicion) */
export default function AdminSuspiciousPage({ players }: AdminSuspiciousPageProps) {
  const open = players.filter((p) => !p.review);
  const reviewed = players.filter((p) => p.review);

  return (
    <Page title="Suspicious accounts" icon="fa-user-shield" iconTone="text-muted" back="/admin" width="max-w-4xl" subtitle="Players whose numbers the game's own records don't explain. Higher score, stronger evidence.">
      <section aria-labelledby="open-h" className="flex flex-col gap-3">
        <h2 id="open-h" className="text-lg font-semibold">
          To review <span className="font-normal text-muted">({open.length})</span>
        </h2>
        {open.length === 0 ? (
          <p className="text-sm text-muted">Nothing to review. Every balance matches the ledger and nothing else stands out.</p>
        ) : (
          <ul className="flex flex-col divide-y divide-border border-y border-border">
            {open.map((p) => (
              <SuspectRow key={p.id} p={p} />
            ))}
          </ul>
        )}
      </section>

      {reviewed.length > 0 && (
        <section aria-labelledby="done-h" className="mt-8 flex flex-col gap-3">
          <h2 id="done-h" className="text-lg font-semibold">
            Reviewed <span className="font-normal text-muted">({reviewed.length})</span>
          </h2>
          <ul className="flex flex-col divide-y divide-border border-y border-border">
            {reviewed.map((p) => (
              <SuspectRow key={p.id} p={p} />
            ))}
          </ul>
        </section>
      )}

      <details className="mt-8 text-sm text-muted">
        <summary className="min-h-11 cursor-pointer py-2 font-medium text-foreground">How players get here</summary>
        <ul className="ml-5 list-disc space-y-1">
          <li>Unexplained cash, 3 points: more than 100,000 above starting cash plus recorded income minus expenses.</li>
          <li>Unexplained Génépis, 2 points: more than 20 above earned minus spent.</li>
          <li>Income spike, 2 points: one day over 250,000 and over 4 times the 14-day average.</li>
          <li>Rapid repeats, 2 points: 5 or more identical income entries within one minute.</li>
          <li>Shared IP, 1 point: 3 or more accounts signed in from one IP in 14 days. Families and schools trigger this too.</li>
        </ul>
        <p className="mt-2">Admin edits to a player's balance also create gaps, so they are shown next to the player.</p>
      </details>
    </Page>
  );
}

function SuspectRow({ p }: { p: Suspect }) {
  return (
    <li className="flex flex-col gap-2 py-4 sm:flex-row sm:items-start sm:justify-between">
      <div className="min-w-0 flex-1">
        <div className="flex flex-wrap items-baseline gap-x-3 gap-y-1">
          <a href={`/admin/user/${p.id}`} className="inline-flex min-h-11 items-center font-semibold text-link hover:underline sm:min-h-0">
            {p.username}
          </a>
          <span className="text-sm text-muted">
            #{p.id} · {p.difficulty} · {p.cashFormatted}
          </span>
          <span className="text-sm font-semibold tabular-nums">Score {p.score}</span>
        </div>
        <ul className="mt-1 space-y-0.5 text-sm">
          {p.reasons.map((r, i) => (
            <li key={i}>
              <span className="font-medium">{r.signal}</span> (+{r.points}): {r.detail}
            </li>
          ))}
        </ul>
        {p.adminEdits > 0 && (
          <p className="mt-1 text-sm text-warning">
            An admin changed this balance {p.adminEdits === 1 ? 'once' : `${p.adminEdits} times`} in the last 30 days. Check the audit log before flagging.
          </p>
        )}
        {p.review && (
          <p className="mt-1 text-sm text-muted">
            {p.review.decision === 'flagged' ? 'Flagged as cheating' : 'Cleared'} {p.review.when}.
          </p>
        )}
      </div>
      <ConfirmForm
        action={`/admin/suspicious/${p.id}/review`}
        submitLabel="Save review"
        title={`Review ${p.username}`}
        describe={(d) =>
          d.get('decision') === 'flagged'
            ? `Records a cheating flag on ${p.username} in the audit log, with your note. It doesn't ban or change the account.`
            : `Marks ${p.username} as checked and fine, with your note. They move to Reviewed.`
        }
        confirmLabel="Save"
        className="flex w-full shrink-0 flex-col gap-2 sm:w-64"
      >
        <label className="flex flex-col gap-1 text-sm">
          <span className="font-medium">Decision</span>
          <select name="decision" defaultValue="cleared" className="min-h-11 rounded-lg border border-border bg-field px-3 text-base text-field-foreground sm:min-h-9 sm:text-sm">
            <option value="cleared">Fine, clear it</option>
            <option value="flagged">Cheating, flag it</option>
          </select>
        </label>
        <Field label="Note" name="note" required maxLength={500} placeholder="What you checked" />
      </ConfirmForm>
    </li>
  );
}
