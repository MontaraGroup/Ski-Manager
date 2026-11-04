import { useState } from 'react';
import { Button, Chip } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput, Empty, Figure, Page, Panel, PostButton } from '@/components/Page';

export type ParkingFacility = {
  id: number;
  name: string;
  parking_type: string;
  typeLabel: string;
  icon: string;
  capacity: number;
  capacityText: string;
  occupied: number;
  occupiedText: string;
  occupancyPct: number;
  dailyRevenue: number;
  dailyRevenueText: string;
  condition_pct: number;
  status: 'open' | 'closed' | 'full' | 'under_construction' | string;
  build_days_left: number;
  fee_per_day: number;
  defaultFeeText: string;
  repairCost: number;
  repairCostText: string;
  canRepair: boolean;
};

export type ParkingConfigItem = {
  key: string;
  label: string;
  icon: string;
  capacity: number;
  capacityText: string;
  cost: number;
  costText: string;
  originalCostText: string;
  upkeepText: string;
  build_days: number;
  canAfford: boolean;
};

export type ParkingPageProps = {
  stats: {
    totalCapacity: number;
    totalCapacityText: string;
    totalOccupied: number;
    occupancyPct: number;
    totalRevenueText: string;
    facilityCount: number;
    nearlyFull: boolean;
  };
  facilities: ParkingFacility[];
  catalog: ParkingConfigItem[];
};

export default function ParkingPage({ stats, facilities, catalog }: ParkingPageProps) {
  return (
    <Page
      title="Parking & Transit"
      icon="fa-square-parking"
      iconTone="text-accent"
      subtitle="Build lots and garages to handle visitor traffic"
      width="max-w-6xl"
    >
      {/* 4 Summary stats */}
      <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
        <Figure label="Total spots" value={stats.totalCapacityText} tone="text-accent" />
        <Figure
          label="Occupied"
          value={`${stats.occupancyPct}%`}
          tone={
            stats.occupancyPct > 90
              ? 'text-danger'
              : stats.occupancyPct > 70
                ? 'text-warning'
                : 'text-success'
          }
        />
        <Figure label="Daily revenue" value={stats.totalRevenueText} tone="text-success" />
        <Figure label="Facilities" value={stats.facilityCount} />
      </div>

      {/* Near full notice */}
      {stats.nearlyFull && (
        <div className="flex items-center gap-2 rounded-xl border border-warning/40 bg-surface p-4 text-sm text-foreground">
          <Icon name="fa-triangle-exclamation" className="text-warning shrink-0" />
          <span>
            Parking is nearly full: drivers who find no space park elsewhere and you miss their fees. Build more
            capacity below.
          </span>
        </div>
      )}

      {/* Existing facilities */}
      <section className="space-y-3">
        <div className="flex items-center justify-between">
          <h2 className="text-lg font-bold text-foreground flex items-center gap-2">
            <Icon name="fa-list" className="text-accent text-base" />
            Your facilities
          </h2>
        </div>

        {facilities.length === 0 ? (
          <Empty icon="fa-square-parking" title="No parking facilities yet">
            Without parking, visitor traffic is limited. Build your first lot or garage below!
          </Empty>
        ) : (
          <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
            {facilities.map((f) => (
              <FacilityCard key={f.id} facility={f} />
            ))}
          </div>
        )}
      </section>

      {/* Build new facility catalog */}
      <section className="space-y-3 pt-2">
        <h2 className="text-lg font-bold text-foreground flex items-center gap-2">
          <Icon name="fa-hammer" className="text-accent text-base" />
          Build new facility
        </h2>

        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
          {catalog.map((cfg) => (
            <div
              key={cfg.key}
              className="flex flex-col justify-between rounded-xl border border-border bg-surface p-4 transition-colors hover:border-accent/40"
            >
              <div>
                <div className="flex size-14 items-center justify-center rounded-xl bg-default mx-auto mb-3">
                  <Icon name={cfg.icon} className="text-2xl text-accent" />
                </div>
                <h3 className="font-bold text-sm text-center text-foreground">{cfg.label}</h3>

                <div className="grid grid-cols-2 gap-1.5 text-xs my-3">
                  <div className="rounded bg-default p-1.5 text-center">
                    <div className="font-bold text-foreground">{cfg.capacityText}</div>
                    <div className="text-muted">Spots</div>
                  </div>
                  <div className="rounded bg-default p-1.5 text-center">
                    <div className="font-bold text-foreground">{cfg.upkeepText}</div>
                    <div className="text-muted">Per day</div>
                  </div>
                </div>
              </div>

              <div className="pt-2 border-t border-border mt-auto space-y-2">
                <div className="flex items-center justify-between">
                  <div>
                    <div className="text-xs text-muted line-through">{cfg.originalCostText}</div>
                    <div className="text-base font-bold text-accent">{cfg.costText}</div>
                  </div>
                  <PostButton
                    action="/parking/build"
                    data={{ parking_type: cfg.key }}
                    variant="primary"
                    size="sm"
                    disabled={!cfg.canAfford}
                    confirm={{
                      title: `Build ${cfg.label}?`,
                      body: `Cost will be ${cfg.costText}. Construction takes ${cfg.build_days} days.`,
                      confirmLabel: 'Build facility',
                    }}
                  >
                    <Icon name="fa-cart-plus" className="mr-1 text-xs" />
                    Build
                  </PostButton>
                </div>
                <div className="text-xs text-muted text-center">
                  {cfg.build_days} {cfg.build_days === 1 ? 'day' : 'days'} to build
                </div>
              </div>
            </div>
          ))}
        </div>
      </section>
    </Page>
  );
}

