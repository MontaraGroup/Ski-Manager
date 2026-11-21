import type { ReactNode } from 'react';
import { Button, Chip, Popover } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Switch } from '@/components/Switch';
import type { LiveSkiersSnapshot, MapLens, MapViewMode, MapWeather } from './types';

const FILTERS: { id: string; label: string; adminOnly?: boolean; requires3d?: boolean }[] = [
  { id: 'all', label: 'All' },
  { id: 'lift', label: 'Lifts' },
  { id: 'slope', label: 'Slopes' },
  { id: 'built', label: 'Built' },
  { id: 'buildings', label: 'Buildings', requires3d: true },
  { id: 'unassigned', label: 'Unassigned', adminOnly: true },
];

const LENSES: { id: MapLens; label: string }[] = [
  { id: 'trails', label: 'Trails' },
  { id: 'traffic', label: 'Traffic' },
  { id: 'snow', label: 'Snow' },
  { id: 'steepness', label: 'Steepness' },
];

const PHASE_LABELS: Record<string, string> = {
  morning: 'Morning',
  peak: 'Peak hours',
  lunch: 'Lunch rush',
  afternoon: 'Afternoon',
  night_skiing: 'Night skiing',
  evening: 'Evening',
  closed: 'Closed',
};

/**
 * Title row: the player's own resort (built runs, buildings, live skier counter, cash), map filters, lens/layers, and Build.
 * Admin-only controls arrive through `adminActions` (and Map Studio quick switch in 3D mode).
 */
