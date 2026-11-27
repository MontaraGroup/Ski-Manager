import { useEffect, useState } from 'react';
import { Alert, Button, Chip, Surface } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput, Page } from '@/components/Page';

export type BonusDay = {
  day: number;
  week: number;
  streak: number;
  multiplier: number;
  cash: number;
  cashFormatted: string;
  genepis: number;
  done: boolean;
  next: boolean;
};

export type DailyBonusPageProps = {
  streak: number;
  bestStreak: number;
  totalClaimed: number;
  totalClaimedFormatted: string;
  claimedToday: boolean;
  lostStreak: number;
  secondsToReset: number;
  next: {
    day: number;
    week: number;
    cash: number;
    cashFormatted: string;
    genepis: number;
    multPct: number;
  };
  currentWeek: number;
  weekMultiplier: number;
  days: BonusDay[];
  cycleAmounts: string[];
  weekBonusPct: number;
  maxMultiplier: number;
  weeklyGenepis: number;
};

const plural = (n: number, one: string, many: string) => `${n} ${n === 1 ? one : many}`;

/**
 * Time left until the server's midnight. Counts against a fixed deadline, so a throttled
 * background tab doesn't fall behind, and reloads the page once the new day starts.
 */
function useTimeLeft(seconds: number): number {
  const [deadline] = useState(() => Date.now() + seconds * 1000);
  const [left, setLeft] = useState(seconds);
  useEffect(() => {
    const tick = () => {
      const s = Math.max(0, Math.round((deadline - Date.now()) / 1000));
      setLeft(s);
      if (s === 0) window.location.reload();
    };
    const id = window.setInterval(tick, 15_000);
    return () => window.clearInterval(id);
  }, [deadline]);
  return left;
}

function formatLeft(s: number): string {
  if (s < 60) return 'under a minute';
  const h = Math.floor(s / 3600);
  const m = Math.floor((s % 3600) / 60);
  return h > 0 ? `${h} h ${m} min` : `${m} min`;
}

function Genepis({ amount }: { amount: number }) {
  return (
    <Chip size="sm" variant="soft" color="success" className="tabular-nums">
      <Icon name="fa-seedling" /> +{amount} Génépis
    </Chip>
  );
}

/** Weekly rate steps: week 1 pays the base table, each later week adds weekBonusPct, up to the cap */
function rateSteps(pct: number, max: number): number[] {
  const steps: number[] = [];
  for (let w = 0; steps.length < 12; w++) {
    const m = Math.min(max, 1 + (pct / 100) * w);
    steps.push(m);
    if (m >= max || pct <= 0) break;
  }
  return steps;
}

const rate = (m: number) => `×${Number(m.toFixed(2))}`;

