import { useRef } from 'react';
import { Button, Chip } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput, Empty, Figure, Page, Panel, PostButton } from '@/components/Page';
import { Select } from '@/components/Select';

type Tone = 'success' | 'warning' | 'danger' | 'neutral';
type Mode = 'off' | 'auto' | 'max';

export type GunUpgrade = {
  key: string;
  label: string;
  icon: string;
  effect: string;
  owned: boolean;
  cost: number;
  costText: string;
  useless: boolean;
};

export type GunItem = {
  id: number;
  name: string;
  brand: string;
  capacity: number;
  condition_pct: number;
  status: string;
  mode: Mode;
  state: 'running' | 'standby' | 'idle' | 'off' | 'broken';
  stateLabel: string;
  stateTone: Tone;
  tempLimitText: string;
  hours: number;
  hoursRunText: string;
  cmTodayText: string;
  potentialText: string;
  outputPct: number;
  coversNames: string[];
  priority: number;
  crew: string | null;
  crewLevel: number | null;
  crewReliefPct: number;
  operatingCostText: string;
  costTodayText: string;
  energyKwhText: string;
  waterLitersText: string;
  upgrades: GunUpgrade[];
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
  made: number;
  base: number;
  target: number;
  madeText: string;
  baseText: string;
  targetText: string;
  thin: boolean;
  gunId: number | null;
  gunName: string | null;
  isPriority: boolean;
  addingText: string | null;
  wearText: string;
};

export type OutlookItem = {
  day: number;
  icon: string;
  tempText: string;
  humidityText: string;
  snowfallText: string | null;
  hours: number;
  hoursText: string;
  gunsCanRunText: string | null;
  canRun: boolean;
  capacityText: string | null;
};

export type HourItem = { hour: number; temp: number; tempText: string; wetBulb: number; wetBulbText: string };
export type LimitItem = { label: string; hours: boolean[]; guns: string[] };

export type SnowmakingPageProps = {
  overview: {
    baseTempText: string;
    nightTempText: string;
    dayTempText: string;
    humidityText: string;
    wetBulbRangeText: string;
    standardHours: number;
    snowBaseText: string;
    thinBaseThresholdText: string;
    thinBase: boolean;
    thinSlopes: number;
    belowTarget: number;
    coveredCount: number;
    openCount: number;
    runningGunsCount: number;
    totalGunsCount: number;
    cmTodayText: string;
    totalOperatingCostText: string;
    operatingCostGunsText: string;
    energyImportText: string | null;
    waterImportText: string | null;
  };
  hourly: HourItem[];
  limits: LimitItem[];
  outlook: OutlookItem[];
  guns: GunItem[];
  slopes: SlopeItem[];
  targets: { value: number; label: string }[];
  crewCount: number;
  utilities: {
    energy: { hidden: boolean; needText: string; totalText: string; supplyText: string; ok: boolean };
    water: { hidden: boolean; needText: string; supplyText: string; ok: boolean };
  };
  rules: {
    thinBaseText: string;
    thinBaseExtra: number;
    crewWearRelief: number;
    uncrewedPct: number;
    crewLevelBonus: number;
    minEfficiency: number;
    rampText: string;
    maxMadeText: string;
    repairSharePct: number;
    resaleSharePct: number;
  };
  cash: { raw: number; text: string };
};

const DIFFICULTY_COLORS: Record<string, string> = {
  green: 'bg-success',
  blue: 'bg-accent',
  red: 'bg-danger',
  black: 'bg-foreground',
  double_black: 'bg-foreground',
};

const CHIP_COLOR: Record<Tone, 'success' | 'warning' | 'danger' | 'default'> = {
  success: 'success',
  warning: 'warning',
  danger: 'danger',
  neutral: 'default',
};

const linkButton =
  'inline-flex min-h-11 items-center gap-1.5 rounded-lg border border-border px-3 text-sm font-medium hover:bg-default';

