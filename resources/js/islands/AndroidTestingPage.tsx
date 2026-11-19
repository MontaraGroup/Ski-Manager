import type { ReactNode } from 'react';
import { Icon } from '@/components/Icon';
import { Page, Panel } from '@/components/Page';

export type AndroidTestingState = {
  eligible: boolean;
  groupUrl: string;
  joinUrl: string;
};

const linkButton =
  'mt-2 inline-flex min-h-11 items-center gap-2 rounded-lg bg-accent px-4 text-sm font-semibold text-accent-foreground hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 md:min-h-9';

function Step({ n, title, children }: { n: number; title: string; children: ReactNode }) {
  return (
    <li className="flex gap-3">
      <span className="flex size-7 shrink-0 items-center justify-center rounded-full bg-default text-sm font-semibold tabular-nums" aria-hidden="true">
        {n}
      </span>
      <div className="min-w-0 text-sm text-foreground">
        <p className="font-semibold">{title}</p>
        {children}
      </div>
    </li>
  );
}

function NotUpYet() {
  return <p className="mt-1 text-muted">The link isn't up yet. Check back soon.</p>;
}

/** /beta/android: join the testers Google Group, then opt in to the closed test on Google Play */
export default function AndroidTestingPage({ eligible, groupUrl, joinUrl }: AndroidTestingState) {
  return (
    <Page
      title="Test the Android app"
      icon="fa-brands fa-android"
      back="/beta"
      width="max-w-3xl"
      subtitle="Get Ski Manager on Google Play before it's public. Google needs 12 testers for 14 days in a row before the app can go public."
    >
      {!eligible ? (
        <Panel>
          <p className="text-sm text-muted">Demo accounts can't join the test. Create a free account to take part.</p>
        </Panel>
      ) : (
        <Panel title="How to join">
          <p className="mb-4 text-sm text-muted">You need an Android phone. Use the same Google account in every step.</p>
          <ol className="flex flex-col gap-5">
            <Step n={1} title="Join the testers group">
              <p className="mt-1 text-muted">Google only lets group members into the test.</p>
              {groupUrl ? (
                <a href={groupUrl} target="_blank" rel="noopener" className={linkButton}>
                  <Icon name="fa-users" /> Join the Google Group
                </a>
              ) : (
                <NotUpYet />
              )}
            </Step>
            <Step n={2} title="Become a tester">
              <p className="mt-1 text-muted">Open the test page and press "Become a tester".</p>
              {joinUrl ? (
                <a href={joinUrl} target="_blank" rel="noopener" className={linkButton}>
                  <Icon name="fa-brands fa-google-play" /> Open the test on Google Play
                </a>
              ) : (
                <NotUpYet />
              )}
            </Step>
            <Step n={3} title="Install Ski Manager">
              <p className="mt-1 text-muted">Use the download link on the test page, or search Ski Manager in the Play Store. Sign in with your ski-manager.net account.</p>
            </Step>
            <Step n={4} title="Keep it for 14 days">
              <p className="mt-1 text-muted">Keep the app installed and play now and then. Leaving the test early resets your 14 days.</p>
            </Step>
          </ol>
        </Panel>
      )}
    </Page>
  );
}
