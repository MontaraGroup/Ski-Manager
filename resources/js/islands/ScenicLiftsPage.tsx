import { useState } from 'react';
import { Button, Chip } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput, Empty, Figure, Page, Panel, PostButton } from '@/components/Page';
import { Select } from '@/components/Select';
import { Slider } from '@/components/Slider';

export type ScenicLiftUpgrade = {
  key: string;
  name: string;
  icon: string;
};

export type ScenicLiftItem = {
  id: number;
  name: string;
  subtype: string;
  subtypeLabel: string;
  lengthText: string;
  sector: number;
  status: string;
  condition_pct: number;
  price: number;
  priceText: string;
  riders: number;
  todayRevenue: number;
  todayRevenueText: string;
  upgrades: ScenicLiftUpgrade[];
  installedKeys: string[];
};

export type AvailableLiftItem = {
  id: number;
  name: string;
  subtypeLabel: string;
  lengthText: string;
};

export type ScenicUpgradeOption = {
  key: string;
  name: string;
  icon: string;
  cost: number;
  costText: string;
  revenueBoost: number;
  revenueBoostText: string;
  desc: string;
};

export type ScenicLiftsPageProps = {
  stats: {
    scenicCount: number;
    totalLifts: number;
    summerRevenueText: string;
    seasonRevenueText: string;
    isSummer: boolean;
    seasonDay: number;
    seasonLength: number;
    winterSharePct: number;
  };
  scenicLifts: ScenicLiftItem[];
  availableLifts: AvailableLiftItem[];
  upgrades: ScenicUpgradeOption[];
  rules: {
    minPrice: number;
    maxPrice: number;
    refPrice: number;
    refPriceText: string;
    baseRiders: number;
    winterSharePct: number;
  };
};

