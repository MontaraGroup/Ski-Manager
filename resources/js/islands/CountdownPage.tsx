import React, { useState, useEffect } from 'react';
import { Button } from '@heroui/react/button';
import { Icon } from '@/components/Icon';

interface CountdownPageProps {
  targetTimestamp?: number;
  seasonStart?: string;
}

interface TimeRemaining {
  days: number;
  hours: number;
  minutes: number;
  seconds: number;
  isPast: boolean;
}

function calculateTimeRemaining(targetTimestamp: number): TimeRemaining {
  const now = Math.floor(Date.now() / 1000);
  const diff = targetTimestamp - now;

  if (diff <= 0) {
    return { days: 0, hours: 0, minutes: 0, seconds: 0, isPast: true };
  }

  const days = Math.floor(diff / 86400);
  const hours = Math.floor((diff % 86400) / 3600);
  const minutes = Math.floor((diff % 3600) / 60);
  const seconds = diff % 60;

  return { days, hours, minutes, seconds, isPast: false };
}

export default function CountdownPage({
  targetTimestamp = 1793923200,
  seasonStart = '2026-11-01',
}: CountdownPageProps) {
  const [time, setTime] = useState<TimeRemaining>(() => calculateTimeRemaining(targetTimestamp));

  useEffect(() => {
    const timer = setInterval(() => {
      setTime(calculateTimeRemaining(targetTimestamp));
    }, 1000);
    return () => clearInterval(timer);
  }, [targetTimestamp]);

  return (
    <div className="relative isolate min-h-[calc(100vh-4rem)] flex items-center justify-center p-4 sm:p-6 lg:p-8 overflow-hidden">
      {/* Mountain Background Art */}
      <div
        className="absolute inset-0 -z-20 bg-parkcity-map bg-cover bg-center opacity-40 transform scale-105"
      />
      <div className="absolute inset-0 -z-10 bg-gradient-to-b from-background/90 via-background/80 to-background/95" />

      <div className="w-full max-w-4xl mx-auto text-center space-y-10 py-8">
        {/* Header Badge & Title */}
        <div className="space-y-4 max-w-2xl mx-auto">
          <div className="inline-flex items-center gap-2 rounded-full border border-primary/30 bg-primary/10 px-3.5 py-1 text-xs font-semibold text-primary">
            <Icon name="fa-solid fa-snowflake" className="text-[11px]" />
            <span>Season 1 Launch</span>
          </div>
          <h1 className="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-foreground leading-tight">
            The mountain is <br className="hidden sm:inline" />
            <span className="text-primary">getting ready.</span>
          </h1>
          <p className="text-sm sm:text-base text-muted max-w-xl mx-auto">
            Start with an empty mountain. Choose your difficulty, build lifts, hire staff, manage snowmaking layouts, and balance operations across a full season without going bankrupt.
          </p>
        </div>

        {/* Live Countdown Cards */}
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 max-w-xl mx-auto">
          <div className="rounded-2xl border border-border bg-card/90 backdrop-blur-sm p-4 sm:p-5 shadow-lg">
            <div className="font-mono tabular-nums text-4xl sm:text-5xl font-extrabold text-foreground">
              {String(time.days).padStart(2, '0')}
            </div>
            <div className="text-xs uppercase font-semibold text-muted mt-1 tracking-wider">
              Days
            </div>
          </div>
          <div className="rounded-2xl border border-border bg-card/90 backdrop-blur-sm p-4 sm:p-5 shadow-lg">
            <div className="font-mono tabular-nums text-4xl sm:text-5xl font-extrabold text-foreground">
              {String(time.hours).padStart(2, '0')}
            </div>
            <div className="text-xs uppercase font-semibold text-muted mt-1 tracking-wider">
              Hours
            </div>
          </div>
          <div className="rounded-2xl border border-border bg-card/90 backdrop-blur-sm p-4 sm:p-5 shadow-lg">
            <div className="font-mono tabular-nums text-4xl sm:text-5xl font-extrabold text-foreground">
              {String(time.minutes).padStart(2, '0')}
            </div>
            <div className="text-xs uppercase font-semibold text-muted mt-1 tracking-wider">
              Minutes
            </div>
          </div>
          <div className="rounded-2xl border border-border bg-card/90 backdrop-blur-sm p-4 sm:p-5 shadow-lg">
            <div className="font-mono tabular-nums text-4xl sm:text-5xl font-extrabold text-primary">
              {String(time.seconds).padStart(2, '0')}
            </div>
            <div className="text-xs uppercase font-semibold text-muted mt-1 tracking-wider">
              Seconds
            </div>
          </div>
        </div>

        {/* Feature Highlights Grid */}
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 max-w-3xl mx-auto text-left">
          <div className="rounded-xl border border-border/70 bg-card/60 backdrop-blur-sm p-4 space-y-1.5">
            <div className="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center text-sm font-bold mb-2">
              <Icon name="fa-solid fa-mountain-sun" />
            </div>
            <h3 className="text-sm font-bold text-foreground">Real alpine maps</h3>
            <p className="text-xs text-muted leading-relaxed">
              Design lifts and trails directly on Park City&apos;s real-world topography and elevation contours.
            </p>
          </div>
          <div className="rounded-xl border border-border/70 bg-card/60 backdrop-blur-sm p-4 space-y-1.5">
            <div className="w-8 h-8 rounded-lg bg-success/10 text-success flex items-center justify-center text-sm font-bold mb-2">
              <Icon name="fa-solid fa-coins" />
            </div>
            <h3 className="text-sm font-bold text-foreground">Dense economy</h3>
            <p className="text-xs text-muted leading-relaxed">
              Manage ticket tiers, guest lodging, mountain dining, payroll, equipment upkeep, and bank loans.
            </p>
          </div>
          <div className="rounded-xl border border-border/70 bg-card/60 backdrop-blur-sm p-4 space-y-1.5">
            <div className="w-8 h-8 rounded-lg bg-warning/10 text-warning flex items-center justify-center text-sm font-bold mb-2">
              <Icon name="fa-solid fa-cloud-showers-water" />
            </div>
            <h3 className="text-sm font-bold text-foreground">Live weather</h3>
            <p className="text-xs text-muted leading-relaxed">
              Nightly storms, temperature drops, wind holds, and natural snow base dictate your daily skier volume.
            </p>
          </div>
        </div>

        {/* Action Buttons */}
        <div className="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
          <a href="/login" className="w-full sm:w-auto no-underline">
            <Button
              variant="primary"
              className="w-full sm:w-auto font-semibold px-6 pointer-coarse:min-h-11 shadow-sm"
            >
              Sign in to resort
            </Button>
          </a>
          <a href="/register" className="w-full sm:w-auto no-underline">
            <Button
              variant="outline"
              className="w-full sm:w-auto font-medium px-6 pointer-coarse:min-h-11"
            >
              Create account
            </Button>
          </a>
        </div>
      </div>
    </div>
  );
}
