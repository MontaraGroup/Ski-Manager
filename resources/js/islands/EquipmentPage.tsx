import { useState } from 'react';
import { Button, Chip } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput, Empty, Figure, Page, Panel, PostButton } from '@/components/Page';

export type EquipmentItem = {
  id: number;
  name: string;
  brand: string;
  equipment_type: 'groomer' | 'snowmaker';
  capacity: number;
  condition_pct: number;
  status: string;
  state: 'running' | 'no_operator' | 'idle' | 'off' | 'broken';
  stateLabel: string;
  stateTone: 'success' | 'warning' | 'danger' | 'neutral';
  crew: string | null;
  fuelCostText: string;
  repairCost: number;
  repairCostText: string;
  resale: number;
  resaleText: string;
  dailyCostText: string;
};

export type CatalogItem = {
  model_key: string;
  name: string;
  brand: string;
  description: string;
  equipment_type: 'groomer' | 'snowmaker';
  capacity: number;
  owned: number;
  costText: string;
  price: number;
  priceText: string;
  runningCostText: string;
  maxTempText: string;
  energyKwhText: string;
  waterLitersText: string;
  costPerSlopeText: string;
};

export type EquipmentPageProps = {
  cash: number;
  cashText: string;
  cashNegative: boolean;
  discount: number;
  stats: {
    slopes: number;
    groomCapacity: number;
    groomers: number;
    operators: number;
    idleGroomers: number;
    guns: number;
    gunCapacity: number;
    runningGuns: number;
    dailyCostText: string;
    fleetValueText: string;
    equipmentCount: number;
  };
  equipment: EquipmentItem[];
  catalog: {
    groomer: CatalogItem[];
    snowmaker: CatalogItem[];
  };
  rules: {
    repairSharePct: number;
    resaleSharePct: number;
  };
};