export default function ScenicLiftsPage({
  stats,
  scenicLifts,
  availableLifts,
  upgrades,
  rules,
}: ScenicLiftsPageProps) {
  return (
    <Page
      title="Scenic Lifts & Sightseeing"
      icon="fa-camera"
      iconTone="text-accent"
      subtitle="Turn your lifts into tourist attractions: set prices, add upgrades, earn year-round"
      width="max-w-6xl"
    >
      {/* 4 Summary stats */}
      <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
        <Figure
          label="Scenic lifts"
          value={stats.scenicCount}
          note={`of ${stats.totalLifts} total lifts`}
        />
        <Figure
          label="Summer revenue"
          value={stats.summerRevenueText}
          tone="text-success"
          note="per summer day"
        />
        <Figure
          label="Current earning"
          value={stats.seasonRevenueText}
          tone={stats.isSummer ? 'text-success' : 'text-warning'}
          note={
            stats.isSummer
              ? 'Summer: full tourist numbers'
              : `Winter: ${stats.winterSharePct}% tourist rate`
          }
        />
        <Figure
          label="Season"
          value={stats.isSummer ? 'Summer' : 'Winter'}
          note={`Day ${stats.seasonDay} / ${stats.seasonLength}`}
        />
      </div>

      {/* Seasonal notice */}
      <div
        className={`flex items-center gap-2 rounded-xl border p-4 text-sm ${
          stats.isSummer
            ? 'border-success/40 bg-surface text-foreground'
            : 'border-border bg-surface text-foreground'
        }`}
      >
        <Icon
          name={stats.isSummer ? 'fa-sun' : 'fa-snowflake'}
          className={stats.isSummer ? 'text-warning shrink-0' : 'text-accent shrink-0'}
        />
        <span>
          {stats.isSummer
            ? 'Summer season: your scenic lifts are earning full revenue from sightseeing tourists.'
            : `Winter season: scenic lifts earn ${stats.winterSharePct}% of the summer rate. Full tourist numbers return in summer.`}
        </span>
      </div>

      {/* Active scenic lifts */}
      <section className="space-y-3">
        <h2 className="text-lg font-bold text-foreground flex items-center gap-2">
          <Icon name="fa-mountain-sun" className="text-success text-base" />
          Your scenic lifts
        </h2>

        {scenicLifts.length === 0 ? (
          <Empty icon="fa-camera" title="No scenic lifts yet">
            Designate an open lift below to start earning from sightseeing tourists.
          </Empty>
        ) : (
          <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
            {scenicLifts.map((lift) => (
              <ScenicLiftCard key={lift.id} lift={lift} rules={rules} />
            ))}
          </div>
        )}
      </section>

      {/* Available lifts to designate */}
      {availableLifts.length > 0 && (
        <section className="space-y-3 pt-2">
          <h2 className="text-lg font-bold text-foreground flex items-center gap-2">
            <Icon name="fa-plus-circle" className="text-accent text-base" />
            Add scenic lift
          </h2>

          <div className="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
            {availableLifts.map((al) => (
              <div
                key={al.id}
                className="flex flex-col justify-between rounded-xl border border-border bg-surface p-4 gap-3"
              >
                <div className="flex items-center gap-3">
                  <div className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-default">
                    <Icon name="fa-cable-car" className="text-base text-muted" />
                  </div>
                  <div className="min-w-0">
                    <div className="font-semibold text-sm truncate text-foreground">{al.name}</div>
                    <div className="text-xs text-muted">
                      {al.subtypeLabel} · {al.lengthText}
                    </div>
                  </div>
                </div>

                <form action="/scenic-lifts/designate" method="post" className="space-y-2 pt-1">
                  <CsrfInput />
                  <input type="hidden" name="item_id" value={al.id} />
                  <div className="flex items-center gap-2 text-xs">
                    <span className="text-muted">Starting price:</span>
                    <Select
                      name="ticket_price"
                      defaultValue={String(rules.refPrice)}
                      aria-label="Starting price"
                      options={[
                        { id: '15', label: '15 € per ride' },
                        { id: '25', label: '25 € per ride' },
                        { id: '35', label: '35 € per ride' },
                        { id: '50', label: '50 € per ride' },
                      ]}
                      className="flex-1"
                    />
                  </div>
                  <Button type="submit" variant="primary" size="sm" className="w-full">
                    <Icon name="fa-camera" className="mr-1 text-xs" />
                    Make scenic
                  </Button>
                </form>
              </div>
            ))}
          </div>
        </section>
      )}

      {/* Upgrades section */}
      <section className="space-y-3 pt-2">
        <div>
          <h2 className="text-lg font-bold text-foreground flex items-center gap-2">
            <Icon name="fa-sparkles" className="text-warning text-base" />
            Available upgrades
          </h2>
          <p className="text-xs text-muted mt-0.5">
            Enhance your scenic lifts with premium attractions to boost daily revenue.
          </p>
        </div>

        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
          {upgrades.map((up) => (
            <UpgradeCard key={up.key} upgrade={up} scenicLifts={scenicLifts} />
          ))}
        </div>
      </section>

      {/* Rules explanation details */}
      <details className="rounded-xl border border-border bg-surface">
        <summary className="p-4 cursor-pointer font-semibold text-sm select-none">How scenic lifts work</summary>
        <div className="px-4 pb-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-muted">
          <ul className="space-y-1.5 list-disc pl-5">
            <li>
              <span className="font-medium text-foreground">Summer:</span> Full revenue from sightseeing tourists.
            </li>
            <li>
              <span className="font-medium text-foreground">Winter:</span> {rules.winterSharePct}% of the summer
              tourists still ride for the views.
            </li>
            <li>
              Set ticket prices between {rules.minPrice} € and {rules.maxPrice} € per ride.
            </li>
          </ul>
          <ul className="space-y-1.5 list-disc pl-5">
            <li>Revenue equals riders × price: higher ticket prices bring fewer riders.</li>
            <li>Only open lifts earn scenic revenue; they continue wearing and burning operational cost.</li>
            <li>Each upgrade adds a fixed daily bonus and can be purchased once per lift.</li>
          </ul>
        </div>
      </details>
    </Page>
  );
}

