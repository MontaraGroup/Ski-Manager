import { Chip } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Empty, Figure, Page, Panel, PostButton } from '@/components/Page';

export type RealEstateProperty = {
  id: number;
  name: string;
  level: number;
  condition_pct: number;
  state: 'sold' | 'building' | 'broken' | 'leased' | 'vacant';
  build_days_left: number;
  rent: number;
  rentText: string;
  upkeep: number;
  upkeepText: string;
  sale_now: number;
  saleNowText: string;
  sale_price: number;
  salePriceText: string;
  fee: number;
  feeText: string;
  repair: number;
  repairText: string;
  canRepair: boolean;
  gain: number;
  gainText: string;
  project: {
    name: string;
    icon: string;
    cost: number;
    costText: string;
  };
};

export type RealEstateProject = {
  level: number;
  name: string;
  icon: string;
  cost: number;
  costText: string;
  days: number;
  rent_now: number;
  rentNowText: string;
  net_now: number;
  netNowText: string;
  payback: number | null;
  paybackText: string;
  sale_now: number;
  saleNowText: string;
  gain: number;
  gainText: string;
  gainPct: number;
  canAfford: boolean;
};

export type RealEstatePageProps = {
  market: {
    value: number;
    valueText: string;
    parts: {
      base: string;
      rating: string;
      slopes: string;
      condition: string;
    };
  };
  plots: {
    total: number;
    used: number;
    free: number;
    nextPlotAt: number | null;
    maxPlots: number;
  };
  stats: {
    stars: number;
    openSlopes: number;
    avgCond: number;
    rentPerDayText: string;
    upkeepPerDayText: string;
    feesPerDayText: string;
    salesTotalText: string;
    salesCount: number;
    isWinter: boolean;
    cashText: string;
    cashRaw: number;
  };
  projects: RealEstateProject[];
  properties: RealEstateProperty[];
};

