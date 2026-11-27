import { useEffect, useMemo, useRef, useState, type ReactNode } from 'react';
import { AlertDialog, Button, Chip, Drawer, Meter, ProgressBar, buttonVariants, toast } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { csrf } from '@/components/Page';
import { TrailSign, type Difficulty } from '@/components/TrailSign';

export type WidgetCategory = 'operations' | 'financial' | 'activity';
type WidgetSize = 'small' | 'medium' | 'large';

export interface WidgetMeta {
  /** Title shown on the widget and in the library */
  name: string;
  category: WidgetCategory;
  icon: string;
  description: string;
  /** Where the widget's link goes, and what it says */
  link?: { href: string; label: string };
}

export const WIDGET_META: Record<string, WidgetMeta> = {
  stats: {
    name: 'Daily cash flow',
    category: 'financial',
    icon: 'fa-chart-bar',
    description: 'Tickets, shops, staff pay, running costs and loan payments for one game day.',
    link: { href: '/finances', label: 'Finances' },
  },
  season: {
    name: 'Season',
    category: 'operations',
    icon: 'fa-calendar',
    description: 'Season day, days left, and the winter and summer split.',
  },
  weather_mini: {
    name: 'Weather',
    category: 'operations',
    icon: 'fa-cloud-sun',
    description: 'Weather now and the next five days.',
    link: { href: '/weather', label: 'Full forecast' },
  },
  achievements_mini: {
    name: 'Achievement rewards',
    category: 'activity',
    icon: 'fa-award',
    description: 'Rewards ready to claim and achievements completed.',
    link: { href: '/achievements', label: 'Achievements' },
  },
  actions: {
    name: 'Shortcuts',
    category: 'operations',
    icon: 'fa-compass',
    description: 'Links to the pages you run the resort from.',
  },
  overview: {
    name: 'Mountain',
    category: 'operations',
    icon: 'fa-mountain-sun',
    description: 'Runs and lifts open, staff, buildings and run condition.',
    link: { href: '/map', label: 'Trail map' },
  },
  achievements: {
    name: 'Achievements in progress',
    category: 'activity',
    icon: 'fa-list-check',
    description: 'The achievements you are closest to.',
    link: { href: '/achievements', label: 'All' },
  },
  activity: {
    name: 'Recent activity',
    category: 'activity',
    icon: 'fa-clock-rotate-left',
    description: 'Builds, hires and nightly results, newest first.',
    link: { href: '/activity', label: 'Full log' },
  },
  parking_mini: {
    name: 'Parking',
    category: 'operations',
    icon: 'fa-square-parking',
    description: 'Parking lots, spaces and how full they were.',
    link: { href: '/parking', label: 'Parking' },
  },
  terrain_parks_mini: {
    name: 'Terrain parks',
    category: 'operations',
    icon: 'fa-person-snowboarding',
    description: 'Parks open, parks being built and riders.',
    link: { href: '/terrain-parks', label: 'Parks' },
  },
  finances_mini: {
    name: 'Lifetime finances',
    category: 'financial',
    icon: 'fa-coins',
    description: 'Everything earned and spent since the resort opened.',
    link: { href: '/finances', label: 'Finances' },
  },
  staff_mini: {
    name: 'Staff',
    category: 'financial',
    icon: 'fa-users',
    description: 'Headcount by role and average morale.',
    link: { href: '/staff', label: 'Staff' },
  },
  equipment_mini: {
    name: 'Equipment',
    category: 'operations',
    icon: 'fa-toolbox',
    description: 'Snowcats, snowmobiles and other machines you own.',
    link: { href: '/equipment', label: 'Equipment' },
  },
  insurance_mini: {
    name: 'Insurance',
    category: 'financial',
    icon: 'fa-shield-halved',
    description: 'Policies active and policies you could take out.',
    link: { href: '/insurance', label: 'Insurance' },
  },
  loans_mini: {
    name: 'Loans',
    category: 'financial',
    icon: 'fa-landmark',
    description: 'Loans you are paying off and what is left to pay.',
    link: { href: '/bank', label: 'Bank' },
  },
  marketing_mini: {
    name: 'Marketing',
    category: 'financial',
    icon: 'fa-bullhorn',
    description: 'Campaigns running now.',
    link: { href: '/marketing', label: 'Marketing' },
  },
  roadmap_mini: {
    name: 'Roadmap',
    category: 'activity',
    icon: 'fa-map-location-dot',
    description: 'The most-voted ideas for the game. Vote from here.',
    link: { href: '/roadmap', label: 'All ideas' },
  },
};

const CATEGORY_LABEL: Record<WidgetCategory, string> = {
  operations: 'Mountain',
  financial: 'Money',
  activity: 'Activity',
};

export type PresetKey = 'default' | 'operations' | 'financial' | 'compact';

export interface LayoutPreset {
  id: PresetKey;
  name: string;
  widgets: { key: string; size: WidgetSize }[];
}

export const PRESETS: LayoutPreset[] = [
  {
    id: 'default',
    name: 'Balanced',
    widgets: [
      { key: 'stats', size: 'medium' },
      { key: 'weather_mini', size: 'medium' },
      { key: 'overview', size: 'large' },
      { key: 'season', size: 'medium' },
      { key: 'achievements_mini', size: 'medium' },
      { key: 'achievements', size: 'medium' },
      { key: 'activity', size: 'medium' },
      { key: 'actions', size: 'large' },
    ],
  },
  {
    id: 'operations',
    name: 'Mountain',
    widgets: [
      { key: 'overview', size: 'large' },
      { key: 'weather_mini', size: 'medium' },
      { key: 'season', size: 'medium' },
      { key: 'equipment_mini', size: 'small' },
      { key: 'terrain_parks_mini', size: 'small' },
      { key: 'parking_mini', size: 'small' },
      { key: 'staff_mini', size: 'small' },
      { key: 'activity', size: 'medium' },
      { key: 'actions', size: 'medium' },
    ],
  },
  {
    id: 'financial',
    name: 'Money',
    widgets: [
      { key: 'stats', size: 'medium' },
      { key: 'finances_mini', size: 'medium' },
      { key: 'staff_mini', size: 'medium' },
      { key: 'loans_mini', size: 'small' },
      { key: 'insurance_mini', size: 'small' },
      { key: 'marketing_mini', size: 'small' },
      { key: 'parking_mini', size: 'small' },
      { key: 'overview', size: 'large' },
    ],
  },
  {
    id: 'compact',
    name: 'Compact',
    widgets: [
      { key: 'stats', size: 'medium' },
      { key: 'weather_mini', size: 'medium' },
      { key: 'overview', size: 'large' },
    ],
  },
];

export type DashboardHero = {
  resortMapKey: string;
  resortMapName: string;
  /** ResortMap::webpSizes: this map has a _low.webp on the server */
  resortMapWebpLow?: boolean;
  resortOpen: boolean;
  seasonNum: number;
  gameDay: number;
  liftCount: number;
  totalLifts: number;
  slopeCount: number;
  totalSlopes: number;
  repScore: number;
  repScoreFormatted: string;
  bonusAvailable: boolean;
};

export type DashboardAlert = {
  type: 'danger' | 'warning';
  icon: string;
  msg: string;
  link: string;
};

export type DashboardKpis = {
  cashBalance: number;
  cashBalanceText: string;
  cashNegative: boolean;
  netProfit: number;
  netProfitText: string;
  netProfitPositive: boolean;
  genepisBal: number;
  genepisBalFormatted: string;
  dailyProfit: number;
  dailyProfitText: string;
  dailyProfitPositive: boolean;
  incomePercent: number;
  incomeText: string;
  expensesText: string;
  dailyVisitors: number;
  dailyVisitorsFormatted: string;
  visitorsSettledFormatted: string;
  visitorsTurnedAway: number;
  ticketPriceText: string;
  totalLiftCapacityFormatted: string;
  weather: {
    condition_name: string;
    tempFormatted: string;
    snowBaseFormatted: string;
    icon: string;
  };
};

