import { Button } from '@heroui/react';
import { CsrfInput, Page, Panel, PostButton } from '@/components/Page';

export type AdminAndroidTestingPageProps = {
  groupUrl: string;
  joinUrl: string;
  invite: { eligible: number; notified: number; notifiedAt: string | null };
};

const inputClass = 'min-h-11 w-full rounded-md border border-border bg-background px-2 text-sm';

/** /admin/android-testing: the closed test's Google Group and opt-in link, and the invite to players */
export default function AdminAndroidTestingPage({ groupUrl, joinUrl, invite }: AdminAndroidTestingPageProps) {
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
      </div>
    </Page>
  );
}
