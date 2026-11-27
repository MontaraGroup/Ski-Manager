import { useState } from 'react';
import { Alert, Button, Chip, Meter, Surface } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput, PostButton } from '@/components/Page';
import type { ActivityTab, HydratedVenue, VenueAddon } from './types';

const statusColor = (s: string) => (s === 'open' ? 'success' : s === 'maintenance' ? 'warning' : 'default');
export const meterColor = (v: number) => (v >= 60 ? 'success' : v >= 30 ? 'warning' : 'danger');

export function VenueHeader({ tab, v, cash }: { tab: ActivityTab; v: HydratedVenue; cash: number }) {
  return (
    <>
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div className="flex items-start gap-3">
          <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-default text-link">
            <Icon name={tab.icon} className="text-lg" />
          </span>
          <div>
            <div className="flex flex-wrap items-center gap-2">
              <h2 className="text-lg font-semibold">{v.name}</h2>
              <Chip size="sm" variant="soft" color={statusColor(v.status)}>
                {v.status === 'open' ? 'Open' : v.status === 'maintenance' ? 'Needs repair' : 'Closed'}
              </Chip>
              <Chip size="sm" variant="soft" color="accent">
                Level {v.level}/{v.maxLevel}: {v.levelName}
              </Chip>
            </div>
            <p className="mt-0.5 text-xs text-muted">
              {v.est.highlight} · +{v.est.visitorBoost.toLocaleString('en-US')} resort visitors/day · {v.est.kwh} kWh/day
            </p>
          </div>
        </div>

        <div className="flex flex-wrap items-center gap-2">
          <a
            href="/tournaments"
            className="inline-flex min-h-8 items-center gap-1.5 rounded-lg border border-border px-2.5 text-xs font-medium text-link hover:bg-default"
          >
            <Icon name="fa-trophy" className="text-warning" />
            {tab.eventName}
          </a>
          <a
            href="/map"
            className="inline-flex min-h-8 items-center gap-1.5 rounded-lg border border-border px-2.5 text-xs font-medium text-muted hover:text-foreground"
          >
            <Icon name="fa-map" /> Map
          </a>
          {v.status !== 'maintenance' && (
            <PostButton action={`/winter-sports/toggle/${v.id}`} size="sm" variant="secondary">
              {v.status === 'open' ? 'Close' : 'Reopen'}
            </PostButton>
          )}
          <PostButton
            action={`/winter-sports/demolish/${v.id}`}
            size="sm"
            variant="danger"
            confirm={{
              title: `Demolish ${v.name}?`,
              body: `You get ${v.salvageText} back (50% salvage). This cannot be undone.`,
              confirmLabel: 'Demolish',
            }}
          >
            Demolish ({v.salvageText})
          </PostButton>
        </div>
      </div>

      {v.est.weatherNote && (
        <Alert status="warning">
          <Alert.Indicator />
          <Alert.Content>
            <Alert.Title>{v.est.weatherNote}</Alert.Title>
          </Alert.Content>
        </Alert>
      )}

      <dl className="grid grid-cols-2 gap-3 rounded-lg bg-default/50 p-3 text-sm sm:grid-cols-6">
        <div>
          <dt className="text-xs text-muted">Est. guests/day</dt>
          <dd className="font-semibold tabular-nums">
            {v.est.guests.toLocaleString('en-US')} / {v.est.capacity.toLocaleString('en-US')}
          </dd>
        </div>
        <div>
          <dt className="text-xs text-muted">Entry / tickets</dt>
          <dd className="font-semibold tabular-nums text-success">{v.est.ticketText}</dd>
        </div>
        <div>
          <dt className="text-xs text-muted">Rentals &amp; extras</dt>
          <dd className="font-semibold tabular-nums text-success">{v.est.addonText}</dd>
        </div>
        <div>
          <dt className="text-xs text-muted">Sponsors / camps</dt>
          <dd className="font-semibold tabular-nums text-success">{v.est.contractText}</dd>
        </div>
        <div>
          <dt className="text-xs text-muted">Daily upkeep</dt>
          <dd className="font-semibold tabular-nums">{v.est.upkeepText}</dd>
        </div>
        <div>
          <dt className="text-xs text-muted">Total earned</dt>
          <dd className="font-semibold tabular-nums">{v.totalRevenueText}</dd>
        </div>
      </dl>

      {v.lastGuests > 0 && (
        <p className="text-xs text-muted tabular-nums">
          Last settled night: {v.lastGuests.toLocaleString('en-US')} guests ({v.lastTicketText} entry, {v.lastAddonText} rentals &amp; extras).
        </p>
      )}

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <div className="mb-1 flex items-center justify-between text-xs text-muted">
            <span>Structural condition</span>
            <span className="font-semibold tabular-nums text-foreground">{v.condition_pct}%</span>
          </div>
          <Meter value={v.condition_pct} color={meterColor(v.condition_pct)} size="sm" aria-label={`${v.name} condition`}>
            <Meter.Track>
              <Meter.Fill />
            </Meter.Track>
          </Meter>
        </div>
        <div>
          <div className="mb-1 flex items-center justify-between text-xs text-muted">
            <span>{tab.surface} quality</span>
            <span className="font-semibold tabular-nums text-foreground">{v.ice_quality}%</span>
          </div>
          <Meter value={v.ice_quality} color={meterColor(v.ice_quality)} size="sm" aria-label={`${v.name} surface quality`}>
            <Meter.Track>
              <Meter.Fill />
            </Meter.Track>
          </Meter>
        </div>
      </div>

      <div className="flex flex-wrap items-center justify-between gap-3 border-t border-border pt-3">
        <EntryPriceForm v={v} label="Entry pass" />
        <div className="flex flex-wrap items-center gap-2">
          {v.resurfaceCost > 0 && (
            <PostButton action={`/winter-sports/resurface/${v.id}`} size="sm" variant="secondary" disabled={cash < v.resurfaceCost}>
              Resurface ({v.resurfaceCostText})
            </PostButton>
          )}
          {v.repairCost > 0 && (
            <PostButton action={`/winter-sports/repair/${v.id}`} size="sm" variant="secondary" disabled={cash < v.repairCost}>
              Repair ({v.repairCostText})
            </PostButton>
          )}
          {v.nextLevel && (
            <PostButton action={`/winter-sports/upgrade/${v.id}`} size="sm" variant="primary" disabled={cash < v.upgradeCost}>
              Upgrade to L{v.nextLevel}: {v.nextLevelName} ({v.upgradeCostText})
            </PostButton>
          )}
        </div>
      </div>
    </>
  );
}

