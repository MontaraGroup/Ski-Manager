import { useState } from 'react';
import { AlertDialog, Button, Chip, Spinner } from '@heroui/react';
import { Icon } from '@/components/Icon';
import type { BuiltItem, EconomyV2Config, MapSegment } from './types';
import { LIFT_TYPES, SLOPE_TYPES, TrailSign, formatCurrency, runKind } from './shared';

const STATUS: Record<string, { label: string; color: 'success' | 'default' | 'danger' | 'accent' }> = {
  open: { label: 'Open', color: 'success' },
  closed: { label: 'Closed', color: 'default' },
  broken: { label: 'Broken', color: 'danger' },
  building: { label: 'Being built', color: 'accent' },
};

/** A run the player owns: what it is, what it costs to run, and demolishing it */
export function BuiltRunView({
  seg,
  item,
  isImperial,
  distUnit,
  sectorLabel,
  economyV2,
  onZoom,
  onDemolish,
}: {
  seg: MapSegment;
  item: BuiltItem;
  isImperial: boolean;
  distUnit: string;
  sectorLabel: string;
  economyV2: EconomyV2Config | null;
  onZoom: () => void;
  onDemolish: (done: (ok: boolean) => void) => void;
}) {
  const [confirming, setConfirming] = useState(false);
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

  function demolish() {
    setWorking(true);
    onDemolish((ok) => {
      setWorking(false);
      if (ok) setConfirming(false);
    });
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center gap-2">
        <TrailSign seg={seg} />
        <span className="text-sm text-muted">{runKind(seg)}</span>
        <Chip size="sm" color={status.color} variant="soft">
          {status.label}
        </Chip>
        <Button
          size="sm"
          variant="ghost"
          onPress={onZoom}
          className="ml-auto min-h-11 gap-1.5 text-sm text-muted hover:text-foreground md:min-h-8"
        >
          <Icon name="fa-location-crosshairs" /> Show on map
        </Button>
      </div>

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
            <dd className="font-semibold text-foreground">{system}</dd>
          </div>
        )}
        {isLift && Number(item.capacity) > 0 && (
          <div>
            <dt className="text-muted">Capacity</dt>
            <dd className="tabular-nums font-semibold text-foreground">{Number(item.capacity).toLocaleString()}/hr</dd>
          </div>
        )}
        {!isLift && item.snow_quality && (
          <div>
            <dt className="text-muted">Snow</dt>
            <dd className="font-semibold capitalize text-foreground">{item.snow_quality}</dd>
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
          </>
        )}
        <a href="/resort" className="inline-flex min-h-11 items-center font-medium text-link hover:underline md:min-h-8">
          Open or close on the Resort page
        </a>
      </div>

      {item.id ? (
        <Button
          variant="danger-soft"
          onPress={() => setConfirming(true)}
          className="min-h-11 w-full gap-2 md:min-h-9"
        >
          <Icon name="fa-trash-can" /> Demolish · refund {formatCurrency(salvage, isImperial)}
        </Button>
      ) : null}

      <AlertDialog.Backdrop isOpen={confirming} onOpenChange={(open) => !working && setConfirming(open)}>
        <AlertDialog.Container>
          <AlertDialog.Dialog className="max-w-md">
            <AlertDialog.Header>
              <AlertDialog.Heading>Demolish {name}?</AlertDialog.Heading>
            </AlertDialog.Header>
            <AlertDialog.Body className="space-y-2 text-sm">
              <p>
                You get {formatCurrency(salvage, isImperial)} back, a quarter of what it cost. This can't be undone.
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
