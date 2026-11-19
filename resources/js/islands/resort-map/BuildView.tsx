import { Button, Spinner } from '@heroui/react';
import { Icon } from '@/components/Icon';
import type { BuildCosts, EconomyV2Config, MapSegment } from './types';
import { LIFT_TYPES, SLOPE_TYPES, TrailSign, buildCost, formatCurrency, runKind } from './shared';

/** Choices for an unbuilt run: lift system and carrier size, or the slope's discipline */
export function BuildView({
  seg,
  costs,
  isImperial,
  distUnit,
  sectorLabel,
  liftType,
  onLiftType,
  seats,
  onSeats,
  slopeType,
  onSlopeType,
  onZoom,
}: {
  seg: MapSegment;
  costs: BuildCosts;
  isImperial: boolean;
  distUnit: string;
  sectorLabel: string;
  liftType: string;
  onLiftType: (t: string) => void;
  seats: number;
  onSeats: (n: number) => void;
  slopeType: string;
  onSlopeType: (t: string) => void;
  onZoom: () => void;
}) {
  const len = Number(seg.length_meters) || 0;
  const days = len < 200 ? 1 : len < 500 ? 2 : 3;

  return (
    <div className="space-y-4">
      <div className="flex items-center gap-2">
        <TrailSign seg={seg} />
        <span className="text-sm text-muted">{runKind(seg)}</span>
        <Button
          size="sm"
          variant="ghost"
          onPress={onZoom}
          className="ml-auto min-h-11 gap-1.5 text-sm text-muted hover:text-foreground md:min-h-8"
        >
          <Icon name="fa-location-crosshairs" /> Show on map
        </Button>
      </div>

      <dl className="grid grid-cols-3 gap-2 rounded-lg bg-surface-secondary p-3 text-sm">
        <div>
          <dt className="text-muted">Length</dt>
          <dd className="tabular-nums font-semibold text-foreground">
            {Math.round(len).toLocaleString()} {distUnit}
          </dd>
        </div>
        <div>
          <dt className="text-muted">Build time</dt>
          <dd className="font-semibold text-foreground">
            {days} {days === 1 ? 'day' : 'days'}
          </dd>
        </div>
        <div className="min-w-0">
          <dt className="text-muted">Sector</dt>
          <dd className="truncate font-semibold text-foreground">{sectorLabel}</dd>
        </div>
      </dl>

      {seg.type === 'lift' ? (
        <>
          <fieldset>
            <legend className="mb-2 text-sm font-semibold text-foreground">Lift system</legend>
            <div className="flex flex-col gap-1.5">
              {LIFT_TYPES.map((l) => {
                const selected = liftType === l.id;
                const firstSeats = costs.seatOptions[l.id]?.[0] ?? 2;
                return (
                  <Button
                    key={l.id}
                    type="button"
                    variant="ghost"
                    aria-pressed={selected}
                    onPress={() => onLiftType(l.id)}
                    className={`h-auto flex min-h-12 w-full items-center justify-between gap-3 rounded-lg border px-3 py-2 text-left text-sm ${
                      selected ? 'border-accent bg-accent/10' : 'border-border'
                    }`}
                  >
                    <span className="min-w-0">
                      <span className="block font-semibold text-foreground">{l.name}</span>
                      <span className="block text-muted">
                        from {formatCurrency(buildCost(seg, costs, l.id, firstSeats), isImperial)}
                      </span>
                    </span>
                    <span className="shrink-0 tabular-nums text-muted">{l.cap}</span>
                  </Button>
                );
              })}
            </div>
          </fieldset>

          <fieldset>
            <legend className="mb-2 text-sm font-semibold text-foreground">Carrier size</legend>
            <div className="flex gap-1.5">
              {(costs.seatOptions[liftType] || [2]).map((n) => (
                <Button
                  key={n}
                  type="button"
                  size="sm"
                  variant={seats === n ? 'primary' : 'ghost'}
                  aria-pressed={seats === n}
                  onPress={() => onSeats(n)}
                  className="min-h-11 flex-1 text-center text-sm font-semibold tabular-nums md:min-h-9"
                >
                  {n} {n === 1 ? 'seat' : liftType === 'gondola' || liftType === 'cable_car' ? 'people' : 'seats'}
                </Button>
              ))}
            </div>
          </fieldset>
        </>
      ) : (
        <fieldset>
          <legend className="mb-2 text-sm font-semibold text-foreground">Kind of run</legend>
          <div className="grid grid-cols-2 gap-1.5">
            {SLOPE_TYPES.map((d) => (
              <Button
                key={d.id}
                type="button"
                size="sm"
                variant={slopeType === d.id ? 'primary' : 'ghost'}
                aria-pressed={slopeType === d.id}
                onPress={() => onSlopeType(d.id)}
                className="flex min-h-11 items-center gap-2 px-3 text-sm font-medium md:min-h-9"
              >
                <Icon name={d.icon} />
                <span>{d.name}</span>
              </Button>
            ))}
          </div>
        </fieldset>
      )}
    </div>
  );
}

/** Price, what's left after, what it will do each day, and the Build button. Sits in the panel footer. */
export function BuildFooter({
  seg,
  cost,
  cash,
  isImperial,
  economyV2,
  liftType,
  seats,
  isBuilding,
  onBuild,
}: {
  seg: MapSegment;
  cost: number;
  cash: number;
  isImperial: boolean;
  economyV2: EconomyV2Config | null;
  liftType: string;
  seats: number;
  isBuilding: boolean;
  onBuild: () => void;
}) {
  const canAfford = cash >= cost;
  const m = Number(seg.length_meters) || 0;

  let perDay: string | null = null;
  if (economyV2) {
    if (seg.type === 'lift') {
      const cap = economyV2.capacity[liftType] || 2400;
      const maxS = economyV2.maxSeats[liftType] || 4;
      const daily = Math.round(Math.round(cap * Math.min(1, seats / maxS)) * economyV2.perDay);
      const run = m * (economyV2.liftRun[liftType] || 0.8);
      perDay = `Carries about ${daily.toLocaleString()} skiers a day and costs ${formatCurrency(run, isImperial)} a day to run.`;
    } else {
      perDay = `Draws about ${Math.round(30 + m / 40)} skiers a day and costs ${formatCurrency(m * economyV2.slopeRun, isImperial)} a day to keep up.`;
    }
  }

  return (
    <div className="space-y-2">
      {perDay && <p className="text-sm text-muted">{perDay}</p>}
      <p className={`text-sm tabular-nums ${canAfford ? 'text-muted' : 'text-danger'}`}>
        {canAfford
          ? `You'll have ${formatCurrency(cash - cost, isImperial)} left.`
          : `You need ${formatCurrency(cost - cash, isImperial)} more.`}
      </p>
      <Button
        variant="primary"
        isDisabled={!canAfford || isBuilding}
        onPress={onBuild}
        className="min-h-12 w-full gap-2 font-semibold"
      >
        {isBuilding ? (
          <>
            <Spinner size="sm" /> Building…
          </>
        ) : (
          <>
            <Icon name="fa-hammer" /> Build for {formatCurrency(cost, isImperial)}
          </>
        )}
      </Button>
    </div>
  );
}
