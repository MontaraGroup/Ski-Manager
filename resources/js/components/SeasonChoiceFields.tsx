import { useId, useState } from 'react';
import { TextArea } from '@heroui/react';
import { Icon } from './Icon';
import { COMMENT_MAX, SEASON_OPTIONS, type SeasonChoice } from '@/lib/seasonVote';

/**
 * The two option cards and the comment field, shared by the popup and Settings.
 * compact: tighter cards and the comment field behind a button, so the popup fits a phone screen.
 * locked: the options can't be picked yet (the popup unlocks them once both have been scrolled past).
 */
export function SeasonChoiceFields({ order, choice, onChoice, comment, onComment, error, compact = false, locked = false }: {
  order: SeasonChoice[];
  choice: SeasonChoice | null;
  onChoice: (c: SeasonChoice) => void;
  comment: string;
  onComment: (c: string) => void;
  error?: string;
  compact?: boolean;
  locked?: boolean;
}) {
  const id = useId();
  const [showComment, setShowComment] = useState(!compact || comment !== '');
  return (
    <div className={`flex flex-col ${compact ? 'gap-3' : 'gap-4'}`}>
      <fieldset className={`flex flex-col ${compact ? 'gap-2' : 'gap-3'}`} disabled={locked} aria-describedby={locked ? `${id}-locked` : undefined}>
        <legend className="sr-only">Season format</legend>
        {order.map((key) => {
          const opt = SEASON_OPTIONS[key];
          const selected = choice === key;
          return (
            <label
              key={key}
              className={`flex gap-3 rounded-xl border transition-colors has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-focus ${compact ? 'p-3' : 'p-4'} ${locked ? 'cursor-default' : 'cursor-pointer'} ${selected ? 'border-accent bg-accent-soft' : `border-border bg-surface ${locked ? '' : 'hover:bg-default'}`}`}
            >
              <input
                type="radio"
                name={`${id}-season`}
                value={key}
                checked={selected}
                onChange={() => onChoice(key)}
                className="mt-0.5 size-5 shrink-0 accent-[var(--accent)] disabled:opacity-50"
              />
              <span className="flex flex-col gap-1">
                <span className="font-semibold">{opt.title}</span>
                <span className={`text-sm text-muted ${compact ? 'leading-snug' : 'leading-relaxed'}`}>{opt.body}</span>
              </span>
            </label>
          );
        })}
      </fieldset>
      {locked && <p id={`${id}-locked`} className="sr-only">Scroll down to read both options before you vote.</p>}
      {error && <p role="alert" className="text-sm text-danger">{error}</p>}
      {showComment ? (
        <div className="flex flex-col gap-1">
          <label htmlFor={`${id}-comment`} className="text-sm font-medium">
            Anything you'd like us to know? (optional, {COMMENT_MAX} characters)
          </label>
          <TextArea
            id={`${id}-comment`}
            value={comment}
            onChange={(e) => onComment(e.target.value)}
            maxLength={COMMENT_MAX}
            rows={compact ? 2 : 3}
            fullWidth
            autoFocus={compact}
            aria-describedby={`${id}-count`}
          />
          <span id={`${id}-count`} className="self-end text-xs text-muted">{comment.length} / {COMMENT_MAX}</span>
        </div>
      ) : (
        <button
          type="button"
          onClick={() => setShowComment(true)}
          className="flex min-h-11 items-center gap-2 self-start rounded-lg px-1 text-sm font-medium text-accent-soft-foreground hover:underline focus-visible:outline-2 focus-visible:outline-focus"
        >
          <Icon name="fa-comment" />
          Add a comment (optional)
        </button>
      )}
    </div>
  );
}
