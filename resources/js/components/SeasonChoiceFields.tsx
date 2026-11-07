import { useId } from 'react';
import { TextArea } from '@heroui/react';
import { COMMENT_MAX, SEASON_OPTIONS, type SeasonChoice } from '@/lib/seasonVote';

/** The two option cards and the comment field, shared by the popup and Settings */
export function SeasonChoiceFields({ order, choice, onChoice, comment, onComment, error }: {
  order: SeasonChoice[];
  choice: SeasonChoice | null;
  onChoice: (c: SeasonChoice) => void;
  comment: string;
  onComment: (c: string) => void;
  error?: string;
}) {
  const id = useId();
  return (
    <div className="flex flex-col gap-4">
      <fieldset className="flex flex-col gap-3">
        <legend className="sr-only">Season format</legend>
        {order.map((key) => {
          const opt = SEASON_OPTIONS[key];
          const selected = choice === key;
          return (
            <label
              key={key}
              className={`flex cursor-pointer gap-3 rounded-xl border p-4 transition-colors has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-focus ${selected ? 'border-accent bg-accent-soft' : 'border-border bg-surface hover:bg-default'}`}
            >
              <input
                type="radio"
                name={`${id}-season`}
                value={key}
                checked={selected}
                onChange={() => onChoice(key)}
                className="mt-1 size-5 shrink-0 accent-[var(--accent)]"
              />
              <span className="flex flex-col gap-1">
                <span className="font-semibold">{opt.title}</span>
                <span className="text-sm leading-relaxed text-muted">{opt.body}</span>
              </span>
            </label>
          );
        })}
      </fieldset>
      {error && <p role="alert" className="text-sm text-danger">{error}</p>}
      <div className="flex flex-col gap-1">
        <label htmlFor={`${id}-comment`} className="text-sm font-medium">
          Anything you'd like us to know? (optional, {COMMENT_MAX} characters)
        </label>
        <TextArea
          id={`${id}-comment`}
          value={comment}
          onChange={(e) => onComment(e.target.value)}
          maxLength={COMMENT_MAX}
          rows={3}
          fullWidth
          aria-describedby={`${id}-count`}
        />
        <span id={`${id}-count`} className="self-end text-xs text-muted">{comment.length} / {COMMENT_MAX}</span>
      </div>
    </div>
  );
}