export default function EquipmentPage({
  cash,
  cashText,
  cashNegative,
  discount,
  stats,
  equipment,
  catalog,
  rules,
}: EquipmentPageProps) {
  const [shopTab, setShopTab] = useState<'groomer' | 'snowmaker'>('groomer');

  const groomers = equipment.filter((e) => e.equipment_type === 'groomer');
  const snowmakers = equipment.filter((e) => e.equipment_type === 'snowmaker');

  return (
    <Page
      title="Equipment"
      icon="fa-shop"
      iconTone="text-accent"
      subtitle="Groomers restore your slopes each night; snow guns protect them when the snow is thin"
      width="max-w-6xl"
      actions={
        <div className="text-sm font-medium">
          Cash <span className={`font-semibold ${cashNegative ? 'text-danger' : 'text-success'}`}>{cashText}</span>
        </div>
      }
    >
      {/* Summary figures */}
      <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <a
          href="/grooming"
          className="flex flex-col justify-between rounded-xl border border-border bg-surface p-4 transition-colors hover:border-accent/40"
        >
          <div className="text-xs text-muted">Grooming tonight</div>
          <div
            className={`text-2xl font-bold ${stats.slopes > 0 && stats.groomCapacity < stats.slopes ? 'text-warning' : 'text-foreground'}`}
          >
            {stats.groomCapacity} <span className="text-sm font-normal text-muted">of {stats.slopes} slopes</span>
          </div>
          <div className="text-xs text-muted">
            {stats.operators} {stats.operators === 1 ? 'operator' : 'operators'} · {stats.groomers}{' '}
            {stats.groomers === 1 ? 'groomer' : 'groomers'}
          </div>
        </a>

        <a
          href="/snowmaking"
          className="flex flex-col justify-between rounded-xl border border-border bg-surface p-4 transition-colors hover:border-accent/40"
        >
          <div className="text-xs text-muted">Snow guns today</div>
          <div className="text-2xl font-bold text-foreground">
            {stats.gunCapacity} <span className="text-sm font-normal text-muted">slopes covered</span>
          </div>
          <div className="text-xs text-muted">
            {stats.runningGuns} of {stats.guns} running
          </div>
        </a>

        <div className="flex flex-col justify-between rounded-xl border border-border bg-surface p-4">
          <div className="text-xs text-muted">Running cost</div>
          <div className="text-2xl font-bold text-foreground">
            {stats.dailyCostText}
            <span className="text-sm font-normal text-muted"> / day</span>
          </div>
          <div className="text-xs text-muted">Fuel for machines that run</div>
        </div>

        <div className="flex flex-col justify-between rounded-xl border border-border bg-surface p-4">
          <div className="text-xs text-muted">Fleet resale value</div>
          <div className="text-2xl font-bold text-foreground">{stats.fleetValueText}</div>
          <div className="text-xs text-muted">
            {stats.equipmentCount} {stats.equipmentCount === 1 ? 'machine' : 'machines'}
          </div>
        </div>
      </div>

      {/* Warnings & Notices */}
      {stats.idleGroomers > 0 && (
        <div className="flex items-center gap-2 rounded-xl border border-warning/40 bg-surface p-4 text-sm text-foreground">
          <Icon name="fa-user-slash" className="text-warning shrink-0" />
          <span>
            {stats.idleGroomers} {stats.idleGroomers === 1 ? 'groomer is' : 'groomers are'} switched on with no operator,
            so {stats.idleGroomers === 1 ? 'it stays' : 'they stay'} parked.{' '}
            <a href="/staff/hire" className="text-link font-medium hover:underline">
              Hire a groomer operator
            </a>{' '}
            for each machine.
          </span>
        </div>
      )}

      {stats.slopes > 0 && stats.groomers === 0 && (
        <div className="flex items-center gap-2 rounded-xl border border-border bg-surface p-4 text-sm text-foreground">
          <Icon name="fa-circle-info" className="text-link shrink-0" />
          <span>
            Slopes wear down every day. A groomer with an operator restores the worst ones each night.
          </span>
        </div>
      )}

      {/* Fleet: Groomers */}
      {groomers.length > 0 && (
        <section className="space-y-3">
          <div className="flex items-center justify-between">
            <h2 className="text-lg font-bold text-foreground">Your groomers</h2>
            <a href="/grooming" className="inline-flex min-h-11 items-center text-sm font-medium text-link hover:underline sm:min-h-0">
              Grooming →
            </a>
          </div>
          <div className="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
            {groomers.map((eq) => (
              <EquipmentCard key={eq.id} eq={eq} cash={cash} />
            ))}
          </div>
        </section>
      )}

      {/* Fleet: Snow guns */}
      {snowmakers.length > 0 && (
        <section className="space-y-3">
          <div className="flex items-center justify-between">
            <h2 className="text-lg font-bold text-foreground">Your snow guns</h2>
            <a href="/snowmaking" className="text-sm font-medium text-link hover:underline">
              Snowmaking →
            </a>
          </div>
          <div className="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
            {snowmakers.map((eq) => (
              <EquipmentCard key={eq.id} eq={eq} cash={cash} />
            ))}
          </div>
        </section>
      )}

      {/* Shop Section */}
      <section className="space-y-4">
        <div className="flex flex-wrap items-center justify-between gap-2">
          <div className="flex items-center gap-2">
            <h2 className="text-lg font-bold text-foreground">Shop</h2>
            {discount > 0 && (
              <Chip variant="soft" color="success" size="sm">
                Alliance −{discount}%
              </Chip>
            )}
          </div>
          <div className="flex items-center gap-1.5">
            <Button
              variant={shopTab === 'groomer' ? 'primary' : 'secondary'}
              size="sm"
              onPress={() => setShopTab('groomer')}
            >
              Groomers ({catalog.groomer.length})
            </Button>
            <Button
              variant={shopTab === 'snowmaker' ? 'primary' : 'secondary'}
              size="sm"
              onPress={() => setShopTab('snowmaker')}
            >
              Snow guns ({catalog.snowmaker.length})
            </Button>
          </div>
        </div>

        <div className="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
          {catalog[shopTab].map((c) => {
            const canBuy = cash >= c.price;
            return (
              <div
                key={c.model_key}
                className="flex flex-col justify-between rounded-xl border border-border bg-surface p-4 gap-3"
              >
                <div>
                  <div className="flex items-center justify-between gap-2 mb-1">
                    <span className="text-xs font-semibold text-muted">{c.brand}</span>
                    {c.owned > 0 && (
                      <Chip variant="soft" size="sm">
                        You own {c.owned}
                      </Chip>
                    )}
                  </div>
                  <h3 className="font-bold text-base text-foreground">{c.name}</h3>
                  <p className="text-xs text-muted line-clamp-2 mt-1">{c.description}</p>
                </div>

                <dl className="grid grid-cols-2 gap-2 text-xs">
                  <div className="rounded-lg bg-default p-2 text-center">
                    <dt className="text-muted">{c.equipment_type === 'groomer' ? 'Grooms per night' : 'Covers'}</dt>
                    <dd className="font-semibold text-foreground">
                      {c.capacity} {c.capacity === 1 ? 'slope' : 'slopes'}
                    </dd>
                  </div>
                  <div className="rounded-lg bg-default p-2 text-center">
                    <dt className="text-muted">Running cost</dt>
                    <dd className="font-semibold text-foreground">{c.runningCostText} / day</dd>
                  </div>
                  {c.equipment_type === 'snowmaker' ? (
                    <>
                      <div className="rounded-lg bg-default p-2 text-center">
                        <dt className="text-muted">Works at</dt>
                        <dd className="font-semibold text-foreground">{c.maxTempText}</dd>
                      </div>
                      <div className="rounded-lg bg-default p-2 text-center">
                        <dt className="text-muted">Uses per day</dt>
                        <dd className="font-semibold text-foreground">
                          {c.energyKwhText} · {c.waterLitersText}
                        </dd>
                      </div>
                    </>
                  ) : (
                    <>
                      <div className="rounded-lg bg-default p-2 text-center">
                        <dt className="text-muted">Needs</dt>
                        <dd className="font-semibold text-foreground">1 operator</dd>
                      </div>
                      <div className="rounded-lg bg-default p-2 text-center">
                        <dt className="text-muted">Cost per slope</dt>
                        <dd className="font-semibold text-foreground">{c.costPerSlopeText}</dd>
                      </div>
                    </>
                  )}
                </dl>

                <div className="flex items-center justify-between gap-2 pt-2 border-t border-border mt-auto">
                  <div>
                    {discount > 0 && <div className="text-xs text-muted line-through">{c.costText}</div>}
                    <div className="text-lg font-bold text-foreground">{c.priceText}</div>
                  </div>
                  <form action="/equipment/buy" method="post">
                    <CsrfInput />
                    <input type="hidden" name="model" value={c.model_key} />
                    <Button
                      type="submit"
                      variant="primary"
                      size="sm"
                      isDisabled={!canBuy}
                    >
                      <Icon name="fa-cart-plus" className="mr-1" />
                      {canBuy ? 'Buy' : 'Short cash'}
                    </Button>
                  </form>
                </div>
              </div>
            );
          })}
        </div>
      </section>

      {/* How equipment works collapsible */}
      <details className="rounded-xl border border-border bg-surface">
        <summary className="p-4 cursor-pointer font-semibold text-sm select-none">How equipment works</summary>
        <ul className="px-4 pb-4 text-sm text-muted space-y-1.5 list-disc pl-9">
          <li>New machines arrive switched off. Switch them on here, or on the Grooming and Snowmaking pages.</li>
          <li>
            A groomer runs each night only with an operator (one per machine, biggest machines first) and grooms the worst
            slopes first. Parked groomers cost nothing.
          </li>
          <li>
            A snow gun runs only on days cold enough for its model and covers that many slopes. On warmer days it waits at no
            cost.
          </li>
          <li>
            Running machines cost their running cost each day and wear 1–5% a day; at 0% they break. Repairs cost up to{' '}
            {rules.repairSharePct}% of the price depending on wear, and selling returns up to {rules.resaleSharePct}%
            depending on condition.
          </li>
        </ul>
      </details>
    </Page>
  );
}