export type DashboardWidgetItem = {
  widget_key: string;
  name: string;
  icon: string;
  size: WidgetSize;
  visible: boolean;
  sort_order: number;
};

export type DashboardAvailableWidget = {
  key: string;
  name: string;
  icon: string;
  default: boolean;
  size: string;
};

export type DashboardData = {
  stats: {
    revenue: string;
    expenses: string;
    tickets: string;
    buildingsFood: string;
    payroll: string;
    runningCosts: string;
    loanPayments: string;
    dailyProfit: string;
    isProfit: boolean;
    /** Plain amounts behind the texts above, for the bars */
    amounts?: { tickets: number; buildings: number; payroll: number; running: number; loans: number };
  };
  overview: {
    slopeCount: number;
    totalSlopes: number;
    slopesByDiff: Record<string, number>;
    liftCount: number;
    totalLifts: number;
    staffCount: number;
    buildingCount: number;
    avgSlopeCondition: number;
    ungroomedCount: number;
    trailConditionNote: string;
  };
  season: {
    seasonNum: number;
    seasonProgress: number;
    seasonLength: number;
    seasonPct: number;
    winterDays: number;
    summerDays: number;
  };
  weather_mini: {
    tempFormatted: string;
    condition_name: string;
    icon: string;
    windFormatted: string;
    snowfallFormatted: string;
    snowBaseFormatted: string;
    forecast: {
      label: string;
      condition: string;
      icon: string;
      tempFormatted: string;
    }[];
  };
  achievements_mini: {
    unclaimed: number;
    completed: number;
    inProgressCount: number;
  };
  achievements: {
    id: number;
    name: string;
    progress: number;
    target: number;
    progressPct: number;
    progressFormatted: string;
    targetFormatted: string;
  }[];
  activity: {
    id: number;
    message: string;
    icon: string;
    game_day: number;
  }[];
  parking_mini: {
    lotsCount: number;
    spacesFormatted: string;
    totalOccupied: number;
    totalCap: number;
    pct: number;
  };
  terrain_parks_mini: {
    openCount: number;
    buildingCount: number;
    ridersFormatted: string;
  };
  finances_mini: {
    incomeText: string;
    expensesText: string;
    netProfitText: string;
    isNetPositive: boolean;
  };
  staff_mini: {
    staffCount: number;
    roles: { role: string; roleName: string; count: number }[];
    avgMorale: number;
    moraleTone: 'success' | 'warning' | 'danger';
  };
  equipment_mini: {
    unitsCount: number;
  };
  insurance_mini: {
    activeCount: number;
    availableCount: number;
  };
  loans_mini: {
    activeCount: number;
    totalDebtText: string;
  };
  marketing_mini: {
    campaignsCount: number;
  };
  roadmap_mini?: {
    items: RoadmapTopItem[];
  };
};

export type RoadmapTopItem = { id: number; title: string; status: string; upvotes: number; voted: boolean };

export type LaunchChecklistStep = {
  id: string;
  title: string;
  description: string;
  done: boolean;
  link: string;
  cta: string;
  icon: string;
};

export type LaunchChecklist = {
  show: boolean;
  completed: number;
  total: number;
  isComplete: boolean;
  progressPct: number;
  steps: LaunchChecklistStep[];
};

export type DashboardPageProps = {
  hero: DashboardHero;
  alerts: DashboardAlert[];
  unclaimedAchievements: number;
  kpis: DashboardKpis;
  widgets: DashboardWidgetItem[];
  availableWidgets: DashboardAvailableWidget[];
  data: DashboardData;
  demoEstimate?: { skiers: string; income: string; costs: string } | null;
  launchChecklist?: LaunchChecklist | null;
  /** Server time the nightly tick settles the day (Forecast::SETTLE_TIME) */
  settleTime?: string;
};

const plural = (n: number, one: string, many: string) => `${n.toLocaleString('en-US')} ${n === 1 ? one : many}`;

/** A link that reads as a quiet button; 44px tall on touch screens */
const quietLink = 'inline-flex items-center gap-1.5 rounded-md text-sm text-link underline-offset-4 hover:underline pointer-coarse:min-h-11';

/** What each alert link asks the player to do */
const ALERT_ACTION: Record<string, string> = {
  '/bank': 'Open the bank',
  '/equipment': 'Repair',
  '/grooming': 'Groom runs',
  '/staff/hire': 'Hire staff',
};

/** Top roadmap ideas with a vote button; same endpoint and reward as /roadmap */
function RoadmapMini({ initial }: { initial: RoadmapTopItem[] }) {
  const [items, setItems] = useState(initial);
  const [busy, setBusy] = useState<number | null>(null);
  const [note, setNote] = useState<{ text: string; ok: boolean } | null>(null);

  const vote = async (id: number) => {
    if (busy !== null) return;
    setBusy(id);
    try {
      const { field, token } = csrf();
      const fd = new FormData();
      fd.append(field, token);
      const res = await fetch(`/roadmap/vote/${id}`, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd });
      const data = await res.json().catch(() => null);
      if (res.ok && data?.success) {
        setItems((prev) => prev.map((it) => (it.id === id ? { ...it, voted: Boolean(data.voted), upvotes: data.upvotes } : it)));
        setNote(data.message ? { text: data.message, ok: true } : null);
      } else {
        setNote({ text: data?.message ?? 'Your vote did not go through. Try again.', ok: false });
      }
    } catch {
      setNote({ text: 'Your vote did not go through. Check your connection and try again.', ok: false });
    } finally {
      setBusy(null);
    }
  };

  if (items.length === 0) {
    return (
      <p className="text-muted">
        No open ideas yet. <a href="/roadmap" className="text-link underline-offset-4 hover:underline">Suggest one</a>.
      </p>
    );
  }
  return (
    <div className="space-y-2">
      <ul className="space-y-1.5">
        {items.map((it) => (
          <li key={it.id} className="flex items-center gap-3">
            <Button
              size="sm"
              variant={it.voted ? 'primary' : 'secondary'}
              onPress={() => vote(it.id)}
              isDisabled={busy !== null}
              aria-pressed={it.voted}
              aria-label={`${it.voted ? 'Remove your vote for' : 'Vote for'} ${it.title}, ${it.upvotes} votes`}
              className="min-w-14 shrink-0 tabular-nums"
            >
              <Icon name="fa-chevron-up" className="text-xs" />
              {it.upvotes}
            </Button>
            <a href={`/roadmap#item-${it.id}`} className="line-clamp-2 text-foreground underline-offset-4 hover:underline">
              {it.title}
            </a>
          </li>
        ))}
      </ul>
      {note && (
        <p role="status" className={note.ok ? 'text-success' : 'text-danger'}>
          {note.text}
        </p>
      )}
    </div>
  );
}

/** One figure: label, big tabular number, optional note */
function Stat({ label, value, tone = 'text-foreground', note, big = false }: { label: string; value: ReactNode; tone?: string; note?: ReactNode; big?: boolean }) {
  return (
    <div className="min-w-0">
      <dt className="text-sm text-muted">{label}</dt>
      <dd className={`font-semibold tabular-nums ${big ? 'text-3xl' : 'text-xl'} ${tone}`}>{value}</dd>
      {note && <dd className="text-xs text-muted">{note}</dd>}
    </div>
  );
}

/** Small figures inside widgets */
function MiniStat({ label, value, tone = 'text-foreground' }: { label: string; value: ReactNode; tone?: string }) {
  return (
    <div className="min-w-0">
      <dt className="truncate text-xs text-muted">{label}</dt>
      <dd className={`text-lg font-semibold tabular-nums ${tone}`}>{value}</dd>
    </div>
  );
}

