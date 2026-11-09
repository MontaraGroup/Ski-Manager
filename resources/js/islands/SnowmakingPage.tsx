import { useState } from 'react';
import { Button, Chip } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput, Empty, Figure, Page, Panel, PostButton } from '@/components/Page';
import { Select } from '@/components/Select';

export type GunItem = {
  id: number;
  name: string;
  brand: string;
  capacity: number;
  condition_pct: number;
  status: string;
  state: 'running' | 'idle' | 'off' | 'broken';
  stateLabel: string;
  stateTone: 'success' | 'warning' | 'danger' | 'neutral';
  tempLimitText: string;
  operatingCostText: string;
  energyKwhText: string;
  waterLitersText: string;
  hours: number;
  coversNames: string[];
  priority: number;
  crew: string | null;
  crewReliefPct: number;
  repairCost: number;
  repairCostText: string;
  resale: number;
  resaleText: string;
};

export type SlopeItem = {
  id: number;
  name: string;
  difficulty: string;
  status: string;
  condition_pct: number;
  gunId: number | null;
  gunName: string | null;
  isPriority: boolean;
  wearText: string;
};

export type OutlookItem = {
  day: number;
  icon: string;
  tempText: string;
  snowfallText: string | null;
  hoursText: string;
  gunsCanRunText: string | null;
  canRun: boolean;
};

export type SnowmakingPageProps = {
  overview: {
    baseTempText: string;
    nightTempText: string;
    dayTempText: string;
    standardHours: number;
    snowBaseText: string;
    thinBaseThresholdText: string;
    thinBase: boolean;
    coveredCount: number;
    openCount: number;
    runningGunsCount: number;
    totalGunsCount: number;
    capacity: number;
    totalOperatingCostText: string;
    operatingCostGunsText: string;
    energyImportText: string | null;
    waterImportText: string | null;
  };
  outlook: OutlookItem[];
  guns: GunItem[];
  slopes: SlopeItem[];
  crewCount: number;
  utilities: {
    energy: {
      hidden: boolean;
      needText: string;
      totalText: string;
      supplyText: string;
      ok: boolean;
    };
    water: {
      hidden: boolean;
      needText: string;
      supplyText: string;
      ok: boolean;
    };
  };
  rules: {
    thinBaseExtra: number;
    crewWearRelief: number;
    repairSharePct: number;
    resaleSharePct: number;
  };
};

const DIFFICULTY_COLORS: Record<string, string> = {
  green: 'bg-success',
  blue: 'bg-accent',
  red: 'bg-danger',
  black: 'bg-foreground',
  double_black: 'bg-foreground',
};

