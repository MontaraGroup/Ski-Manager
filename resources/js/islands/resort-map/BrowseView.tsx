import { Icon } from '@/components/Icon';
import type { BuildCosts, MapSegment } from './types';
import { TrailSign, cheapestCost, formatCurrency, runKind } from './shared';

export type SortOrder = 'cost_asc' | 'cost_desc' | 'len_desc' | 'name_asc';

export interface Suggestion {
  seg: MapSegment;
  reason: string;
}

/** What the panel says after a build: what was built and the one next step */
export interface BuildNotice {
  name: string;
  next: 'lift' | 'slope' | 'tickets';
}

function RunRow({
  seg,
  costs,
  cash,
  isImperial,
  distUnit,
  note,
  onSelect,
}: {
  seg: MapSegment;
  costs: BuildCosts;
  cash: number;
  isImperial: boolean;
  distUnit: string;
  note?: string;
  onSelect: (seg: MapSegment) => void;
}) {
  const price = cheapestCost(seg, costs);
  const len = Math.round(Number(seg.length_meters) || 0);
  return (
    <li>
      <button
        type="button"
        onClick={() => onSelect(seg)}
        className="flex min-h-14 w-full items-center gap-3 rounded-lg border border-border px-3 py-2 text-left transition-colors hover:bg-surface-secondary"
      >
        <span className="flex size-7 shrink-0 items-center justify-center">
          <TrailSign seg={seg} />
        </span>
        <span className="min-w-0 flex-1">
          <span className="block truncate text-sm font-semibold text-foreground">{seg.name || 'Unnamed run'}</span>
          <span className="block truncate text-sm text-muted">
            {note ?? runKind(seg)} · <span className="tabular-nums">{len.toLocaleString()} {distUnit}</span>
          </span>
        </span>
        <span className={`shrink-0 text-right text-sm font-semibold tabular-nums ${cash >= price ? 'text-foreground' : 'text-danger'}`}>
          {seg.type === 'lift' && <span className="block text-xs font-normal text-muted">from</span>}
          {formatCurrency(price, isImperial)}
        </span>
      </button>
    </li>
  );
}

