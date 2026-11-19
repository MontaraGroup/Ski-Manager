import { useState } from 'react';
import { AlertDialog, Button, Spinner, toast } from '@heroui/react';
import { ApiError } from '@/lib/api';
import { joinBeta, leaveBeta, type BetaState } from '@/lib/beta';

function failMessage(e: unknown, fallback: string): string {
  return e instanceof ApiError && (e.status === 403 || e.status === 404) ? e.message : fallback;
}

/** Join or leave the beta program. Used in Settings and on /beta. */
export function BetaMembership({
  state,
  onChange,
  showLink = true,
}: {
  state: BetaState;
  onChange: (s: BetaState) => void;
  showLink?: boolean;
}) {
  const [busy, setBusy] = useState(false);
  const [confirmLeave, setConfirmLeave] = useState(false);

  async function join() {
    setBusy(true);
    try {
      onChange(await joinBeta());
      toast.success('You joined the beta program.');
    } catch (e) {
      toast.danger(failMessage(e, "You couldn't join just now. Try again."));
    } finally {
      setBusy(false);
    }
  }

  async function leave() {
    setBusy(true);
    try {
      onChange(await leaveBeta());
      setConfirmLeave(false);
      toast.success('You left the beta program.');
    } catch (e) {
      toast.danger(failMessage(e, "You couldn't leave just now. Try again."));
    } finally {
      setBusy(false);
    }
  }

  if (!state.eligible) {
    return <p className="text-sm text-muted">Demo accounts can't join the beta program. Save your resort to take part.</p>;
  }

  return (
    <div className="space-y-3">
      {state.member ? (
        <p className="text-sm text-foreground">
          You're in the beta program. {state.onCount} beta {state.onCount === 1 ? 'feature is' : 'features are'} on for you.
        </p>
      ) : (
        <p className="text-sm text-foreground">
          Try new features before everyone else. Beta features may change or break, and you can switch any of them off on the
          Beta page.
        </p>
      )}
      <div className="flex flex-wrap items-center gap-x-4 gap-y-2">
        {state.member ? (
          <Button variant="secondary" onPress={() => setConfirmLeave(true)} isDisabled={busy} className="min-h-11 md:min-h-9">
            Leave the beta program
          </Button>
        ) : (
          <Button variant="primary" onPress={join} isDisabled={busy} className="min-h-11 gap-2 md:min-h-9">
            {busy && <Spinner size="sm" />} Join the beta program
          </Button>
        )}
        {showLink && (
          <a href="/beta" className="inline-flex min-h-11 items-center text-sm font-medium text-link hover:underline md:min-h-9">
            {state.member ? 'Manage beta features' : 'See beta features'}
          </a>
        )}
      </div>

      <AlertDialog.Backdrop isOpen={confirmLeave} onOpenChange={(open) => !busy && setConfirmLeave(open)}>
        <AlertDialog.Container>
          <AlertDialog.Dialog className="max-w-md">
            <AlertDialog.Header>
              <AlertDialog.Heading>Leave the beta program?</AlertDialog.Heading>
            </AlertDialog.Header>
            <AlertDialog.Body>
              <p className="text-sm">Every beta feature switches off for you. You can join again any time.</p>
            </AlertDialog.Body>
            <AlertDialog.Footer>
              <Button variant="tertiary" onPress={() => setConfirmLeave(false)} isDisabled={busy}>
                Stay
              </Button>
              <Button variant="danger" onPress={leave} isDisabled={busy} className="gap-2">
                {busy && <Spinner size="sm" />} Leave
              </Button>
            </AlertDialog.Footer>
          </AlertDialog.Dialog>
        </AlertDialog.Container>
      </AlertDialog.Backdrop>
    </div>
  );
}