export function EntryPriceForm({ v, label }: { v: HydratedVenue; label: string }) {
  const [price, setPrice] = useState(String(v.ticket_price));
  return (
    <form action={`/winter-sports/price/${v.id}`} method="post" className="flex items-center gap-2">
      <CsrfInput />
      <label htmlFor={`price-${v.id}`} className="text-xs text-muted">
        {label} ({v.min_price}-{v.max_price})
      </label>
      <input
        id={`price-${v.id}`}
        name="ticket_price"
        type="number"
        min={v.min_price}
        max={v.max_price}
        value={price}
        onChange={(e) => setPrice(e.target.value)}
        className="h-9 w-20 rounded-lg border border-border bg-background px-2 text-sm font-semibold tabular-nums"
      />
      <Button type="submit" size="sm" variant="secondary">
        Set
      </Button>
    </form>
  );
}

export function AddonsGrid({ venueId, addons, cash, title }: { venueId: number; addons: VenueAddon[]; cash: number; title: string }) {
  return (
    <div className="border-t border-border pt-3">
      <div className="mb-2 text-xs font-semibold uppercase tracking-wide text-muted">{title}</div>
      <div className="grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:grid-cols-4">
        {addons.map((a) => (
          <div
            key={a.key}
            className={`flex flex-col justify-between gap-2 rounded-lg border p-3 text-xs ${
              a.installed ? 'border-success/40 bg-success/5' : 'border-border bg-default/40'
            }`}
          >
            <div>
              <div className="flex items-center justify-between gap-1 font-semibold">
                <span className="flex items-center gap-1.5">
                  <Icon name={a.icon} className={a.installed ? 'text-success' : 'text-muted'} />
                  {a.name}
                </span>
                {a.installed && <Icon name="fa-circle-check" className="text-success" />}
              </div>
              <p className="mt-1 text-muted">{a.desc}</p>
            </div>
            {a.installed ? (
              <span className="font-medium text-success tabular-nums">
                Active (+{a.upkeepText}/day, +{a.kwh} kWh)
              </span>
            ) : (
              <form action={`/winter-sports/install-upgrade/${venueId}`} method="post">
                <CsrfInput />
                <input type="hidden" name="upgrade" value={a.key} />
                <Button type="submit" size="sm" variant="secondary" isDisabled={cash < a.cost} className="w-full">
                  Install ({a.costText})
                </Button>
              </form>
            )}
          </div>
        ))}
      </div>
    </div>
  );
}

