import { useState, useEffect } from 'react';
import { Button } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput, Page, Panel } from '@/components/Page';

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

export default function DailyBonusPage({
  streak = 0,
  bestStreak = 0,
  totalClaimedFormatted = '€0',
  claimedToday = false,
  lostStreak = 0,
  secondsToReset = 86400,
  next,
  currentWeek = 1,
  weekMultiplier = 1.0,
  days = [],
  cycleAmounts = [],
  weekBonusPct = 25,
  maxMultiplier = 2.0,
  weeklyGenepis = 5,
}: DailyBonusPageProps) {
  const [timeLeft, setTimeLeft] = useState(secondsToReset);

  useEffect(() => {
    if (timeLeft <= 0) return;
    const interval = setInterval(() => {
      setTimeLeft((prev) => {
        if (prev <= 1) {
          window.location.reload();
          return 0;
        }
        return prev - 1;
      });
    }, 1000);
    return () => clearInterval(interval);
  }, [timeLeft]);

  const hours = Math.floor(timeLeft / 3600);
  const minutes = Math.floor((timeLeft % 3600) / 60);
  const seconds = timeLeft % 60;
  const timeFormatted = `${hours}h ${minutes}m ${seconds}s`;

  return (
    <Page
      title="Daily Bonus"
      icon="fa-fire"
      iconTone="text-warning"
      back="/dashboard"
      width="max-w-3xl"
      subtitle="Claim once a day. Consecutive days grow your streak and boost reward multipliers"
    >
      <div className="space-y-6">
        {/* Main Claim Card */}
        <div
          className={`rounded-2xl border p-6 text-center transition-all ${
            !claimedToday
              ? 'border-warning/60 bg-gradient-to-b from-warning/15 via-base-100 to-base-100 shadow-md ring-1 ring-warning/30'
              : 'border-border bg-base-100 shadow-sm'
          }`}
        >
          {claimedToday ? (
            <div className="space-y-3">
              <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-success/15 text-success text-xs font-semibold">
                <Icon name="fa-circle-check" /> Claimed for today
              </div>
              <div className="text-xs text-muted">Tomorrow's bonus:</div>
              <div className="text-3xl sm:text-4xl font-extrabold text-foreground tabular-nums">
                {next?.cashFormatted || '€0'}
                {next?.genepis ? (
                  <span className="text-lg sm:text-xl font-bold text-success ml-2">
                    + {next.genepis} <Icon name="fa-seedling" className="text-sm" /> Génépis
                  </span>
                ) : null}
              </div>
              <p className="text-xs text-muted max-w-md mx-auto pt-1">
                Unlocks at midnight server time, in{' '}
                <span className="font-semibold text-foreground tabular-nums">{timeFormatted}</span>.
                Claim before tomorrow ends to preserve your streak.
              </p>
            </div>
          ) : (
            <div className="space-y-4">
              {lostStreak > 0 && (
                <div className="flex items-center gap-2 rounded-lg border border-warning/40 bg-warning/10 p-3 text-xs text-foreground text-left max-w-lg mx-auto">
                  <Icon name="fa-heart-crack" className="text-warning text-base shrink-0" />
                  <div>
                    You missed a day, so your {lostStreak}-day streak ended. Today starts a fresh one!
                  </div>
                </div>
              )}

              <div>
                <div className="text-xs font-semibold uppercase tracking-wider text-muted">
                  Day {streak + 1} Bonus Ready
                </div>
                <div className="text-4xl sm:text-5xl font-black text-warning mt-1 tabular-nums">
                  {next?.cashFormatted || '€0'}
                  {next?.genepis ? (
                    <span className="text-xl sm:text-2xl font-bold text-success ml-2 inline-flex items-center gap-1">
                      + {next.genepis} <Icon name="fa-seedling" className="text-sm" />
                    </span>
                  ) : null}
                </div>

                {next?.multPct > 0 && (
                  <div className="text-xs font-semibold text-success mt-1.5">
                    Includes +{next.multPct}% bonus for week {next.week} streak!
                  </div>
                )}
              </div>

              <form action="/daily-bonus/claim" method="post" className="pt-2">
                <CsrfInput />
                <Button
                  type="submit"
                  variant="primary"
                  className="min-h-[48px] px-8 text-base font-bold bg-warning hover:bg-warning/90 text-warning-foreground border-none shadow-md inline-flex items-center gap-2"
                >
                  <Icon name="fa-gift" /> Claim Day {streak + 1} Reward
                </Button>
              </form>

              <div className="text-xs text-muted">
                Available until midnight, in{' '}
                <span className="font-semibold text-foreground tabular-nums">{timeFormatted}</span>
              </div>
            </div>
          )}
        </div>

        {/* 3 Stats Grid */}
        <div className="grid grid-cols-3 gap-3">
          <div className="rounded-xl border border-border bg-base-100 p-4 text-center">
            <div className="text-xs text-muted mb-1 flex items-center justify-center gap-1">
              <Icon name="fa-fire" className="text-warning text-xs" />
              Current streak
            </div>
            <div className="text-2xl sm:text-3xl font-extrabold text-warning tabular-nums">
              {streak}{' '}
              <span className="text-xs sm:text-sm font-normal text-muted">
                {streak === 1 ? 'day' : 'days'}
              </span>
            </div>
          </div>

          <div className="rounded-xl border border-border bg-base-100 p-4 text-center">
            <div className="text-xs text-muted mb-1 flex items-center justify-center gap-1">
              <Icon name="fa-trophy" className="text-primary text-xs" />
              Best streak
            </div>
            <div className="text-2xl sm:text-3xl font-extrabold text-foreground tabular-nums">
              {bestStreak}{' '}
              <span className="text-xs sm:text-sm font-normal text-muted">days</span>
            </div>
          </div>

          <div className="rounded-xl border border-border bg-base-100 p-4 text-center">
            <div className="text-xs text-muted mb-1 flex items-center justify-center gap-1">
              <Icon name="fa-sack-dollar" className="text-success text-xs" />
              Total claimed
            </div>
            <div className="text-xl sm:text-2xl font-extrabold text-success tabular-nums truncate">
              {totalClaimedFormatted}
            </div>
          </div>
        </div>

        {/* Streak Schedule / This Week */}
        <Panel
          title={
            <div className="flex items-center justify-between w-full">
              <span className="flex items-center gap-2">
                <Icon name="fa-calendar-week" className="text-primary" />
                Week {currentWeek} Schedule
              </span>
              <span className="text-xs font-normal text-muted">
                {weekMultiplier > 1 ? `Rewards ×${weekMultiplier.toFixed(2)}` : 'Base rewards'}
              </span>
            </div>
          }
        >
          <div className="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-2">
            {days.map((d) => {
              const isDone = d.done;
              const isNext = d.next;

              return (
                <div
                  key={d.streak}
                  className={`rounded-xl border p-2.5 text-center flex flex-col justify-between transition-all ${
                    isDone
                      ? 'bg-warning/15 border-warning/40'
                      : isNext
                      ? 'border-primary bg-primary/10 ring-1 ring-primary/30 shadow-sm'
                      : 'border-border/60 bg-base-200/40'
                  }`}
                >
                  <div className="text-xs font-semibold text-muted">Day {d.streak}</div>

                  <div className="my-2">
                    <Icon
                      name={
                        isDone
                          ? 'fa-circle-check'
                          : d.genepis > 0
                          ? 'fa-seedling'
                          : 'fa-gift'
                      }
                      className={`text-base ${
                        isDone
                          ? 'text-warning'
                          : d.genepis > 0
                          ? 'text-success'
                          : isNext
                          ? 'text-primary'
                          : 'text-muted/40'
                      }`}
                    />
                  </div>

                  <div>
                    <div className="text-xs font-bold text-foreground tabular-nums">
                      {d.cashFormatted}
                    </div>
                    {d.genepis > 0 && (
                      <div className="text-xs font-semibold text-success tabular-nums mt-0.5">
                        +{d.genepis} Génépis
                      </div>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        </Panel>

        {/* Streak Mechanics Guide */}
        <details className="rounded-xl border border-border bg-base-100 shadow-sm overflow-hidden group">
          <summary className="p-4 cursor-pointer font-semibold text-xs text-foreground flex items-center justify-between hover:bg-base-200/50 transition-colors">
            <span className="flex items-center gap-2">
              <Icon name="fa-circle-info" className="text-primary" />
              How the daily streak works
            </span>
            <Icon
              name="fa-chevron-down"
              className="text-muted text-xs transition-transform group-open:rotate-180"
            />
          </summary>
          <div className="px-5 pb-5 pt-1 text-xs text-muted border-t border-border/40 space-y-2">
            <div className="flex items-start gap-2 pt-2">
              <Icon name="fa-clock" className="text-primary text-xs mt-0.5 shrink-0" />
              <span>One claim per day; days reset at midnight server time.</span>
            </div>
            <div className="flex items-start gap-2">
              <Icon name="fa-bolt" className="text-warning text-xs mt-0.5 shrink-0" />
              <span>
                Claim consecutive days to build streak momentum. Missing a day resets the active
                streak back to day 1.
              </span>
            </div>
            <div className="flex items-start gap-2">
              <Icon name="fa-chart-line" className="text-success text-xs mt-0.5 shrink-0" />
              <span>
                Base 7-day cycle: {cycleAmounts.join(', ')}. Each full week of streak adds +
                {weekBonusPct}% up to ×{maxMultiplier} max multiplier.
              </span>
            </div>
            <div className="flex items-start gap-2">
              <Icon name="fa-seedling" className="text-success text-xs mt-0.5 shrink-0" />
              <span>
                Day 7 of every week rewards +{weeklyGenepis} Génépis. Consecutive streaks of 7 and 30
                days also unlock achievements.
              </span>
            </div>
          </div>
        </details>
      </div>
    </Page>
  );
}