export default function SnowmakingPage({
  overview,
  hourly,
  limits,
  outlook,
  guns,
  slopes,
  targets,
  crewCount,
  utilities,
  rules,
  cash,
}: SnowmakingPageProps) {
  const open = slopes.filter((s) => s.status === 'open');
  // Bars on the base chart share one scale: the deepest base or target on the page
  const scale = Math.max(80, ...open.map((s) => Math.max(s.base, s.target)));

  return (
    <Page
      title="Snowmaking"
      icon="fa-snowflake"
      iconTone="text-accent"
      subtitle="Build each slope's base with made snow when the natural snow is not enough"
      width="max-w-6xl"
      actions={
        <div className="flex flex-wrap items-center gap-2">
          {guns.length > 0 && (
            <>
              <PostButton action="/snowmaking/toggle-all" data={{ action: 'on' }} variant="primary" size="sm">
                <Icon name="fa-power-off" className="mr-1 text-xs" />
                All on
              </PostButton>
              <PostButton action="/snowmaking/toggle-all" data={{ action: 'off' }} variant="secondary" size="sm">
                <Icon name="fa-power-off" className="mr-1 text-xs" />
                All off
              </PostButton>
            </>
          )}
          <a href="/equipment" className={linkButton}>
            <Icon name="fa-shop" />
            Buy snow guns
          </a>
        </div>
      }
    >
      <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <Figure
          label="Snowmaking window"
          value={overview.standardHours > 0 ? `${overview.standardHours} h` : 'Too warm'}
          tone={overview.standardHours > 0 ? 'text-accent' : 'text-danger'}
          note={`${overview.baseTempText} at ${overview.humidityText} humidity`}
        />
        <Figure
          label="Natural snow base"
          value={overview.snowBaseText}
          tone={overview.thinBase ? 'text-warning' : 'text-success'}
          note={overview.thinBase ? `Thin: under ${overview.thinBaseThresholdText}` : 'Deep enough on its own'}
        />
        <Figure
          label="Snow made tonight"
          value={overview.cmTodayText}
          note={`${overview.runningGunsCount} of ${overview.totalGunsCount} guns running`}
        />
        <Figure
          label="Cost tonight"
          value={overview.totalOperatingCostText}
          note={`${overview.operatingCostGunsText} guns${overview.energyImportText ? ` + ${overview.energyImportText} grid` : ''}${
            overview.waterImportText ? ` + ${overview.waterImportText} water` : ''
          }`}
        />
      </div>

      {open.length > 0 && (overview.thinSlopes > 0 || overview.belowTarget > 0) && (
        <div className="flex items-start gap-2.5 rounded-xl border border-warning/40 bg-surface p-4 text-sm text-foreground">
          <Icon name="fa-triangle-exclamation" className="mt-0.5 shrink-0 text-warning" />
          <span>
            {overview.thinSlopes > 0
              ? `${overview.thinSlopes} open ${overview.thinSlopes === 1 ? 'slope has' : 'slopes have'} less than ${
                  rules.thinBaseText
                } of snow and ${overview.thinSlopes === 1 ? 'wears' : 'wear'} an extra ${rules.thinBaseExtra}% a day.`
              : `${overview.belowTarget} open ${overview.belowTarget === 1 ? 'slope is' : 'slopes are'} under target.`}{' '}
            {guns.length === 0
              ? 'Buy snow guns to build them up.'
              : overview.standardHours === 0 && overview.runningGunsCount === 0
                ? 'Too warm for your guns today: check the next five days.'
                : overview.runningGunsCount === 0
                  ? 'Switch guns on to build them up.'
                  : null}
          </span>
        </div>
      )}

      <Panel title="Today, hour by hour">
        <HourStrip hourly={hourly} limits={limits} />
        <p className="mt-2 text-xs text-muted">
          Wet-bulb {overview.wetBulbRangeText}. Guns work off the wet-bulb temperature: dry air lets them make snow on
          days just above freezing, damp air shortens the window.
        </p>
      </Panel>

      {outlook.length > 0 && (
        <Panel title="Next 5 days">
          <div className="grid grid-cols-5 gap-2">
            {outlook.map((o) => (
              <div key={o.day} className="rounded-lg bg-default p-2 text-center">
                <div className="text-xs text-muted">Day {o.day}</div>
                <Icon name={o.icon} className="my-1.5 block text-lg text-foreground" />
                <div className="text-sm font-semibold text-foreground">{o.tempText}</div>
                <div className="text-xs text-muted">{o.humidityText}</div>
                {o.snowfallText && <div className="text-xs text-accent">{o.snowfallText}</div>}
                <div className={`mt-1 text-xs ${o.canRun ? 'text-accent' : 'text-danger'}`}>{o.hoursText}</div>
                {o.gunsCanRunText && <div className="text-xs font-medium text-success">{o.gunsCanRunText}</div>}
                {o.capacityText && <div className="text-xs text-muted">{o.capacityText}</div>}
              </div>
            ))}
          </div>
          <p className="mt-2 text-xs text-muted">
            Hours a standard gun can run, and the most snow your switched-on guns could make that day.
          </p>
        </Panel>
      )}

      {slopes.length > 0 && (
        <Panel
          title="Slope bases"
          actions={
            <TargetForm
              slopeId="all"
              value={null}
              targets={targets}
              label="Set every slope's target"
              placeholder="Target for all"
            />
          }
        >
          <p className="mb-3 text-xs text-muted">
            Base = natural snow + made snow. Guns in auto stop once a slope reaches its target. Under{' '}
            {rules.thinBaseText}, a slope wears an extra {rules.thinBaseExtra}% a day.
          </p>
          <ul className="divide-y divide-border">
            {slopes.map((sl) => (
              <SlopeRow key={sl.id} slope={sl} scale={scale} targets={targets} />
            ))}
          </ul>
        </Panel>
      )}

      <section className="space-y-3">
        <h2 className="text-lg font-bold text-foreground">Snow guns</h2>
        {guns.length === 0 ? (
          <Empty icon="fa-snowflake" title="No snow guns yet">
            Guns build up made snow on your slopes on cold nights. Buy them in the equipment shop.
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
              <GunCard key={g.id} gun={g} slopes={slopes} cash={cash.raw} />
            ))}
          </div>
        )}
      </section>

      {(!utilities.energy.hidden || !utilities.water.hidden) && guns.length > 0 && (
        <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
          {!utilities.energy.hidden && (
            <UtilityCard
              icon="fa-bolt"
              iconTone="text-warning"
              label="Energy for snowmaking tonight"
              href="/energy"
              value={`${utilities.energy.needText} kWh`}
              ok={utilities.energy.ok}
              note={`Resort total ${utilities.energy.totalText} kWh, your sources make ${utilities.energy.supplyText} kWh${
                utilities.energy.ok ? '' : ': the rest is bought'
              }`}
            />
          )}
          {!utilities.water.hidden && (
            <UtilityCard
              icon="fa-droplet"
              iconTone="text-accent"
              label="Water for snowmaking tonight"
              href="/water"
              value={`${utilities.water.needText} L`}
              ok={utilities.water.ok}
              note={`Your sources supply ${utilities.water.supplyText} L${utilities.water.ok ? '' : ': the rest is bought'}`}
            />
          )}
        </div>
      )}

      {crewCount === 0 && guns.length > 0 && (
        <div className="flex items-center gap-2 rounded-xl border border-border bg-surface p-4 text-sm text-foreground">
          <Icon name="fa-user-plus" className="shrink-0 text-accent" />
          <span>
            Without a crew member, a gun makes {rules.uncrewedPct}% of its snow.{' '}
            <a href="/staff/hire" className="font-medium text-link hover:underline">
              Hire snowmakers
            </a>
            , then assign one to each gun on the{' '}
            <a href="/staff" className="font-medium text-link hover:underline">
              staff page
            </a>
            .
          </span>
        </div>
      )}

      <details className="rounded-xl border border-border bg-surface">
        <summary className="cursor-pointer select-none p-4 text-sm font-semibold">How snowmaking works</summary>
        <ul className="list-disc space-y-1.5 px-4 pb-4 pl-9 text-sm text-muted">
          <li>
            Each slope&apos;s base is the natural snow plus its own made snow. Under {rules.thinBaseText} it wears an
            extra {rules.thinBaseExtra}% a day. Made snow melts in warm weather and rain, like natural snow, and a slope
            holds up to {rules.maxMadeText}.
          </li>
          <li>
            A gun makes snow in hours whose wet-bulb temperature is at or below its limit. Right at the limit it makes{' '}
            {rules.minEfficiency}% of its snow; {rules.rampText} colder it makes all of it.
          </li>
          <li>
            Auto: the gun runs only while one of its slopes is under target, and only as long as it needs to. Max: it
            runs every cold hour, to build a deep base before a warm spell.
          </li>
          <li>
            A gun covers up to its capacity in slopes: its priority slope first, then the slopes furthest under target.
            Its snow is shared between them, so fewer slopes fill faster.
          </li>
          <li>
            Running cost, energy and water count only for the hours a gun runs. Energy and water your own sources do
            not supply are bought.
          </li>
          <li>
            A crew member gives full output, +{rules.crewLevelBonus}% per level, and {rules.crewWearRelief}% less wear a
            day. Without one, a gun makes {rules.uncrewedPct}%, unless it has the automation kit.
          </li>
          <li>
            Guns wear 1–3% a day while running and break at 0%. Repairs cost up to {rules.repairSharePct}% of the gun
            price; selling returns up to {rules.resaleSharePct}%.
          </li>
        </ul>
      </details>

      <div className="flex flex-wrap items-center gap-2 pt-2">
        <a href="/equipment" className={linkButton}>
          <Icon name="fa-toolbox" />
          Equipment shop
        </a>
        <a href="/weather" className={linkButton}>
          <Icon name="fa-cloud-sun" />
          Weather
        </a>
        <a href="/grooming" className={linkButton}>
          <Icon name="fa-tractor" />
          Grooming
        </a>
        <a href="/staff" className={linkButton}>
          <Icon name="fa-users" />
          Staff
        </a>
      </div>
    </Page>
  );
}

