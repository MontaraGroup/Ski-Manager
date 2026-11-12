import { useEffect, useRef, useState, type ReactNode } from 'react';
import { Icon } from '@/components/Icon';
import type { Stats } from '@/lib/shell';

type ZoneParts = { year: number; month: number; day: number; hour: number; minute: number };

/** The wall clock in a zone, as numbers */
function zoneParts(ms: number, timeZone: string): ZoneParts {
  const parts = new Intl.DateTimeFormat('en-US', {
    timeZone,
    hourCycle: 'h23',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  }).formatToParts(new Date(ms));
  const value = (type: string) => Number(parts.find((part) => part.type === type)?.value ?? 0);
  return { year: value('year'), month: value('month'), day: value('day'), hour: value('hour'), minute: value('minute') };
}

/** Offset of a zone at an instant, in milliseconds east of UTC */
function zoneOffset(ms: number, timeZone: string): number {
  const p = zoneParts(ms, timeZone);
  return Date.UTC(p.year, p.month - 1, p.day, p.hour, p.minute) - Math.floor(ms / 60_000) * 60_000;
}

/** HH:MM, 24-hour, in the resort's zone */
function resortTime(ms: number, timeZone: string): string {
  return new Intl.DateTimeFormat('en-GB', { timeZone, hour: '2-digit', minute: '2-digit', hourCycle: 'h23' }).format(new Date(ms));
}

/** The next 00:05 in the resort's zone, as a UTC instant: that is when a game day turns over */
function nextDayStart(ms: number, timeZone: string): number {
  const p = zoneParts(ms, timeZone);
  let wall = Date.UTC(p.year, p.month - 1, p.day, 0, 5);
  let instant = wall - zoneOffset(wall, timeZone);
  // Once more with the offset at the candidate: the zone may be on the other side of a DST change
  instant = wall - zoneOffset(instant, timeZone);
  if (instant <= ms) {
    wall += 86_400_000;
    instant = wall - zoneOffset(wall, timeZone);
    instant = wall - zoneOffset(instant, timeZone);
  }
  return instant;
}

function plural(count: number, unit: string): string {
  return `${count} ${unit}${count === 1 ? '' : 's'}`;
}

/** Countdown to the next day, short for the bar and spelled out for screen readers */
function countdown(ms: number): { short: string; long: string } {
  const totalMinutes = Math.max(0, Math.ceil(ms / 60_000));
  const hours = Math.floor(totalMinutes / 60);
  const minutes = totalMinutes % 60;
  return {
    short: hours > 0 ? `${hours}h ${minutes}m` : `${minutes}m`,
    long: hours > 0 ? `${plural(hours, 'hour')} ${plural(minutes, 'minute')}` : plural(minutes, 'minute'),
  };
}

/** Server time plus time elapsed on the page, so a wrong device clock can't move the resort clock */
function useResortNow(serverNowMs: number): number {
  const [now, setNow] = useState(serverNowMs);
  const startedAt = useRef(0);

  useEffect(() => {
    startedAt.current = performance.now();
    const sync = () => setNow(serverNowMs + (performance.now() - startedAt.current));
    sync();
    let interval = 0;
    // Land on the next minute of resort time, then tick once a minute
    const timeout = window.setTimeout(() => {
      sync();
      interval = window.setInterval(sync, 60_000);
    }, 60_000 - (serverNowMs % 60_000));
    return () => {
      window.clearTimeout(timeout);
      window.clearInterval(interval);
    };
  }, [serverNowMs]);

  return now;
}

/** The resort at a glance, under the navbar. Every figure is the player's own, from the server */
export default function StatsBar(stats: Stats) {
  const now = useResortNow(stats.now * 1000);
  const time = resortTime(now, stats.timezone);
  const next = countdown(nextDayStart(now, stats.timezone) - now);
  const clockLabel = `Resort time ${time}, next day in ${next.long}`;

  return (
    <div className="border-b border-border bg-background">
      <ul aria-label="Resort status" className="mx-auto flex max-w-7xl items-center gap-1 overflow-x-auto px-2 py-1 text-sm [scrollbar-width:none] sm:px-4">
        <Stat href="/finances" label={`Cash ${stats.cash}. Open Finances`} icon="fa-money-bill-wave" tone="text-success">
          <span className="font-semibold tabular-nums">{stats.cash}</span>
        </Stat>
        <Stat href="/weather" label={`${stats.weather || 'Weather'}, ${stats.temp}. Open the forecast`} icon={stats.tempIcon} tone="text-link">
          <span className="font-semibold tabular-nums">{stats.temp}</span>
        </Stat>
        <Stat href="/snowmaking" label={`Snow base ${stats.snow}. Open Snowmaking`} icon="fa-layer-group" tone="text-link">
          <span className="tabular-nums">{stats.snow}</span>
        </Stat>
        <Stat label={`Season day ${stats.gameDay} of ${stats.seasonLength}`} icon="fa-calendar-day" tone="text-muted">
          <span className="tabular-nums">
            Day <b className="font-semibold">{stats.gameDay}</b>/{stats.seasonLength}
          </span>
        </Stat>
        <li aria-label={clockLabel}>
          <span
            title={clockLabel}
            aria-hidden="true"
            className="flex min-h-9 shrink-0 items-center gap-1.5 rounded-lg px-2 whitespace-nowrap pointer-coarse:min-h-11 text-muted"
          >
            <Icon name="fa-clock" className="text-muted" />
            <span className="font-semibold tabular-nums">{time}</span>
            <span className="hidden text-xs sm:inline">Next day in {next.short}</span>
          </span>
        </li>
        <Stat href="/genepis" label={`${stats.genepis.toLocaleString('en-US')} Génépis. Open the Génépis shop`} icon="fa-seedling" tone="text-success">
          <span className="font-semibold tabular-nums">{stats.genepis.toLocaleString('en-US')}</span>
        </Stat>
        <Stat href="/resort-analysis" label={`Resort rating ${stats.stars} of 5 stars. Open the analysis`} icon="fa-star" tone="text-warning">
          <span className="tabular-nums">{stats.stars}/5</span>
        </Stat>
      </ul>
    </div>
  );
}

function Stat({ href, label, icon, tone, children }: { href?: string; label: string; icon: string; tone: string; children: ReactNode }) {
  const body = (
    <>
      <Icon name={icon} className={tone} />
      {children}
    </>
  );
  const cls = 'flex min-h-9 shrink-0 items-center gap-1.5 rounded-lg px-2 whitespace-nowrap pointer-coarse:min-h-11';
  return (
    <li>
      {href ? (
        <a href={href} aria-label={label} title={label} className={`${cls} hover:bg-default`}>
          {body}
        </a>
      ) : (
        <span title={label} className={cls}>
          {body}
          <span className="sr-only">{label}</span>
        </span>
      )}
    </li>
  );
}