function EquipmentCard({ eq, cash }: { eq: EquipmentItem; cash: number }) {
  const isBroken = eq.state === 'broken';
  const cond = eq.condition_pct;

  return (
    <div
      className={`flex flex-col justify-between rounded-xl border bg-surface p-4 gap-2.5 ${
        isBroken ? 'border-danger/40' : 'border-border'
      }`}
    >
      <div className="flex items-start justify-between gap-2">
        <div className="min-w-0">
          <div className="font-semibold text-sm truncate text-foreground">{eq.name}</div>
          <div className="text-xs text-muted">
            {eq.brand} · {eq.equipment_type === 'groomer' ? 'grooms' : 'covers'} {eq.capacity}{' '}
            {eq.capacity === 1 ? 'slope' : 'slopes'} · {eq.fuelCostText} / day running
          </div>
        </div>
        <Chip
          variant="soft"
          size="sm"
          color={
            eq.stateTone === 'success'
              ? 'success'
              : eq.stateTone === 'warning'
                ? 'warning'
                : eq.stateTone === 'danger'
                  ? 'danger'
                  : 'default'
          }
          className="shrink-0"
        >
          {eq.stateLabel}
        </Chip>
      </div>

      {/* Health progress */}
      <div className="flex items-center gap-2">
        <span className="text-xs text-muted w-12">Health</span>
        <div className="relative flex-1 h-1.5 rounded-full bg-default overflow-hidden">
          <div
            className={`h-full rounded-full ${
              cond >= 60 ? 'bg-success' : cond >= 30 ? 'bg-warning' : 'bg-danger'
            }`}
            style={{ width: `${cond}%` }}
          />
        </div>
        <span className="text-xs font-mono w-9 text-right text-foreground">{cond}%</span>
      </div>

      <div className="text-xs text-muted flex items-center gap-1.5">
        <Icon name="fa-user" className="text-xs text-muted" />
        <span className="truncate">
          {eq.crew
            ? eq.crew
            : eq.equipment_type === 'groomer'
              ? 'Any free operator can drive it'
              : 'No crew (optional)'}
        </span>
      </div>

      <div className="flex flex-wrap items-center gap-1.5 pt-2 border-t border-border mt-auto">
        {!isBroken && (
          <PostButton
            action={`/equipment/toggle/${eq.id}`}
            variant={eq.status === 'active' ? 'ghost' : 'secondary'}
            size="sm"
            className="flex-1"
          >
            <Icon name="fa-power-off" className="mr-1 text-xs" />
            {eq.status === 'active' ? 'Switch off' : 'Switch on'}
          </PostButton>
        )}

        {cond < 100 && (
          <PostButton
            action={`/equipment/repair/${eq.id}`}
            variant={isBroken ? 'primary' : 'secondary'}
            size="sm"
            className="flex-1"
            disabled={cash < eq.repairCost}
            confirm={{
              title: `Repair ${eq.name}?`,
              body: `Restore condition to 100% for ${eq.repairCostText}.`,
              confirmLabel: 'Repair',
            }}
          >
            <Icon name="fa-wrench" className="mr-1 text-xs" />
            Repair {eq.repairCostText}
          </PostButton>
        )}

        <PostButton
          action={`/equipment/sell/${eq.id}`}
          variant="ghost"
          size="sm"
          className="text-danger hover:bg-danger/10"
          confirm={{
            title: `Sell ${eq.name}?`,
            body: `You will receive ${eq.resaleText}. This machine will be removed from your fleet.`,
            confirmLabel: 'Sell',
          }}
        >
          <Icon name="fa-coins" className="mr-1 text-xs" />
          Sell {eq.resaleText}
        </PostButton>
      </div>
    </div>
  );
}
