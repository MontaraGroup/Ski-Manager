import { useState } from 'react';
import { Alert, Button, Chip, Meter, Surface } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput, Empty, Figure, Page, Panel, PostButton } from '@/components/Page';

export type FacilityAddon = {
  key: string;
  name: string;
  icon: string;
  desc: string;
  cost: number;
  costText: string;
  upkeepText: string;
  installed: boolean;
};

export type SessionModeOption = {
  key: string;
  label: string;
  desc: string;
  active: boolean;
  locked: boolean;
};

export type WinterSportFacility = {
  id: number;
  facility_type: string;
  name: string;
  surface: string;
  weather: string;
  weatherNote: string | null;
  level: number;
  levelName: string;
  maxLevel: number;
  status: string;
  condition_pct: number;
  ice_quality: number;
  session_mode: string;
  modes: SessionModeOption[];
  addons: FacilityAddon[];
  ticket_price: number;
  min_price: number;
  max_price: number;
  capacity: number;
  estGuests: number;
  estTicketText: string;
  estAddonText: string;
  estRevenue: number;
  estRevenueText: string;
  upkeep: number;
  upkeepText: string;
  visitorBoost: number;
  lastGuests: number;
  lastTicketText: string;
  lastAddonText: string;
  totalRevenue: number;
  totalRevenueText: string;
  icon: string;
  eventName: string;
  nextLevel: number | null;
  nextLevelName: string | null;
  upgradeCost: number;
  upgradeCostText: string;
  repairCost: number;
  repairCostText: string;
  resurfaceCost: number;
  resurfaceCostText: string;
  salvage: number;
  salvageText: string;
};

export type WinterSportCatalogItem = {
  type: string;
  name: string;
  icon: string;
  desc: string;
  surface: string;
  weather: string;
  cost: number;
  costText: string;
  upkeep: number;
  upkeepText: string;
  capacity: number;
  defaultPrice: number;
  defaultPriceText: string;
  visitorDraw: number;
  eventName: string;
  levels: string[];
  built: number;
  maxAllowed: number;
  canBuild: boolean;
};

export type WinterSportsPageProps = {
  cash: number;
  cashText: string;
  stats: {
    openCount: number;
    totalCount: number;
    estRevenueText: string;
    estTicketText: string;
    estAddonText: string;
    totalUpkeepText: string;
    estNetText: string;
    estGuests: number;
    visitorBoost: number;
  };
  facilities: WinterSportFacility[];
  catalog: WinterSportCatalogItem[];
};

const statusColor = (s: string) => (s === 'open' ? 'success' : s === 'maintenance' ? 'warning' : 'default');
const meterColor = (v: number) => (v >= 60 ? 'success' : v >= 30 ? 'warning' : 'danger');

