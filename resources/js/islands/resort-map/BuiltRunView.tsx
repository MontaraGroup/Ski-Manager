import { useState } from 'react';
import { AlertDialog, Button, Chip, Label, Meter, Spinner } from '@heroui/react';
import { Icon } from '@/components/Icon';
import type { BuiltItem, EconomyV2Config, MapSegment, SegmentTraffic } from './types';
import { LIFT_TYPES, SLOPE_TYPES, TrailSign, formatCurrency, runKind } from './shared';
import { LiftSwapDialog, type LiftSwapOption } from './LiftSwapDialog';

const STATUS: Record<string, { label: string; color: 'success' | 'default' | 'danger' | 'accent' }> = {
  open: { label: 'Open', color: 'success' },
  closed: { label: 'Closed', color: 'default' },
  broken: { label: 'Broken', color: 'danger' },
  building: { label: 'Being built', color: 'accent' },
};

function formatSnowDepth(cm: number, isImperial: boolean): string {
  if (isImperial) {
    return `${Math.round(cm * 0.393701)} in`;
  }
  return `${Math.round(cm)} cm`;
}

function conditionColor(pct: number): 'success' | 'warning' | 'danger' {
  if (pct >= 70) return 'success';
  if (pct >= 40) return 'warning';
  return 'danger';
}

function loadColor(pct: number): 'success' | 'warning' | 'danger' {
  if (pct >= 85) return 'danger';
  if (pct >= 60) return 'warning';
  return 'success';
}