export function UnbuiltBlueprint({ tab }: { tab: ActivityTab }) {
  return (
    <Surface className="flex flex-col gap-5 rounded-xl border border-border p-5 sm:p-6">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div className="flex items-start gap-3">
          <span className="flex size-12 shrink-0 items-center justify-center rounded-xl bg-default text-link">
            <Icon name={tab.icon} className="text-xl" />
          </span>
          <div>
            <div className="flex flex-wrap items-center gap-2">
              <h2 className="text-xl font-semibold">{tab.name}</h2>
              <Chip size="sm" variant="soft" color="default">
                Not built yet
              </Chip>
            </div>
            <p className="mt-1 text-sm text-muted">{tab.desc}</p>
          </div>
        </div>

        <form action="/winter-sports/build" method="post" className="shrink-0">
          <CsrfInput />
          <input type="hidden" name="facility_type" value={tab.type} />
          <Button type="submit" size="lg" variant="primary" isDisabled={!tab.canBuild}>
            <Icon name="fa-hammer" /> Build {tab.name} ({tab.buildCostText})
          </Button>
        </form>
      </div>

      <dl className="grid grid-cols-2 gap-3 rounded-lg bg-default/50 p-3.5 text-sm sm:grid-cols-4">
        <div>
          <dt className="text-xs text-muted">Build cost</dt>
          <dd className="font-semibold tabular-nums">{tab.buildCostText}</dd>
        </div>
        <div>
          <dt className="text-xs text-muted">Base upkeep</dt>
          <dd className="font-semibold tabular-nums">{tab.upkeepText}/day</dd>
        </div>
        <div>
          <dt className="text-xs text-muted">Weather profile</dt>
          <dd className="font-medium text-foreground">{tab.weather}</dd>
        </div>
        <div>
          <dt className="text-xs text-muted">Unlocks event</dt>
          <dd className="font-medium text-warning">{tab.eventName}</dd>
        </div>
      </dl>

      <div>
        <div className="mb-2 text-xs font-semibold uppercase tracking-wide text-muted">Progression tiers</div>
        <div className="flex flex-wrap items-center gap-2 text-xs">
          {tab.levels.map((lvl, idx) => (
            <Chip key={lvl} size="sm" variant="soft" color="accent">
              L{idx + 1}: {lvl}
            </Chip>
          ))}
        </div>
      </div>

      <div>
        <div className="mb-2 text-xs font-semibold uppercase tracking-wide text-muted">Unique venue upgrades</div>
        <div className="grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:grid-cols-4">
          {tab.upgradePreviews.map((u) => (
            <div key={u.name} className="rounded-lg border border-border bg-default/30 p-3 text-xs">
              <div className="flex items-center justify-between font-semibold">
                <span className="flex items-center gap-1.5">
                  <Icon name={u.icon} className="text-link" />
                  {u.name}
                </span>
                <span className="tabular-nums text-muted">{u.costText}</span>
              </div>
              <p className="mt-1 text-muted">{u.desc}</p>
            </div>
          ))}
        </div>
      </div>
    </Surface>
  );
}
