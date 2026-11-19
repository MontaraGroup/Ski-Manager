import { useId, useState } from 'react';
import { Button, Description, Label, Radio, RadioGroup, TextArea, TextField } from '@heroui/react';
import { Icon } from './Icon';
import { COMMENT_MAX, SEASON_OPTIONS, type SeasonChoice } from '@/lib/seasonVote';

/**
 * The two option cards and the comment field, shared by the popup and Settings.
 * compact: tighter cards side by side from sm up, and the comment field behind a button, so the popup fits without scrolling.
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
      <RadioGroup
        value={choice}
        onChange={(v) => onChoice(v as SeasonChoice)}
        isDisabled={locked}
        aria-describedby={locked ? `${id}-locked` : undefined}
        className={compact ? 'gap-2 sm:grid sm:grid-cols-2 sm:gap-3' : 'gap-3'}
      >
        <Label className="sr-only">Season format</Label>
        {order.map((key) => {
          const opt = SEASON_OPTIONS[key];
          return (
            <Radio key={key} value={key}>
              <Radio.Content
                className={`flex w-full items-start gap-3 rounded-xl border border-border bg-surface transition-colors data-[selected]:border-accent data-[selected]:bg-accent-soft data-[focus-visible]:outline-2 data-[focus-visible]:outline-offset-2 data-[focus-visible]:outline-focus ${compact ? 'p-3' : 'p-4'} ${locked ? 'cursor-default' : 'cursor-pointer hover:bg-default data-[selected]:hover:bg-accent-soft'}`}
              >
                <Radio.Control className="mt-0.5 size-5">
                  <Radio.Indicator />
                </Radio.Control>
                <span className="flex flex-col gap-1">
                  <span className="text-base font-semibold">{opt.title}</span>
                  <span className={`text-sm font-normal text-muted ${compact ? 'leading-snug' : 'leading-relaxed'}`}>{opt.body}</span>
                </span>
              </Radio.Content>
            </Radio>
          );
        })}
      </RadioGroup>
      {locked && <p id={`${id}-locked`} className="sr-only">Scroll down to read both options before you vote.</p>}
      {error && <p role="alert" className="text-sm text-danger">{error}</p>}
      {showComment ? (
        <TextField value={comment} onChange={onComment} maxLength={COMMENT_MAX} fullWidth autoFocus={compact}>
          <Label>Anything you'd like us to know? (optional, {COMMENT_MAX} characters)</Label>
          <TextArea rows={compact ? 2 : 3} />
          <Description className="self-end text-xs">{comment.length} / {COMMENT_MAX}</Description>
        </TextField>
      ) : (
        <Button variant="ghost" onPress={() => setShowComment(true)} className="min-h-11 self-start px-1 text-accent-soft-foreground">
          <Icon name="fa-comment" />
          Add a comment (optional)
        </Button>
      )}
    </div>
  );
}