/** A run the player owns: live telemetry, condition, upgrades/maintenance links, swapping a lift, and demolishing */
export function BuiltRunView({
  seg,
  item,
  isImperial,
  distUnit,
  sectorLabel,
  economyV2,
  traffic,
  onZoom,
  onDemolish,
  cash,
  onSwap,
}: {
  seg: MapSegment;
  item: BuiltItem;
  isImperial: boolean;
  distUnit: string;
  sectorLabel: string;
  economyV2: EconomyV2Config | null;
  traffic?: SegmentTraffic | null;
  onZoom: () => void;
  onDemolish: (done: (ok: boolean) => void) => void;
  cash: number;
  onSwap: (option: LiftSwapOption, done: (ok: boolean) => void) => void;
}) {
  const [confirming, setConfirming] = useState(false);
  const [swapping, setSwapping] = useState(false);
  const [working, setWorking] = useState(false);
  const status = STATUS[item.status || 'open'] ?? { label: item.status || 'Open', color: 'default' as const };
  const len = Number(item.length_meters ?? seg.length_meters) || 0;
  const isLift = seg.type === 'lift';
  const system = isLift
    ? LIFT_TYPES.find((l) => l.id === item.subtype)?.name
    : SLOPE_TYPES.find((s) => s.id === item.subtype)?.name;
  const runCost = economyV2
    ? isLift
      ? len * (economyV2.liftRun[item.subtype || 'chair_fixed'] || 0.8)
      : len * economyV2.slopeRun
    : null;
  const salvage = Number(item.salvage || 0);
  const name = seg.name || (isLift ? 'this lift' : 'this slope');
  const hasCondition = typeof item.condition_pct === 'number';
  const condPct = hasCondition ? Math.max(0, Math.min(100, Math.round(item.condition_pct!))) : 0;
  const snowDepth = typeof item.snow_depth === 'number' ? item.snow_depth : null;
  const madeSnow = typeof item.made_snow_cm === 'number' ? item.made_snow_cm : 0;
  const snowTarget = typeof item.snow_target_cm === 'number' ? item.snow_target_cm : 0;
  const snowmakersTotal = item.snowmakers_count ?? 0;
  const snowmakersActive = item.snowmakers_active ?? 0;

  function demolish() {
    setWorking(true);
    onDemolish((ok) => {
      setWorking(false);
      if (ok) setConfirming(false);
    });
  }

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center gap-2">
        <TrailSign seg={seg} />
        <span className="text-sm text-muted">{runKind(seg)}</span>
        <Chip size="sm" color={status.color} variant="soft">
          {status.label}
        </Chip>
        {!isLift && item.night_lit && (
          <Chip size="sm" color="accent" variant="soft">
            Night lit
          </Chip>
        )}
        {!isLift && item.terrain_park && (
          <Chip size="sm" color="success" variant="soft">
            Park
          </Chip>
        )}
        {isLift && item.scenic && (
          <Chip size="sm" color="accent" variant="soft">
            Scenic
          </Chip>
        )}
        <Button
          size="sm"
          variant="ghost"
          onPress={onZoom}
          className="ml-auto min-h-11 gap-1.5 text-sm text-muted hover:text-foreground md:min-h-8"
        >
          <Icon name="fa-location-crosshairs" /> Show on map
        </Button>
      </div>

      {(traffic || hasCondition) && (
        <div className="space-y-3 rounded-lg bg-surface-secondary p-3 text-sm">
          {traffic && (
            isLift ? (
              <div className="space-y-1">
                <Meter value={Math.min(100, Math.max(0, Math.round(traffic.loadPct)))} color={loadColor(traffic.loadPct)}>
                  <Label>Live lift load</Label>
                  <Meter.Output />
                  <Meter.Track>
                    <Meter.Fill />
                  </Meter.Track>
                </Meter>
                <p className="tabular-nums text-xs text-muted">
                  {traffic.skiers.toLocaleString()} riding now · {traffic.queue.toLocaleString()} in queue
                </p>
              </div>
            ) : (
              <div className="flex items-center justify-between">
                <span className="text-muted">Live on run</span>
                <span className="tabular-nums font-semibold text-foreground">
                  {traffic.skiers.toLocaleString()} {traffic.skiers === 1 ? 'skier' : 'skiers'}
                  {traffic.loadPct > 0 ? ` · ${traffic.loadPct}% busy` : ''}
                </span>
              </div>
            )
          )}
          {hasCondition && (
            <Meter value={condPct} color={conditionColor(condPct)}>
              <Label>Condition</Label>
              <Meter.Output />
              <Meter.Track>
                <Meter.Fill />
              </Meter.Track>
            </Meter>
          )}
        </div>
      )}

      <dl className="grid grid-cols-2 gap-x-3 gap-y-2 rounded-lg bg-surface-secondary p-3 text-sm">
        <div>
          <dt className="text-muted">Length</dt>
          <dd className="tabular-nums font-semibold text-foreground">
            {Math.round(len).toLocaleString()} {distUnit}
          </dd>
        </div>
        {system && (
          <div>
            <dt className="text-muted">{isLift ? 'System' : 'Kind'}</dt>
            <dd className="font-semibold text-foreground">
              {system}
              {isLift && item.seats ? ` (${item.seats}-seat)` : ''}
            </dd>
          </div>
        )}
        {isLift && Number(item.capacity) > 0 && (
          <div>
            <dt className="text-muted">Capacity</dt>
            <dd className="tabular-nums font-semibold text-foreground">{Number(item.capacity).toLocaleString()}/hr</dd>
          </div>
        )}
        {isLift && item.mechanic_name !== undefined && (
          <div>
            <dt className="text-muted">Mechanic</dt>
            <dd className="truncate font-semibold text-foreground">{item.mechanic_name || 'Unassigned'}</dd>
          </div>
        )}
        {isLift && item.scenic && (
          <div>
            <dt className="text-muted">Scenic rides</dt>
            <dd className="tabular-nums font-semibold text-foreground">
              {formatCurrency(item.scenic.revenue_per_day, isImperial)}/day ({formatCurrency(item.scenic.ticket_price, isImperial)}/ticket)
            </dd>
          </div>
        )}
        {!isLift && (item.snow_quality || snowDepth !== null) && (
          <div>
            <dt className="text-muted">Snow</dt>
            <dd className="font-semibold text-foreground">
              {snowDepth !== null ? (
                <span className="tabular-nums">
                  {formatSnowDepth(snowDepth, isImperial)}
                  {item.snow_quality ? ` · ${item.snow_quality}` : ''}
                </span>
              ) : (
                <span className="capitalize">{item.snow_quality}</span>
              )}
            </dd>
          </div>
        )}
        {!isLift && (madeSnow > 0 || snowTarget > 0) && snowDepth !== null && (
          <div>
            <dt className="text-muted">Made snow</dt>
            <dd className="tabular-nums font-semibold text-foreground">
              +{formatSnowDepth(madeSnow, isImperial)}
              {snowTarget > 0 ? ` / ${formatSnowDepth(snowTarget, isImperial)} target` : ''}
            </dd>
          </div>
        )}
        {!isLift && typeof item.groomed_day === 'number' && (
          <div>
            <dt className="text-muted">Grooming</dt>
            <dd className="tabular-nums font-semibold text-foreground">
              {item.groomed_day > 0 ? `Day ${item.groomed_day}` : 'Not groomed'}
            </dd>
          </div>
        )}
        {!isLift && snowmakersTotal > 0 && (
          <div>
            <dt className="text-muted">Snow cannons</dt>
            <dd className="tabular-nums font-semibold text-foreground">
              {snowmakersActive}/{snowmakersTotal} active
            </dd>
          </div>
        )}
        {!isLift && item.terrain_park && (
          <div className="col-span-2">
            <dt className="text-muted">Terrain park</dt>
            <dd className="font-semibold text-foreground">
              {item.terrain_park.name} ({item.terrain_park.size} {item.terrain_park.park_type.replace('_', ' ')} · {Math.round(item.terrain_park.condition_pct)}% cond)
            </dd>
          </div>
        )}
        {runCost !== null && (
          <div>
            <dt className="text-muted">{isLift ? 'Running cost' : 'Upkeep'}</dt>
            <dd className="tabular-nums font-semibold text-foreground">{formatCurrency(runCost, isImperial)}/day</dd>
          </div>
        )}
        <div className="min-w-0">
          <dt className="text-muted">Sector</dt>
          <dd className="truncate font-semibold text-foreground">{sectorLabel}</dd>
        </div>
      </dl>

      <div className="flex flex-wrap gap-x-4 gap-y-1 text-sm">
        {!isLift && (
          <>
            <a href="/grooming" className="inline-flex min-h-11 items-center font-medium text-link hover:underline md:min-h-8">
              Grooming
            </a>
            <a href="/snowmaking" className="inline-flex min-h-11 items-center font-medium text-link hover:underline md:min-h-8">
              Snowmaking
            </a>
            {item.terrain_park && (
              <a href="/terrain-parks" className="inline-flex min-h-11 items-center font-medium text-link hover:underline md:min-h-8">
                Terrain parks
              </a>
            )}
            {item.night_lit && (
              <a href="/night-skiing" className="inline-flex min-h-11 items-center font-medium text-link hover:underline md:min-h-8">
                Night skiing
              </a>
            )}
          </>
        )}
        {isLift && (
          <a href="/scenic-lifts" className="inline-flex min-h-11 items-center font-medium text-link hover:underline md:min-h-8">
            Scenic lifts
          </a>
        )}
        <a href="/resort" className="inline-flex min-h-11 items-center font-medium text-link hover:underline md:min-h-8">
          Open or close on the Resort page
        </a>
      </div>

      {isLift && item.id && item.swaps?.length ? (
        <Button variant="secondary" onPress={() => setSwapping(true)} className="min-h-11 w-full gap-2 md:min-h-9">
          <Icon name="fa-arrows-rotate" /> Upgrade or replace lift
        </Button>
      ) : null}

      {item.id ? (
        <Button
          variant="danger-soft"
          onPress={() => setConfirming(true)}
          className="min-h-11 w-full gap-2 md:min-h-9"
        >
          <Icon name="fa-trash-can" /> Demolish · refund {formatCurrency(salvage, isImperial)}
        </Button>
      ) : null}

      {isLift && (
        <LiftSwapDialog
          lift={{ ...item, name }}
          isOpen={swapping}
          onOpenChange={setSwapping}
          cash={cash}
          isImperial={isImperial}
          onConfirm={onSwap}
        />
      )}

      <AlertDialog.Backdrop isOpen={confirming} onOpenChange={(open) => !working && setConfirming(open)}>
        <AlertDialog.Container>
          <AlertDialog.Dialog className="max-w-md">
            <AlertDialog.Header>
              <AlertDialog.Heading>Demolish {name}?</AlertDialog.Heading>
            </AlertDialog.Header>
            <AlertDialog.Body className="space-y-2 text-sm">
              <p>
                You get {formatCurrency(salvage, isImperial)} back, {isLift ? 'half' : 'a quarter'} of what it cost. This can't be undone.
              </p>
              {!isLift && <p className="text-muted">Snow cannons and terrain parks on it are kept, but no longer assigned.</p>}
            </AlertDialog.Body>
            <AlertDialog.Footer>
              <Button variant="tertiary" onPress={() => setConfirming(false)} isDisabled={working}>
                Keep it
              </Button>
              <Button variant="danger" onPress={demolish} isDisabled={working} className="gap-2">
                {working ? <Spinner size="sm" /> : <Icon name="fa-trash-can" />} Demolish
              </Button>
            </AlertDialog.Footer>
          </AlertDialog.Dialog>
        </AlertDialog.Container>
      </AlertDialog.Backdrop>
    </div>
  );
}
