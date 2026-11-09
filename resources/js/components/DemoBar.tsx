import { useState } from 'react';
import { Button, Popover } from '@heroui/react';
import { Icon } from './Icon';
import { PostButton } from './Page';
import type { DemoBarState } from '@/lib/shell';

const START_OVER = { title: 'Start over?', body: 'Your demo resort goes back to its starting setup. Your progress is lost.', confirmLabel: 'Start over', cancelLabel: 'Keep playing' };
const LEAVE = { title: 'Leave the demo?', body: "You can't come back to this resort unless you save it first.", confirmLabel: 'Leave demo', cancelLabel: 'Stay' };

function dismissed(key: string): boolean {
  try {
    return localStorage.getItem('demo_prompt_' + key) === '1';
  } catch {
    return false;
  }
}

/** Always says this is a demo and offers one way to keep it. Shows one save prompt at a time */
export default function DemoBar({ demo }: { demo: DemoBarState }) {
  const [hidden, setHidden] = useState<string[]>([]);
  const prompt = demo.prompts.find((p) => !hidden.includes(p.key) && !dismissed(p.key));

  const dismiss = (key: string) => {
    try {
      localStorage.setItem('demo_prompt_' + key, '1');
    } catch {
      // private mode: it stays hidden for this page only
    }
    setHidden((h) => [...h, key]);
  };

  return (
    <div className="border-b border-border bg-surface text-sm">
      <div className="mx-auto flex min-h-14 max-w-7xl items-center gap-2 px-3 sm:px-4">
        <p className="min-w-0 flex-1">
          <span className="font-semibold">Demo resort</span>
          <span className="hidden text-muted lg:inline">
            : {demo.cash}, {demo.lifts} lifts, {demo.runs} runs, {demo.buildings} buildings. Deleted after 7 days without play.
          </span>
        </p>
        <div className="hidden items-center gap-1 sm:flex">
          <PostButton action="/demo/reset" variant="ghost" size="sm" confirm={START_OVER}>Start over</PostButton>
          <PostButton action="/demo/exit" variant="ghost" size="sm" confirm={LEAVE}>Leave demo</PostButton>
        </div>
        <a href="/demo/save" className="inline-flex min-h-11 shrink-0 items-center justify-center rounded-lg bg-accent px-3 font-medium text-accent-foreground sm:min-h-9">
          Save your resort
        </a>
        <div className="sm:hidden">
          <Popover>
            <Button isIconOnly variant="ghost" aria-label="More demo options" className="size-11">
              <Icon name="fa-ellipsis-vertical" />
            </Button>
            <Popover.Content placement="bottom end" className="w-64">
              <Popover.Dialog aria-label="Demo options" className="flex flex-col gap-2 p-3">
                <p className="text-sm text-muted">
                  {demo.cash}, {demo.lifts} lifts, {demo.runs} runs, {demo.buildings} buildings. Deleted after 7 days without play.
                </p>
                <PostButton action="/demo/reset" variant="secondary" confirm={START_OVER}>Start over</PostButton>
                <PostButton action="/demo/exit" variant="secondary" confirm={LEAVE}>Leave demo</PostButton>
              </Popover.Dialog>
            </Popover.Content>
          </Popover>
        </div>
      </div>
      {prompt && (
        <div className="border-t border-border" role="status">
          <div className="mx-auto flex max-w-7xl flex-col gap-2 px-3 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-4">
            <p>
              <span className="font-semibold">{prompt.title}</span>{' '}
              <span className="text-muted">{prompt.body}</span>
            </p>
            <div className="flex shrink-0 items-center gap-2">
              <a href="/demo/save" className="inline-flex min-h-11 items-center justify-center rounded-lg bg-accent px-3 font-medium text-accent-foreground sm:min-h-9">
                Save your resort
              </a>
              <Button variant="ghost" className="min-h-11 sm:min-h-9" onPress={() => dismiss(prompt.key)}>Not now</Button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