/** One row per wet-bulb limit your guns have; a filled cell is an hour that limit can make snow */
function HourStrip({ hourly, limits }: { hourly: HourItem[]; limits: LimitItem[] }) {
  return (
    <div className="overflow-x-auto">
      <div className="min-w-[36rem] space-y-1.5">
        <div className="grid grid-cols-[9rem_repeat(24,minmax(0,1fr))] gap-px text-[10px] text-muted">
          <span />
          {hourly.map((h) => (
            <span key={h.hour} className="text-center tabular-nums">
              {h.hour % 3 === 0 ? String(h.hour).padStart(2, '0') : ''}
            </span>
          ))}
        </div>
        <div className="grid grid-cols-[9rem_repeat(24,minmax(0,1fr))] gap-px text-[10px]">
          <span className="pr-2 text-xs text-muted">Wet-bulb</span>
          {hourly.map((h) => (
            <span
              key={h.hour}
              title={`${String(h.hour).padStart(2, '0')}:00 · air ${h.tempText}, wet-bulb ${h.wetBulbText}`}
              className="text-center tabular-nums text-foreground"
            >
              {h.hour % 3 === 0 ? Math.round(h.wetBulb) : ''}
            </span>
          ))}
        </div>
        {limits.map((l) => {
          const n = l.hours.filter(Boolean).length;
          return (
            <div key={l.label} className="grid grid-cols-[9rem_repeat(24,minmax(0,1fr))] items-center gap-px">
              <span className="truncate pr-2 text-xs text-foreground" title={l.guns.join(', ')}>
                {l.label} <span className="text-muted">· {n} h</span>
              </span>
              {l.hours.map((on, h) => (
                <span key={h} className={`h-4 first:rounded-l last:rounded-r ${on ? 'bg-accent' : 'bg-default'}`} />
              ))}
            </div>
          );
        })}
      </div>
    </div>
  );
}

