import { Button, Chip, Label, Meter } from '@heroui/react';
import { Icon } from '@/components/Icon';
import type { PlacedFacility } from './types';
import { formatCurrency } from './shared';

const STATUS_STYLE: Record<string, { label: string; color: 'success' | 'warning' | 'default' | 'danger' | 'accent' }> = {
  open: { label: 'Open', color: 'success' },
  full: { label: 'Full', color: 'warning' },
  closed: { label: 'Closed', color: 'default' },
  broken: { label: 'Broken', color: 'danger' },
  upgrading: { label: 'Upgrading', color: 'accent' },
  under_construction: { label: 'Building', color: 'accent' },
  maintenance: { label: 'Maintenance', color: 'default' },
};

function meterColor(pct: number): 'success' | 'warning' | 'danger' {
  if (pct >= 70) return 'success';
  if (pct >= 40) return 'warning';
  return 'danger';
}

/** Inspector content inside MapPanel when the player clicks a built building, parking lot, or terrain park. */
export function BuildingInspectorView({
  facility,
  isImperial,
  canReposition,
  onStartMove,
  onResetMove,
  onSelectClusterItem,
  onZoom,
}: {
  facility: PlacedFacility;
  isImperial: boolean;
  canReposition: boolean;
  onStartMove: (f: PlacedFacility) => void;
  onResetMove: (f: PlacedFacility) => void;
  onSelectClusterItem: (f: PlacedFacility) => void;
  onZoom: () => void;
}) {
  const status = STATUS_STYLE[facility.status] ?? { label: facility.status, color: 'default' as const };
  const isCluster = facility.clusterCount > 1 && facility.clusteredFacilities?.length;
  const movable = canReposition && !isCluster && (facility.kind === 'building' || facility.kind === 'parking');

  return (
    <div className="space-y-4">
      <div className="flex items-center gap-2">
        <Chip size="sm" variant="soft" color={status.color}>
          {status.label}
        </Chip>
        <Chip size="sm" variant="secondary">
          {facility.typeLabel} · Lv {facility.level}
        </Chip>
        <Button
          size="sm"
          variant="ghost"
          onPress={onZoom}
          className="ml-auto min-h-11 gap-1.5 text-sm text-muted hover:text-foreground md:min-h-8"
        >
          <Icon name="fa-location-crosshairs" /> Focus
        </Button>
      </div>

      {/* Live occupancy & Condition Meters */}
      <div className="space-y-3 rounded-lg bg-surface-secondary p-3">
        <Meter value={Math.max(0, Math.min(100, facility.occupancyPct))} color="accent">
          <div className="flex items-center justify-between text-sm">
            <Label>{facility.kind === 'parking' ? 'Spaces filled' : 'Live occupancy'}</Label>
            <Meter.Output className="tabular-nums font-semibold">
              {facility.capacity > 0
                ? `${facility.currentCount.toLocaleString()} / ${facility.capacity.toLocaleString()} (${facility.occupancyPct}%)`
                : `${facility.occupancyPct}%`}
            </Meter.Output>
          </div>
          <Meter.Track>
            <Meter.Fill />
          </Meter.Track>
        </Meter>

        <Meter value={Math.max(0, Math.min(100, facility.conditionPct))} color={meterColor(facility.conditionPct)}>
          <div className="flex items-center justify-between text-sm">
            <Label>Condition</Label>
            <Meter.Output className="tabular-nums font-semibold">{facility.conditionPct}%</Meter.Output>
          </div>
          <Meter.Track>
            <Meter.Fill />
          </Meter.Track>
        </Meter>
      </div>

      {/* Financials & Location */}
      <dl className="grid grid-cols-2 gap-3 rounded-lg bg-surface-secondary p-3 text-sm">
        <div>
          <dt className="text-muted">Daily revenue</dt>
          <dd className="tabular-nums font-semibold text-success">
            +{formatCurrency(facility.dailyRevenue, isImperial)}
          </dd>
        </div>
        <div>
          <dt className="text-muted">Daily upkeep</dt>
          <dd className="tabular-nums font-semibold text-foreground">
            {formatCurrency(facility.dailyUpkeep, isImperial)}
          </dd>
        </div>
        <div className="col-span-2 flex items-center justify-between text-xs text-muted">
          <span>Map placement</span>
          <span className="font-medium text-foreground">
            {facility.customPlaced ? 'Custom pinned position' : 'Auto-placed near lift hub'}
          </span>
        </div>
      </dl>

      {isCluster && (
        <div className="space-y-1.5">
          <p className="text-xs font-semibold uppercase tracking-wide text-muted">
            Buildings in district ({facility.clusteredFacilities!.length})
          </p>
          <div className="max-h-44 space-y-1 overflow-y-auto pr-1">
            {facility.clusteredFacilities!.map((child) => (
              <Button
                key={child.key}
                variant="ghost"
                size="sm"
                onPress={() => onSelectClusterItem(child)}
                className="flex h-auto min-h-10 w-full items-center justify-between rounded-lg border border-border px-2.5 py-1.5 text-left"
              >
                <span className="truncate font-medium text-foreground">{child.label}</span>
                <span className="shrink-0 tabular-nums text-xs text-muted">
                  Lv {child.level} · {child.occupancyPct}%
                </span>
              </Button>
            ))}
          </div>
        </div>
      )}

      {movable && (
        <div className="flex gap-2">
          <Button variant="secondary" onPress={() => onStartMove(facility)} className="min-h-11 flex-1 gap-2 md:min-h-9">
            <Icon name="fa-up-down-left-right" /> Move on map
          </Button>
          {facility.customPlaced && (
            <Button
              variant="outline"
              onPress={() => onResetMove(facility)}
              className="min-h-11 gap-1.5 md:min-h-9"
              aria-label="Reset to automatic village placement"
            >
              <Icon name="fa-rotate-left" /> Auto
            </Button>
          )}
        </div>
      )}

      <a
        href={facility.route}
        className="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-lg border border-border px-3 text-sm font-semibold text-link hover:bg-surface-secondary md:min-h-9"
      >
        <Icon name="fa-arrow-up-right-from-square" /> Manage on {facility.typeLabel} page
      </a>
    </div>
  );
}