export default function DailyBonusPage({
  streak = 0,
  bestStreak = 0,
  totalClaimedFormatted = '',
  claimedToday = false,
  lostStreak = 0,
  secondsToReset = 86400,
  next,
  currentWeek = 1,
  weekMultiplier = 1,
  days = [],
  cycleAmounts = [],
  weekBonusPct = 25,
  maxMultiplier = 2,
  weeklyGenepis = 5,
}: DailyBonusPageProps) {
  const left = formatLeft(useTimeLeft(secondsToReset));
  const [claiming, setClaiming] = useState(false);
  const nextDay = streak + 1;
  const base = next ? cycleAmounts[next.day - 1] : undefined;
  const doneCount = days.filter((d) => d.done).length;
  const steps = rateSteps(weekBonusPct, maxMultiplier);
  const stepIndex = Math.min(currentWeek, steps.length) - 1;

  return (
    <Page
      title="Daily bonus"
      icon="fa-gift"
      back="/dashboard"
      width="max-w-3xl"
      subtitle="One claim a day. Claim on back-to-back days to grow your streak and its rewards."
    >
      {lostStreak > 0 && !claimedToday && (
        <Alert status="warning">
          <Alert.Indicator />
          <Alert.Content>
            <Alert.Title>Your {lostStreak}-day streak ended</Alert.Title>
            <Alert.Description>You missed a day. Today's claim starts a new streak at day 1.</Alert.Description>
          </Alert.Content>
        </Alert>
      )}

      {/* Today: what the claim pays, or what tomorrow pays once it's claimed */}
      <Surface render={(props) => <section {...props} />} className="rounded-xl border border-border" aria-labelledby="bonus-today">
        <div className="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
          <div className="min-w-0">
            <h2 id="bonus-today" className="flex flex-wrap items-center gap-2 text-sm font-normal text-muted">
              {claimedToday ? (
                <>
                  <Chip size="sm" variant="soft" color="success">
                    <Icon name="fa-check" /> Claimed today
                  </Chip>
                  <span>Tomorrow, day {nextDay}</span>
                </>
              ) : (
                <span>{streak > 0 ? `Day ${nextDay} of your streak` : 'Day 1 of a new streak'}</span>
              )}
            </h2>
            <p className="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1">
              <span className={`font-bold tabular-nums ${claimedToday ? 'text-3xl text-muted' : 'text-4xl'}`}>{next?.cashFormatted}</span>
              {next?.genepis ? <Genepis amount={next.genepis} /> : null}
            </p>
            {next && next.multPct > 0 && base && (
              <p className="mt-1 text-sm text-muted">
                <span className="tabular-nums">{base}</span> base + {next.multPct}% for week {next.week}
              </p>
            )}
          </div>

          {!claimedToday && (
            <form action="/daily-bonus/claim" method="post" onSubmit={() => setClaiming(true)} className="shrink-0">
              <CsrfInput />
              <Button type="submit" variant="primary" size="lg" isPending={claiming} className="min-h-12 w-full sm:w-auto">
                {!claiming && <Icon name="fa-gift" />} Claim {next?.cashFormatted}
              </Button>
            </form>
          )}
        </div>

        <p className="border-t border-border px-5 py-3 text-sm text-muted">
          <Icon name="fa-clock" className="mr-1.5" />
          {claimedToday ? (
            <>
              Unlocks at midnight server time, in <strong className="font-semibold text-foreground tabular-nums">{left}</strong>. Skip a day and the streak restarts at day 1.
            </>
          ) : streak > 0 ? (
            <>
              Claim in the next <strong className="font-semibold text-foreground tabular-nums">{left}</strong> to keep your {streak}-day streak.
            </>
          ) : (
            <>
              Open until midnight server time, <strong className="font-semibold text-foreground tabular-nums">{left}</strong> left.
            </>
          )}
        </p>

        {/* Phones: the two streaks side by side, the total on its own row so long amounts fit */}
        <dl className="grid grid-cols-2 border-t border-border sm:grid-cols-3">
          {[
            { label: 'Streak', value: plural(streak, 'day', 'days'), className: 'border-r' },
            { label: 'Best streak', value: plural(bestStreak, 'day', 'days'), className: 'sm:border-r' },
            { label: 'Total claimed', value: totalClaimedFormatted, className: 'col-span-2 border-t sm:col-span-1 sm:border-t-0' },
          ].map((s) => (
            <div key={s.label} className={`min-w-0 border-border px-5 py-3 ${s.className}`}>
              <dt className="text-sm text-muted">{s.label}</dt>
              <dd className="text-xl font-semibold tabular-nums">{s.value}</dd>
            </div>
          ))}
        </dl>
      </Surface>

      {/* This week's seven days */}
      <section aria-labelledby="bonus-week" className="flex flex-col gap-3">
        <div className="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
          <h2 id="bonus-week" className="text-base font-semibold">
            Week {currentWeek}
          </h2>
          <p className="text-sm text-muted tabular-nums">
            {doneCount} of {days.length} claimed · {weekMultiplier > 1 ? `rewards ${rate(weekMultiplier)}` : 'base rewards'}
          </p>
        </div>
        <ol className="flex flex-col divide-y divide-border overflow-hidden rounded-xl border border-border bg-surface md:grid md:grid-cols-7 md:divide-x md:divide-y-0">
          {days.map((d) => {
            const today = d.next;
            const tomorrow = claimedToday && d.streak === nextDay;
            const state = d.done ? 'Claimed' : today ? 'Today' : tomorrow ? 'Tomorrow' : '';
            return (
              <li
                key={d.streak}
                aria-current={today ? 'step' : undefined}
                className={`flex items-center gap-3 px-4 py-3 md:flex-col md:items-stretch md:gap-1 md:px-2 md:text-center ${
                  today ? 'bg-accent/10 shadow-[inset_3px_0_0_var(--accent)] md:shadow-[inset_0_3px_0_var(--accent)]' : ''
                }`}
              >
                <span className="flex w-20 shrink-0 items-center gap-2 text-sm font-medium md:w-auto md:justify-center">
                  {d.done ? (
                    <Icon name="fa-circle-check" className="text-success" />
                  ) : (
                    <Icon name={d.genepis > 0 ? 'fa-seedling' : 'fa-gift'} className={today ? 'text-accent' : 'text-muted'} />
                  )}
                  Day {d.streak}
                </span>
                <span className="flex flex-1 flex-wrap items-baseline gap-x-2 md:flex-col md:items-center md:gap-0.5">
                  <span className={`font-semibold tabular-nums ${d.done ? 'text-muted' : ''}`}>{d.cashFormatted}</span>
                  {d.genepis > 0 && <span className="text-xs font-medium text-success">+{d.genepis} Génépis</span>}
                </span>
                <span className={`text-xs md:min-h-4 ${today ? 'font-semibold text-accent-soft-foreground' : 'text-muted'}`}>{state}</span>
              </li>
            );
          })}
        </ol>
      </section>

      {/* How the reward grows week by week */}
      <section aria-labelledby="bonus-rate" className="flex flex-col gap-3">
        <div>
          <h2 id="bonus-rate" className="text-base font-semibold">
            Weekly rate
          </h2>
          <p className="text-sm text-muted">
            Each full week of streak adds {weekBonusPct}% to every reward, up to {rate(maxMultiplier)}.
          </p>
        </div>
        <ol className="grid overflow-hidden rounded-xl border border-border" style={{ gridTemplateColumns: `repeat(${steps.length}, minmax(0, 1fr))` }}>
          {steps.map((m, i) => {
            const current = i === stepIndex;
            return (
              <li
                key={i}
                aria-current={current ? 'step' : undefined}
                className={`flex flex-col items-center gap-0.5 border-border px-1 py-2.5 text-center [&:not(:last-child)]:border-r ${
                  current ? 'bg-accent text-accent-foreground' : 'bg-surface'
                }`}
              >
                <span className={`text-xs ${current ? '' : 'text-muted'}`}>
                  Week {i + 1}
                  {i === steps.length - 1 ? '+' : ''}
                </span>
                <span className={`font-semibold tabular-nums ${i < stepIndex ? 'text-muted' : ''}`}>{rate(m)}</span>
              </li>
            );
          })}
        </ol>
      </section>

      <details className="group rounded-xl border border-border bg-surface">
        <summary className="flex min-h-11 cursor-pointer list-none items-center justify-between gap-2 px-4 py-3 text-sm font-semibold [&::-webkit-details-marker]:hidden">
          <span className="flex items-center gap-2">
            <Icon name="fa-circle-info" className="text-muted" />
            How the streak works
          </span>
          <Icon name="fa-chevron-down" className="text-muted transition-transform group-open:rotate-180 motion-reduce:transition-none" />
        </summary>
        <ul className="flex list-disc flex-col gap-2 border-t border-border py-3 pr-4 pl-9 text-sm text-muted">
          <li>One claim per day. The day turns over at midnight server time.</li>
          <li>Claim on back-to-back days to grow the streak. It has no cap. Miss a day and it restarts at day 1.</li>
          {cycleAmounts.length > 0 && (
            <li>
              Base rewards for days 1 to {cycleAmounts.length}: <span className="tabular-nums">{cycleAmounts.join(', ')}</span>.
            </li>
          )}
          <li>
            Day {cycleAmounts.length || 7} of every week also pays {weeklyGenepis} Génépis.
          </li>
          <li>Streaks of 7 and 30 days unlock achievements.</li>
        </ul>
      </details>
    </Page>
  );
}