function TargetForm({
  slopeId,
  value,
  targets,
  label,
  placeholder,
}: {
  slopeId: number | 'all';
  value: number | null;
  targets: SnowmakingPageProps['targets'];
  label: string;
  placeholder?: string;
}) {
  const form = useRef<HTMLFormElement>(null);
  return (
    <form ref={form} action="/snowmaking/target" method="post" className="w-40 shrink-0">
      <CsrfInput />
      <input type="hidden" name="slope_id" value={slopeId} />
      <Select
        name="target_cm"
        aria-label={label}
        placeholder={placeholder}
        defaultValue={value === null ? undefined : String(value)}
        onChange={() => setTimeout(() => form.current?.submit(), 0)}
        options={targets.map((t) => ({ id: String(t.value), label: t.label }))}
      />
    </form>
  );
}

function SlopeRow({
  slope: sl,
  scale,
  targets,
}: {
  slope: SlopeItem;
  scale: number;
  targets: SnowmakingPageProps['targets'];
}) {
  const isOpen = sl.status === 'open';
  const natural = Math.max(0, sl.base - sl.made);
  const pct = (v: number) => `${Math.min(100, (v / scale) * 100)}%`;
  return (
    <li className={`flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:gap-4 ${isOpen ? '' : 'opacity-50'}`}>
      <div className="flex min-w-0 items-center gap-2 sm:w-44">
        {sl.difficulty && (
          <span className={`size-2 shrink-0 rounded-full ${DIFFICULTY_COLORS[sl.difficulty] ?? 'bg-muted'}`} />
        )}
        <span className="truncate font-medium text-foreground">{sl.name}</span>
        {!isOpen && <span className="text-xs capitalize text-muted">{sl.status}</span>}
      </div>

      <div className="min-w-0 flex-1">
        <div
          className="relative h-3 overflow-hidden rounded-full bg-default"
          role="img"
          aria-label={`${sl.baseText} base, ${sl.madeText} made, target ${sl.targetText}`}
        >
          <div className="absolute inset-y-0 left-0 bg-foreground/25" style={{ width: pct(natural) }} />
          <div
            className={`absolute inset-y-0 ${sl.thin ? 'bg-warning' : 'bg-accent'}`}
            style={{ left: pct(natural), width: pct(sl.made) }}
          />
          {sl.target > 0 && (
            <div className="absolute inset-y-0 w-0.5 bg-foreground" style={{ left: pct(sl.target) }} />
          )}
        </div>
        <div className="mt-1 flex flex-wrap gap-x-3 text-xs text-muted">
          <span className={sl.thin ? 'font-semibold text-warning' : 'font-semibold text-foreground'}>
            {sl.baseText} base
          </span>
          <span>{sl.madeText} made</span>
          {sl.addingText && (
            <span className="text-accent">
              <Icon name="fa-snowflake" className="mr-0.5 text-[10px]" />
              {sl.addingText} tonight{sl.gunName ? ` from ${sl.gunName}` : ''}
              {sl.isPriority ? ' (priority)' : ''}
            </span>
          )}
          {isOpen && sl.thin && <span className="text-warning">{sl.wearText} wear</span>}
        </div>
      </div>

      <TargetForm slopeId={sl.id} value={sl.target} targets={targets} label={`Target base for ${sl.name}`} />
    </li>
  );
}

