import { Button } from '@heroui/react';
import { CsrfInput, Page, Panel, PostButton } from '@/components/Page';

export type AdminAndroidTestingPageProps = {
  groupUrl: string;
  joinUrl: string;
  invite: { eligible: number; notified: number; notifiedAt: string | null };
  rewards: {
    cap: number;
    needed: number;
    genepis: number;
    paid: { userId: number; username: string; paidAt: string }[];
    testers: { userId: number; username: string; days: number; firstDay: string; lastDay: string }[];
  };
};

const inputClass = 'min-h-11 w-full rounded-md border border-border bg-background px-2 text-sm';

/** /admin/android-testing: the closed test's Google Group and opt-in link, and the invite to players */
export default function AdminAndroidTestingPage({ groupUrl, joinUrl, invite, rewards }: AdminAndroidTestingPageProps) {
  const toNotify = Math.max(0, invite.eligible - invite.notified);
  const linksMissing = !groupUrl || !joinUrl;
  return (
    <Page title="Android testing" icon="fa-brands fa-android" back="/admin" subtitle="Players join the testers Google Group, then opt in to the closed test at /beta/android.">
      <div className="flex flex-col gap-6">
        <Panel title="Links">
          <form action="/admin/android-testing/link" method="post" className="flex flex-col gap-3">
            <CsrfInput />
            <label htmlFor="group_url" className="text-sm font-medium">Testers Google Group (shown to players in step 1)</label>
            <input id="group_url" name="group_url" type="url" defaultValue={groupUrl} placeholder="https://groups.google.com/g/…" className={inputClass} />
            <p className="text-sm text-muted">
              In Google Groups, let anyone ask to join or join directly. In Play Console, add the group's email on the closed track's Testers tab.
            </p>
            <label htmlFor="join_url" className="text-sm font-medium">Opt-in link from Play Console (step 2)</label>
            <input id="join_url" name="join_url" type="url" defaultValue={joinUrl} placeholder="https://play.google.com/apps/testing/…" className={inputClass} />
            <p className="text-sm text-muted">Testers tab, under "How testers join your test". Leave a field empty to hide that step's button.</p>
            <div>
              <Button type="submit" variant="primary" className="min-h-11 md:min-h-9">Save links</Button>
            </div>
          </form>
        </Panel>

        <Panel title="Tell players">
          <p className="text-sm text-foreground">
            Sends one notification to every active, non-demo account: <q>Ski Manager for Android: join the closed test</q>. It opens
            /beta/android. Accounts that already have it are skipped.
          </p>
          <p className="mt-2 text-sm text-muted">
            {invite.notified > 0
              ? `${invite.notified} of ${invite.eligible} eligible accounts have it${invite.notifiedAt ? `, last sent ${invite.notifiedAt}` : ''}.`
              : `${invite.eligible} eligible accounts. Not sent yet.`}
          </p>
          {linksMissing && <p className="mt-2 text-sm text-warning">Save both links first: without them, players have no way to join.</p>}
          <div className="mt-3">
            <PostButton
              action="/admin/android-testing/notify"
              variant="primary"
              disabled={toNotify === 0 || linksMissing}
              confirm={{
                title: `Notify ${toNotify} ${toNotify === 1 ? 'player' : 'players'}?`,
                body: "Every eligible account that hasn't had this notification gets it now. It can't be taken back.",
                confirmLabel: 'Send notification',
              }}
            >
              {toNotify === 0 ? 'Everyone has it' : `Notify ${toNotify} ${toNotify === 1 ? 'player' : 'players'}`}
            </PostButton>
          </div>
        </Panel>

        <Panel title={`Tester rewards (${rewards.paid.length} of ${rewards.cap} paid)`}>
          <p className="text-sm text-muted">
            Paid automatically: {rewards.genepis} Génépis to each of the first {rewards.cap} players who use the Android app on {rewards.needed} different days.
          </p>
          {rewards.paid.length > 0 && (
            <ul className="mt-3 flex flex-col divide-y divide-border">
              {rewards.paid.map((p) => (
                <li key={p.userId} className="flex flex-wrap items-center gap-x-3 py-2 text-sm">
                  <a href={`/admin/user/${p.userId}`} className="font-medium text-accent-soft-foreground underline">{p.username}</a>
                  <span className="ml-auto tabular-nums text-muted">paid {p.paidAt}</span>
                </li>
              ))}
            </ul>
          )}
          <h3 className="mt-4 text-sm font-semibold">Testing now</h3>
          {rewards.testers.length === 0 ? (
            <p className="mt-1 text-sm text-muted">Nobody has used the Android app yet.</p>
          ) : (
            <ul className="mt-1 flex flex-col divide-y divide-border">
              {rewards.testers.map((t) => (
                <li key={t.userId} className="flex flex-wrap items-center gap-x-3 py-2 text-sm">
                  <a href={`/admin/user/${t.userId}`} className="font-medium text-accent-soft-foreground underline">{t.username}</a>
                  <span className="tabular-nums">{t.days} / {rewards.needed} days</span>
                  <span className="ml-auto tabular-nums text-muted">{t.firstDay} to {t.lastDay}</span>
                </li>
              ))}
            </ul>
          )}
        </Panel>
      </div>
    </Page>
  );
}