/** Empty widget: why it's empty and the one action that fills it */
function WidgetEmpty({ children, href, action }: { children: ReactNode; href: string; action: string }) {
  return (
    <div className="space-y-2">
      <p className="text-muted">{children}</p>
      <a href={href} className={buttonVariants({ variant: 'secondary', size: 'sm' })}>
        {action}
      </a>
    </div>
  );
}

/** "Tmrw" / "+3d" from the server, spelled out where there's room */
function forecastLabel(label: string): { short: string; long: string } {
  if (label === 'Tmrw') return { short: 'Tmrw', long: 'Tomorrow' };
  const n = /^\+(\d+)d$/.exec(label)?.[1];
  return n ? { short: `+${n}d`, long: `In ${n} days` } : { short: label, long: label };
}

/** The resort snow board (DESIGN.md): dark header strip, light paper panel, big numbers. Same look in both themes */
function WeatherBoard({ w }: { w: DashboardData['weather_mini'] }) {
  const ink = 'text-[oklch(26%_0.04_260)]';
  const soft = 'text-[oklch(42%_0.03_260)]';
  const hasEstimate = w.forecast.some((fc) => fc.tempFormatted.startsWith('~'));
  return (
    <div className={`overflow-hidden rounded-lg bg-[oklch(97%_0.006_250)] ${ink}`}>
      <div className="flex items-center justify-between gap-3 bg-[oklch(24%_0.03_260)] px-3 py-2 text-white">
        <span className="flex items-center gap-2 text-sm font-semibold">
          <Icon name={w.icon} />
          {w.condition_name}
        </span>
        <span className="text-xs text-white/80">Now</span>
      </div>
      <dl className="grid grid-cols-3 gap-px bg-[oklch(88%_0.01_250)]">
        {[
          { label: 'Temperature', value: w.tempFormatted },
          { label: 'Base', value: w.snowBaseFormatted },
          { label: 'Wind', value: w.windFormatted },
        ].map((s) => (
          <div key={s.label} className="min-w-0 bg-[oklch(97%_0.006_250)] px-3 py-2">
            <dt className={`truncate text-xs ${soft}`}>{s.label}</dt>
            <dd className="truncate text-xl font-bold tabular-nums @md:text-2xl">{s.value}</dd>
          </div>
        ))}
      </dl>
      {w.forecast.length > 0 && (
        <div className="border-t border-[oklch(88%_0.01_250)] px-3 py-2">
          <ol aria-label="Next days" className="grid grid-cols-5 gap-1 text-center">
            {w.forecast.map((fc) => {
              const l = forecastLabel(fc.label);
              return (
                <li key={fc.label} className="min-w-0">
                  <span className={`block text-xs ${soft}`}>
                    <span className="@md:hidden" aria-hidden="true">{l.short}</span>
                    <span className="sr-only @md:not-sr-only">{l.long}</span>
                  </span>
                  <Icon name={fc.icon} className="my-1 text-[oklch(45%_0.2_260)]" />
                  <span className="sr-only">{fc.condition}, </span>
                  <span className="block text-sm font-semibold tabular-nums">{fc.tempFormatted}</span>
                </li>
              );
            })}
          </ol>
          {hasEstimate && <p className={`mt-1 text-xs ${soft}`}>~ is the middle of the forecast range.</p>}
        </div>
      )}
    </div>
  );
}

/** A line of the cash flow ledger with a bar sized against the biggest line */
function LedgerRow({ label, text, amount, max, sign }: { label: string; text: string; amount?: number; max: number; sign: '+' | '-' }) {
  const pct = amount !== undefined && max > 0 ? Math.round((amount / max) * 100) : null;
  return (
    <li className="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-3 gap-y-1">
      <span className="truncate text-muted">{label}</span>
      <span className={`text-right font-semibold tabular-nums ${sign === '+' ? 'text-success' : 'text-danger'}`}>
        {sign === '+' ? '+' : '−'}
        {text}
      </span>
      {pct !== null && (
        <span aria-hidden="true" className="col-span-2 h-1.5 overflow-hidden rounded-full bg-default">
          <span className={`block h-full rounded-full ${sign === '+' ? 'bg-success' : 'bg-danger'}`} style={{ width: `${pct}%` }} />
        </span>
      )}
    </li>
  );
}