export default function SnowmakingPage({
  overview,
  outlook,
  guns,
  slopes,
  crewCount,
  utilities,
  rules,
}: SnowmakingPageProps) {
  const uncovered = overview.openCount - overview.coveredCount;

  return (
    <Page
      title="Snowmaking"
      icon="fa-snowflake"
      iconTone="text-accent"
      subtitle="Snow guns keep slopes skiable when the natural base is thin"
      width="max-w-6xl"
      actions={
        <div className="flex flex-wrap items-center gap-2">
          {guns.length > 0 && (
            <>
              <form action="/snowmaking/toggle-all" method="post">
                <CsrfInput />
                <input type="hidden" name="action" value="on" />
                <Button type="submit" variant="primary" size="sm">
                  <Icon name="fa-power-off" className="mr-1 text-xs" />
                  All on
                </Button>
              </form>
              <form action="/snowmaking/toggle-all" method="post">
                <CsrfInput />
                <input type="hidden" name="action" value="off" />
                <Button type="submit" variant="secondary" size="sm">
                  <Icon name="fa-power-off" className="mr-1 text-xs" />
                  All off
                </Button>
              </form>
            </>
          )}
          <a
            href="/equipment"
            className="inline-flex min-h-11 items-center gap-1.5 rounded-lg border border-border px-3 text-sm font-medium hover:bg-default"
          >
            <Icon name="fa-shop" />
            Buy snow guns
          </a>
        </div>
      }
    >
      {/* 4 Overview figures */}
      <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <Figure
          label="Today's temperature"
          value={overview.baseTempText}
          note={`Night ${overview.nightTempText} · Day ${overview.dayTempText}`}
        />
        <Figure
          label="Snowmaking window"
          value={overview.standardHours > 0 ? `${overview.standardHours} h` : 'Too warm'}
          tone={overview.standardHours > 0 ? 'text-accent' : 'text-danger'}
          note="Hours at freezing or colder"
        />
        <Figure
          label="Natural snow base"
          value={overview.snowBaseText}
          tone={overview.thinBase ? 'text-warning' : 'text-success'}
          note={
            overview.thinBase
              ? `Thin: under ${overview.thinBaseThresholdText}`
              : 'Deep enough: guns optional'
          }
        />
        <Figure
          label="Slopes covered today"
          value={`${overview.coveredCount} / ${overview.openCount}`}
          tone={uncovered > 0 && overview.thinBase ? 'text-warning' : 'text-foreground'}
          note={`${overview.runningGunsCount} of ${overview.totalGunsCount} running · room for ${overview.capacity}`}
        />
      </div>

      {/* Thin Base Notice */}
      {overview.thinBase && overview.openCount > 0 && (
        <div
          className={`flex items-center gap-2.5 rounded-xl border p-4 text-sm ${
            uncovered > 0
              ? 'border-warning/40 bg-surface text-foreground'
              : 'border-success/40 bg-surface text-foreground'
          }`}
        >
          <Icon
            name={uncovered > 0 ? 'fa-triangle-exclamation' : 'fa-circle-check'}
            className={uncovered > 0 ? 'text-warning shrink-0' : 'text-success shrink-0'}
          />
          <span>
            {uncovered <= 0 ? (
              'The natural base is thin, but every open slope has a snow gun today.'
            ) : guns.length === 0 ? (
              `The natural base is thin: all ${overview.openCount} open slopes wear an extra ${rules.thinBaseExtra}% a day. Buy snow guns to protect them.`
            ) : overview.standardHours === 0 && overview.runningGunsCount === 0 ? (
              `The natural base is thin and it's too warm for your guns today: ${uncovered} ${
                uncovered === 1 ? 'slope wears' : 'slopes wear'
              } an extra ${rules.thinBaseExtra}% a day.`
            ) : (
              `The natural base is thin: ${uncovered} uncovered ${
                uncovered === 1 ? 'slope wears' : 'slopes wear'
              } an extra ${rules.thinBaseExtra}% a day. Switch on more guns or buy guns with higher capacity.`
            )}
          </span>
        </div>
      )}

      {/* Next 5 Days Outlook */}
      {outlook.length > 0 && (
        <Panel title="Next 5 days">
          <div className="grid grid-cols-5 gap-2">
            {outlook.map((o) => (
              <div key={o.day} className="rounded-lg bg-default p-2 text-center">
                <div className="text-xs text-muted">Day {o.day}</div>
                <Icon name={o.icon} className="my-1.5 text-lg text-foreground block" />
                <div className="font-semibold text-sm text-foreground">{o.tempText}</div>
                {o.snowfallText && <div className="text-xs text-accent">{o.snowfallText}</div>}
                <div className={`text-xs mt-1 ${o.canRun ? 'text-accent' : 'text-danger'}`}>{o.hoursText}</div>
                {o.gunsCanRunText && <div className="text-xs text-success font-medium">{o.gunsCanRunText}</div>}
              </div>
            ))}
          </div>
          <p className="text-xs text-muted mt-2">
            Hours a standard gun can run. Guns left switched on simply wait out warm days at no cost.
          </p>
        </Panel>
      )}

      {/* Snow guns roster */}
      <section className="space-y-3">
        <h2 className="text-lg font-bold text-foreground">Snow guns</h2>
        {guns.length === 0 ? (
          <Empty icon="fa-snowflake" title="No snow guns yet">
            Each gun covers one or more slopes on cold days. Buy them in the equipment shop.
            <div className="mt-4">
              <a
                href="/equipment"
                className="inline-flex min-h-11 items-center gap-1.5 rounded-lg bg-accent px-4 py-2 text-sm font-medium text-accent-foreground hover:bg-accent-hover"
              >
                <Icon name="fa-shop" />
                Equipment shop
              </a>
            </div>
          </Empty>
        ) : (
          <div className="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
            {guns.map((g) => (
              <GunCard key={g.id} gun={g} slopes={slopes} />
            ))}
          </div>
        )}
      </section>

      {/* Slope coverage today table */}
      {slopes.length > 0 && (
        <Panel title="Slope coverage today">
          <p className="text-xs text-muted mb-3">
            Each gun takes its priority slope first, then the worst-condition slopes no other gun covers.
          </p>
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <thead className="border-b border-border text-xs text-muted">
                <tr>
                  <th className="pb-2 font-medium">Slope</th>
                  <th className="pb-2 font-medium">Condition</th>
                  <th className="pb-2 font-medium">Snow gun</th>
                  <th className="pb-2 font-medium text-right">Base wear</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {slopes.map((sl) => {
                  const isOpen = sl.status === 'open';
                  return (
                    <tr key={sl.id} className={isOpen ? '' : 'opacity-50'}>
                      <td className="py-2.5">
                        <div className="flex items-center gap-2">
                          {sl.difficulty && (
                            <span
                              className={`size-2 shrink-0 rounded-full ${
                                DIFFICULTY_COLORS[sl.difficulty] ?? 'bg-muted'
                              }`}
                            />
                          )}
                          <span className="font-medium text-foreground">{sl.name}</span>
                        </div>
                      </td>
                      <td className="py-2.5">
                        <span
                          className={
                            sl.condition_pct < 40
                              ? 'text-danger font-semibold'
                              : sl.condition_pct < 70
                                ? 'text-warning font-semibold'
                                : 'text-foreground'
                          }
                        >
                          {sl.condition_pct}%
                        </span>
                      </td>
                      <td className="py-2.5">
                        {!isOpen ? (
                          <span className="text-muted capitalize">{sl.status}</span>
                        ) : sl.gunName ? (
                          <div className="flex items-center gap-1.5">
                            <span className="text-accent font-medium flex items-center gap-1">
                              <Icon name="fa-snowflake" className="text-xs" />
                              {sl.gunName}
                            </span>
                            {sl.isPriority && (
                              <Chip variant="soft" size="sm">
                                Priority
                              </Chip>
                            )}
                          </div>
                        ) : (
                          <span className="text-muted">Not covered</span>
                        )}
                      </td>
                      <td className="py-2.5 text-right">
                        {!isOpen ? (
                          ''
                        ) : sl.gunId || !overview.thinBase ? (
                          <span className="text-success font-medium">Normal</span>
                        ) : (
                          <span className="text-warning font-medium">+{rules.thinBaseExtra}% / day</span>
                        )}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </Panel>
      )}

      {/* Utilities & Costs */}
      <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
        <Figure
          label="Running cost today"
          value={overview.totalOperatingCostText}
          note={`${overview.operatingCostGunsText} guns${
            overview.energyImportText ? ` + ${overview.energyImportText} grid power` : ''
          }${overview.waterImportText ? ` + ${overview.waterImportText} bought water` : ''}`}
        />

        {!utilities.energy.hidden && (
          <div className="flex flex-col justify-between rounded-xl border border-border bg-surface p-4">
            <div className="flex items-center justify-between">
              <span className="text-xs text-muted flex items-center gap-1">
                <Icon name="fa-bolt" className="text-warning" />
                Energy for snowmaking
              </span>
              <a href="/energy" className="text-xs text-link font-medium hover:underline">
                Manage
              </a>
            </div>
            <div className="text-2xl font-bold text-foreground">
              {utilities.energy.needText} <span className="text-sm font-normal text-muted">kWh</span>
            </div>
            <div className={`text-xs ${utilities.energy.ok ? 'text-success' : 'text-warning'}`}>
              Resort total {utilities.energy.totalText} kWh, sources make {utilities.energy.supplyText} kWh
              {!utilities.energy.ok && ' · remainder bought'}
            </div>
          </div>
        )}

        {!utilities.water.hidden && (
          <div className="flex flex-col justify-between rounded-xl border border-border bg-surface p-4">
            <div className="flex items-center justify-between">
              <span className="text-xs text-muted flex items-center gap-1">
                <Icon name="fa-droplet" className="text-accent" />
                Water for snowmaking
              </span>
              <a href="/water" className="text-xs text-link font-medium hover:underline">
                Manage
              </a>
            </div>
            <div className="text-2xl font-bold text-foreground">
              {utilities.water.needText} <span className="text-sm font-normal text-muted">L</span>
            </div>
            <div className={`text-xs ${utilities.water.ok ? 'text-success' : 'text-warning'}`}>
              Your sources supply {utilities.water.supplyText} L{!utilities.water.ok && ' · remainder bought'}
            </div>
          </div>
        )}
      </div>

      {crewCount === 0 && guns.length > 0 && (
        <div className="flex items-center gap-2 rounded-xl border border-border bg-surface p-4 text-sm text-foreground">
          <Icon name="fa-user-plus" className="text-accent shrink-0" />
          <span>
            Snowmaking crew is optional: a crew member assigned to a gun cuts its wear by {rules.crewWearRelief}% a day.{' '}
            <a href="/staff/hire" className="text-link font-medium hover:underline">
              Hire snowmakers
            </a>
            , then assign them on the{' '}
            <a href="/staff" className="text-link font-medium hover:underline">
              staff page
            </a>
            .
          </span>
        </div>
      )}

      {/* How it works details */}
      <details className="rounded-xl border border-border bg-surface">
        <summary className="p-4 cursor-pointer font-semibold text-sm select-none">How snowmaking works</summary>
        <ul className="px-4 pb-4 text-sm text-muted space-y-1.5 list-disc pl-9">
          <li>
            When the natural base is under {overview.thinBaseThresholdText}, slopes wear an extra {rules.thinBaseExtra}% a
            day unless a snow gun covers them. With a deep base, guns do not change wear.
          </li>
          <li>
            A switched-on gun runs on any day with at least one hour cold enough for its model. On warmer days it waits at no
            cost.
          </li>
          <li>
            Each running gun covers as many open slopes as its capacity: its priority slope first, then the worst-condition
            slopes left.
          </li>
          <li>
            Running guns cost their daily running cost and use energy and water; anything your own sources do not supply is
            bought from the grid.
          </li>
          <li>
            Guns wear 1–3% a day while running and break at 0%. Repairs cost up to {rules.repairSharePct}% of the gun price;
            selling returns up to {rules.resaleSharePct}%.
          </li>
        </ul>
      </details>

      {/* Navigation footer */}
      <div className="flex flex-wrap items-center gap-2 pt-2">
        <a
          href="/equipment"
          className="inline-flex min-h-11 items-center gap-1.5 rounded-lg border border-border px-3 text-sm font-medium hover:bg-default"
        >
          <Icon name="fa-toolbox" />
          Equipment shop
        </a>
        <a
          href="/weather"
          className="inline-flex min-h-11 items-center gap-1.5 rounded-lg border border-border px-3 text-sm font-medium hover:bg-default"
        >
          <Icon name="fa-cloud-sun" />
          Weather
        </a>
        <a
          href="/grooming"
          className="inline-flex min-h-11 items-center gap-1.5 rounded-lg border border-border px-3 text-sm font-medium hover:bg-default"
        >
          <Icon name="fa-tractor" />
          Grooming
        </a>
        <a
          href="/staff"
          className="inline-flex min-h-11 items-center gap-1.5 rounded-lg border border-border px-3 text-sm font-medium hover:bg-default"
        >
          <Icon name="fa-users" />
          Staff
        </a>
      </div>
    </Page>
  );
}

function GunCard({ gun, slopes }: { gun: GunItem; slopes: SlopeItem[] }) {
  const isBroken = gun.state === 'broken';
  const cond = gun.condition_pct;

  return (
    <div
      className={`flex flex-col justify-between rounded-xl border bg-surface p-4 gap-2.5 ${
        isBroken ? 'border-danger/40' : 'border-border'
      }`}
    >
      <div className="flex items-start justify-between gap-2">
        <div className="min-w-0">
          <div className="font-semibold text-sm truncate text-foreground">{gun.name}</div>
          <div className="text-xs text-muted">
            {gun.brand} · works at {gun.tempLimitText}
          </div>
        </div>
        <Chip
          variant="soft"
          size="sm"
          color={
            gun.stateTone === 'success'
              ? 'success'
              : gun.stateTone === 'warning'
                ? 'warning'
                : gun.stateTone === 'danger'
                  ? 'danger'
                  : 'default'
          }
          className="shrink-0"
        >
          {gun.stateLabel}
        </Chip>
      </div>

      <dl className="grid grid-cols-3 gap-2 text-xs text-center">
        <div className="rounded bg-default p-1.5">
          <dt className="text-muted">Covers</dt>
          <dd className="font-semibold text-foreground">
            {gun.capacity} {gun.capacity === 1 ? 'slope' : 'slopes'}
          </dd>
        </div>
        <div className="rounded bg-default p-1.5">
          <dt className="text-muted">Daily cost</dt>
          <dd className="font-semibold text-foreground">{gun.operatingCostText}</dd>
        </div>
        <div className="rounded bg-default p-1.5">
          <dt className="text-muted">Uses</dt>
          <dd className="font-semibold text-foreground">
            {gun.energyKwhText} · {gun.waterLitersText}
          </dd>
        </div>
      </dl>

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

      {/* Status line */}
      <div className="text-xs">
        {gun.state === 'running' ? (
          <span className="text-accent flex items-center gap-1">
            <Icon name="fa-snowflake" className="text-xs" />
            {gun.hours} h today, covering{' '}
            {gun.coversNames.length > 0 ? gun.coversNames.join(', ') : 'no open slopes left'}
          </span>
        ) : gun.state === 'idle' ? (
          <span className="text-warning">Waiting for a colder day: no cost or wear today.</span>
        ) : gun.state === 'off' ? (
          <span className="text-muted">Switched off.</span>
        ) : (
          <span className="text-danger">Broken: repair it to use it again.</span>
        )}
      </div>

      <div className="text-xs text-muted flex items-center gap-1.5">
        <Icon name="fa-user" className="text-xs" />
        <span>
          {gun.crew ? `Crew: ${gun.crew} (−${gun.crewReliefPct}% wear a day)` : 'No crew assigned'}
        </span>
      </div>

      {/* Priority slope assignment form */}
      <form action="/snowmaking/assign" method="post" className="flex items-center gap-2 pt-1">
        <CsrfInput />
        <input type="hidden" name="cannon_id" value={gun.id} />
        <Select
          name="slope_id"
          defaultValue={gun.priority ? String(gun.priority) : ''}
          aria-label={`Priority slope for ${gun.name}`}
          className="flex-1 min-w-0"
          options={[
            { id: '', label: 'Auto: worst slopes first' },
            ...slopes.map((sl) => ({
              id: String(sl.id),
              label: `First: ${sl.name}${sl.status !== 'open' ? ` (${sl.status})` : ''}`,
            })),
          ]}
        />
        <Button type="submit" variant="primary" size="sm" isIconOnly aria-label="Save priority slope">
          <Icon name="fa-check" />
        </Button>
      </form>

      {/* Actions */}
      <div className="flex flex-wrap items-center gap-1.5 pt-2 border-t border-border mt-auto">
        {!isBroken && (
          <PostButton
            action={`/snowmaking/toggle/${gun.id}`}
            variant={gun.status === 'active' ? 'ghost' : 'secondary'}
            size="sm"
            className="flex-1"
          >
            <Icon name="fa-power-off" className="mr-1 text-xs" />
            {gun.status === 'active' ? 'Switch off' : 'Switch on'}
          </PostButton>
        )}

        {cond < 100 && (
          <PostButton
            action={`/snowmaking/repair/${gun.id}`}
            variant={isBroken ? 'primary' : 'secondary'}
            size="sm"
            className={isBroken ? 'flex-1' : ''}
            confirm={{
              title: `Repair ${gun.name}?`,
              body: `Restore condition to 100% for ${gun.repairCostText}.`,
              confirmLabel: 'Repair',
            }}
          >
            <Icon name="fa-wrench" className="mr-1 text-xs" />
            Repair {gun.repairCostText}
          </PostButton>
        )}

        <PostButton
          action={`/snowmaking/sell/${gun.id}`}
          variant="ghost"
          size="sm"
          className="text-danger hover:bg-danger/10"
          confirm={{
            title: `Sell ${gun.name}?`,
            body: `You will receive ${gun.resaleText}. This gun will be removed from your equipment.`,
            confirmLabel: 'Sell',
          }}
        >
          <Icon name="fa-coins" className="mr-1 text-xs" />
          Sell {gun.resaleText}
        </PostButton>
      </div>
    </div>
  );
}