function UtilityCard({
  icon,
  iconTone,
  label,
  href,
  value,
  ok,
  note,
}: {
  icon: string;
  iconTone: string;
  label: string;
  href: string;
  value: string;
  ok: boolean;
  note: string;
}) {
  return (
    <div className="flex flex-col justify-between gap-1 rounded-xl border border-border bg-surface p-4">
      <div className="flex items-center justify-between">
        <span className="flex items-center gap-1 text-xs text-muted">
          <Icon name={icon} className={iconTone} />
          {label}
        </span>
        <a href={href} className="text-xs font-medium text-link hover:underline">
          Manage
        </a>
      </div>
      <div className="text-2xl font-bold tabular-nums text-foreground">{value}</div>
      <div className={`text-xs ${ok ? 'text-success' : 'text-warning'}`}>{note}</div>
    </div>
  );
}

const MODES: { mode: Mode; label: string }[] = [
  { mode: 'off', label: 'Off' },
  { mode: 'auto', label: 'Auto' },
  { mode: 'max', label: 'Max' },
];

function GunCard({ gun, slopes, cash }: { gun: GunItem; slopes: SlopeItem[]; cash: number }) {
  const isBroken = gun.state === 'broken';
  const cond = gun.condition_pct;

  return (
    <div
      className={`flex flex-col gap-3 rounded-xl border bg-surface p-4 ${isBroken ? 'border-danger/40' : 'border-border'}`}
    >
      <div className="flex items-start justify-between gap-2">
        <div className="min-w-0">
          <div className="truncate text-sm font-semibold text-foreground">{gun.name}</div>
          <div className="text-xs text-muted">Works at {gun.tempLimitText}</div>
        </div>
        <Chip variant="soft" size="sm" color={CHIP_COLOR[gun.stateTone]} className="shrink-0">
          {gun.stateLabel}
        </Chip>
      </div>

      {!isBroken && (
        <form action={`/snowmaking/mode/${gun.id}`} method="post">
          <CsrfInput />
          <div role="group" aria-label={`Mode for ${gun.name}`} className="grid grid-cols-3 gap-1 rounded-lg bg-default p-1">
            {MODES.map((m) => (
              <button
                key={m.mode}
                type="submit"
                name="mode"
                value={m.mode}
                aria-pressed={gun.mode === m.mode}
                className={`min-h-9 rounded-md text-sm font-medium transition-colors focus-visible:outline-2 focus-visible:outline-accent ${
                  gun.mode === m.mode ? 'bg-surface text-foreground shadow-sm' : 'text-muted hover:text-foreground'
                }`}
              >
                {m.label}
              </button>
            ))}
          </div>
        </form>
      )}

      <dl className="grid grid-cols-3 gap-2 text-center text-xs">
        <div className="rounded bg-default p-1.5">
          <dt className="text-muted">Tonight</dt>
          <dd className="font-semibold tabular-nums text-foreground">{gun.cmTodayText}</dd>
        </div>
        <div className="rounded bg-default p-1.5">
          <dt className="text-muted">Runs</dt>
          <dd className="font-semibold tabular-nums text-foreground">
            {gun.hoursRunText} / {gun.hours} h
          </dd>
        </div>
        <div className="rounded bg-default p-1.5">
          <dt className="text-muted">Cost</dt>
          <dd className="font-semibold tabular-nums text-foreground">{gun.costTodayText}</dd>
        </div>
      </dl>

      <div className="text-xs">
        {gun.state === 'running' ? (
          <span className="text-accent">
            <Icon name="fa-snowflake" className="mr-1 text-xs" />
            Covering {gun.coversNames.join(', ')}
          </span>
        ) : gun.state === 'standby' ? (
          <span className="text-muted">Every slope it can reach is at target: no cost tonight.</span>
        ) : gun.state === 'idle' ? (
          <span className="text-warning">Too warm today: no cost or wear.</span>
        ) : gun.state === 'off' ? (
          <span className="text-muted">Switched off.</span>
        ) : (
          <span className="text-danger">Broken: repair it to use it again.</span>
        )}
      </div>

      <div className="space-y-1 text-xs text-muted">
        <div>
          Up to {gun.potentialText} a day at {gun.outputPct}% output, across up to {gun.capacity}{' '}
          {gun.capacity === 1 ? 'slope' : 'slopes'}. Full day {gun.operatingCostText}, {gun.energyKwhText},{' '}
          {gun.waterLitersText}.
        </div>
        <div className="flex items-center gap-1.5">
          <Icon name="fa-user" className="text-xs" />
          {gun.crew ? `${gun.crew}, level ${gun.crewLevel} (−${gun.crewReliefPct}% wear a day)` : 'No crew member'}
        </div>
      </div>

      <div className="flex flex-wrap gap-1.5">
        {gun.upgrades
          .filter((u) => !u.useless)
          .map((u) =>
            u.owned ? (
              <Chip key={u.key} variant="soft" size="sm" color="accent">
                <Icon name={u.icon} className="mr-1 text-[10px]" />
                {u.label}
              </Chip>
            ) : (
              <PostButton
                key={u.key}
                action={`/snowmaking/upgrade/${gun.id}`}
                data={{ upgrade: u.key }}
                variant="tertiary"
                size="sm"
                disabled={cash < u.cost}
                confirm={{
                  title: `Fit ${u.label.toLowerCase()} to ${gun.name}?`,
                  body: `${u.effect}. Costs ${u.costText}, paid now.`,
                  confirmLabel: `Pay ${u.costText}`,
                }}
              >
                <Icon name={u.icon} className="mr-1 text-xs" />
                {u.label} {u.costText}
              </PostButton>
            ),
          )}
      </div>

      <form action="/snowmaking/assign" method="post" className="flex items-center gap-2">
        <CsrfInput />
        <input type="hidden" name="cannon_id" value={gun.id} />
        <Select
          name="slope_id"
          defaultValue={gun.priority ? String(gun.priority) : ''}
          aria-label={`Priority slope for ${gun.name}`}
          className="min-w-0 flex-1"
          options={[
            { id: '', label: 'No priority: furthest under target' },
            ...slopes.map((sl) => ({
              id: String(sl.id),
              label: `First: ${sl.name}${sl.status !== 'open' ? ` (${sl.status})` : ''}`,
            })),
          ]}
        />
        <Button type="submit" variant="secondary" size="sm" isIconOnly aria-label="Save priority slope">
          <Icon name="fa-check" />
        </Button>
      </form>

      <div className="mt-auto flex items-center gap-2 border-t border-border pt-2">
        <span className="w-12 text-xs text-muted">Health</span>
        <div className="relative h-1.5 flex-1 overflow-hidden rounded-full bg-default">
          <div
            className={`h-full rounded-full ${cond >= 60 ? 'bg-success' : cond >= 30 ? 'bg-warning' : 'bg-danger'}`}
            style={{ width: `${cond}%` }}
          />
        </div>
        <span className="w-9 text-right font-mono text-xs text-foreground">{cond}%</span>
      </div>

      <div className="flex flex-wrap items-center gap-1.5">
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