function WidgetBody({
  widgetKey,
  data,
  activity,
  tonightLabel,
}: {
  widgetKey: string;
  data: DashboardData;
  activity: DashboardData['activity'];
  tonightLabel: string;
}) {
  switch (widgetKey) {
    case 'stats': {
      const a = data.stats.amounts;
      const max = a ? Math.max(a.tickets, a.buildings, a.payroll, a.running, a.loans) : 0;
      return (
        <div className="space-y-3">
          <p className="text-xs text-muted">{tonightLabel}</p>
          <ul className="grid gap-x-6 gap-y-2.5 @xl:grid-cols-2">
            <LedgerRow label="Lift tickets" text={data.stats.tickets} amount={a?.tickets} max={max} sign="+" />
            <LedgerRow label="Shops, food and lodging" text={data.stats.buildingsFood} amount={a?.buildings} max={max} sign="+" />
            <LedgerRow label="Staff pay" text={data.stats.payroll} amount={a?.payroll} max={max} sign="-" />
            <LedgerRow label="Running costs" text={data.stats.runningCosts} amount={a?.running} max={max} sign="-" />
            <LedgerRow label="Loan payments" text={data.stats.loanPayments} amount={a?.loans} max={max} sign="-" />
          </ul>
          <div className="flex items-baseline justify-between border-t border-border pt-2">
            <span className="font-semibold">Net for the day</span>
            <span className={`text-lg font-semibold tabular-nums ${data.stats.isProfit ? 'text-success' : 'text-danger'}`}>
              {data.stats.isProfit ? '+' : ''}
              {data.stats.dailyProfit}
            </span>
          </div>
        </div>
      );
    }

    case 'overview': {
      const o = data.overview;
      if (o.totalSlopes === 0 && o.totalLifts === 0) {
        return (
          <WidgetEmpty href="/map" action="Open the trail map">
            No runs or lifts yet. Draw your first run on the trail map.
          </WidgetEmpty>
        );
      }
      const diffs: Difficulty[] = ['green', 'blue', 'black', 'double_black'];
      return (
        <div className="space-y-3">
          <dl className="grid grid-cols-2 gap-3 @md:grid-cols-4">
            <div className="min-w-0">
              <dt className="text-xs text-muted">Runs open</dt>
              <dd className="text-lg font-semibold tabular-nums">
                {o.slopeCount} <span className="text-sm font-normal text-muted">of {o.totalSlopes}</span>
              </dd>
              <dd className="mt-0.5 flex flex-wrap items-center gap-x-2.5 gap-y-1 text-sm">
                {diffs
                  .filter((d) => (o.slopesByDiff[d] ?? 0) > 0)
                  .map((d) => (
                    <span key={d} className="inline-flex items-center gap-1 tabular-nums">
                      <TrailSign difficulty={d} />
                      {o.slopesByDiff[d]}
                    </span>
                  ))}
              </dd>
            </div>
            <MiniStat label="Lifts open" value={<>{o.liftCount} <span className="text-sm font-normal text-muted">of {o.totalLifts}</span></>} />
            <MiniStat label="Staff" value={o.staffCount} />
            <MiniStat label="Buildings" value={o.buildingCount} />
          </dl>
          <Meter
            value={o.avgSlopeCondition}
            maxValue={100}
            size="sm"
            color={o.avgSlopeCondition >= 70 ? 'success' : o.avgSlopeCondition >= 40 ? 'warning' : 'danger'}
            className="gap-1"
          >
            <div className="flex w-full items-baseline justify-between gap-2 text-sm">
              <span className="text-muted">Average run condition</span>
              <span className="tabular-nums">
                <span className="font-semibold">{o.avgSlopeCondition}%</span>
                <span className={o.ungroomedCount > 0 ? 'text-warning' : 'text-muted'}> · {o.trailConditionNote}</span>
              </span>
            </div>
            <Meter.Track>
              <Meter.Fill />
            </Meter.Track>
          </Meter>
        </div>
      );
    }

    case 'season': {
      const s = data.season;
      const left = Math.max(0, s.seasonLength - s.seasonProgress);
      return (
        <div className="space-y-2">
          <ProgressBar value={s.seasonPct} maxValue={100} size="sm" aria-label={`Season ${s.seasonNum}: day ${s.seasonProgress} of ${s.seasonLength}`} className="gap-1.5">
            <div className="flex w-full items-baseline justify-between gap-2">
              <span className="text-lg font-semibold tabular-nums">
                Day {s.seasonProgress} <span className="text-sm font-normal text-muted">of {s.seasonLength}</span>
              </span>
              <span className="text-sm tabular-nums text-muted">{left === 0 ? 'Last day' : plural(left, 'day', 'days') + ' left'}</span>
            </div>
            <ProgressBar.Track>
              <ProgressBar.Fill className="bg-gradient-to-r from-[oklch(60%_0.15_245)] to-[oklch(45%_0.2_260)]" />
            </ProgressBar.Track>
          </ProgressBar>
          <p className="flex flex-wrap justify-between gap-x-4 text-sm text-muted">
            <span className="inline-flex items-center gap-1.5">
              <Icon name="fa-snowflake" className="text-link" />
              Winter: days 1 to {s.winterDays}
            </span>
            {s.summerDays > 0 && (
              <span className="inline-flex items-center gap-1.5">
                <Icon name="fa-sun" className="text-warning" />
                Summer: {plural(s.summerDays, 'day', 'days')}
              </span>
            )}
          </p>
        </div>
      );
    }

    case 'weather_mini':
      return <WeatherBoard w={data.weather_mini} />;

    case 'actions': {
      const links: [string, string, string][] = [
        ['/map', 'fa-map', 'Trail map'],
        ['/resort', 'fa-sliders', 'Resort'],
        ['/tickets', 'fa-ticket', 'Tickets'],
        ['/finances', 'fa-coins', 'Finances'],
        ['/staff', 'fa-users', 'Staff'],
        ['/grooming', 'fa-tractor', 'Grooming'],
        ['/snowmaking', 'fa-snowflake', 'Snowmaking'],
        ['/buildings', 'fa-hotel', 'Buildings'],
        ['/parking', 'fa-square-parking', 'Parking'],
        ['/equipment', 'fa-toolbox', 'Equipment'],
        ['/marketing', 'fa-bullhorn', 'Marketing'],
        ['/alliances', 'fa-handshake', 'Alliances'],
      ];
      return (
        <ul className="grid grid-cols-2 gap-1 @md:grid-cols-3 @3xl:grid-cols-6">
          {links.map(([href, icon, label]) => (
            <li key={href}>
              <a href={href} className="flex min-h-10 items-center gap-2 rounded-md px-2 text-foreground hover:bg-default pointer-coarse:min-h-11">
                <Icon name={icon} className="w-4 text-muted" />
                {label}
              </a>
            </li>
          ))}
        </ul>
      );
    }

    case 'achievements_mini': {
      const a = data.achievements_mini;
      if (a.unclaimed > 0) {
        return (
          <div className="flex flex-wrap items-center justify-between gap-3">
            <div>
              <p className="text-lg font-semibold text-warning">{plural(a.unclaimed, 'reward', 'rewards')} to claim</p>
              <p className="text-muted">{plural(a.completed, 'achievement', 'achievements')} completed</p>
            </div>
            <a href="/achievements" className={buttonVariants({ variant: 'primary', size: 'sm' })}>
              Claim rewards
            </a>
          </div>
        );
      }
      return (
        <dl className="grid grid-cols-2 gap-3">
          <MiniStat label="Completed" value={a.completed} />
          <MiniStat label="Closest to done" value={a.inProgressCount} />
          <dd className="col-span-2 text-muted">No rewards waiting.</dd>
        </dl>
      );
    }

    case 'achievements':
      return data.achievements.length === 0 ? (
        <p className="text-muted">Every achievement is complete.</p>
      ) : (
        <ul className="space-y-3">
          {data.achievements.map((ach) => (
            <li key={ach.id}>
              <Meter value={ach.progressPct} maxValue={100} size="sm" color="accent" className="gap-1">
                <div className="flex w-full items-baseline justify-between gap-2">
                  <span className="truncate">{ach.name}</span>
                  <span className="shrink-0 text-xs tabular-nums text-muted">
                    {ach.progressFormatted} of {ach.targetFormatted}
                  </span>
                </div>
                <Meter.Track>
                  <Meter.Fill />
                </Meter.Track>
              </Meter>
            </li>
          ))}
        </ul>
      );

    case 'activity':
      return activity.length === 0 ? (
        <p className="text-muted">Nothing yet. Builds, hires and each night's results show up here.</p>
      ) : (
        <ol className="divide-y divide-border">
          {activity.map((log, i) => (
            <li key={log.id || i} className="flex items-start gap-2.5 py-1.5 first:pt-0 last:pb-0">
              <Icon name={log.icon} className="mt-0.5 w-4 text-muted" />
              <span className="min-w-0 flex-1">{log.message}</span>
              <span className="shrink-0 text-xs tabular-nums text-muted">Day {log.game_day}</span>
            </li>
          ))}
        </ol>
      );

    case 'parking_mini': {
      const p = data.parking_mini;
      if (p.lotsCount === 0) {
        return (
          <WidgetEmpty href="/parking" action="Build a parking lot">
            No parking yet. Skiers who drive need somewhere to park.
          </WidgetEmpty>
        );
      }
      return (
        <div className="space-y-2">
          <dl className="grid grid-cols-2 gap-3">
            <MiniStat label="Lots" value={p.lotsCount} />
            <MiniStat label="Spaces" value={p.spacesFormatted} />
          </dl>
          <Meter value={Math.min(100, p.pct)} maxValue={100} size="sm" color={p.pct > 90 ? 'danger' : p.pct > 70 ? 'warning' : 'accent'} className="gap-1">
            <div className="flex w-full justify-between text-sm">
              <span className="text-muted">Full</span>
              <span className="font-semibold tabular-nums">{p.pct}%</span>
            </div>
            <Meter.Track>
              <Meter.Fill />
            </Meter.Track>
          </Meter>
        </div>
      );
    }

    case 'terrain_parks_mini': {
      const t = data.terrain_parks_mini;
      if (t.openCount === 0 && t.buildingCount === 0) {
        return (
          <WidgetEmpty href="/terrain-parks" action="Build a park">
            No terrain parks yet.
          </WidgetEmpty>
        );
      }
      return (
        <dl className="grid grid-cols-3 gap-3">
          <MiniStat label="Open" value={t.openCount} />
          <MiniStat label="Building" value={t.buildingCount} />
          <MiniStat label="Riders" value={t.ridersFormatted} />
        </dl>
      );
    }

    case 'finances_mini': {
      const f = data.finances_mini;
      return (
        <dl className="space-y-1.5">
          <div className="flex justify-between gap-3">
            <dt className="text-muted">Earned</dt>
            <dd className="tabular-nums">{f.incomeText}</dd>
          </div>
          <div className="flex justify-between gap-3">
            <dt className="text-muted">Spent</dt>
            <dd className="tabular-nums">{f.expensesText}</dd>
          </div>
          <div className="flex justify-between gap-3 border-t border-border pt-1.5 font-semibold">
            <dt>Net since opening</dt>
            <dd className={`tabular-nums ${f.isNetPositive ? 'text-success' : 'text-danger'}`}>{f.netProfitText}</dd>
          </div>
        </dl>
      );
    }

    case 'staff_mini': {
      const s = data.staff_mini;
      if (s.staffCount === 0) {
        return (
          <WidgetEmpty href="/staff/hire" action="Hire staff">
            No one hired yet. Lifts and runs need staff to open.
          </WidgetEmpty>
        );
      }
      return (
        <div className="space-y-3">
          <dl className="grid grid-cols-2 gap-x-4 gap-y-1 @md:grid-cols-3">
            {s.roles.map((r) => (
              <div key={r.role} className="flex justify-between gap-2">
                <dt className="truncate text-muted">{r.roleName}</dt>
                <dd className="font-semibold tabular-nums">{r.count}</dd>
              </div>
            ))}
          </dl>
          <Meter value={s.avgMorale} maxValue={100} size="sm" color={s.moraleTone} className="gap-1">
            <div className="flex w-full justify-between text-sm">
              <span className="text-muted">Average morale</span>
              <span className="font-semibold tabular-nums">{s.avgMorale}%</span>
            </div>
            <Meter.Track>
              <Meter.Fill />
            </Meter.Track>
          </Meter>
        </div>
      );
    }

    case 'equipment_mini':
      return data.equipment_mini.unitsCount === 0 ? (
        <WidgetEmpty href="/equipment" action="Buy equipment">
          No machines yet. Snowcats groom your runs overnight.
        </WidgetEmpty>
      ) : (
        <dl>
          <MiniStat label="Machines" value={data.equipment_mini.unitsCount} />
        </dl>
      );

    case 'insurance_mini':
      return data.insurance_mini.activeCount === 0 ? (
        <WidgetEmpty href="/insurance" action="See policies">
          No active policy.
        </WidgetEmpty>
      ) : (
        <dl className="grid grid-cols-2 gap-3">
          <MiniStat label="Active" value={data.insurance_mini.activeCount} tone="text-success" />
          <MiniStat label="Not taken" value={data.insurance_mini.availableCount} />
        </dl>
      );

    case 'loans_mini':
      return data.loans_mini.activeCount === 0 ? (
        <p className="text-muted">No loans to pay off.</p>
      ) : (
        <dl>
          <MiniStat label={`Left to pay on ${plural(data.loans_mini.activeCount, 'loan', 'loans')}`} value={data.loans_mini.totalDebtText} tone="text-danger" />
        </dl>
      );

    case 'marketing_mini':
      return data.marketing_mini.campaignsCount === 0 ? (
        <WidgetEmpty href="/marketing" action="Start a campaign">
          No campaign running.
        </WidgetEmpty>
      ) : (
        <dl>
          <MiniStat label="Campaigns running" value={data.marketing_mini.campaignsCount} />
        </dl>
      );

    case 'roadmap_mini':
      return <RoadmapMini initial={data.roadmap_mini?.items ?? []} />;

    default:
      return null;
  }
}

