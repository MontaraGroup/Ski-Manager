import type { ReactNode } from 'react';
import { Icon } from '@/components/Icon';
import type { Stats } from '@/lib/shell';

/** The resort at a glance, under the navbar. Every figure is the player's own, from the server */
export default function StatsBar(stats: Stats) {
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
