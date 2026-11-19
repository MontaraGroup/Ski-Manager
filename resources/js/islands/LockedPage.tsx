import React from 'react';
import { Icon } from '@/components/Icon';

interface UnlockData {
  label?: string;
  name?: string;
  desc?: string;
  icon?: string;
  progress?: number;
  target?: number;
}

interface LockedPageProps {
  unlock?: UnlockData;
}

export default function LockedPage({ unlock }: LockedPageProps) {
  const label = unlock?.label || 'Feature';
  const name = unlock?.name || 'Achievement';
  const desc = unlock?.desc || '';
  const icon = unlock?.icon || 'fa-solid fa-trophy';
  const progress = Number(unlock?.progress ?? 0);
  const target = Math.max(1, Number(unlock?.target ?? 1));
  const percent = Math.min(100, Math.round((progress / target) * 100));
  // Features unlock when the reward is claimed, not when the goal is reached (isFeatureUnlocked)
  const done = progress >= target;

  return (
    <div className="mx-auto max-w-lg p-4 sm:p-6 lg:p-8">
      <div className="rounded-2xl border border-border bg-card p-6 sm:p-10 text-center shadow-xl">
        <div className="mx-auto mb-4 flex size-20 items-center justify-center rounded-full bg-warning/10 text-3xl text-warning">
          <Icon name="fa-solid fa-lock" />
        </div>

        <h1 className="text-2xl font-bold tracking-tight text-foreground">{label} Locked</h1>
        <p className="mt-2 text-sm text-muted">
          {done ? 'Achievement complete. Claim its reward to unlock this feature.' : 'Complete an achievement to unlock this feature.'}
        </p>

        <div className="mx-auto mt-6 max-w-xs rounded-xl border border-border/80 bg-default/40 p-4 text-left">
          <div className="flex items-center gap-3">
            <div className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-warning/10 text-warning">
              <Icon name={icon} />
            </div>
            <div className="min-w-0 flex-1">
              <div className="truncate text-sm font-semibold text-foreground">{name}</div>
              {desc ? <div className="text-xs text-muted">{desc}</div> : null}
            </div>
          </div>

          <div className="mt-3 flex items-center gap-2">
            <div className="h-2 flex-1 overflow-hidden rounded-full bg-default">
              <div
                className="h-full rounded-full bg-warning transition-all"
                style={{ width: `${percent}%` }}
                role="progressbar"
                aria-valuenow={progress}
                aria-valuemin={0}
                aria-valuemax={target}
              />
            </div>
            <span className="font-mono text-xs text-muted">
              {progress}/{target}
            </span>
          </div>
        </div>

        <div className="mt-8">
          <a
            href="/achievements"
            className="inline-flex min-h-11 sm:min-h-9 items-center justify-center gap-2 rounded-xl bg-warning px-5 py-2.5 text-sm font-semibold text-warning-foreground hover:bg-warning/90"
          >
            <Icon name="fa-solid fa-trophy" />
            <span>{done ? 'Claim reward' : 'View Achievements'}</span>
          </a>
        </div>
      </div>
    </div>
  );
}