function FacilityCard({ f, cash }: { f: WinterSportFacility; cash: number }) {
  const [price, setPrice] = useState(String(f.ticket_price));

  return (
    <Surface className="flex flex-col gap-4 rounded-xl border border-border p-4 sm:p-5">
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div className="flex items-start gap-3">
          <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-default text-link">
            <Icon name={f.icon} />
          </span>
          <div>
            <div className="flex flex-wrap items-center gap-2">
              <h3 className="text-base font-semibold">{f.name}</h3>
              <Chip size="sm" variant="soft" color={statusColor(f.status)}>
                {f.status === 'open' ? 'Open' : f.status === 'maintenance' ? 'Needs repair' : 'Closed'}
              </Chip>
              <Chip size="sm" variant="soft" color="accent">
                Level {f.level}/{f.maxLevel}: {f.levelName}
              </Chip>
            </div>
            <p className="mt-0.5 text-xs text-muted">
              {f.surface} · {f.weather} · Draws +{f.visitorBoost.toLocaleString('en-US')} resort visitors/day
            </p>
          </div>
        </div>

        <div className="flex flex-wrap items-center gap-2">
          <a
            href="/tournaments"
            className="inline-flex min-h-8 items-center gap-1.5 rounded-lg border border-border px-2.5 text-xs font-medium text-link hover:bg-default"
          >
            <Icon name="fa-trophy" className="text-warning" />
            {f.eventName}
          </a>
          {f.status !== 'maintenance' && (
            <PostButton action={`/winter-sports/toggle/${f.id}`} size="sm" variant="secondary">
              {f.status === 'open' ? 'Close' : 'Reopen'}
            </PostButton>
          )}
          <PostButton
            action={`/winter-sports/demolish/${f.id}`}
            size="sm"
            variant="danger"
            confirm={{
              title: `Demolish ${f.name}?`,
              body: `You get ${f.salvageText} back (50% salvage). This cannot be undone.`,
              confirmLabel: 'Demolish',
            }}
          >
            Demolish ({f.salvageText})
          </PostButton>
        </div>
      </div>

      {f.weatherNote && (
        <Alert status="warning">
          <Alert.Indicator />
          <Alert.Content>
            <Alert.Title>{f.weatherNote}</Alert.Title>
          </Alert.Content>
        </Alert>
      )}

      {/* Revenue & Attendance Breakdown */}
      <dl className="grid grid-cols-2 gap-3 rounded-lg bg-default/50 p-3 text-sm sm:grid-cols-5">
        <div>
          <dt className="text-xs text-muted">Est. guests/day</dt>
          <dd className="font-semibold tabular-nums">
            {f.estGuests.toLocaleString('en-US')} / {f.capacity.toLocaleString('en-US')}
          </dd>
        </div>
        <div>
          <dt className="text-xs text-muted">Est. ticket income</dt>
          <dd className="font-semibold tabular-nums text-success">{f.estTicketText}</dd>
        </div>
        <div>
          <dt className="text-xs text-muted">Rentals &amp; stands</dt>
          <dd className="font-semibold tabular-nums text-success">{f.estAddonText}</dd>
        </div>
        <div>
          <dt className="text-xs text-muted">Daily upkeep</dt>
          <dd className="font-semibold tabular-nums">{f.upkeepText}</dd>
        </div>
        <div>
          <dt className="text-xs text-muted">Total earned</dt>
          <dd className="font-semibold tabular-nums">{f.totalRevenueText}</dd>
        </div>
      </dl>

      {f.lastGuests > 0 && (
        <p className="text-xs text-muted tabular-nums">
          Last settled day: {f.lastGuests.toLocaleString('en-US')} guests ({f.lastTicketText} tickets, {f.lastAddonText} rentals &amp; stands).
        </p>
      )}

      {/* Condition + Surface Quality Meters */}
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <div className="mb-1 flex items-center justify-between text-xs text-muted">
            <span>Structural condition</span>
            <span className="font-semibold tabular-nums text-foreground">{f.condition_pct}%</span>
          </div>
          <Meter value={f.condition_pct} color={meterColor(f.condition_pct)} size="sm" aria-label={`${f.name} condition ${f.condition_pct}%`}>
            <Meter.Track>
              <Meter.Fill />
            </Meter.Track>
          </Meter>
        </div>
        <div>
          <div className="mb-1 flex items-center justify-between text-xs text-muted">
            <span>{f.surface} quality</span>
            <span className="font-semibold tabular-nums text-foreground">{f.ice_quality}%</span>
          </div>
          <Meter value={f.ice_quality} color={meterColor(f.ice_quality)} size="sm" aria-label={`${f.name} surface quality ${f.ice_quality}%`}>
            <Meter.Track>
              <Meter.Fill />
            </Meter.Track>
          </Meter>
        </div>
      </div>

      {/* Operations: Schedule, Ticket Price, Maintenance, Level Upgrade */}
      <div className="flex flex-wrap items-center justify-between gap-3 border-t border-border pt-3">
        <div className="flex flex-wrap items-center gap-1.5">
          <span className="mr-1 text-xs text-muted">Schedule:</span>
          {f.modes.map((m) => (
            <form key={m.key} action={`/winter-sports/mode/${f.id}`} method="post" className="inline">
              <CsrfInput />
              <input type="hidden" name="session_mode" value={m.key} />
              <Button
                type="submit"
                size="sm"
                variant={m.active ? 'primary' : 'secondary'}
                isDisabled={m.locked || m.active}
                aria-label={`${m.label}: ${m.desc}`}
              >
                {m.label}
              </Button>
            </form>
          ))}
        </div>

        <form action={`/winter-sports/price/${f.id}`} method="post" className="flex items-center gap-2">
          <CsrfInput />
          <label htmlFor={`price-${f.id}`} className="text-xs text-muted">
            Entry pass
          </label>
          <input
            id={`price-${f.id}`}
            name="ticket_price"
            type="number"
            min={f.min_price}
            max={f.max_price}
            value={price}
            onChange={(e) => setPrice(e.target.value)}
            className="h-9 w-20 rounded-lg border border-border bg-background px-2 text-sm font-semibold tabular-nums"
          />
          <Button type="submit" size="sm" variant="secondary">
            Set
          </Button>
        </form>

        <div className="flex flex-wrap items-center gap-2">
          {f.resurfaceCost > 0 && (
            <PostButton action={`/winter-sports/resurface/${f.id}`} size="sm" variant="secondary" disabled={cash < f.resurfaceCost}>
              Resurface ({f.resurfaceCostText})
            </PostButton>
          )}
          {f.repairCost > 0 && (
            <PostButton action={`/winter-sports/repair/${f.id}`} size="sm" variant="secondary" disabled={cash < f.repairCost}>
              Repair ({f.repairCostText})
            </PostButton>
          )}
          {f.nextLevel && (
            <PostButton action={`/winter-sports/upgrade/${f.id}`} size="sm" variant="primary" disabled={cash < f.upgradeCost}>
              Upgrade to L{f.nextLevel} ({f.upgradeCostText})
            </PostButton>
          )}
        </div>
      </div>

      {/* Add-on Upgrades */}
      <div className="border-t border-border pt-3">
        <div className="mb-2 text-xs font-medium text-muted">Facility add-ons</div>
        <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-4">
          {f.addons.map((a) => (
            <div
              key={a.key}
              className={`flex flex-col justify-between gap-2 rounded-lg border p-2.5 text-xs ${
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
                <span className="font-medium text-success">Installed (+{a.upkeepText}/day)</span>
              ) : (
                <form action={`/winter-sports/install-upgrade/${f.id}`} method="post">
                  <CsrfInput />
                  <input type="hidden" name="upgrade" value={a.key} />
                  <Button type="submit" size="sm" variant="secondary" isDisabled={cash < a.cost} className="w-full">
                    Add ({a.costText})
                  </Button>
                </form>
              )}
            </div>
          ))}
        </div>
      </div>
    </Surface>
  );
}

export default function WinterSportsPage({ cash, cashText, stats, facilities, catalog }: WinterSportsPageProps) {
  return (
    <Page
      title="Winter Activities"
      icon="fa-hockey-puck"
      iconTone="text-link"
      back="/resort"
      width="max-w-6xl"
      subtitle="Build ice rinks, cross-country trails, hockey venues, ski jumps and bobsleigh tracks for extra guest income, rentals, and events."
      actions={
        <span className="rounded-full border border-border px-3 py-1 text-xs font-semibold tabular-nums text-success">
          Cash {cashText}
        </span>
      }
    >
      <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <Figure
          label="Open venues"
          value={`${stats.openCount} / ${stats.totalCount}`}
          tone="text-foreground"
          note={`+${stats.visitorBoost.toLocaleString('en-US')} resort visitors/day`}
        />
        <Figure
          label="Est. daily income"
          value={stats.estRevenueText}
          tone="text-success"
          note={`${stats.estTicketText} pass + ${stats.estAddonText} add-ons`}
        />
        <Figure
          label="Est. guests/day"
          value={stats.estGuests.toLocaleString('en-US')}
          tone="text-link"
          note="Across open activities"
        />
        <Figure
          label="Est. daily net"
          value={stats.estNetText}
          tone="text-foreground"
          note={`Upkeep ${stats.totalUpkeepText}/day`}
        />
      </div>

      <section aria-labelledby="ws-owned" className="flex flex-col gap-3">
        <h2 id="ws-owned" className="text-lg font-semibold">
          Your winter activities
        </h2>
        {facilities.length === 0 ? (
          <Panel>
            <Empty icon="fa-hockey-puck" title="No winter activities built yet">
              Build an Ice Rink, Cross-Country Skiing network, Ice Hockey Venue, Ski Jump Ramp, or Bobsleigh Track below to start earning ticket and rental income.
            </Empty>
          </Panel>
        ) : (
          <div className="flex flex-col gap-4">
            {facilities.map((f) => (
              <FacilityCard key={f.id} f={f} cash={cash} />
            ))}
          </div>
        )}
      </section>

      <section aria-labelledby="ws-catalog" className="flex flex-col gap-3">
        <h2 id="ws-catalog" className="text-lg font-semibold">
          Build a winter activity
        </h2>
        <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
          {catalog.map((c) => (
            <Surface key={c.type} className="flex flex-col justify-between gap-3 rounded-xl border border-border p-4">
              <div className="flex items-start gap-3">
                <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-default text-link">
                  <Icon name={c.icon} />
                </span>
                <div className="min-w-0 flex-1">
                  <div className="flex items-center justify-between gap-2">
                    <h3 className="text-base font-semibold">{c.name}</h3>
                    <span className="text-xs tabular-nums text-muted">
                      {c.built}/{c.maxAllowed} built
                    </span>
                  </div>
                  <p className="mt-0.5 text-sm text-muted">{c.desc}</p>
                  <p className="mt-1 text-xs text-muted">
                    {c.surface} · {c.weather}
                  </p>
                </div>
              </div>

              <dl className="grid grid-cols-3 gap-2 rounded-lg bg-default/50 p-2.5 text-xs">
                <div>
                  <dt className="text-muted">Base capacity</dt>
                  <dd className="font-semibold tabular-nums">{c.capacity}/day</dd>
                </div>
                <div>
                  <dt className="text-muted">Visitor draw</dt>
                  <dd className="font-semibold tabular-nums">+{c.visitorDraw}/day</dd>
                </div>
                <div>
                  <dt className="text-muted">Base upkeep</dt>
                  <dd className="font-semibold tabular-nums">{c.upkeepText}/day</dd>
                </div>
              </dl>

              <div className="text-xs text-muted">
                Levels: <span className="text-foreground">{c.levels.join(' -> ')}</span>
              </div>

              <div className="flex items-center justify-between gap-2 border-t border-border pt-2.5">
                <span className="text-xs text-muted">
                  Event: <strong className="font-medium text-foreground">{c.eventName}</strong>
                </span>
                <form action="/winter-sports/build" method="post">
                  <CsrfInput />
                  <input type="hidden" name="facility_type" value={c.type} />
                  <Button type="submit" size="sm" variant="primary" isDisabled={!c.canBuild}>
                    Build ({c.costText})
                  </Button>
                </form>
              </div>
            </Surface>
          ))}
        </div>
      </section>
    </Page>
  );
}
