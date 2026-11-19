import { useState } from 'react';
import { Button, toast } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Empty, Page, Panel } from '@/components/Page';
import { api, ApiError } from '@/lib/api';

export type AndroidTestingState = {
  eligible: boolean;
  code: string | null;
  claimedAt: string | null;
  available: boolean;
  joinUrl: string;
};

/** /beta/android: one Google Play access code per player for the Android app's closed test */
export default function AndroidTestingPage(props: AndroidTestingState) {
  const [state, setState] = useState<AndroidTestingState>(props);
  const [busy, setBusy] = useState(false);

  async function claim() {
    setBusy(true);
    try {
      setState(await api<AndroidTestingState>('beta/android/claim', { method: 'POST', body: {} }));
    } catch (e) {
      toast.danger(e instanceof ApiError && e.status !== 500 ? e.message : "That didn't work. Try again.");
    } finally {
      setBusy(false);
    }
  }

  function copy() {
    if (!state.code) return;
    navigator.clipboard.writeText(state.code).then(
      () => toast.success('Code copied.'),
      () => toast.danger("Couldn't copy. Select the code and copy it yourself."),
    );
  }

  return (
    <Page
      title="Test the Android app"
      icon="fa-brands fa-android"
      back="/beta"
      width="max-w-3xl"
      subtitle="Get Ski Manager on Google Play before it's public. Google needs 12 testers for 14 days in a row before the app can go public."
    >
      {!state.eligible ? (
        <Panel>
          <p className="text-sm text-muted">Demo accounts can't join the test. Create a free account to get a tester code.</p>
        </Panel>
      ) : !state.code && !state.available ? (
        <Empty icon="fa-mobile-screen" title="No tester codes left right now.">
          More are on the way. Check back in a few days.
        </Empty>
      ) : !state.code ? (
        <Panel>
          <p className="mb-3 text-sm text-foreground">You need an Android phone and a Google account.</p>
          <Button variant="primary" onPress={claim} isDisabled={busy} className="min-h-11 md:min-h-9">
            Get my tester code
          </Button>
        </Panel>
      ) : (
        <>
          <Panel title="Your access code">
            <div className="flex flex-wrap items-center gap-3">
              <code className="select-all break-all rounded-md bg-default px-3 py-2 font-mono text-lg font-semibold tracking-wide text-foreground">
                {state.code}
              </code>
              <Button variant="secondary" onPress={copy} className="min-h-11 md:min-h-9">
                <Icon name="fa-copy" /> Copy
              </Button>
            </div>
            <p className="mt-2 text-sm text-muted">This code is yours. It stays here if you come back later.</p>
            {state.joinUrl && (
              <a
                href={state.joinUrl}
                target="_blank"
                rel="noopener"
                className="mt-4 inline-flex min-h-11 items-center gap-2 rounded-lg bg-accent px-4 text-sm font-semibold text-accent-foreground hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 md:min-h-9"
              >
                <Icon name="fa-brands fa-google-play" /> Join the test on Google Play
              </a>
            )}
          </Panel>

          <Panel title="How to join">
            <ol className="list-decimal space-y-2 pl-5 text-sm text-foreground">
              <li>
                On your Android phone, open the test link and sign in with your Google account.
                {state.joinUrl ? (
                  <>
                    {' '}
                    <a href={state.joinUrl} target="_blank" rel="noopener" className="inline-flex min-h-11 items-center font-medium text-accent-soft-foreground underline sm:min-h-0">
                      Open the test link
                    </a>
                  </>
                ) : (
                  <span className="text-muted"> The link isn't up yet. Your code will work once it is.</span>
                )}
              </li>
              <li>Enter your tester code when Google Play asks for it.</li>
              <li>Install Ski Manager from Google Play.</li>
              <li>Keep the app installed and play for at least 14 days. Leaving the test early resets your 14 days.</li>
            </ol>
          </Panel>
        </>
      )}
    </Page>
  );
}