export function MapHeader({
  mapName,
  builtSlopes,
  builtLifts,
  builtBuildingsCount = 0,
  cashText,
  filter,
  onFilter,
  showAvailable,
  onShowAvailable,
  onFit,
  onBuild,
  isAdmin,
  adminActions,
  map3dEnabled = false,
  viewMode = '2d',
  onViewMode,
  webglSupported = true,
  lens = 'trails',
  onLens,
  showBuildings = true,
  onShowBuildings,
  showSkiers = true,
  onShowSkiers,
  liveSkiers = null,
  weather = null,
  onOpenMapStudio,
}: {
  mapName: string;
  builtSlopes: number;
  builtLifts: number;
  builtBuildingsCount?: number;
  cashText: string;
  filter: string;
  onFilter: (f: string) => void;
  showAvailable: boolean;
  onShowAvailable: (v: boolean) => void;
  onFit: () => void;
  onBuild: () => void;
  isAdmin: boolean;
  adminActions?: ReactNode;
  map3dEnabled?: boolean;
  viewMode?: MapViewMode;
  onViewMode?: (m: MapViewMode) => void;
  webglSupported?: boolean;
  lens?: MapLens;
  onLens?: (l: MapLens) => void;
  showBuildings?: boolean;
  onShowBuildings?: (v: boolean) => void;
  showSkiers?: boolean;
  onShowSkiers?: (v: boolean) => void;
  liveSkiers?: LiveSkiersSnapshot | null;
  weather?: MapWeather | null;
  onOpenMapStudio?: () => void;
}) {
  const phaseLabel = liveSkiers ? PHASE_LABELS[liveSkiers.operatingPhase] || 'Open' : 'Closed';

  return (
    <header className="border-b border-border bg-surface px-4 py-2">
      <div className="flex flex-wrap items-center justify-between gap-2">
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
              {map3dEnabled && builtBuildingsCount > 0 && (
                <>
                  {' · '}
                  <span className="tabular-nums font-semibold text-foreground">{builtBuildingsCount}</span>{' '}
                  {builtBuildingsCount === 1 ? 'facility' : 'facilities'}
                </>
              )}
              {/* The stats bar right above already shows cash on a phone */}
              <span className="hidden sm:inline">
                {' · '}
                <span className="tabular-nums font-semibold text-foreground">{cashText}</span>
              </span>
            </p>
          </div>
        </div>

        <div className="flex shrink-0 flex-wrap items-center gap-2">
          {map3dEnabled && liveSkiers && (
            <Popover>
              <Button
                size="sm"
                variant="outline"
                className="min-h-11 gap-1.5 px-2.5 tabular-nums font-semibold text-foreground md:min-h-9"
                aria-label={`${liveSkiers.liveTotal.toLocaleString()} skiers currently in the resort`}
              >
                <Icon name="fa-person-skiing" className="text-link" />
                <span>{liveSkiers.liveTotal.toLocaleString()}</span>
                <span className="hidden text-xs font-normal text-muted lg:inline">live</span>
              </Button>
              <Popover.Content placement="bottom end" className="w-[min(22rem,calc(100vw-1rem))]">
                <Popover.Dialog aria-label="Live skiers breakdown" className="flex flex-col gap-3 p-3.5 text-sm">
                  <div className="flex items-center justify-between gap-2">
                    <Popover.Heading className="text-sm font-semibold text-foreground">
                      Live skiers in resort
                    </Popover.Heading>
                    <Chip
                      size="sm"
                      color={liveSkiers.resortOpen ? (liveSkiers.nightLit ? 'accent' : 'success') : 'default'}
                      variant="soft"
                    >
                      {liveSkiers.resortOpen ? phaseLabel : 'Closed'}
                    </Chip>
                  </div>

                  {!liveSkiers.resortOpen && (
                    <p className="rounded-lg bg-surface-secondary px-2.5 py-2 text-xs text-muted">
                      Your resort is currently closed. Reopen it on{' '}
                      <a href="/resort" className="font-medium text-link hover:underline">
                        Resort
                      </a>{' '}
                      to welcome skiers.
                    </p>
                  )}

                  <dl className="grid grid-cols-2 gap-2.5 rounded-lg bg-surface-secondary p-2.5">
                    <div>
                      <dt className="text-xs text-muted">On slopes</dt>
                      <dd className="tabular-nums font-semibold text-foreground">
                        {liveSkiers.onSlopes.toLocaleString()}
                      </dd>
                    </div>
                    <div>
                      <dt className="text-xs text-muted">On lifts</dt>
                      <dd className="tabular-nums font-semibold text-foreground">
                        {liveSkiers.onLifts.toLocaleString()}
                      </dd>
                    </div>
                    <div>
                      <dt className="text-xs text-muted">In village</dt>
                      <dd className="tabular-nums font-semibold text-foreground">
                        {liveSkiers.inVillage.toLocaleString()}
                      </dd>
                    </div>
                    <div>
                      <dt className="text-xs text-muted">Today's estimate</dt>
                      <dd className="tabular-nums font-semibold text-foreground">
                        {liveSkiers.dailyProjected.toLocaleString()}
                      </dd>
                    </div>
                    <div>
                      <dt className="text-xs text-muted">Lift load</dt>
                      <dd className="tabular-nums font-semibold text-foreground">
                        {liveSkiers.liftUtilizationPct}%
                      </dd>
                    </div>
                    {weather && (
                      <div>
                        <dt className="text-xs text-muted">Weather</dt>
                        <dd className="truncate font-semibold text-foreground">
                          {weather.tempFormatted} · {weather.condition_name}
                        </dd>
                      </div>
                    )}
                  </dl>

                  <p className="text-xs text-muted">
                    Syncs with Economy demand and weather every 30s and whenever you build or modify runs.
                  </p>
                </Popover.Dialog>
              </Popover.Content>
            </Popover>
          )}

          {map3dEnabled && onViewMode && (
            <div
              role="group"
              aria-label="Map view mode"
              className="flex shrink-0 rounded-lg border border-border bg-surface-secondary p-0.5"
            >
              <Button
                size="sm"
                variant={viewMode === '3d' ? 'primary' : 'ghost'}
                aria-pressed={viewMode === '3d'}
                isDisabled={!webglSupported}
                onPress={() => onViewMode('3d')}
                className="min-h-10 px-2.5 text-xs font-semibold md:min-h-8"
              >
                3D
              </Button>
              <Button
                size="sm"
                variant={viewMode === '2d' ? 'primary' : 'ghost'}
                aria-pressed={viewMode === '2d'}
                onPress={() => onViewMode('2d')}
                className="min-h-10 px-2.5 text-xs font-semibold md:min-h-8"
              >
                2D
              </Button>
            </div>
          )}

          {isAdmin && map3dEnabled && viewMode === '3d' && onOpenMapStudio && (
            <Button
              size="sm"
              variant="outline"
              onPress={onOpenMapStudio}
              className="min-h-11 gap-1.5 md:min-h-9"
            >
              <Icon name="fa-pen-ruler" />
              <span className="hidden sm:inline">Map Studio (2D)</span>
            </Button>
          )}

          {adminActions}

          <Button variant="primary" onPress={onBuild} className="min-h-11 gap-1.5 font-semibold md:min-h-9">
            <Icon name="fa-hammer" />
            Build
          </Button>
        </div>
      </div>

      {/* Filters, data lenses, and layers wrap cleanly on phones */}
      <div className="mt-2 flex flex-wrap items-center gap-2 pb-0.5">
        <div role="group" aria-label="Show on the map" className="flex flex-wrap shrink-0 rounded-lg border border-border bg-surface-secondary p-0.5">
          {FILTERS.filter((f) => (!f.adminOnly || isAdmin) && (!f.requires3d || map3dEnabled)).map((f) => (
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

        {map3dEnabled && onLens && (
          <div role="group" aria-label="Data lens" className="flex flex-wrap shrink-0 rounded-lg border border-border bg-surface-secondary p-0.5">
            {LENSES.map((l) => (
              <Button
                key={l.id}
                size="sm"
                variant={lens === l.id ? 'primary' : 'ghost'}
                aria-pressed={lens === l.id}
                onPress={() => onLens(l.id)}
                className="min-h-10 md:min-h-8"
              >
                {l.label}
              </Button>
            ))}
          </div>
        )}

        {map3dEnabled && onShowBuildings && onShowSkiers && (
          <div role="group" aria-label="Map layers" className="flex shrink-0 rounded-lg border border-border bg-surface-secondary p-0.5">
            <Button
              size="sm"
              variant={showBuildings ? 'secondary' : 'ghost'}
              aria-pressed={showBuildings}
              onPress={() => onShowBuildings(!showBuildings)}
              className="min-h-10 gap-1.5 md:min-h-8"
            >
              <Icon name="fa-building" />
              <span className="hidden sm:inline">Buildings</span>
            </Button>
            <Button
              size="sm"
              variant={showSkiers ? 'secondary' : 'ghost'}
              aria-pressed={showSkiers}
              onPress={() => onShowSkiers(!showSkiers)}
              className="min-h-10 gap-1.5 md:min-h-8"
            >
              <Icon name="fa-person-skiing" />
              <span className="hidden sm:inline">Skiers</span>
            </Button>
          </div>
        )}

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