function FacilityCard({ facility: f }: { facility: ParkingFacility }) {
  const isUnderConstruction = f.status === 'under_construction';
  const isOpen = f.status === 'open' || f.status === 'full';
  const cond = f.condition_pct;

  return (
    <div
      className={`flex flex-col justify-between rounded-xl border bg-surface p-4 gap-3 ${
        isUnderConstruction ? 'border-warning/40' : 'border-border'
      }`}
    >
      <div>
        <div className="flex items-start justify-between gap-2 mb-2">
          <div className="flex items-center gap-2.5 min-w-0">
            <div className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-default">
              <Icon name={f.icon} className="text-base text-accent" />
            </div>
            <div className="min-w-0">
              <div className="font-bold text-sm truncate text-foreground">{f.name}</div>
              <div className="text-xs text-muted">
                {f.typeLabel} · {f.capacityText} spots
              </div>
            </div>
          </div>
          <Chip
            variant="soft"
            size="sm"
            color={
              f.status === 'open'
                ? 'success'
                : f.status === 'under_construction'
                  ? 'warning'
                  : f.status === 'full'
                    ? 'danger'
                    : 'default'
            }
            className="shrink-0"
          >
            {isUnderConstruction ? `Building (${f.build_days_left}d)` : f.status.toUpperCase()}
          </Chip>
        </div>

        {!isUnderConstruction && (
          <div className="space-y-2.5 pt-1">
            <div className="grid grid-cols-2 gap-2 text-xs">
              <div className="rounded-lg bg-default p-2 text-center">
                <div className="font-bold text-sm text-foreground">
                  {f.occupiedText} / {f.capacityText}
                </div>
                <div className="text-muted">Occupied ({f.occupancyPct}%)</div>
              </div>
              <div className="rounded-lg bg-default p-2 text-center">
                <div className="font-bold text-sm text-success">{f.dailyRevenueText}</div>
                <div className="text-muted">Revenue</div>
              </div>
            </div>

            {/* Occupancy progress */}
            <div className="flex items-center gap-2">
              <span className="text-xs text-muted w-16">Occupancy</span>
              <div className="relative flex-1 h-1.5 rounded-full bg-default overflow-hidden">
                <div
                  className="h-full rounded-full bg-accent"
                  style={{ width: `${Math.min(100, f.occupancyPct)}%` }}
                />
              </div>
              <span className="text-xs font-mono w-8 text-right text-foreground">{f.occupancyPct}%</span>
            </div>

            {/* Condition progress */}
            <div className="flex items-center gap-2">
              <span className="text-xs text-muted w-16">Condition</span>
              <div className="relative flex-1 h-1.5 rounded-full bg-default overflow-hidden">
                <div
                  className={`h-full rounded-full ${
                    cond >= 60 ? 'bg-success' : cond >= 30 ? 'bg-warning' : 'bg-danger'
                  }`}
                  style={{ width: `${cond}%` }}
                />
              </div>
              <span className="text-xs font-mono w-8 text-right text-foreground">{cond}%</span>
            </div>

            {/* Fee update inline form */}
            <form action={`/parking/update-fee/${f.id}`} method="post" className="flex items-center gap-2 pt-1">
              <CsrfInput />
              <span className="text-xs text-muted">Fee:</span>
              <input
                type="number"
                name="fee"
                defaultValue={f.fee_per_day}
                min={0}
                max={100}
                step={1}
                className="w-20 rounded-lg border border-border bg-surface px-2 py-1 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-accent"
              />
              <span className="text-xs text-muted">per car</span>
              <Button type="submit" variant="ghost" size="sm" isIconOnly aria-label="Save fee">
                <Icon name="fa-check" className="text-success" />
              </Button>
            </form>
            <div className="text-xs text-muted">
              Usual fee {f.defaultFeeText}: a higher fee parks fewer cars.
            </div>
          </div>
        )}
      </div>

      {!isUnderConstruction && (
        <div className="flex items-center gap-1.5 pt-2 border-t border-border mt-auto">
          <PostButton
            action={`/parking/toggle/${f.id}`}
            variant={isOpen ? 'ghost' : 'secondary'}
            size="sm"
            className="flex-1"
          >
            <Icon name={isOpen ? 'fa-pause' : 'fa-play'} className="mr-1 text-xs" />
            {isOpen ? 'Close' : 'Open'}
          </PostButton>

          {f.canRepair && (
            <PostButton
              action={`/parking/repair/${f.id}`}
              variant="secondary"
              size="sm"
              confirm={{
                title: `Repair ${f.name}?`,
                body: `Cost will be ${f.repairCostText} to restore to 100% condition.`,
                confirmLabel: 'Repair',
              }}
            >
              <Icon name="fa-wrench" className="mr-1 text-xs" />
              Repair {f.repairCostText}
            </PostButton>
          )}

          <PostButton
            action={`/parking/demolish/${f.id}`}
            variant="ghost"
            size="sm"
            className="text-danger hover:bg-danger/10"
            confirm={{
              title: `Demolish ${f.name}?`,
              body: "You will receive a 20% refund of the original construction cost.",
              confirmLabel: 'Demolish',
            }}
          >
            <Icon name="fa-trash" className="text-xs" />
          </PostButton>
        </div>
      )}
    </div>
  );
}