const SIZE_LABEL: Record<WidgetSize, string> = { small: 'Small', medium: 'Medium', large: 'Wide' };

export default function DashboardPage({
  hero,
  alerts,
  unclaimedAchievements,
  kpis,
  widgets: initialWidgets,
  availableWidgets,
  data,
  demoEstimate,
  launchChecklist,
  settleTime = '00:05',
}: DashboardPageProps) {
  const [widgets, setWidgets] = useState<DashboardWidgetItem[]>(initialWidgets);
  const [editMode, setEditMode] = useState(false);
  const [activityLogs, setActivityLogs] = useState(data.activity);
  const [draggingKey, setDraggingKey] = useState<string | null>(null);
  const [dragOverKey, setDragOverKey] = useState<string | null>(null);
  const [libraryOpen, setLibraryOpen] = useState(false);
  const [resetOpen, setResetOpen] = useState(false);
  const [librarySearch, setLibrarySearch] = useState('');
  const [libraryCategory, setLibraryCategory] = useState<'all' | WidgetCategory>('all');
  const [announcement, setAnnouncement] = useState('');
  const dragItem = useRef<string | null>(null);
  const editHeading = useRef<HTMLHeadingElement>(null);

  // Refresh recent activity every 30 seconds while the tab is visible
  useEffect(() => {
    const poll = async () => {
      if (document.hidden) return;
      try {
        const res = await fetch('/dashboard/activity', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        if (!res.ok) return;
        const fresh = await res.json();
        if (Array.isArray(fresh) && fresh.length > 0) {
          setActivityLogs(
            fresh.slice(0, 6).map((log: { id?: number; message?: string; icon?: string; game_day?: number }) => ({
              id: Number(log.id || 0),
              message: String(log.message || ''),
              icon: String(log.icon || 'fa-circle-dot').replace(/^fa-(solid|regular|brands)\s+/, ''),
              game_day: Number(log.game_day || hero.gameDay),
            })),
          );
        }
      } catch {
        // Offline or signed out: keep the list we have
      }
    };
    const interval = setInterval(poll, 30000);
    return () => clearInterval(interval);
  }, [hero.gameDay]);

  useEffect(() => {
    if (editMode) editHeading.current?.focus();
  }, [editMode]);

  const nameOf = (key: string) => WIDGET_META[key]?.name ?? widgets.find((w) => w.widget_key === key)?.name ?? key;

  /** Posts a layout change; on failure puts the layout back and says so */
  const save = async (url: string, payload: Record<string, unknown>, rollback: DashboardWidgetItem[]): Promise<boolean> => {
    const { field, token } = csrf();
    try {
      const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token },
        body: JSON.stringify({ ...payload, [field]: token }),
      });
      const json = await res.json().catch(() => null);
      if (res.ok && json?.success) return true;
    } catch {
      // handled below
    }
    setWidgets(rollback);
    toast.danger('Your layout change was not saved. Reload the page and try again.');
    return false;
  };

  const visibleWidgets = widgets.filter((w) => w.visible);
  const hiddenWidgets = widgets.filter((w) => !w.visible);

  const reorder = (keys: string[], prev: DashboardWidgetItem[]) => {
    const byKey = new Map(prev.map((w) => [w.widget_key, w]));
    const shown = keys.map((k) => byKey.get(k)!).filter(Boolean);
    const hidden = prev.filter((w) => !w.visible);
    return [...shown, ...hidden].map((w, idx) => ({ ...w, sort_order: idx }));
  };

  const handleResize = (key: string, size: WidgetSize) => {
    const prev = widgets;
    setWidgets(prev.map((w) => (w.widget_key === key ? { ...w, size } : w)));
    setAnnouncement(`${nameOf(key)} is now ${SIZE_LABEL[size].toLowerCase()}.`);
    void save('/dashboard/resize-widget', { widget_key: key, size }, prev);
  };

  const handleToggle = (key: string, visible: boolean) => {
    const prev = widgets;
    setWidgets(prev.map((w) => (w.widget_key === key ? { ...w, visible } : w)));
    setAnnouncement(visible ? `${nameOf(key)} added to the dashboard.` : `${nameOf(key)} hidden.`);
    // The server flips the stored value, so only send when it really changes
    if (prev.find((w) => w.widget_key === key)?.visible !== visible) {
      void save('/dashboard/toggle-widget', { widget_key: key }, prev);
    }
  };

  const moveTo = (key: string, to: number) => {
    const keys = visibleWidgets.map((w) => w.widget_key);
    const from = keys.indexOf(key);
    if (from === -1 || to < 0 || to >= keys.length || from === to) return;
    keys.splice(from, 1);
    keys.splice(to, 0, key);
    const prev = widgets;
    setWidgets(reorder(keys, prev));
    setAnnouncement(`${nameOf(key)} moved to position ${to + 1} of ${keys.length}.`);
    void save('/dashboard/reorder-widgets', { order: keys }, prev);
  };

  const handleDrop = (targetKey: string) => {
    const sourceKey = dragItem.current;
    dragItem.current = null;
    setDraggingKey(null);
    setDragOverKey(null);
    if (!sourceKey || sourceKey === targetKey) return;
    moveTo(sourceKey, visibleWidgets.findIndex((w) => w.widget_key === targetKey));
  };

  const handleApplyPreset = async (preset: LayoutPreset) => {
    const prev = widgets;
    const inPreset = new Set(preset.widgets.map((p) => p.key));
    const next: DashboardWidgetItem[] = [];
    for (const p of preset.widgets) {
      const existing = prev.find((w) => w.widget_key === p.key);
      const aw = availableWidgets.find((a) => a.key === p.key);
      if (existing) next.push({ ...existing, visible: true, size: p.size, sort_order: next.length });
      else if (aw) next.push({ widget_key: aw.key, name: aw.name, icon: aw.icon, size: p.size, visible: true, sort_order: next.length });
    }
    for (const w of prev) {
      if (!inPreset.has(w.widget_key)) next.push({ ...w, visible: false, sort_order: next.length });
    }
    setWidgets(next);
    const ok = await save(
      '/dashboard/batch-update-widgets',
      { widgets: next.map((w) => ({ widget_key: w.widget_key, visible: w.visible ? 1 : 0, size: w.size, sort_order: w.sort_order })) },
      prev,
    );
    if (ok) toast.success(`Switched to the ${preset.name} layout.`);
  };

  const handleResetLayout = async () => {
    setResetOpen(false);
    const prev = widgets;
    const ok = await save('/dashboard/reset-widgets', {}, prev);
    if (!ok) return;
    setWidgets(
      availableWidgets.map((aw, idx) => ({
        widget_key: aw.key,
        name: aw.name,
        icon: aw.icon,
        size: (aw.size as WidgetSize) || 'medium',
        visible: Boolean(aw.default),
        sort_order: idx,
      })),
    );
    toast.success('Dashboard back to the default layout.');
  };

  const handleShowAll = async () => {
    const prev = widgets;
    const next = prev.map((w, idx) => ({ ...w, visible: true, sort_order: idx }));
    setWidgets(next);
    const ok = await save(
      '/dashboard/batch-update-widgets',
      { widgets: next.map((w) => ({ widget_key: w.widget_key, visible: 1, size: w.size, sort_order: w.sort_order })) },
      prev,
    );
    if (ok) setAnnouncement('Every widget is on the dashboard.');
  };

  const filteredLibraryWidgets = useMemo(() => {
    const q = librarySearch.toLowerCase().trim();
    return widgets.filter((w) => {
      const meta = WIDGET_META[w.widget_key];
      if (libraryCategory !== 'all' && meta?.category !== libraryCategory) return false;
      if (!q) return true;
      return [meta?.name ?? w.name, meta?.description ?? '', meta ? CATEGORY_LABEL[meta.category] : ''].some((s) => s.toLowerCase().includes(q));
    });
  }, [widgets, libraryCategory, librarySearch]);

  // Where tonight's numbers come from: the live forecast, the first-night estimate, or the last settle
  const mode: 'forecast' | 'estimate' | 'last' = kpis.visitorsSettledFormatted ? 'forecast' : demoEstimate ? 'estimate' : 'last';
  const tonightTitle = mode === 'last' ? 'Last night' : 'Tonight';
  const tonightNote =
    mode === 'forecast'
      ? `Forecast for the ${settleTime} settle, from your resort as it is now. Builds and price changes show here at once.`
      : mode === 'estimate'
        ? `Estimate for your first night. Skiers come and money moves at ${settleTime} server time.`
        : `Skiers and income from the last settle at ${settleTime}, with today's costs.`;
  const ledgerNote = mode === 'forecast' ? 'Forecast for tonight' : mode === 'estimate' ? 'Estimate for your first night' : "Last night's income, today's costs";

  // Things the player should act on, most urgent first
  const todo: { key: string; tone: 'danger' | 'warning' | 'accent'; icon: string; msg: string; href: string; action: string }[] = [
    ...alerts
      .slice()
      .sort((a, b) => (a.type === b.type ? 0 : a.type === 'danger' ? -1 : 1))
      .map((a, i) => ({ key: `alert-${i}`, tone: a.type, icon: a.icon, msg: a.msg, href: a.link, action: ALERT_ACTION[a.link] ?? 'Fix' })),
    ...(unclaimedAchievements > 0
      ? [{ key: 'ach', tone: 'warning' as const, icon: 'fa-award', msg: `${plural(unclaimedAchievements, 'achievement reward', 'achievement rewards')} to claim.`, href: '/achievements', action: 'Claim' }]
      : []),
    ...(hero.bonusAvailable && !launchChecklist?.show
      ? [{ key: 'bonus', tone: 'accent' as const, icon: 'fa-gift', msg: "Today's daily bonus is waiting.", href: '/daily-bonus', action: 'Claim bonus' }]
      : []),
  ];
  const toneText = { danger: 'text-danger', warning: 'text-warning', accent: 'text-link' } as const;

  const checklist = launchChecklist?.show ? launchChecklist : null;
  const nextStep = checklist?.steps.find((s) => !s.done);

  return (
    <div className="mx-auto flex max-w-7xl flex-col gap-6 p-4 text-foreground lg:p-8">
      {/* Title row */}
      <header className="flex flex-wrap items-end justify-between gap-x-6 gap-y-3">
        <div className="min-w-0 space-y-1">
          <h1 className="text-2xl font-semibold sm:text-3xl">{hero.resortMapName}</h1>
          <p className="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted">
            <Chip size="sm" variant="soft" color={hero.resortOpen ? 'success' : 'danger'}>
              <Icon name={hero.resortOpen ? 'fa-circle-check' : 'fa-circle-xmark'} />
              {hero.resortOpen ? 'Open' : 'Closed'}
            </Chip>
            <span className="tabular-nums">
              Season {hero.seasonNum}, day {hero.gameDay}
            </span>
            <span className="tabular-nums">
              {hero.liftCount} of {plural(hero.totalLifts, 'lift', 'lifts')} and {hero.slopeCount} of {plural(hero.totalSlopes, 'run', 'runs')} open
            </span>
            <span className="tabular-nums">Reputation {hero.repScoreFormatted}</span>
          </p>
        </div>
        <div className="flex w-full gap-2 sm:w-auto">
          <Button variant="secondary" onPress={() => setEditMode((v) => !v)} aria-pressed={editMode} className="flex-1 sm:flex-none">
            <Icon name={editMode ? 'fa-check' : 'fa-sliders'} />
            {editMode ? 'Done' : 'Customize'}
          </Button>
          <a href="/map" className={buttonVariants({ variant: 'primary', className: 'flex-1 sm:flex-none' })}>
            <Icon name="fa-map" />
            Trail map
          </a>
        </div>
      </header>

      {/* First days: the steps that open the resort */}
      {checklist && (
        <section aria-labelledby="launch-title" className="rounded-xl border border-border bg-surface p-4 sm:p-5">
          <div className="flex flex-wrap items-end justify-between gap-3">
            <div>
              <h2 id="launch-title" className="text-lg font-semibold">
                {checklist.isComplete ? 'Your resort is ready' : 'Open your resort'}
              </h2>
              <p className="text-sm text-muted">
                {checklist.isComplete
                  ? `Every first step is done. Your first skiers arrive at ${settleTime} server time.`
                  : `Finish these and your first skiers arrive at ${settleTime} server time.`}
              </p>
            </div>
            <ProgressBar
              value={checklist.completed}
              maxValue={Math.max(1, checklist.total)}
              size="sm"
              color={checklist.isComplete ? 'success' : 'accent'}
              aria-label="Steps done"
              className="w-full gap-1 sm:w-48"
            >
              <span className="w-full text-right text-sm tabular-nums text-muted">
                {checklist.completed} of {checklist.total} done
              </span>
              <ProgressBar.Track>
                <ProgressBar.Fill />
              </ProgressBar.Track>
            </ProgressBar>
          </div>
          <ol className="mt-4 divide-y divide-border">
            {checklist.steps.map((step, idx) => (
              <li key={step.id} className="flex flex-wrap items-center gap-x-3 gap-y-2 py-3 first:pt-0 last:pb-0">
                <span
                  className={`flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold ${
                    step.done ? 'bg-success text-success-foreground' : 'bg-default text-foreground'
                  }`}
                >
                  {step.done ? <Icon name="fa-check" /> : <span aria-hidden="true">{idx + 1}</span>}
                  <span className="sr-only">{step.done ? 'Done:' : `Step ${idx + 1}:`}</span>
                </span>
                <div className="min-w-0 flex-1 basis-48">
                  <h3 className={`font-semibold ${step.done ? 'text-muted line-through decoration-1' : ''}`}>{step.title}</h3>
                  {!step.done && <p className="text-sm text-muted">{step.description}</p>}
                </div>
                {!step.done && (
                  <a
                    href={step.link}
                    className={buttonVariants({
                      variant: step === nextStep ? 'primary' : 'secondary',
                      size: 'sm',
                      className: 'ml-11 sm:ml-0',
                    })}
                  >
                    {step.cta}
                  </a>
                )}
              </li>
            ))}
          </ol>
        </section>
      )}

      {/* Tonight + what needs doing */}
      <div className={`grid gap-4 ${todo.length > 0 ? 'lg:grid-cols-3' : ''}`}>
        <section aria-labelledby="tonight-title" className={`@container rounded-xl border border-border bg-surface p-4 sm:p-5 ${todo.length > 0 ? 'lg:col-span-2' : ''}`}>
          <div className="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
            <h2 id="tonight-title" className="text-lg font-semibold">
              {tonightTitle}
            </h2>
            {mode !== 'last' && (
              <Chip size="sm" variant="soft" color="default">
                {mode === 'forecast' ? 'Forecast' : 'Estimate'}
              </Chip>
            )}
          </div>
          <p className="mt-1 text-sm text-muted">{tonightNote}</p>

          {!hero.resortOpen && (
            <p className="mt-3 flex flex-wrap items-center gap-x-2 text-sm text-danger">
              <Icon name="fa-circle-xmark" />
              Your resort is closed, so no skiers come.
              <a href="/resort" className={quietLink}>
                Open the resort
              </a>
            </p>
          )}

          <dl className="mt-4 grid grid-cols-2 gap-x-4 gap-y-4 @2xl:grid-cols-4">
            <Stat label="Net" value={kpis.dailyProfitText} tone={kpis.dailyProfitPositive ? 'text-success' : 'text-danger'} big />
            <Stat
              label="Skiers"
              value={kpis.dailyVisitorsFormatted}
              big
              note={mode === 'forecast' ? `Last night ${kpis.visitorsSettledFormatted}` : undefined}
            />
            <Stat label="Income" value={kpis.incomeText} note={`Day pass ${kpis.ticketPriceText}`} />
            <Stat label="Costs" value={kpis.expensesText} note={`Lifts carry ${kpis.totalLiftCapacityFormatted} an hour`} />
          </dl>

          {kpis.visitorsTurnedAway > 0 && (
            <p className="mt-4 flex flex-wrap items-center gap-x-2 border-t border-border pt-3 text-sm">
              <Icon name="fa-triangle-exclamation" className="text-warning" />
              <span>
                <strong className="font-semibold tabular-nums text-warning">{plural(kpis.visitorsTurnedAway, 'skier', 'skiers')}</strong> turned away: your lifts are full.
              </span>
              <a href="/map" className={quietLink}>
                Add lift capacity
              </a>
            </p>
          )}
        </section>

        {todo.length > 0 && (
          <section aria-labelledby="todo-title" className="rounded-xl border border-border bg-surface p-4 sm:p-5">
            <h2 id="todo-title" className="text-lg font-semibold">
              Needs you
            </h2>
            <ul className="mt-2 divide-y divide-border">
              {todo.map((t) => (
                <li key={t.key} className="flex items-start gap-3 py-2.5 last:pb-0">
                  <Icon name={t.icon} className={`mt-0.5 w-4 ${toneText[t.tone]}`} />
                  <span className="min-w-0 flex-1 text-sm">{t.msg}</span>
                  <a href={t.href} className={buttonVariants({ variant: t.tone === 'danger' ? 'primary' : 'secondary', size: 'sm', className: 'shrink-0' })}>
                    {t.action}
                  </a>
                </li>
              ))}
            </ul>
          </section>
        )}
      </div>

      {/* Customize bar */}
      {editMode && (
        <section aria-labelledby="customize-title" className="rounded-xl border border-accent bg-surface p-4">
          <div className="flex flex-wrap items-start justify-between gap-3">
            <div>
              <h2 id="customize-title" ref={editHeading} tabIndex={-1} className="text-lg font-semibold outline-none">
                Customize your dashboard
              </h2>
              <p className="text-sm text-muted">
                Drag widgets, or use the arrows, to change the order. Changes save as you go. {visibleWidgets.length} shown, {hiddenWidgets.length} hidden.
              </p>
            </div>
            <Button variant="primary" onPress={() => setEditMode(false)}>
              <Icon name="fa-check" />
              Done
            </Button>
          </div>
          <div className="mt-3 flex flex-wrap items-center gap-2">
            <span id="preset-label" className="text-sm text-muted">
              Layouts:
            </span>
            <div role="group" aria-labelledby="preset-label" className="flex flex-wrap gap-2">
              {PRESETS.map((p) => (
                <Button key={p.id} size="sm" variant="tertiary" onPress={() => handleApplyPreset(p)}>
                  {p.name}
                </Button>
              ))}
            </div>
            <span className="hidden h-6 w-px bg-border sm:block" aria-hidden="true" />
            <Button size="sm" variant="secondary" onPress={() => setLibraryOpen(true)}>
              <Icon name="fa-plus" />
              Add widgets{hiddenWidgets.length > 0 ? ` (${hiddenWidgets.length})` : ''}
            </Button>
            <Button size="sm" variant="ghost" onPress={() => setResetOpen(true)}>
              <Icon name="fa-rotate-left" />
              Reset
            </Button>
          </div>
        </section>
      )}

      <p role="status" aria-live="polite" className="sr-only">
        {announcement}
      </p>

      {/* No widgets */}
      {visibleWidgets.length === 0 && (
        <div className="flex flex-col items-center gap-3 rounded-xl border border-dashed border-border px-4 py-10 text-center">
          <Icon name="fa-table-cells" className="text-3xl text-muted" />
          <p className="font-semibold">Every widget is hidden</p>
          <p className="max-w-sm text-sm text-muted">Add the ones you want, or go back to the default layout.</p>
          <div className="flex flex-wrap justify-center gap-2">
            <Button
              variant="primary"
              onPress={() => {
                setEditMode(true);
                setLibraryOpen(true);
              }}
            >
              <Icon name="fa-plus" />
              Add widgets
            </Button>
            <Button variant="secondary" onPress={handleResetLayout}>
              Default layout
            </Button>
          </div>
        </div>
      )}

      {/* Widgets: 1 column on phones, 2 on tablets, 4 on desktops; each widget lays itself out by its own width */}
      {visibleWidgets.length > 0 && (
        <ul aria-label="Widgets" className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {visibleWidgets.map((w, idx) => {
            const meta = WIDGET_META[w.widget_key];
            const name = meta?.name ?? w.name;
            const span = w.size === 'large' ? 'sm:col-span-2 lg:col-span-4' : w.size === 'medium' ? 'sm:col-span-2' : '';
            const isDropTarget = dragOverKey === w.widget_key && draggingKey !== w.widget_key;
            const isDragging = draggingKey === w.widget_key;
            const titleId = `widget-${w.widget_key}`;
            return (
              <li
                key={w.widget_key}
                aria-labelledby={titleId}
                draggable={editMode}
                onDragStart={(e) => {
                  dragItem.current = w.widget_key;
                  setDraggingKey(w.widget_key);
                  e.dataTransfer.effectAllowed = 'move';
                }}
                onDragEnter={() => editMode && setDragOverKey(w.widget_key)}
                onDragOver={(e) => {
                  if (!editMode) return;
                  e.preventDefault();
                  if (dragOverKey !== w.widget_key) setDragOverKey(w.widget_key);
                }}
                onDragEnd={() => {
                  dragItem.current = null;
                  setDraggingKey(null);
                  setDragOverKey(null);
                }}
                onDrop={(e) => {
                  e.preventDefault();
                  handleDrop(w.widget_key);
                }}
                className={`@container flex flex-col rounded-xl border bg-surface p-4 text-sm transition-colors duration-150 ${span} ${
                  isDropTarget
                    ? 'border-accent ring-2 ring-accent'
                    : isDragging
                      ? 'border-dashed border-accent opacity-50'
                      : editMode
                        ? 'cursor-grab border-dashed border-border'
                        : 'border-border'
                }`}
              >
                <div className="mb-3 flex flex-wrap items-center justify-between gap-x-3 gap-y-2">
                  <h2 id={titleId} className="flex min-w-0 items-center gap-2 text-base font-semibold">
                    {editMode && <Icon name="fa-grip-vertical" className="text-muted" />}
                    <Icon name={meta?.icon ?? w.icon} className="w-4 text-muted" />
                    <span className="truncate">{name}</span>
                  </h2>
                  {!editMode && meta?.link && (
                    <a href={meta.link.href} className={quietLink}>
                      {meta.link.label}
                    </a>
                  )}
                </div>

                {editMode && (
                  <div className="mb-3 flex flex-wrap items-center gap-2 border-b border-border pb-3">
                    <div role="group" aria-label={`Order of ${name}`} className="flex gap-1">
                      <Button size="sm" variant="tertiary" isIconOnly onPress={() => moveTo(w.widget_key, idx - 1)} isDisabled={idx === 0} aria-label={`Move ${name} earlier`}>
                        <Icon name="fa-arrow-up" />
                      </Button>
                      <Button
                        size="sm"
                        variant="tertiary"
                        isIconOnly
                        onPress={() => moveTo(w.widget_key, idx + 1)}
                        isDisabled={idx === visibleWidgets.length - 1}
                        aria-label={`Move ${name} later`}
                      >
                        <Icon name="fa-arrow-down" />
                      </Button>
                    </div>
                    <div role="group" aria-label={`Width of ${name}`} className="flex gap-1">
                      {(['small', 'medium', 'large'] as const).map((sz) => (
                        <Button
                          key={sz}
                          size="sm"
                          variant={w.size === sz ? 'primary' : 'tertiary'}
                          aria-pressed={w.size === sz}
                          onPress={() => w.size !== sz && handleResize(w.widget_key, sz)}
                        >
                          {SIZE_LABEL[sz]}
                        </Button>
                      ))}
                    </div>
                    <Button size="sm" variant="ghost" onPress={() => handleToggle(w.widget_key, false)} aria-label={`Hide ${name}`} className="ml-auto">
                      <Icon name="fa-eye-slash" />
                      Hide
                    </Button>
                  </div>
                )}

                <div className="flex-1">
                  <WidgetBody widgetKey={w.widget_key} data={data} activity={activityLogs} tonightLabel={ledgerNote} />
                </div>
              </li>
            );
          })}
        </ul>
      )}

      {/* Widget library */}
      <Drawer.Backdrop isOpen={libraryOpen} onOpenChange={setLibraryOpen}>
        <Drawer.Content placement="right">
          <Drawer.Dialog aria-labelledby="library-title" className="flex w-[28rem] max-w-[92vw] flex-col">
            <Drawer.Header className="flex-row items-start justify-between gap-3">
              <div>
                <Drawer.Heading id="library-title" className="text-lg font-semibold">
                  Widgets
                </Drawer.Heading>
                <p className="text-sm text-muted">Choose what your dashboard shows.</p>
              </div>
              <Drawer.CloseTrigger aria-label="Close widgets" />
            </Drawer.Header>
            <Drawer.Body className="flex-1 space-y-4 overflow-y-auto">
              <div className="space-y-1.5">
                <label htmlFor="widget-search" className="text-sm font-medium">
                  Search
                </label>
                <input
                  id="widget-search"
                  type="search"
                  value={librarySearch}
                  onChange={(e) => setLibrarySearch(e.target.value)}
                  placeholder="Weather, staff, loans…"
                  className="w-full rounded-xl border border-border bg-surface px-3 py-2 text-base text-foreground placeholder:text-muted focus-visible:border-accent focus-visible:outline-2 focus-visible:outline-accent sm:text-sm"
                />
              </div>
              <div role="group" aria-label="Show widgets about" className="flex flex-wrap gap-1.5">
                {(['all', 'operations', 'financial', 'activity'] as const).map((cat) => (
                  <Button
                    key={cat}
                    size="sm"
                    variant={libraryCategory === cat ? 'primary' : 'tertiary'}
                    aria-pressed={libraryCategory === cat}
                    onPress={() => setLibraryCategory(cat)}
                  >
                    {cat === 'all' ? 'All' : CATEGORY_LABEL[cat]}
                  </Button>
                ))}
              </div>
              {filteredLibraryWidgets.length === 0 ? (
                <p className="py-6 text-center text-sm text-muted">No widget matches "{librarySearch}".</p>
              ) : (
                <ul className="divide-y divide-border">
                  {filteredLibraryWidgets.map((w) => {
                    const meta = WIDGET_META[w.widget_key];
                    const name = meta?.name ?? w.name;
                    return (
                      <li key={w.widget_key} className="flex items-start gap-3 py-3">
                        <Icon name={meta?.icon ?? w.icon} className="mt-1 w-4 text-muted" />
                        <div className="min-w-0 flex-1">
                          <p className="font-semibold">{name}</p>
                          <p className="text-sm text-muted">{meta?.description}</p>
                        </div>
                        <Button
                          size="sm"
                          variant={w.visible ? 'tertiary' : 'secondary'}
                          onPress={() => handleToggle(w.widget_key, !w.visible)}
                          aria-label={w.visible ? `Hide ${name}` : `Add ${name}`}
                          className="shrink-0"
                        >
                          {w.visible ? 'Hide' : 'Add'}
                        </Button>
                      </li>
                    );
                  })}
                </ul>
              )}
            </Drawer.Body>
            <Drawer.Footer className="flex justify-between gap-2">
              <Button variant="tertiary" onPress={handleShowAll} isDisabled={hiddenWidgets.length === 0}>
                Show all {widgets.length}
              </Button>
              <Button variant="primary" onPress={() => setLibraryOpen(false)}>
                Done
              </Button>
            </Drawer.Footer>
          </Drawer.Dialog>
        </Drawer.Content>
      </Drawer.Backdrop>

      {/* Reset confirmation; Escape cancels (DESIGN.md) */}
      <AlertDialog.Backdrop isOpen={resetOpen} onOpenChange={setResetOpen} isKeyboardDismissDisabled={false}>
        <AlertDialog.Container>
          <AlertDialog.Dialog>
            <AlertDialog.Header>
              <AlertDialog.Heading>Go back to the default layout?</AlertDialog.Heading>
            </AlertDialog.Header>
            <AlertDialog.Body>Your widget order, sizes and hidden widgets are replaced by the default layout.</AlertDialog.Body>
            <AlertDialog.Footer>
              <Button variant="tertiary" onPress={() => setResetOpen(false)}>
                Keep my layout
              </Button>
              <Button variant="danger" onPress={handleResetLayout}>
                Reset layout
              </Button>
            </AlertDialog.Footer>
          </AlertDialog.Dialog>
        </AlertDialog.Container>
      </AlertDialog.Backdrop>
    </div>
  );
}
