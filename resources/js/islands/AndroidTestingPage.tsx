import type { ReactNode } from 'react';
import { Icon } from '@/components/Icon';
import { Page, Panel } from '@/components/Page';

export type TesterReward = { days: number; needed: number; genepis: number; cap: number; left: number; paid: boolean };

export type AndroidTestingState = {
  eligible: boolean;
  groupUrl: string;
  joinUrl: string;
  reward: TesterReward;
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

/** Progress toward the tester reward: days the Android app was used, out of the days needed */
function RewardPanel({ reward }: { reward: TesterReward }) {
  const pct = Math.min(100, Math.round((reward.days / reward.needed) * 100));
  return (
    <Panel title={`Tester reward: ${reward.genepis} Génépis`}>
      {reward.paid ? (
        <p className="text-sm text-foreground">You earned your {reward.genepis} Génépis. Thanks for testing.</p>
      ) : (
        <>
          <p className="text-sm text-foreground">
            The first {reward.cap} testers to open the app on {reward.needed} different days get {reward.genepis} Génépis.{' '}
            {reward.left > 0 ? `${reward.left} of ${reward.cap} rewards left.` : 'All rewards are taken, but your testing still counts toward the launch.'}
          </p>
          <div className="mt-3 flex items-center gap-3">
            <div
              className="h-2 flex-1 overflow-hidden rounded-full bg-default"
              role="progressbar"
              aria-label="Days you used the Android app"
              aria-valuemin={0}
              aria-valuemax={reward.needed}
              aria-valuenow={Math.min(reward.days, reward.needed)}
            >
              <div className="h-full rounded-full bg-accent" style={{ width: `${pct}%` }} />
            </div>
            <span className="text-sm tabular-nums text-muted">
              {Math.min(reward.days, reward.needed)} / {reward.needed} days
            </span>
          </div>
          <p className="mt-2 text-sm text-muted">A day counts when you open the Android app signed in to your account.</p>
        </>
      )}
    </Panel>
  );
}

/** /beta/android: join the testers Google Group, then opt in to the closed test on Google Play */
export default function AndroidTestingPage({ eligible, groupUrl, joinUrl, reward }: AndroidTestingState) {
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
        <div className="flex flex-col gap-6">
        <RewardPanel reward={reward} />
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
              <p className="mt-1 text-muted">Keep the app installed and open it on 14 different days. Leaving the test early resets Google's 14 days.</p>
            </Step>
          </ol>
        </Panel>
        </div>
      )}
    </Page>
  );
}