export default function RealEstatePage({
  market,
  plots,
  stats,
  projects,
  properties,
}: RealEstatePageProps) {
  const badgeColor = (state: RealEstateProperty['state']) => {
    switch (state) {
      case 'leased':
        return 'success';
      case 'building':
        return 'warning';
      case 'broken':
        return 'danger';
      case 'sold':
        return 'accent';
      default:
        return 'default';
    }
  };

  const badgeLabel = (p: RealEstateProperty) => {
    if (p.state === 'building') return `${p.build_days_left} days left`;
    switch (p.state) {
      case 'leased':
        return 'Leased';
      case 'vacant':
        return 'Vacant';
      case 'sold':
        return 'Sold';
      case 'broken':
        return 'Needs repair';
    }
  };

  return (
    <Page
      title="Real estate"
      icon="fa-city"
      iconTone="text-success"
      subtitle="Develop properties on your plots, lease them for rent or sell them when the market is high"
      width="max-w-6xl"
    >
      {/* Market + plots overview */}
      <div className="grid grid-cols-1 gap-3 lg:grid-cols-3">
        {/* Market card (2 cols) */}
        <Panel className="lg:col-span-2 space-y-3">
          <div className="flex flex-wrap items-start justify-between gap-4">
            <div>
              <div className="text-xs text-muted">Property market index</div>
              <div
                className={`text-4xl font-bold tabular-nums ${
                  market.value >= 1.2
                    ? 'text-success'
                    : market.value >= 0.9
                    ? 'text-foreground'
                    : 'text-warning'
                }`}
              >
                {market.valueText}
              </div>
              <div className="text-xs text-muted mt-0.5">Multiplies all resort rents and property valuations</div>
            </div>

            <dl className="grid grid-cols-2 gap-x-6 gap-y-1 text-xs">
              <dt className="text-muted">Base valuation</dt>
              <dd className="text-right font-mono font-medium tabular-nums">{market.parts.base}</dd>
              <dt className="text-muted">
                Rating {stats.stars > 0 ? '★'.repeat(stats.stars) : '–'}
              </dt>
              <dd className="text-right font-mono font-medium tabular-nums">{market.parts.rating}</dd>
              <dt className="text-muted">{stats.openSlopes} open slopes</dt>
              <dd className="text-right font-mono font-medium tabular-nums">{market.parts.slopes}</dd>
              <dt className="text-muted">Slope condition ({stats.avgCond}%)</dt>
              <dd className="text-right font-mono font-medium tabular-nums">{market.parts.condition}</dd>
            </dl>
          </div>

          <p className="text-xs text-muted border-t border-border pt-2.5">
            Market multiplier rises with star rating (+0.15/star), open slopes (+0.02 each, up to 15), and slope condition above 60%. Falls when trails wear down. Range: 0.50× to 1.80×.
          </p>
        </Panel>

        {/* Plots card (1 col) */}
        <Panel className="flex flex-col justify-between space-y-3">
          <div>
            <div className="text-xs text-muted">Resort land plots</div>
            <div className="text-3xl font-bold tabular-nums">
              {plots.used}{' '}
              <span className="text-lg font-normal text-muted">of {plots.total} used</span>
            </div>

            {/* Visual plot segments */}
            <div className="flex gap-1 mt-3" aria-hidden="true">
              {Array.from({ length: plots.total }).map((_, i) => (
                <span
                  key={i}
                  className={`h-2 flex-1 rounded-sm ${
                    i < plots.used ? 'bg-success' : 'bg-default'
                  }`}
                />
              ))}
            </div>
          </div>

          <p className="text-xs text-muted">
            {plots.nextPlotAt !== null
              ? `Next plot unlocks at ${plots.nextPlotAt} open slopes.`
              : `All ${plots.maxPlots} plots unlocked.`}{' '}
            Sold units permanently retain their assigned plot.
          </p>
        </Panel>
      </div>

      {/* 4 Telemetry figures */}
      <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <Figure
          label="Rent per day"
          value={stats.rentPerDayText}
          tone="text-success"
          note={stats.isWinter ? 'Winter peak rate' : 'Summer: 60% of winter'}
        />
        <Figure
          label="Upkeep per day"
          value={stats.upkeepPerDayText}
          tone="text-danger"
          note="Active leased units only"
        />
        <Figure
          label="Management fees"
          value={stats.feesPerDayText}
          tone="text-success"
          note="0.1% daily fee from sold units"
        />
        <Figure
          label="Sold this season"
          value={stats.salesTotalText}
          note={`From ${stats.salesCount} sold properties`}
        />
      </div>

      {/* Develop new properties catalog */}
      <Panel title="Develop real estate">
        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
          {projects.map((pr) => (
            <div
              key={pr.level}
              className="flex flex-col justify-between rounded-lg border border-border bg-surface-secondary/40 p-4"
            >
              <div>
                <div className="flex items-center gap-2 mb-2 font-semibold text-sm">
                  <Icon name={pr.icon || 'fa-building'} className="text-success text-base" />
                  <span>{pr.name}</span>
                </div>

                <dl className="grid grid-cols-2 gap-y-1.5 text-xs text-muted mb-4">
                  <dt>Build investment</dt>
                  <dd className="text-right font-medium text-foreground tabular-nums">
                    {pr.costText} · {pr.days}d
                  </dd>
                  <dt>Current rent</dt>
                  <dd className="text-right font-medium text-foreground tabular-nums">
                    {pr.rentNowText}/day
                  </dd>
                  <dt>Net operating</dt>
                  <dd
                    className={`text-right font-medium tabular-nums ${
                      pr.net_now > 0 ? 'text-success' : 'text-danger'
                    }`}
                  >
                    {pr.netNowText}/day
                  </dd>
                  <dt>Payback period</dt>
                  <dd className="text-right font-medium text-foreground tabular-nums">
                    {pr.paybackText}
                  </dd>
                  <dt>Estimated sale price</dt>
                  <dd
                    className={`text-right font-medium tabular-nums ${
                      pr.gain >= 0 ? 'text-success' : 'text-warning'
                    }`}
                  >
                    {pr.saleNowText} ({pr.gain >= 0 ? '+' : ''}
                    {pr.gainPct}%)
                  </dd>
                </dl>
              </div>

              <PostButton
                action="/real-estate/develop"
                data={{ level: pr.level }}
                variant="primary"
                size="sm"
                className="w-full"
                disabled={plots.free <= 0 || !pr.canAfford}
                confirm={{
                  title: `Develop ${pr.name}`,
                  body: `Invest ${pr.costText} to start construction of a new ${pr.name}? Completion takes ${pr.days} game days.`,
                  confirmLabel: `Build for ${pr.costText}`,
                }}
              >
                {plots.free <= 0
                  ? 'No plot free'
                  : !pr.canAfford
                  ? `Need ${pr.costText}`
                  : `Build · ${pr.costText}`}
              </PostButton>
            </div>
          ))}
        </div>
      </Panel>

      {/* Property portfolio */}
      <Panel title="Your properties portfolio">
        {properties.length === 0 ? (
          <Empty icon="fa-city" title="No properties built yet">
            Develop a chalet or apartment complex on your available land plots to generate daily rental income or secure lump-sum sale capital.
          </Empty>
        ) : (
          <div className="space-y-3">
            {properties.map((p) => (
              <div
                key={p.id}
                className="flex flex-col gap-3 rounded-lg border border-border bg-surface-secondary/40 p-4 sm:flex-row sm:items-center sm:justify-between"
              >
                <div className="flex items-start gap-3 flex-1 min-w-0">
                  <div
                    className={`flex size-10 shrink-0 items-center justify-center rounded-lg border ${
                      p.state === 'sold'
                        ? 'border-accent/30 bg-accent-soft text-accent-soft-foreground'
                        : 'border-success/30 bg-success/10 text-success'
                    }`}
                  >
                    <Icon name={p.project.icon || 'fa-building'} className="text-lg" />
                  </div>

                  <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2">
                      <span className="font-semibold text-sm">{p.name}</span>
                      <Chip size="sm" variant="soft" color={badgeColor(p.state)}>
                        {badgeLabel(p)}
                      </Chip>
                    </div>

                    <div className="mt-0.5 text-xs text-muted">
                      {p.state === 'sold' && (
                        <span>
                          Sold for <span className="tabular-nums font-medium text-foreground">{p.salePriceText}</span> · Earning <span className="tabular-nums font-medium text-success">{p.feeText}</span>/day management fee
                        </span>
                      )}
                      {p.state === 'building' && (
                        <span>Under construction. Leased out automatically upon handover.</span>
                      )}
                      {p.state === 'leased' && (
                        <span>
                          Rent <span className="tabular-nums font-medium text-success">{p.rentText}</span>/day · Upkeep <span className="tabular-nums font-medium text-danger">{p.upkeepText}</span>/day · Condition <span className="tabular-nums font-medium text-foreground">{p.condition_pct}%</span>
                        </span>
                      )}
                      {p.state === 'broken' && (
                        <span className="text-danger">
                          Worn out. No rent collected until repaired to operating condition.
                        </span>
                      )}
                      {p.state === 'vacant' && (
                        <span>
                          Vacant: zero rent, zero upkeep · Condition <span className="tabular-nums font-medium text-foreground">{p.condition_pct}%</span>
                        </span>
                      )}
                    </div>
                  </div>
                </div>

                {/* Property actions */}
                {p.state !== 'sold' && p.state !== 'building' && (
                  <div className="flex flex-wrap items-center gap-2 self-end sm:self-center">
                    {p.state !== 'broken' && (
                      <>
                        <PostButton
                          action={`/real-estate/sell/${p.id}`}
                          variant="secondary"
                          size="sm"
                          confirm={{
                            title: `Sell ${p.name}`,
                            body: `Sell ${p.name} on the open market for ${p.saleNowText}? The unit will remain on its plot and generate ${p.feeText}/day in ongoing management fees.`,
                            confirmLabel: `Sell for ${p.saleNowText}`,
                          }}
                        >
                          <Icon name="fa-handshake" className="mr-1 text-xs" />
                          Sell · {p.saleNowText}
                        </PostButton>

                        <PostButton
                          action={`/real-estate/toggle/${p.id}`}
                          variant="tertiary"
                          size="sm"
                        >
                          {p.state === 'leased' ? 'Make vacant' : 'Lease out'}
                        </PostButton>
                      </>
                    )}

                    {p.condition_pct < 100 && (
                      <PostButton
                        action={`/real-estate/repair/${p.id}`}
                        variant="primary"
                        size="sm"
                        disabled={!p.canRepair}
                        confirm={{
                          title: `Repair ${p.name}`,
                          body: `Restore ${p.name} back to 100% condition for ${p.repairText}?`,
                          confirmLabel: `Repair for ${p.repairText}`,
                        }}
                      >
                        <Icon name="fa-wrench" className="mr-1 text-xs" />
                        Repair · {p.repairText}
                      </PostButton>
                    )}
                  </div>
                )}
              </div>
            ))}
          </div>
        )}
      </Panel>

      {/* How real estate works info panel */}
      <Panel title="How real estate works">
        <ul className="space-y-1.5 text-xs text-muted list-disc pl-4">
          <li>Resorts start with 2 base plots and unlock an additional plot for every 2 open slopes (up to 12 max).</li>
          <li>Construction takes 3 to 8 game days. Once finished, properties are automatically leased.</li>
          <li>Leased properties generate daily rent multiplied by the market index (60% in summer) minus daily upkeep. Rents decrease as structure condition wears down.</li>
          <li>Selling a property grants an immediate payout based on cost, premium, and current market multiplier, plus a permanent 0.1% daily management fee.</li>
          <li>Vacant properties incur no upkeep costs but earn no rental revenue.</li>
        </ul>
      </Panel>
    </Page>
  );
}
