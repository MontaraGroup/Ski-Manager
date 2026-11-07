import { useEffect, useRef, useState } from 'react';
import { Button, Modal, toast } from '@heroui/react';
import { Icon } from './Icon';
import { SeasonChoiceFields } from './SeasonChoiceFields';
import { ApiError } from '@/lib/api';
import { sendSnooze, sendVote, type SeasonChoice, type SeasonVoteState } from '@/lib/seasonVote';

const DELAY_MS = 2000;

/**
 * The season format vote popup. Opens once per page load, 2 s after the shell mounts.
 * Closing it any way other than voting (Escape, the backdrop, "Remind me later") hides it for 3 days.
 */
export default function SeasonVoteDialog({ state }: { state: SeasonVoteState }) {
  const [isOpen, setOpen] = useState(false);
  const [choice, setChoice] = useState<SeasonChoice | null>(null);
  const [comment, setComment] = useState('');
  const [error, setError] = useState<string>();
  const [busy, setBusy] = useState(false);
  const [done, setDone] = useState(false);
  const shown = useRef(false);

  useEffect(() => {
    const t = window.setTimeout(() => {
      if (shown.current) return;
      shown.current = true;
      setOpen(true);
    }, DELAY_MS);
    return () => window.clearTimeout(t);
  }, []);

  const close = (open: boolean) => {
    if (open) return;
    setOpen(false);
    // Not voting counts as "remind me later"; a failed snooze only means the popup comes back next page
    if (!done) sendSnooze().catch(() => undefined);
  };

  const vote = async () => {
    if (!choice) return;
    setBusy(true);
    setError(undefined);
    try {
      await sendVote(choice, comment.trim());
      setDone(true);
    } catch (e) {
      if (e instanceof ApiError && e.status === 422) setError(e.message);
      else toast.danger(e instanceof ApiError && e.status === 403 ? e.message : "Your vote didn't save. Try again.");
    } finally {
      setBusy(false);
    }
  };

  return (
    <Modal.Backdrop isOpen={isOpen} onOpenChange={close} isDismissable>
      <Modal.Container placement="center" size="lg" scroll="inside">
        <Modal.Dialog aria-labelledby="season-vote-title">
          <Modal.Header className="flex flex-col items-start gap-1">
            <Modal.Heading id="season-vote-title" className="text-lg font-semibold">How should seasons work?</Modal.Heading>
            {!done && (
              <p className="text-sm text-muted">
                We're deciding how seasons run from here. Nothing changes until we announce it, and you can change your vote until {state.closesLabel}.
              </p>
            )}
          </Modal.Header>
          <Modal.Body>
            {done ? (
              <p className="flex items-start gap-2 text-sm" role="status">
                <Icon name="fa-circle-check" className="mt-0.5 text-success" />
                <span>Thanks. You can change your vote until {state.closesLabel} in <a href="/settings" className="font-medium text-accent-soft-foreground underline">Settings</a>.</span>
              </p>
            ) : (
              <SeasonChoiceFields order={state.order} choice={choice} onChoice={(c) => { setChoice(c); setError(undefined); }} comment={comment} onComment={setComment} error={error} />
            )}
          </Modal.Body>
          <Modal.Footer className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            {done ? (
              <Button variant="primary" className="min-h-11 w-full sm:w-auto" onPress={() => close(false)}>Close</Button>
            ) : (
              <>
                <Button variant="secondary" className="min-h-11 w-full sm:w-auto" onPress={() => close(false)} isDisabled={busy}>Remind me later</Button>
                <Button variant="primary" className="min-h-11 w-full sm:w-auto" onPress={vote} isDisabled={!choice || busy} isPending={busy}>Vote</Button>
              </>
            )}
          </Modal.Footer>
        </Modal.Dialog>
      </Modal.Container>
    </Modal.Backdrop>
  );
}
