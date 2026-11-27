import { useState } from 'react';
import { Button, Chip, Meter, Surface } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput, Empty, Figure, Page, Panel, PostButton } from '@/components/Page';

export type WinterSportFacility = {
  id: number;
  facility_type: string;
  name: string;
  level: number;
  levelName: string;
  maxLevel: number;
  status: string;
  condition_pct: number;
  ticket_price: number;
  min_price: number;
  max_price: number;
  capacity: number;
  estGuests: number;
  estRevenue: number;
  estRevenueText: string;
  upkeep: number;
  upkeepText: string;
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
  salvage: number;
  salvageText: string;
};

export type WinterSportCatalogItem = {
  type: string;
  name: string;
  icon: string;
  desc: string;
  cost: number;
  costText: string;
  upkeep: number;
  upkeepText: string;
  capacity: number;
  defaultPrice: number;
  defaultPriceText: string;
  visitorDraw: number;
  eventName: string;
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
    totalUpkeepText: string;
    estNetText: string;
    estGuests: number;
  };
  facilities: WinterSportFacility[];
  catalog: WinterSportCatalogItem[];
};

const statusColor = (s: string) => (s === 'open' ? 'success' : s === 'maintenance' ? 'warning' : 'default');

function FacilityCard({ f, cash }: { f: WinterSportFacility; cash: number }) {
  const [price, setPrice] = useState(String(f.ticket_price));

  return (
    <Surface className="flex flex-col gap-4 rounded-xl border border-border p-4">
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
              <span className="text-xs text-muted">
                Level {f.level}/{f.maxLevel} ({f.levelName})
              </span>
            </div>
            <p className="text-sm text-muted">
              Unlocks <span className="font-medium text-foreground">{f.eventName}</span> on Events &amp; Tournaments
            </p>
          </div>
        </div>

        <div className="flex flex-wrap items-center gap-2">
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

      <dl className="grid grid-cols-2 gap-3 rounded-lg bg-default/50 p-3 text-sm sm:grid-cols-4">
        <div>
          <dt className="text-xs text-muted">Est. guests/day</dt>
          <dd className="font-semibold tabular-nums">
            {f.estGuests.toLocaleString('en-US')} / {f.capacity.toLocaleString('en-US')}
          </dd>
        </div>
        <div>
          <dt className="text-xs text-muted">Est. daily income</dt>
          <dd className="font-semibold tabular-nums text-success">{f.estRevenueText}</dd>
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

      <div className="flex flex-wrap items-center justify-between gap-4 border-t border-border pt-3">
        <div className="min-w-[180px] flex-1">
          <div className="mb-1 flex items-center justify-between text-xs text-muted">
            <span>Condition</span>
            <span className="font-semibold tabular-nums text-foreground">{f.condition_pct}%</span>
          </div>
          <Meter
            value={f.condition_pct}
            color={f.condition_pct >= 60 ? 'success' : f.condition_pct >= 30 ? 'warning' : 'danger'}
            size="sm"
            aria-label={`${f.name} condition ${f.condition_pct}%`}
          >
            <Meter.Track>
              <Meter.Fill />
            </Meter.Track>
          </Meter>
        </div>

        <form action={`/winter-sports/price/${f.id}`} method="post" className="flex items-center gap-2">
          <CsrfInput />
          <label htmlFor={`price-${f.id}`} className="text-xs text-muted">
            Ticket
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
    </Surface>
  );
}

export default function WinterSportsPage({ cash, cashText, stats, facilities, catalog }: WinterSportsPageProps) {
  return (
    <Page
      title="Winter Sport Facilities"
      icon="fa-hockey-puck"
      iconTone="text-link"
      back="/resort"
      width="max-w-6xl"
      subtitle="Build skating rinks, hockey venues, ski jumps and bobsleigh tracks for extra ticket income and special events."
      actions={
        <span className="rounded-full border border-border px-3 py-1 text-xs font-semibold tabular-nums text-success">
          Cash {cashText}
        </span>
      }
    >
      <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <Figure label="Open venues" value={`${stats.openCount} / ${stats.totalCount}`} tone="text-foreground" note="Active facilities" />
        <Figure label="Est. daily income" value={stats.estRevenueText} tone="text-success" note={`${stats.estGuests.toLocaleString('en-US')} guests/day`} />
        <Figure label="Daily upkeep" value={stats.totalUpkeepText} tone="text-warning" note="Operating cost" />
        <Figure label="Est. daily net" value={stats.estNetText} tone="text-link" note="After upkeep" />
      </div>

      <section aria-labelledby="ws-owned" className="flex flex-col gap-3">
        <h2 id="ws-owned" className="text-lg font-semibold">
          Your facilities
        </h2>
        {facilities.length === 0 ? (
          <Panel>
            <Empty icon="fa-hockey-puck" title="No winter sport facilities yet">
              Build an Ice Rink, Ice Hockey Venue, Ski Jump Ramp, or Bobsleigh Track below to start earning spectator and ticket income.
            </Empty>
          </Panel>
        ) : (
          <div className="flex flex-col gap-3">
            {facilities.map((f) => (
              <FacilityCard key={f.id} f={f} cash={cash} />
            ))}
          </div>
        )}
      </section>

      <section aria-labelledby="ws-catalog" className="flex flex-col gap-3">
        <h2 id="ws-catalog" className="text-lg font-semibold">
          Build a facility
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
                </div>
              </div>

              <dl className="grid grid-cols-3 gap-2 rounded-lg bg-default/50 p-2.5 text-xs">
                <div>
                  <dt className="text-muted">Capacity</dt>
                  <dd className="font-semibold tabular-nums">{c.capacity}/day</dd>
                </div>
                <div>
                  <dt className="text-muted">Visitor draw</dt>
                  <dd className="font-semibold tabular-nums">+{c.visitorDraw}/day</dd>
                </div>
                <div>
                  <dt className="text-muted">Upkeep</dt>
                  <dd className="font-semibold tabular-nums">{c.upkeepText}/day</dd>
                </div>
              </dl>

              <div className="flex items-center justify-between gap-2">
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