function ScenicLiftCard({ lift, rules }: { lift: ScenicLiftItem; rules: ScenicLiftsPageProps['rules'] }) {
  const [sliderPrice, setSliderPrice] = useState<number>(lift.price);

  return (
    <div className="flex flex-col justify-between rounded-xl border border-border bg-surface p-4 gap-3">
      <div>
        <div className="flex items-start justify-between gap-2">
          <div className="flex items-center gap-2.5 min-w-0">
            <div className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-default">
              <Icon name="fa-cable-car" className="text-base text-accent" />
            </div>
            <div className="min-w-0">
              <h3 className="font-bold text-sm truncate text-foreground">{lift.name}</h3>
              <div className="text-xs text-muted">
                {lift.subtypeLabel} · {lift.lengthText} · Sector {lift.sector}
              </div>
            </div>
          </div>

          <PostButton
            action={`/scenic-lifts/remove/${lift.id}`}
            variant="ghost"
            size="sm"
            className="text-danger hover:bg-danger/10"
            confirm={{
              title: `Remove scenic designation from ${lift.name}?`,
              body: 'This lift will no longer sell sightseeing tickets to tourists.',
              confirmLabel: 'Remove scenic',
            }}
          >
            <Icon name="fa-xmark" className="text-xs" />
          </PostButton>
        </div>

        {/* Ticket price range adjustment */}
        <div className="mt-3 rounded-lg bg-default p-3 space-y-2">
          <form action={`/scenic-lifts/update-price/${lift.id}`} method="post" className="space-y-2">
            <CsrfInput />
            <div className="flex items-center justify-between text-xs">
              <label htmlFor={`price-${lift.id}`} className="text-muted">
                Ticket price per ride:
              </label>
              <span className="font-bold text-foreground">{sliderPrice} €</span>
            </div>
            <div className="flex items-center gap-3">
              <Slider
                id={`price-${lift.id}`}
                name="price"
                minValue={rules.minPrice}
                maxValue={rules.maxPrice}
                step={1}
                value={sliderPrice}
                onChange={(val) => setSliderPrice(typeof val === 'number' ? val : (Array.isArray(val) ? val[0] : rules.minPrice))}
                aria-label="Ticket price per ride"
                showOutput={false}
                className="flex-1"
              />
              <Button type="submit" variant="primary" size="sm" isIconOnly aria-label="Save ticket price">
                <Icon name="fa-check" />
              </Button>
            </div>
          </form>
          <div className="text-xs text-muted">
            At {rules.refPriceText} about {rules.baseRiders} tourists ride on a summer day; higher prices bring fewer
            riders.
          </div>
        </div>

        {/* Performance stats */}
        <div className="grid grid-cols-3 gap-2 mt-3 text-center text-xs">
          <div className="rounded-lg bg-default p-2">
            <div className="font-bold text-success text-sm">{lift.todayRevenueText}</div>
            <div className="text-muted">Today ({lift.riders} riders)</div>
          </div>
          <div className="rounded-lg bg-default p-2">
            <div className="font-bold text-foreground text-sm">{lift.condition_pct}%</div>
            <div className="text-muted">Condition</div>
          </div>
          <div className="rounded-lg bg-default p-2">
            <div
              className={`font-bold text-sm ${lift.status === 'open' ? 'text-success' : 'text-danger'}`}
            >
              {lift.status === 'open' ? 'Open' : 'Closed'}
            </div>
            <div className="text-muted">Status</div>
          </div>
        </div>

        {/* Installed upgrades badges */}
        {lift.upgrades.length > 0 && (
          <div className="flex flex-wrap items-center gap-1.5 pt-2.5 mt-3 border-t border-border">
            {lift.upgrades.map((u) => (
              <Chip key={u.key} variant="soft" color="warning" size="sm">
                <Icon name={u.icon} className="mr-1 text-xs" />
                {u.name}
              </Chip>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}

function UpgradeCard({
  upgrade,
  scenicLifts,
}: {
  upgrade: ScenicUpgradeOption;
  scenicLifts: ScenicLiftItem[];
}) {
  const [selectedLiftId, setSelectedLiftId] = useState<number>(scenicLifts[0]?.id ?? 0);

  const selectedLift = scenicLifts.find((l) => l.id === selectedLiftId) ?? scenicLifts[0];
  const isInstalled = selectedLift?.installedKeys.includes(upgrade.key);

  return (
    <div className="flex flex-col justify-between rounded-xl border border-border bg-surface p-3.5 gap-2.5">
      <div>
        <div className="flex items-center gap-2 mb-1.5">
          <Icon name={upgrade.icon} className="text-warning text-sm" />
          <span className="font-semibold text-sm text-foreground">{upgrade.name}</span>
        </div>
        <p className="text-xs text-muted line-clamp-2">{upgrade.desc}</p>
        <div className="text-xs text-muted mt-2">
          Cost: <span className="font-semibold text-foreground">{upgrade.costText}</span> ·{' '}
          <span className="text-success font-semibold">+{upgrade.revenueBoostText} / day</span>
        </div>
      </div>

      <div className="pt-2 border-t border-border mt-auto">
        {scenicLifts.length === 0 ? (
          <Button variant="secondary" size="sm" isDisabled className="w-full">
            Need scenic lift first
          </Button>
        ) : (
          <form action="/scenic-lifts/upgrade" method="post" className="space-y-2">
            <CsrfInput />
            <input type="hidden" name="upgrade" value={upgrade.key} />
            {scenicLifts.length > 1 ? (
              <Select
                name="item_id"
                value={selectedLiftId}
                onChange={(val) => setSelectedLiftId(Number(val))}
                aria-label="Select scenic lift"
                options={scenicLifts.map((sl) => ({
                  id: sl.id,
                  label: `${sl.name}${sl.installedKeys.includes(upgrade.key) ? ' (Installed)' : ''}`,
                }))}
              />
            ) : (
              <input type="hidden" name="item_id" value={scenicLifts[0].id} />
            )}
            <Button
              type="submit"
              variant={isInstalled ? 'secondary' : 'primary'}
              size="sm"
              isDisabled={isInstalled}
              className="w-full"
            >
              <Icon name="fa-arrow-up" className="mr-1 text-xs" />
              {isInstalled ? 'Already installed' : `Buy ${upgrade.costText}`}
            </Button>
          </form>
        )}
      </div>
    </div>
  );
}
