import type { ReactNode } from 'react';
import { Button } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Switch } from '@/components/Switch';

const FILTERS: { id: string; label: string; adminOnly?: boolean }[] = [
  { id: 'all', label: 'All' },
  { id: 'lift', label: 'Lifts' },
  { id: 'slope', label: 'Slopes' },
  { id: 'built', label: 'Built' },
  { id: 'unassigned', label: 'Unassigned', adminOnly: true },
];

/**
 * Title row: the player's own resort (what they built and their cash), the map filters, and Build.
 * Admin-only controls arrive through `adminActions` so this stays the same for everyone else.
 */
export function MapHeader({
  mapName,
  builtSlopes,
  builtLifts,
  cashText,
  filter,
  onFilter,
  showAvailable,
  onShowAvailable,
  onFit,
  onBuild,
  isAdmin,
  adminActions,
}: {
  mapName: string;
  builtSlopes: number;
  builtLifts: number;
  cashText: string;
  filter: string;
  onFilter: (f: string) => void;
  showAvailable: boolean;
  onShowAvailable: (v: boolean) => void;
  onFit: () => void;
  onBuild: () => void;
  isAdmin: boolean;
  adminActions?: ReactNode;
}) {
  return (
    <header className="border-b border-border bg-surface px-4 py-2">
      <div className="flex items-center justify-between gap-2">
        <div className="flex min-w-0 items-center gap-3">
          <a
            href="/resort"
            className="flex size-11 shrink-0 items-center justify-center rounded-lg border border-border text-foreground hover:bg-surface-secondary md:size-9"
            aria-label="Back to resort"
          >
            <Icon name="fa-arrow-left" />
          </a>
          <div className="min-w-0">
            <h1 className="truncate text-lg font-semibold text-foreground">{mapName}</h1>
            <p className="truncate text-sm text-muted">
              <span className="tabular-nums font-semibold text-foreground">{builtSlopes}</span>{' '}
              {builtSlopes === 1 ? 'slope' : 'slopes'} ·{' '}
              <span className="tabular-nums font-semibold text-foreground">{builtLifts}</span>{' '}
              {builtLifts === 1 ? 'lift' : 'lifts'} built
              {/* The stats bar right above already shows cash on a phone */}
              <span className="hidden sm:inline">
                {' · '}
                <span className="tabular-nums font-semibold text-foreground">{cashText}</span>
              </span>
            </p>
          </div>
        </div>
        <div className="flex shrink-0 items-center gap-2">
          {adminActions}
          <Button variant="primary" onPress={onBuild} className="min-h-11 gap-1.5 font-semibold md:min-h-9">
            <Icon name="fa-hammer" />
            Build
          </Button>
        </div>
      </div>

      {/* Filters scroll sideways on a phone rather than hiding */}
      <div className="-mx-4 mt-2 flex items-center gap-2 overflow-x-auto px-4 pb-0.5 [scrollbar-width:none]">
        <div role="group" aria-label="Show on the map" className="flex shrink-0 rounded-lg border border-border bg-surface-secondary p-0.5">
          {FILTERS.filter((f) => !f.adminOnly || isAdmin).map((f) => (
            <Button
              key={f.id}
              size="sm"
              variant={filter === f.id ? 'primary' : 'ghost'}
              aria-pressed={filter === f.id}
              onPress={() => onFilter(f.id)}
              className="min-h-10 md:min-h-8"
            >
              {f.label}
            </Button>
          ))}
        </div>
        {!isAdmin && (
          <Switch
            isSelected={showAvailable}
            onChange={onShowAvailable}
            size="sm"
            className="flex min-h-10 shrink-0 items-center gap-2 rounded-lg px-2 text-sm text-foreground md:min-h-8"
          >
            Show runs you can build
          </Switch>
        )}
        <Button
          size="sm"
          variant="outline"
          isIconOnly
          onPress={onFit}
          className="ml-auto size-10 shrink-0 text-foreground md:size-8"
          aria-label="Show the whole mountain (F)"
        >
          <Icon name="fa-expand" />
        </Button>
      </div>
    </header>
  );
}
