import { Button } from '@heroui/react';
import { CsrfInput, Empty, Page, Panel, PostButton } from '@/components/Page';

export type AdminAndroidTestingPageProps = {
  total: number;
  claimed: number;
  left: number;
  joinUrl: string;
  claims: { userId: number; username: string; code: string; claimedAt: string }[];
  invite: { eligible: number; notified: number; notifiedAt: string | null };
};

/** /admin/android-testing: load Play Console access codes and see who claimed one */
export default function AdminAndroidTestingPage({ total, claimed, left, joinUrl, claims, invite }: AdminAndroidTestingPageProps) {
  const toNotify = Math.max(0, invite.eligible - invite.notified);
  return (
    <Page title="Android testing" icon="fa-brands fa-android" back="/admin" subtitle="Players claim one Google Play access code each at /beta/android.">
      <div className="flex flex-col gap-6">
        <Panel title="Codes">
          <dl className="grid grid-cols-3 gap-3 text-center">
            {[['Loaded', total], ['Claimed', claimed], ['Left', left]].map(([label, n]) => (
              <div key={label} className="rounded-md bg-default p-3">
                <dt className="text-sm text-muted">{label}</dt>
                <dd className="text-2xl font-semibold tabular-nums">{n}</dd>
              </div>
            ))}
          </dl>
          {left < 20 && <p className="mt-3 text-sm text-warning">{left === 0 ? 'No codes left: players see "No tester codes left".' : 'Running low. Generate more in Play Console.'}</p>}
        </Panel>

        <Panel title="Add codes">
          <form action="/admin/android-testing/codes" method="post" encType="multipart/form-data" className="flex flex-col gap-3">
            <CsrfInput />
            <label htmlFor="codes" className="text-sm font-medium">Paste codes (one per line, or any separator)</label>
            <textarea id="codes" name="codes" rows={6} className="w-full rounded-md border border-border bg-background p-2 font-mono text-sm" />
            <label htmlFor="codes_file" className="text-sm font-medium">Or upload the CSV from Play Console</label>
            <input id="codes_file" name="codes_file" type="file" accept=".csv,.txt,text/csv,text/plain" className="text-sm" />
            <p className="text-sm text-muted">Codes already loaded are skipped.</p>
            <div>
              <Button type="submit" variant="primary" className="min-h-11 md:min-h-9">Add codes</Button>
            </div>
          </form>
        </Panel>

        <Panel title="Test link">
          <form action="/admin/android-testing/link" method="post" className="flex flex-col gap-3">
            <CsrfInput />
            <label htmlFor="join_url" className="text-sm font-medium">The closed test's link from Play Console (shown to players in step 1)</label>
            <input id="join_url" name="join_url" type="url" defaultValue={joinUrl} placeholder="https://play.google.com/…" className="min-h-11 w-full rounded-md border border-border bg-background px-2 text-sm" />
            <p className="text-sm text-muted">Leave it empty to hide the link.</p>
            <div>
              <Button type="submit" variant="secondary" className="min-h-11 md:min-h-9">Save link</Button>
            </div>
          </form>
        </Panel>

        <Panel title="Tell players">
          <p className="text-sm text-foreground">
            Sends one notification to every active, non-demo account: <q>Ski Manager for Android: your access code</q>. It opens
            /beta/android, which gives the player their code on the first visit. Accounts that already have it are skipped.
          </p>
          <p className="mt-2 text-sm text-muted">
            {invite.notified > 0
              ? `${invite.notified} of ${invite.eligible} eligible accounts have it${invite.notifiedAt ? `, last sent ${invite.notifiedAt}` : ''}.`
              : `${invite.eligible} eligible accounts. Not sent yet.`}
          </p>
          {!joinUrl && <p className="mt-2 text-sm text-warning">Save the test link first: without it, players see a code but no way to join.</p>}
          {left < toNotify && (
            <p className="mt-2 text-sm text-warning">
              {left} codes left for {toNotify} accounts to notify. Players who open it after the codes run out see "No tester codes left".
            </p>
          )}
          <div className="mt-3">
            <PostButton
              action="/admin/android-testing/notify"
              variant="primary"
              disabled={toNotify === 0}
              confirm={{
                title: `Notify ${toNotify} ${toNotify === 1 ? 'player' : 'players'}?`,
                body: 'Every eligible account that hasn\'t had this notification gets it now. It can\'t be taken back.',
                confirmLabel: 'Send notification',
              }}
            >
              {toNotify === 0 ? 'Everyone has it' : `Notify ${toNotify} ${toNotify === 1 ? 'player' : 'players'}`}
            </PostButton>
          </div>
        </Panel>

        <Panel
          title={`Claimed (${claimed})`}
          actions={
            left > 0 ? (
              <PostButton
                action="/admin/android-testing/codes"
                data={{ remove_unclaimed: 1 }}
                variant="danger"
                size="sm"
                confirm={{
                  title: `Remove ${left} unclaimed ${left === 1 ? 'code' : 'codes'}?`,
                  body: 'Claimed codes stay, so players keep theirs. Use this if you loaded the wrong codes.',
                  confirmLabel: 'Remove unclaimed',
                }}
              >
                Remove unclaimed
              </PostButton>
            ) : undefined
          }
        >
          {claims.length === 0 ? (
            <Empty icon="fa-mobile-screen" title="Nobody has claimed a code yet" />
          ) : (
            <ul className="flex flex-col divide-y divide-border">
              {claims.map((c) => (
                <li key={c.code} className="flex flex-wrap items-center gap-x-3 gap-y-1 py-2 text-sm">
                  <a href={`/admin/user/${c.userId}`} className="font-medium text-accent-soft-foreground underline">{c.username}</a>
                  <code className="font-mono text-muted">{c.code}</code>
                  <span className="ml-auto tabular-nums text-muted">{c.claimedAt}</span>
                </li>
              ))}
            </ul>
          )}
        </Panel>
      </div>
    </Page>
  );
}