/** No run selected: first-build help, then every run the player can build, searchable */
export function BrowseView({
  tab,
  onTab,
  liftCount,
  slopeCount,
  items,
  suggestions,
  suggestionTitle,
  notice,
  settle,
  costs,
  cash,
  isImperial,
  distUnit,
  search,
  onSearch,
  grade,
  onGrade,
  sort,
  onSort,
  onSelect,
}: {
  tab: 'lift' | 'slope';
  onTab: (t: 'lift' | 'slope') => void;
  liftCount: number;
  slopeCount: number;
  items: MapSegment[];
  suggestions: Suggestion[];
  suggestionTitle: string | null;
  notice: BuildNotice | null;
  settle: string;
  costs: BuildCosts;
  cash: number;
  isImperial: boolean;
  distUnit: string;
  search: string;
  onSearch: (q: string) => void;
  grade: string;
  onGrade: (g: string) => void;
  sort: SortOrder;
  onSort: (s: SortOrder) => void;
  onSelect: (seg: MapSegment) => void;
}) {
  const row = { costs, cash, isImperial, distUnit, onSelect };

  return (
    <div className="space-y-4">
      {notice && (
        <div role="status" className="rounded-lg border border-success/40 bg-success/10 p-3 text-sm">
          <p className="flex items-center gap-2 font-semibold text-foreground">
            <Icon name="fa-circle-check" className="text-success" /> Built {notice.name}.
          </p>
          <p className="mt-1 text-muted">
            {notice.next === 'lift' && 'Next: add a lift so skiers can reach it.'}
            {notice.next === 'slope' && 'Next: add a slope for this lift to serve.'}
            {notice.next === 'tickets' && `Your resort settles tonight at ${settle}. Set your ticket prices before then.`}
          </p>
          {notice.next === 'tickets' && (
            <a
              href="/tickets"
              className="mt-2 inline-flex min-h-11 items-center gap-1.5 font-semibold text-link hover:underline md:min-h-8"
            >
              Set ticket prices <Icon name="fa-arrow-right" />
            </a>
          )}
        </div>
      )}

      {suggestionTitle && suggestions.length > 0 && (
        <section aria-labelledby="map-suggest-title">
          <h3 id="map-suggest-title" className="mb-1 text-sm font-semibold text-foreground">
            {suggestionTitle}
          </h3>
          <p className="mb-2 text-sm text-muted">
            {tab === 'slope'
              ? 'Slopes bring skiers. These are cheap, gentle runs to start with. Pick one to see the price.'
              : 'Skiers need a lift to reach your slope. These end close to one you built.'}
          </p>
          <ul className="flex flex-col gap-1.5">
            {suggestions.map((s) => (
              <RunRow key={s.seg.id} seg={s.seg} note={s.reason} {...row} />
            ))}
          </ul>
        </section>
      )}

      <section aria-labelledby="map-list-title" className="space-y-2">
        <h3 id="map-list-title" className="sr-only">
          Runs you can build
        </h3>
        <div role="group" aria-label="Kind of run" className="grid grid-cols-2 gap-1 rounded-lg border border-border bg-surface-secondary p-1">
          {(['slope', 'lift'] as const).map((t) => (
            <button
              key={t}
              type="button"
              aria-pressed={tab === t}
              onClick={() => onTab(t)}
              className={`flex min-h-11 items-center justify-center gap-2 rounded-md text-sm font-semibold transition-colors md:min-h-9 ${
                tab === t ? 'bg-surface text-foreground shadow-sm' : 'text-muted hover:text-foreground'
              }`}
            >
              <Icon name={t === 'lift' ? 'fa-elevator' : 'fa-person-skiing'} />
              {t === 'lift' ? 'Lifts' : 'Slopes'}
              <span className="tabular-nums text-muted">{t === 'lift' ? liftCount : slopeCount}</span>
            </button>
          ))}
        </div>

        <label className="relative block">
          <span className="sr-only">Search runs by name</span>
          <Icon name="fa-magnifying-glass" className="absolute top-1/2 left-3 -translate-y-1/2 text-sm text-muted" />
          <input
            type="search"
            value={search}
            onChange={(e) => onSearch(e.target.value)}
            placeholder="Search by name"
            className="h-11 w-full rounded-lg border border-border bg-surface-secondary pr-3 pl-9 text-base text-foreground outline-none focus:border-accent md:h-9 md:text-sm"
          />
        </label>

        <div className="flex gap-2">
          {tab === 'slope' && (
            <label className="flex-1">
              <span className="sr-only">Difficulty</span>
              <select
                value={grade}
                onChange={(e) => onGrade(e.target.value)}
                className="h-11 w-full rounded-lg border border-border bg-surface-secondary px-2 text-base text-foreground outline-none md:h-9 md:text-sm"
              >
                <option value="">All difficulties</option>
                <option value="green">Easiest</option>
                <option value="blue">More difficult</option>
                <option value="black">Most difficult</option>
                <option value="double_black">Experts only</option>
              </select>
            </label>
          )}
          <label className="flex-1">
            <span className="sr-only">Sort</span>
            <select
              value={sort}
              onChange={(e) => onSort(e.target.value as SortOrder)}
              className="h-11 w-full rounded-lg border border-border bg-surface-secondary px-2 text-base text-foreground outline-none md:h-9 md:text-sm"
            >
              <option value="cost_asc">Cheapest first</option>
              <option value="cost_desc">Most expensive first</option>
              <option value="len_desc">Longest first</option>
              <option value="name_asc">Name, A to Z</option>
            </select>
          </label>
        </div>

        <p className="px-1 text-sm text-muted">
          {items.length} {tab === 'lift' ? (items.length === 1 ? 'lift' : 'lifts') : items.length === 1 ? 'slope' : 'slopes'} you can build.
          Pick one here or on the map.
        </p>

        {items.length === 0 ? (
          <div className="rounded-lg border border-dashed border-border p-6 text-center text-sm">
            <p className="font-semibold text-foreground">No {tab === 'lift' ? 'lifts' : 'slopes'} match.</p>
            <p className="mt-1 text-muted">Clear the search or the difficulty to see them all.</p>
          </div>
        ) : (
          <ul className="flex flex-col gap-1.5">
            {items.map((seg) => (
              <RunRow key={seg.id} seg={seg} {...row} />
            ))}
          </ul>
        )}
      </section>
    </div>
  );
}
