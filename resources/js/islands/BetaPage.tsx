import { useState } from 'react';
import { Button, Modal, Spinner, toast } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Empty, Page, Panel } from '@/components/Page';
import { BetaMembership } from '@/components/BetaMembership';
import { ApiError } from '@/lib/api';
import { COMMENT_MAX, sendBetaFeedback, setBetaFeature, type BetaFeature, type BetaState } from '@/lib/beta';

/** /beta: every feature in beta, with this player's switch and feedback */
export default function BetaPage(props: BetaState) {
  const [state, setState] = useState<BetaState>(props);
  const [busyKey, setBusyKey] = useState<string | null>(null);
  const [feedbackFor, setFeedbackFor] = useState<BetaFeature | null>(null);

  async function toggle(f: BetaFeature) {
    setBusyKey(f.key);
    try {
      setState(await setBetaFeature(f.key, !f.on));
      toast.success(`${f.name} is now ${f.on ? 'off' : 'on'}.`);
    } catch (e) {
      toast.danger(e instanceof ApiError && e.status !== 500 ? e.message : "That didn't save. Try again.");
    } finally {
      setBusyKey(null);
    }
  }

  return (
    <Page
      title="Beta features"
      icon="fa-flask"
      back="/settings"
      width="max-w-3xl"
      subtitle="New features we're still working on. Switch them on to try them, and tell us what you think."
    >
      {!state.member && (
        <Panel>
          <p className="mb-3 text-sm font-semibold text-foreground">Join the beta program to try these.</p>
          <BetaMembership state={state} onChange={setState} showLink={false} />
        </Panel>
      )}

      {state.features.length === 0 ? (
        <Empty icon="fa-flask" title="No features are in beta right now.">
          Check back after the next update.
        </Empty>
      ) : (
        <ul className="space-y-3">
          {state.features.map((f) => (
            <li key={f.key}>
              <Panel>
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                  <div className="min-w-0 space-y-1">
                    <h2 className="flex items-center gap-2 text-base font-semibold text-foreground">
                      {f.name}
                      <span className="rounded-full bg-warning/15 px-2 py-0.5 text-xs font-semibold text-warning">Beta</span>
                    </h2>
                    {f.description && <p className="text-sm text-muted">{f.description}</p>}
                    {f.since && <p className="text-sm text-muted">In beta since {f.since}</p>}
                  </div>
                  <div className="flex shrink-0 flex-wrap items-center gap-3">
                    <label
                      className={`flex min-h-11 items-center gap-2 text-sm text-foreground md:min-h-9 ${state.member ? 'cursor-pointer' : 'opacity-60'}`}
                    >
                      <input
                        type="checkbox"
                        role="switch"
                        checked={f.on}
                        disabled={!state.member || busyKey === f.key}
                        onChange={() => toggle(f)}
                        className="size-5 accent-[var(--accent)]"
                      />
                      On for me
                    </label>
                    {state.eligible && (
                      <Button variant="secondary" onPress={() => setFeedbackFor(f)} className="min-h-11 md:min-h-9">
                        {f.feedback ? 'Edit feedback' : 'Give feedback'}
                      </Button>
                    )}
                  </div>
                </div>
                {f.feedback && (
                  <p className="mt-2 text-sm text-muted">
                    Your rating: {f.feedback.rating} out of 5
                  </p>
                )}
              </Panel>
            </li>
          ))}
        </ul>
      )}

      {state.member && (
        <Panel title="Beta program">
          <BetaMembership state={state} onChange={setState} showLink={false} />
        </Panel>
      )}

      {feedbackFor && (
        <FeedbackDialog
          feature={feedbackFor}
          onClose={() => setFeedbackFor(null)}
          onSaved={(s) => {
            setState(s);
            setFeedbackFor(null);
          }}
        />
      )}
    </Page>
  );
}

function FeedbackDialog({
  feature,
  onClose,
  onSaved,
}: {
  feature: BetaFeature;
  onClose: () => void;
  onSaved: (s: BetaState) => void;
}) {
  const [rating, setRating] = useState<number>(feature.feedback?.rating ?? 0);
  const [comment, setComment] = useState<string>(feature.feedback?.comment ?? '');
  const [saving, setSaving] = useState(false);
  const editing = !!feature.feedback;

  async function save() {
    setSaving(true);
    try {
      onSaved(await sendBetaFeedback(feature.key, rating, comment));
      toast.success('Thanks for your feedback.');
    } catch (e) {
      if (e instanceof ApiError && e.status === 429) toast.danger("You've sent a lot of feedback. Try again in a while.");
      else if (e instanceof ApiError && (e.status === 403 || e.status === 404 || e.status === 422)) toast.danger(e.message);
      else toast.danger("Your feedback didn't save. Try again.");
    } finally {
      setSaving(false);
    }
  }

  return (
    <Modal.Backdrop isOpen onOpenChange={(open) => !open && !saving && onClose()} isDismissable>
      <Modal.Container placement="center" size="md">
        <Modal.Dialog aria-labelledby="beta-feedback-title">
          <Modal.Header>
            <Modal.Heading id="beta-feedback-title" className="text-lg font-semibold">
              What do you think of {feature.name}?
            </Modal.Heading>
          </Modal.Header>
          <Modal.Body className="space-y-4">
            <fieldset>
              <legend className="mb-2 text-sm font-semibold text-foreground">Your rating</legend>
              <div role="radiogroup" aria-label="Your rating" className="flex gap-1">
                {[1, 2, 3, 4, 5].map((n) => (
                  <label
                    key={n}
                    className="flex size-11 cursor-pointer items-center justify-center rounded-lg text-xl text-warning has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-accent"
                  >
                    <input
                      type="radio"
                      name="beta-rating"
                      value={n}
                      checked={rating === n}
                      onChange={() => setRating(n)}
                      className="sr-only"
                    />
                    <Icon name="fa-star" style={n <= rating ? 'fa-solid' : 'fa-regular'} />
                    <span className="sr-only">{n === 1 ? '1 (poor)' : n === 5 ? '5 (great)' : String(n)}</span>
                  </label>
                ))}
              </div>
            </fieldset>
            <label className="block">
              <span className="mb-1 block text-sm font-semibold text-foreground">Anything to add? (optional, 500 characters)</span>
              <textarea
                value={comment}
                onChange={(e) => setComment(e.target.value.slice(0, COMMENT_MAX))}
                maxLength={COMMENT_MAX}
                rows={4}
                className="w-full rounded-lg border border-border bg-surface-secondary p-3 text-base text-foreground outline-none focus:border-accent md:text-sm"
              />
              <span className="mt-1 block text-right text-xs tabular-nums text-muted">
                {comment.length}/{COMMENT_MAX}
              </span>
            </label>
          </Modal.Body>
          <Modal.Footer className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <Button variant="tertiary" onPress={onClose} isDisabled={saving} className="min-h-11 md:min-h-9">
              Cancel
            </Button>
            <Button variant="primary" onPress={save} isDisabled={saving || rating < 1} className="min-h-11 gap-2 md:min-h-9">
              {saving && <Spinner size="sm" />} {editing ? 'Save changes' : 'Send feedback'}
            </Button>
          </Modal.Footer>
        </Modal.Dialog>
      </Modal.Container>
    </Modal.Backdrop>
  );
}
