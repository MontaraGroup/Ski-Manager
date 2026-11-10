import { useEffect, useMemo, useRef, useState, type DragEvent } from 'react';
import { Button } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { csrf } from '@/components/Page';

export type WidgetCategory = 'operations' | 'financial' | 'activity';

export interface WidgetMeta {
  name: string;
  category: WidgetCategory;
  categoryLabel: string;
  icon: string;
  description: string;
  defaultSize: 'small' | 'medium' | 'large';
}

export const WIDGET_META: Record<string, WidgetMeta> = {
  overview: {
    name: 'Mountain Overview',
    category: 'operations',
    categoryLabel: 'Operations',
    icon: 'fa-mountain-sun',
    description: 'Slopes, lifts, trail status, buildings, and grooming condition breakdown.',
    defaultSize: 'large',
  },
  season: {
    name: 'Season Progress',
    category: 'operations',
    categoryLabel: 'Operations',
    icon: 'fa-calendar',
    description: 'Current season progress calendar with winter vs summer days breakdown.',
    defaultSize: 'large',
  },
  weather_mini: {
    name: 'Live Weather',
    category: 'operations',
    categoryLabel: 'Operations',
    icon: 'fa-cloud-sun',
    description: 'Real-time temperature, snow base, wind velocity, and 4-day forecast.',
    defaultSize: 'medium',
  },
  actions: {
    name: 'Quick Actions',
    category: 'operations',
    categoryLabel: 'Operations',
    icon: 'fa-bolt',
    description: 'Fast shortcuts for grooming, ticket pricing, snowmaking, and lifts.',
    defaultSize: 'medium',
  },
  parking_mini: {
    name: 'Parking Capacity',
    category: 'operations',
    categoryLabel: 'Operations',
    icon: 'fa-square-parking',
    description: 'Real-time lot occupancy, total vehicle stalls, and fill percentage.',
    defaultSize: 'small',
  },
  terrain_parks_mini: {
    name: 'Terrain Parks',
    category: 'operations',
    categoryLabel: 'Operations',
    icon: 'fa-person-snowboarding',
    description: 'Open park count, freestyle feature structures, and daily rider attendance.',
    defaultSize: 'small',
  },
  equipment_mini: {
    name: 'Fleet & Equipment',
    category: 'operations',
    categoryLabel: 'Operations',
    icon: 'fa-toolbox',
    description: 'Grooming snowcats, service snowmobiles, and maintenance fleet status.',
    defaultSize: 'small',
  },
  stats: {
    name: 'Financial Ledger',
    category: 'financial',
    categoryLabel: 'Finances',
    icon: 'fa-chart-bar',
    description: 'Comprehensive breakdown of revenue, tickets, payroll, loans, and profit.',
    defaultSize: 'large',
  },
  finances_mini: {
    name: 'Finances Summary',
    category: 'financial',
    categoryLabel: 'Finances',
    icon: 'fa-coins',
    description: 'Snapshot of day revenue, operating expenditures, and net balance.',
    defaultSize: 'medium',
  },
  staff_mini: {
    name: 'Staff & Morale',
    category: 'financial',
    categoryLabel: 'Finances',
    icon: 'fa-users',
    description: 'Workforce headcount, key department distribution, and morale health.',
    defaultSize: 'medium',
  },
  insurance_mini: {
    name: 'Insurance Policies',
    category: 'financial',
    categoryLabel: 'Finances',
    icon: 'fa-shield-halved',
    description: 'Active liability policies and casualty insurance coverage status.',
    defaultSize: 'small',
  },
  loans_mini: {
    name: 'Bank Loans & Debt',
    category: 'financial',
    categoryLabel: 'Finances',
    icon: 'fa-landmark',
    description: 'Active commercial debt obligations, principal balances, and interest.',
    defaultSize: 'small',
  },
  marketing_mini: {
    name: 'Marketing Campaigns',
    category: 'financial',
    categoryLabel: 'Finances',
    icon: 'fa-bullhorn',
    description: 'Active promotional campaigns driving daily resort visitor turnout.',
    defaultSize: 'small',
  },
  achievements_mini: {
    name: 'Achievement Alerts',
    category: 'activity',
    categoryLabel: 'Achievements',
    icon: 'fa-bell',
    description: 'Pending milestones, claimable Genepi rewards, and status alerts.',
    defaultSize: 'large',
  },
  achievements: {
    name: 'Featured Achievements',
    category: 'activity',
    categoryLabel: 'Achievements',
    icon: 'fa-award',
    description: 'Top priority achievements tracked with active progress meters.',
    defaultSize: 'medium',
  },
  activity: {
    name: 'Live Activity Log',
    category: 'activity',
    categoryLabel: 'Activity',
    icon: 'fa-clock-rotate-left',
    description: 'Chronological telemetry of resort events, guest feedback, and incidents.',
    defaultSize: 'medium',
  },
};

export type PresetKey = 'default' | 'operations' | 'financial' | 'compact';

export interface LayoutPreset {
  id: PresetKey;
  name: string;
  badge: string;
  icon: string;
  desc: string;
  widgets: {
    key: string;
    size: 'small' | 'medium' | 'large';
  }[];
}

export const PRESETS: LayoutPreset[] = [
  {
    id: 'default',
    name: 'Balanced',
    badge: 'Default',
    icon: 'fa-table-cells-large',
    desc: 'Standard operations, stats, calendar, weather, actions, and recent activity.',
    widgets: [
      { key: 'stats', size: 'large' },
      { key: 'season', size: 'large' },
      { key: 'weather_mini', size: 'medium' },
      { key: 'achievements_mini', size: 'large' },
      { key: 'actions', size: 'medium' },
      { key: 'overview', size: 'large' },
      { key: 'achievements', size: 'medium' },
      { key: 'activity', size: 'medium' },
    ],
  },
  {
    id: 'operations',
    name: 'Operations',
    badge: 'Mountain',
    icon: 'fa-mountain-sun',
    desc: 'Focus on trails, snow, weather, grooming fleet, lifts, and park status.',
    widgets: [
      { key: 'overview', size: 'large' },
      { key: 'weather_mini', size: 'medium' },
      { key: 'season', size: 'medium' },
      { key: 'actions', size: 'medium' },
      { key: 'equipment_mini', size: 'small' },
      { key: 'terrain_parks_mini', size: 'small' },
      { key: 'parking_mini', size: 'small' },
      { key: 'activity', size: 'medium' },
    ],
  },
  {
    id: 'financial',
    name: 'Financial',
    badge: 'Executive',
    icon: 'fa-chart-line',
    desc: 'Cash flow, payroll, loans, insurance, marketing campaigns, and resort ledger.',
    widgets: [
      { key: 'stats', size: 'large' },
      { key: 'finances_mini', size: 'medium' },
      { key: 'staff_mini', size: 'medium' },
      { key: 'loans_mini', size: 'small' },
      { key: 'insurance_mini', size: 'small' },
      { key: 'marketing_mini', size: 'small' },
      { key: 'overview', size: 'large' },
      { key: 'actions', size: 'medium' },
    ],
  },
  {
    id: 'compact',
    name: 'Compact',
    badge: 'Minimal',
    icon: 'fa-compress',
    desc: 'Minimal high-speed layout with mountain overview, weather, stats, and quick actions.',
    widgets: [
      { key: 'overview', size: 'medium' },
      { key: 'weather_mini', size: 'medium' },
      { key: 'stats', size: 'medium' },
      { key: 'actions', size: 'medium' },
    ],
  },
];

export type DashboardHero = {
  resortMapKey: string;
  resortMapName: string;
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
  size: 'small' | 'medium' | 'large';
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
};

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
};

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
}: DashboardPageProps) {
  const [widgets, setWidgets] = useState<DashboardWidgetItem[]>(initialWidgets);
  const [editMode, setEditMode] = useState(false);
  const [activityLogs, setActivityLogs] = useState(data.activity);
  const [draggingKey, setDraggingKey] = useState<string | null>(null);
  const [dragOverKey, setDragOverKey] = useState<string | null>(null);
  const [libraryOpen, setLibraryOpen] = useState(false);
  const [librarySearch, setLibrarySearch] = useState('');
  const [libraryCategory, setLibraryCategory] = useState<'all' | 'operations' | 'financial' | 'activity'>('all');
  const [toast, setToast] = useState<{ msg: string; type: 'success' | 'info' | 'warning' | 'error' } | null>(null);
  const dragItem = useRef<string | null>(null);
  const toastTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  const showToast = (msg: string, type: 'success' | 'info' | 'warning' | 'error' = 'info') => {
    if (toastTimeoutRef.current) clearTimeout(toastTimeoutRef.current);
    setToast({ msg, type });
    toastTimeoutRef.current = setTimeout(() => {
      setToast(null);
    }, 3200);
  };

  // Poll recent activity every 30 seconds
  useEffect(() => {
    const poll = async () => {
      if (document.hidden) return;
      try {
        const res = await fetch('/dashboard/activity', {
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (res.ok) {
          const fresh = await res.json();
          if (Array.isArray(fresh) && fresh.length > 0) {
            setActivityLogs(
              fresh.slice(0, 6).map((log: any) => ({
                id: Number(log.id || 0),
                message: String(log.message || ''),
                icon: String(log.icon || 'circle-dot').replace(/^fa-(solid|regular|brands)\s+/, ''),
                game_day: Number(log.game_day || hero.gameDay),
              })),
            );
          }
        }
      } catch (err) {
        console.error('Failed to poll dashboard activity', err);
      }
    };

    const interval = setInterval(poll, 30000);
    return () => clearInterval(interval);
  }, [hero.gameDay]);

  const sendPost = async (url: string, payload: Record<string, any>) => {
    const { field, token } = csrf();
    try {
      const res = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': token,
        },
        body: JSON.stringify({ ...payload, [field]: token }),
      });
      return await res.json();
    } catch (err) {
      console.error(`POST ${url} failed`, err);
    }
  };

  const handleResize = async (key: string, size: 'small' | 'medium' | 'large') => {
    setWidgets((prev) =>
      prev.map((w) => (w.widget_key === key ? { ...w, size } : w)),
    );
    showToast(`Resized '${WIDGET_META[key]?.name || key}' to ${size}`, 'info');
    await sendPost('/dashboard/resize-widget', { widget_key: key, size });
  };

  const handleToggle = async (key: string, visible: boolean) => {
    setWidgets((prev) =>
      prev.map((w) => (w.widget_key === key ? { ...w, visible } : w)),
    );
    showToast(
      visible
        ? `Added '${WIDGET_META[key]?.name || key}' to dashboard`
        : `Hidden '${WIDGET_META[key]?.name || key}'`,
      'info',
    );
    await sendPost('/dashboard/toggle-widget', { widget_key: key });
  };

  const handleMove = async (key: string, direction: 'up' | 'down') => {
    const visibleWidgets = widgets.filter((w) => w.visible);
    const index = visibleWidgets.findIndex((w) => w.widget_key === key);
    if (index === -1) return;

    const swapIndex = direction === 'up' ? index - 1 : index + 1;
    if (swapIndex < 0 || swapIndex >= visibleWidgets.length) return;

    const reordered = [...visibleWidgets];
    const temp = reordered[index];
    reordered[index] = reordered[swapIndex];
    reordered[swapIndex] = temp;

    const newOrderKeys = reordered.map((w) => w.widget_key);

    setWidgets((prev) => {
      const hiddenOnes = prev.filter((w) => !w.visible);
      return [...reordered, ...hiddenOnes].map((w, idx) => ({ ...w, sort_order: idx }));
    });

    await sendPost('/dashboard/reorder-widgets', { order: newOrderKeys });
  };

  const handleDragStart = (key: string) => {
    dragItem.current = key;
    setDraggingKey(key);
  };

  const handleDragEnd = () => {
    dragItem.current = null;
    setDraggingKey(null);
    setDragOverKey(null);
  };

  const handleDrop = async (targetKey: string) => {
    const sourceKey = dragItem.current;
    setDraggingKey(null);
    setDragOverKey(null);
    dragItem.current = null;

    if (!sourceKey || sourceKey === targetKey) return;

    const visibleWidgets = widgets.filter((w) => w.visible);
    const sourceIdx = visibleWidgets.findIndex((w) => w.widget_key === sourceKey);
    const targetIdx = visibleWidgets.findIndex((w) => w.widget_key === targetKey);
    if (sourceIdx === -1 || targetIdx === -1) return;

    const reordered = [...visibleWidgets];
    const [moved] = reordered.splice(sourceIdx, 1);
    reordered.splice(targetIdx, 0, moved);

    const newOrderKeys = reordered.map((w) => w.widget_key);

    setWidgets((prev) => {
      const hiddenOnes = prev.filter((w) => !w.visible);
      return [...reordered, ...hiddenOnes].map((w, idx) => ({ ...w, sort_order: idx }));
    });

    showToast(`Moved ${WIDGET_META[sourceKey]?.name || sourceKey}`, 'info');
    await sendPost('/dashboard/reorder-widgets', { order: newOrderKeys });
  };

  const handleApplyPreset = async (preset: LayoutPreset) => {
    const presetMap = new Map(preset.widgets.map((p, idx) => [p.key, { size: p.size, order: idx }]));

    const newWidgets: DashboardWidgetItem[] = [];

    // Add preset widgets in exact preset order
    for (const p of preset.widgets) {
      const existing = widgets.find((w) => w.widget_key === p.key);
      const aw = availableWidgets.find((a) => a.key === p.key);
      if (existing) {
        newWidgets.push({
          ...existing,
          visible: true,
          size: p.size,
          sort_order: newWidgets.length,
        });
      } else if (aw) {
        newWidgets.push({
          widget_key: aw.key,
          name: aw.name,
          icon: aw.icon,
          size: p.size,
          visible: true,
          sort_order: newWidgets.length,
        });
      }
    }

    // Add remaining widgets marked hidden
    for (const w of widgets) {
      if (!presetMap.has(w.widget_key)) {
        newWidgets.push({
          ...w,
          visible: false,
          sort_order: newWidgets.length,
        });
      }
    }

    setWidgets(newWidgets);
    showToast(`Applied "${preset.name}" preset layout`, 'success');

    await sendPost('/dashboard/batch-update-widgets', {
      widgets: newWidgets.map((w) => ({
        widget_key: w.widget_key,
        visible: w.visible ? 1 : 0,
        size: w.size,
        sort_order: w.sort_order,
      })),
    });
  };

  const handleResetLayout = async () => {
    const res = await sendPost('/dashboard/reset-widgets', {});
    if (res?.success || res?.csrf_hash) {
      const resetWidgets: DashboardWidgetItem[] = availableWidgets.map((aw, idx) => ({
        widget_key: aw.key,
        name: aw.name,
        icon: aw.icon,
        size: (aw.size as 'small' | 'medium' | 'large') || 'medium',
        visible: Boolean(aw.default),
        sort_order: idx,
      }));
      setWidgets(resetWidgets);
      showToast('Dashboard reset to default layout', 'success');
    } else {
      showToast('Failed to reset dashboard', 'error');
    }
  };

  const handleRestoreAll = async () => {
    const allVisible = widgets.map((w, idx) => ({
      ...w,
      visible: true,
      sort_order: idx,
    }));
    setWidgets(allVisible);
    showToast('All widgets added to dashboard', 'success');

    await sendPost('/dashboard/batch-update-widgets', {
      widgets: allVisible.map((w) => ({
        widget_key: w.widget_key,
        visible: 1,
        size: w.size,
        sort_order: w.sort_order,
      })),
    });
  };

  const filteredLibraryWidgets = useMemo(() => {
    return widgets.filter((w) => {
      const meta = WIDGET_META[w.widget_key];
      if (libraryCategory !== 'all') {
        if (!meta || meta.category !== libraryCategory) {
          return false;
        }
      }
      if (librarySearch.trim()) {
        const query = librarySearch.toLowerCase().trim();
        const nameMatch = w.name.toLowerCase().includes(query);
        const descMatch = meta?.description.toLowerCase().includes(query) ?? false;
        const catMatch = meta?.categoryLabel.toLowerCase().includes(query) ?? false;
        return nameMatch || descMatch || catMatch;
      }
      return true;
    });
  }, [widgets, libraryCategory, librarySearch]);

  const visibleWidgets = widgets.filter((w) => w.visible);
  const hiddenWidgets = widgets.filter((w) => !w.visible);

  return (
    <div className="mx-auto max-w-7xl space-y-4 p-4 text-foreground lg:p-8">
      {demoEstimate && (
        <section className="flex flex-col gap-3 rounded-xl border border-border bg-surface p-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h2 className="flex items-center gap-2 font-semibold">
              <Icon name="fa-moon" className="text-accent" />
              Tonight's estimate
            </h2>
            <p className="text-sm text-muted">
              About {demoEstimate.skiers} skiers, {demoEstimate.income} income, {demoEstimate.costs} costs. Your first day is settled at 00:05 server time.
            </p>
          </div>
          <a href="/tickets" className="inline-flex min-h-11 shrink-0 items-center justify-center rounded-lg bg-accent px-4 text-sm font-medium text-accent-foreground">
            Check your ticket prices
          </a>
        </section>
      )}
      {/* Mountain Hero Header */}
      <header
        className="relative overflow-hidden rounded-2xl border border-border bg-slate-900 p-5 text-white shadow-sm lg:p-6"
        style={{
          backgroundImage: `linear-gradient(180deg, rgba(15,23,42,0.4) 0%, rgba(15,23,42,0.85) 100%), url('/img/${hero.resortMapKey}_low.jpg')`,
          backgroundSize: 'cover',
          backgroundPosition: 'center 40%',
        }}
      >
        <div className="flex flex-wrap items-end justify-between gap-4">
          <div className="space-y-1.5">
            <div className="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-white/80">
              <span
                className={`rounded px-2 py-0.5 font-mono text-[11px] font-bold ${
                  hero.resortOpen ? 'bg-emerald-600 text-white' : 'bg-red-600 text-white'
                }`}
              >
                {hero.resortOpen ? 'Open' : 'Closed'}
              </span>
              <span>
                Season {hero.seasonNum} &middot; Day {hero.gameDay}
              </span>
            </div>
            <h1 className="text-2xl font-black text-white drop-shadow sm:text-3xl">
              {hero.resortMapName}
            </h1>
            <p className="text-xs text-white/80">
              <strong className="text-white">{hero.liftCount}</strong> of{' '}
              {hero.totalLifts} lifts &middot;{' '}
              <strong className="text-white">{hero.slopeCount}</strong> of{' '}
              {hero.totalSlopes} slopes &middot;{' '}
              <strong className="font-mono text-white">{hero.repScoreFormatted}</strong> reputation
            </p>
          </div>

          <div className="flex flex-wrap items-center gap-2">
            {hero.bonusAvailable && (
              <a
                href="/daily-bonus"
                className="inline-flex min-h-[40px] items-center gap-1.5 rounded-lg border border-warning/40 bg-warning px-3 text-xs font-bold text-black shadow hover:opacity-90"
              >
                <Icon name="fa-gift" />
                Daily Bonus
              </a>
            )}
            <a
              href="/map"
              className="inline-flex min-h-[40px] items-center gap-1.5 rounded-lg border border-white/20 bg-accent px-3 text-xs font-bold text-accent-foreground shadow hover:opacity-90"
            >
              <Icon name="fa-map" />
              Trail Map
            </a>
            <a
              href="/resort"
              className="inline-flex min-h-[40px] items-center rounded-lg border border-white/25 bg-white/10 px-3 text-xs font-semibold text-white backdrop-blur-sm hover:bg-white/20"
            >
              Resort Settings
            </a>
            <Button
              variant={editMode ? 'primary' : 'secondary'}
              size="sm"
              onPress={() => setEditMode(!editMode)}
              className="min-h-[40px] font-bold"
            >
              <Icon name={editMode ? 'fa-check' : 'fa-sliders'} className="mr-1.5" />
              {editMode ? 'Done Customizing' : 'Customize'}
            </Button>
          </div>
        </div>
      </header>

      {/* Sticky Floating Command Bar */}
      {editMode && (
        <div className="sticky top-3 z-30 mb-2 rounded-2xl border border-accent/40 bg-surface/95 p-3.5 shadow-xl backdrop-blur-md">
          <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            {/* Status chip & widget counter */}
            <div className="flex flex-wrap items-center gap-2.5">
              <span className="inline-flex items-center gap-1.5 rounded-full bg-accent/15 px-3 py-1 text-xs font-bold text-accent">
                <span className="h-2 w-2 rounded-full bg-accent animate-pulse" />
                Customize Mode
              </span>
              <span className="text-xs text-muted">
                <strong className="font-mono text-foreground">{visibleWidgets.length}</strong> active &middot;{' '}
                <strong className="font-mono text-foreground">{hiddenWidgets.length}</strong> hidden
              </span>
            </div>

            {/* Presets */}
            <div className="flex flex-wrap items-center gap-1.5">
              <span className="text-xs font-semibold text-muted mr-1 hidden sm:inline">Presets:</span>
              {PRESETS.map((p) => (
                <Button
                  key={p.id}
                  type="button"
                  size="sm"
                  variant="ghost"
                  onPress={() => handleApplyPreset(p)}
                  className="inline-flex items-center gap-1.5 rounded-lg border border-border px-2.5 py-1 text-xs font-medium"
                >
                  <Icon name={p.icon} className="text-accent text-[11px]" />
                  <span>{p.name}</span>
                </Button>
              ))}
            </div>

            {/* Actions */}
            <div className="flex flex-wrap items-center gap-2">
              <Button
                size="sm"
                variant="outline"
                onPress={() => setLibraryOpen(true)}
                className="font-semibold text-xs"
              >
                <Icon name="fa-plus" className="mr-1 text-accent text-xs" />
                Add Widgets
                {hiddenWidgets.length > 0 && (
                  <span className="ml-1.5 rounded-full bg-accent/20 px-1.5 py-0.2 font-mono text-[10px] font-bold text-accent">
                    {hiddenWidgets.length}
                  </span>
                )}
              </Button>

              <Button
                size="sm"
                variant="outline"
                onPress={handleResetLayout}
                className="font-semibold text-xs"
              >
                <Icon name="fa-rotate-left" className="mr-1 text-muted text-xs" />
                Reset Defaults
              </Button>

              <Button
                size="sm"
                variant="primary"
                onPress={() => setEditMode(false)}
                className="font-bold text-xs"
              >
                <Icon name="fa-check" className="mr-1 text-xs" />
                Done
              </Button>
            </div>
          </div>
        </div>
      )}

      {/* Resort Launch Checklist */}
      {launchChecklist?.show && (
        <section
          aria-labelledby="launch-checklist-title"
          className="rounded-2xl border border-border bg-surface p-4 shadow-sm sm:p-5"
        >
          <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-b border-border pb-4">
            <div className="space-y-1">
              <div className="flex items-center gap-2">
                <span className="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-accent/15 text-accent text-xs">
                  <Icon name="fa-flag-checkered" />
                </span>
                <h2 id="launch-checklist-title" className="text-base font-bold text-foreground">
                  Resort Launch Checklist
                </h2>
                <span
                  className={`rounded-full px-2.5 py-0.5 text-xs font-semibold ${
                    launchChecklist.isComplete ? 'bg-success/15 text-success' : 'bg-accent/15 text-accent'
                  }`}
                >
                  {launchChecklist.isComplete
                    ? 'Ready for opening'
                    : `${launchChecklist.completed} of ${launchChecklist.total} complete`}
                </span>
              </div>
              <p className="text-xs text-muted">
                {launchChecklist.isComplete
                  ? 'All starter milestones complete. Your mountain operations are running and visitors arrive tonight at 00:05.'
                  : 'Complete these essential milestones to open your slopes and welcome your first skiers tonight at 00:05.'}
              </p>
            </div>
            <div className="flex items-center gap-3 shrink-0">
              <div className="w-36 sm:w-44 space-y-1">
                <div className="flex justify-between text-[11px] font-mono text-muted">
                  <span>Launch readiness</span>
                  <span className="font-semibold text-foreground">{launchChecklist.progressPct}%</span>
                </div>
                <div className="h-2 w-full overflow-hidden rounded-full bg-surface-secondary border border-border">
                  <div
                    className={`h-full transition-all duration-200 ${
                      launchChecklist.isComplete ? 'bg-success' : 'bg-accent'
                    }`}
                    style={{ width: `${Math.max(5, launchChecklist.progressPct)}%` }}
                  />
                </div>
              </div>
            </div>
          </div>

          <div className="grid grid-cols-1 gap-3 pt-4 sm:grid-cols-2 lg:grid-cols-4">
            {launchChecklist.steps.map((step, idx) => (
              <div
                key={step.id}
                className={`flex flex-col justify-between rounded-xl border p-3.5 transition-colors ${
                  step.done
                    ? 'border-success/30 bg-success/5'
                    : 'border-border bg-surface-secondary/40 hover:bg-surface-secondary'
                }`}
              >
                <div className="space-y-2">
                  <div className="flex items-center justify-between">
                    <span
                      className={`inline-flex h-8 w-8 items-center justify-center rounded-lg text-sm ${
                        step.done ? 'bg-success/20 text-success' : 'bg-default text-muted'
                      }`}
                    >
                      <Icon name={step.done ? 'fa-check' : step.icon} />
                    </span>
                    {step.done ? (
                      <span className="inline-flex items-center gap-1 text-xs font-semibold text-success">
                        <Icon name="fa-circle-check" />
                        Done
                      </span>
                    ) : (
                      <span className="text-[11px] font-medium text-muted">Step {idx + 1}</span>
                    )}
                  </div>
                  <div>
                    <h3 className="text-sm font-semibold text-foreground">
                      {step.title}
                    </h3>
                    <p className="mt-1 text-xs text-muted leading-relaxed">
                      {step.description}
                    </p>
                  </div>
                </div>

                <div className="mt-3 pt-2">
                  {step.done ? (
                    <a
                      href={step.link}
                      className="inline-flex min-h-11 w-full items-center justify-center rounded-lg border border-border bg-surface px-3 text-xs font-medium text-muted hover:text-foreground transition-colors"
                    >
                      View on {step.id === 'bonus' ? 'Town Hall' : (step.id === 'tickets' ? 'Tickets' : 'Trail Map')}
                    </a>
                  ) : (
                    <a
                      href={step.link}
                      className="inline-flex min-h-11 w-full items-center justify-center gap-1.5 rounded-lg bg-accent px-3 text-xs font-bold text-accent-foreground shadow hover:opacity-95 transition-opacity"
                    >
                      <span>{step.cta}</span>
                      <Icon name="fa-arrow-right" className="text-[11px]" />
                    </a>
                  )}
                </div>
              </div>
            ))}
          </div>
        </section>
      )}

      {/* Alerts */}
      {(alerts.length > 0 || unclaimedAchievements > 0) && (
        <div className="grid grid-cols-1 gap-2 md:grid-cols-2">
          {alerts.map((alert, idx) => (
            <a
              key={idx}
              href={alert.link}
              className={`flex items-center justify-between rounded-xl border p-3 text-xs font-medium transition-colors hover:bg-surface-raised ${
                alert.type === 'danger'
                  ? 'border-danger/40 bg-danger/10 text-danger'
                  : 'border-warning/40 bg-warning/10 text-warning'
              }`}
            >
              <div className="flex items-center gap-2 truncate">
                <Icon name={alert.icon} />
                <span className="truncate">{alert.msg}</span>
              </div>
              <span className="underline shrink-0 ml-2">Fix &rarr;</span>
            </a>
          ))}
          {unclaimedAchievements > 0 && (
            <a
              href="/achievements"
              className="flex items-center justify-between rounded-xl border border-warning/40 bg-warning/10 p-3 text-xs font-medium text-warning hover:bg-surface-raised"
            >
              <div className="flex items-center gap-2">
                <Icon name="fa-award" />
                <span>
                  {unclaimedAchievements} achievement reward
                  {unclaimedAchievements > 1 ? 's' : ''} ready to claim
                </span>
              </div>
              <span className="underline shrink-0 ml-2">Claim &rarr;</span>
            </a>
          )}
        </div>
      )}

      {/* 4 Core KPIs */}
      <section className="grid grid-cols-2 gap-3 rounded-2xl border border-border bg-surface p-4 shadow-sm lg:grid-cols-4 lg:p-5">
        {/* Cash */}
        <div className="space-y-1">
          <div className="flex items-center gap-1.5 text-xs text-muted">
            <Icon name="fa-sack-dollar" className="text-accent" />
            <span>Cash</span>
          </div>
          <div
            className={`font-mono text-2xl font-bold tracking-tight ${
              kpis.cashNegative ? 'text-danger' : 'text-foreground'
            }`}
          >
            {kpis.cashBalanceText}
          </div>
          <div className="truncate text-[11px] text-muted">
            Lifetime{' '}
            <span
              className={`font-mono font-semibold ${
                kpis.netProfitPositive ? 'text-success' : 'text-danger'
              }`}
            >
              {kpis.netProfitText}
            </span>{' '}
            &middot; {kpis.genepisBalFormatted} genepis
          </div>
        </div>

        {/* Net Per Day */}
        <div className="space-y-1">
          <div className="flex items-center gap-1.5 text-xs text-muted">
            <Icon name="fa-chart-line" className="text-accent" />
            <span>Net Per Day</span>
          </div>
          <div
            className={`font-mono text-2xl font-bold tracking-tight ${
              kpis.dailyProfitPositive ? 'text-success' : 'text-danger'
            }`}
          >
            {kpis.dailyProfitText}
          </div>
          <div className="flex h-1.5 w-full overflow-hidden rounded-full bg-border">
            <div
              className="bg-success"
              style={{ width: `${Math.max(5, Math.min(95, kpis.incomePercent))}%` }}
            />
            <div className="flex-1 bg-danger/80" />
          </div>
          <div className="truncate font-mono text-[11px] text-muted">
            {kpis.incomeText} in &middot; {kpis.expensesText} out
          </div>
        </div>

        {/* Visitors Today */}
        <div className="space-y-1">
          <div className="flex items-center gap-1.5 text-xs text-muted">
            <Icon name="fa-person-skiing" className="text-accent" />
            <span>Visitors Today</span>
          </div>
          <div className="font-mono text-2xl font-bold tracking-tight text-foreground">
            {kpis.dailyVisitorsFormatted}
          </div>
          <div className="truncate text-[11px] text-muted">
            Ticket {kpis.ticketPriceText} &middot; lifts {kpis.totalLiftCapacityFormatted}/hr
          </div>
        </div>

        {/* Weather */}
        <div className="space-y-1">
          <div className="flex items-center gap-1.5 text-xs text-muted">
            <Icon name={kpis.weather.icon} className="text-accent" />
            <span>Weather</span>
          </div>
          <div className="font-mono text-2xl font-bold tracking-tight text-foreground">
            {kpis.weather.tempFormatted}
          </div>
          <div className="truncate text-[11px] text-muted">
            {kpis.weather.condition_name} &middot; {kpis.weather.snowBaseFormatted} base
          </div>
        </div>
      </section>

      {/* Empty State */}
      {visibleWidgets.length === 0 && (
        <div className="rounded-2xl border-2 border-dashed border-border bg-surface p-10 text-center shadow-xs">
          <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-accent/10 text-accent">
            <Icon name="fa-table-cells" className="text-2xl" />
          </div>
          <h3 className="mt-4 text-base font-bold text-foreground">
            No Widgets Currently Visible
          </h3>
          <p className="mx-auto mt-1 max-w-md text-xs text-muted">
            All widgets are hidden. Choose a preset layout above, reset to default, or open the widget library to choose which ones to display.
          </p>
          <div className="mt-5 flex flex-wrap justify-center gap-2">
            <Button
              variant="primary"
              size="sm"
              onPress={handleResetLayout}
              className="font-bold text-xs"
            >
              <Icon name="fa-rotate-left" className="mr-1 text-xs" />
              Restore Default Layout
            </Button>
            <Button
              variant="outline"
              size="sm"
              onPress={() => {
                setEditMode(true);
                setLibraryOpen(true);
              }}
              className="font-semibold text-xs"
            >
              <Icon name="fa-plus" className="mr-1 text-accent text-xs" />
              Browse Widget Library
            </Button>
          </div>
        </div>
      )}

      {/* Widget Grid */}
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {visibleWidgets.map((w, idx) => {
          const colSpan =
            w.size === 'large'
              ? 'sm:col-span-2 lg:col-span-4'
              : w.size === 'medium'
                ? 'sm:col-span-2'
                : 'sm:col-span-1';

          const isDropTarget = dragOverKey === w.widget_key && draggingKey !== w.widget_key;
          const isDragging = draggingKey === w.widget_key;

          return (
            <div
              key={w.widget_key}
              draggable={editMode}
              onDragStart={() => handleDragStart(w.widget_key)}
              onDragEnter={() => setDragOverKey(w.widget_key)}
              onDragOver={(e) => {
                e.preventDefault();
                if (dragOverKey !== w.widget_key) setDragOverKey(w.widget_key);
              }}
              onDragEnd={handleDragEnd}
              onDrop={() => handleDrop(w.widget_key)}
              className={`flex flex-col justify-between rounded-2xl border bg-surface p-4 shadow-sm transition-all duration-150 ${colSpan} ${
                isDropTarget
                  ? 'ring-2 ring-accent border-accent bg-accent/5 scale-[1.01]'
                  : isDragging
                    ? 'opacity-40 border-dashed border-accent scale-[0.99]'
                    : editMode
                      ? 'border-dashed border-accent/40 hover:border-accent'
                      : 'border-border'
              }`}
            >
              {/* Header */}
              <div className="mb-3 border-b border-border pb-2.5">
                <div className="flex items-center justify-between gap-2">
                  <div className="flex items-center gap-2 font-semibold text-foreground">
                    {editMode && (
                      <span
                        className="cursor-grab active:cursor-grabbing text-muted hover:text-foreground flex items-center gap-1.5"
                        title="Drag to reorder"
                      >
                        <Icon name="fa-grip-vertical" className="text-xs" />
                        <span className="rounded bg-surface-raised px-1.5 py-0.5 font-mono text-[10px] font-bold text-muted">
                          #{idx + 1}
                        </span>
                      </span>
                    )}
                    <Icon name={w.icon} className="text-accent text-xs" />
                    <span className="text-sm">{w.name}</span>
                  </div>

                  {/* Normal mode shortcut link or customize controls */}
                  {editMode ? (
                    <div className="flex items-center gap-1.5">
                      {/* Move Up/Down buttons for accessible mobile reorder */}
                      <div className="flex items-center gap-0.5 rounded-lg border border-border bg-surface-raised p-0.5">
                        <Button
                          type="button"
                          variant="ghost"
                          size="sm"
                          isIconOnly
                          onPress={() => handleMove(w.widget_key, 'up')}
                          isDisabled={idx === 0}
                          aria-label={`Move ${w.name} up`}
                          className="size-7 min-h-7 min-w-7 rounded text-muted hover:text-foreground"
                        >
                          <Icon name="fa-chevron-up" className="text-[10px]" />
                        </Button>
                        <Button
                          type="button"
                          variant="ghost"
                          size="sm"
                          isIconOnly
                          onPress={() => handleMove(w.widget_key, 'down')}
                          isDisabled={idx === visibleWidgets.length - 1}
                          aria-label={`Move ${w.name} down`}
                          className="size-7 min-h-7 min-w-7 rounded text-muted hover:text-foreground"
                        >
                          <Icon name="fa-chevron-down" className="text-[10px]" />
                        </Button>
                      </div>

                      {/* Size buttons */}
                      <div className="inline-flex items-center rounded-lg border border-border bg-surface-raised p-0.5 text-[10px]">
                        {(['small', 'medium', 'large'] as const).map((sz) => (
                          <Button
                            key={sz}
                            type="button"
                            size="sm"
                            variant={w.size === sz ? 'primary' : 'ghost'}
                            onPress={() => handleResize(w.widget_key, sz)}
                            aria-label={`Resize ${w.name} to ${sz}`}
                            className="rounded px-1.5 py-0.5 min-h-6 min-w-6 text-[10px] font-bold uppercase"
                          >
                            {sz[0]}
                          </Button>
                        ))}
                      </div>

                      {/* Hide button */}
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        isIconOnly
                        onPress={() => handleToggle(w.widget_key, false)}
                        aria-label={`Hide ${w.name} widget`}
                        className="size-7 min-h-7 min-w-7 rounded-lg text-muted hover:text-danger"
                      >
                        <Icon name="fa-eye-slash" className="text-xs" />
                      </Button>
                    </div>
                  ) : (
                    <div>
                      {w.widget_key === 'stats' && (
                        <a href="/finances" className="text-xs text-accent hover:underline">
                          Ledger &rarr;
                        </a>
                      )}
                      {w.widget_key === 'overview' && (
                        <a href="/map" className="text-xs text-accent hover:underline">
                          Trail Map &rarr;
                        </a>
                      )}
                      {w.widget_key === 'weather_mini' && (
                        <a href="/weather" className="text-xs text-accent hover:underline">
                          Forecast &rarr;
                        </a>
                      )}
                      {w.widget_key === 'achievements_mini' && (
                        <a href="/achievements" className="text-xs text-accent hover:underline">
                          All &rarr;
                        </a>
                      )}
                      {w.widget_key === 'achievements' && (
                        <a href="/achievements" className="text-xs text-accent hover:underline">
                          All &rarr;
                        </a>
                      )}
                      {w.widget_key === 'activity' && (
                        <a href="/activity" className="text-xs text-accent hover:underline">
                          Full log &rarr;
                        </a>
                      )}
                      {w.widget_key === 'parking_mini' && (
                        <a href="/parking" className="text-xs text-accent hover:underline">
                          Manage &rarr;
                        </a>
                      )}
                      {w.widget_key === 'terrain_parks_mini' && (
                        <a href="/terrain-parks" className="text-xs text-accent hover:underline">
                          Manage &rarr;
                        </a>
                      )}
                      {w.widget_key === 'finances_mini' && (
                        <a href="/finances" className="text-xs text-accent hover:underline">
                          Details &rarr;
                        </a>
                      )}
                      {w.widget_key === 'staff_mini' && (
                        <a href="/staff" className="text-xs text-accent hover:underline">
                          Manage &rarr;
                        </a>
                      )}
                      {w.widget_key === 'equipment_mini' && (
                        <a href="/equipment" className="text-xs text-accent hover:underline">
                          Fleet &rarr;
                        </a>
                      )}
                      {w.widget_key === 'insurance_mini' && (
                        <a href="/insurance" className="text-xs text-accent hover:underline">
                          Policies &rarr;
                        </a>
                      )}
                      {w.widget_key === 'loans_mini' && (
                        <a href="/bank" className="text-xs text-accent hover:underline">
                          Bank &rarr;
                        </a>
                      )}
                      {w.widget_key === 'marketing_mini' && (
                        <a href="/marketing" className="text-xs text-accent hover:underline">
                          Campaigns &rarr;
                        </a>
                      )}
                    </div>
                  )}
                </div>
              </div>

              {/* Widget Body Content */}
              <div className="flex-1 text-xs">
                {/* Stats Widget */}
                {w.widget_key === 'stats' && (
                  <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                    <div>
                      <div className="text-muted">Tickets</div>
                      <div className="font-mono text-sm font-semibold text-success">
                        +{data.stats.tickets}
                      </div>
                    </div>
                    <div>
                      <div className="text-muted">Buildings &amp; Food</div>
                      <div className="font-mono text-sm font-semibold text-success">
                        +{data.stats.buildingsFood}
                      </div>
                    </div>
                    <div>
                      <div className="text-muted">Payroll</div>
                      <div className="font-mono text-sm font-semibold text-danger">
                        -{data.stats.payroll}
                      </div>
                    </div>
                    <div>
                      <div className="text-muted">Upkeep &amp; Running</div>
                      <div className="font-mono text-sm font-semibold text-danger">
                        -{data.stats.runningCosts}
                      </div>
                    </div>
                    <div>
                      <div className="text-muted">Loan Payments</div>
                      <div className="font-mono text-sm font-semibold text-danger">
                        -{data.stats.loanPayments}
                      </div>
                    </div>
                    <div>
                      <div className="text-muted">Net Daily</div>
                      <div
                        className={`font-mono text-sm font-bold ${
                          data.stats.isProfit ? 'text-success' : 'text-danger'
                        }`}
                      >
                        {data.stats.dailyProfit}
                      </div>
                    </div>
                  </div>
                )}

                {/* Overview Mountain Widget */}
                {w.widget_key === 'overview' && (
                  <div className="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-5">
                    <div>
                      <div className="text-muted">Slopes Open</div>
                      <div className="font-mono text-sm font-bold text-foreground">
                        {data.overview.slopeCount}{' '}
                        <span className="text-xs font-normal text-muted">
                          / {data.overview.totalSlopes}
                        </span>
                      </div>
                      <div className="mt-1 flex items-center gap-1.5 text-[10px]">
                        <span className="flex items-center gap-0.5 text-emerald-600 font-mono">
                          &bull; {data.overview.slopesByDiff.green || 0}
                        </span>
                        <span className="flex items-center gap-0.5 text-blue-600 font-mono">
                          &bull; {data.overview.slopesByDiff.blue || 0}
                        </span>
                        <span className="flex items-center gap-0.5 text-foreground font-mono">
                          &bull; {data.overview.slopesByDiff.black || 0}
                        </span>
                      </div>
                    </div>
                    <div>
                      <div className="text-muted">Lifts Open</div>
                      <div className="font-mono text-sm font-bold text-foreground">
                        {data.overview.liftCount}{' '}
                        <span className="text-xs font-normal text-muted">
                          / {data.overview.totalLifts}
                        </span>
                      </div>
                    </div>
                    <div>
                      <div className="text-muted">Staff</div>
                      <div className="font-mono text-sm font-bold text-foreground">
                        {data.overview.staffCount}
                      </div>
                    </div>
                    <div>
                      <div className="text-muted">Buildings</div>
                      <div className="font-mono text-sm font-bold text-foreground">
                        {data.overview.buildingCount}
                      </div>
                    </div>
                    <div className="col-span-2 sm:col-span-1">
                      <div className="text-muted">Trail Condition</div>
                      <div
                        className={`font-mono text-sm font-bold ${
                          data.overview.avgSlopeCondition >= 70 ? 'text-foreground' : 'text-warning'
                        }`}
                      >
                        {data.overview.avgSlopeCondition}%
                      </div>
                      <div className="text-[10px] text-muted">
                        {data.overview.trailConditionNote}
                      </div>
                    </div>
                  </div>
                )}

                {/* Season Progress Widget */}
                {w.widget_key === 'season' && (
                  <div className="space-y-2.5">
                    <div className="flex items-center justify-between text-xs">
                      <span>
                        Season {data.season.seasonNum} &middot; Day {data.season.seasonProgress} of{' '}
                        {data.season.seasonLength}
                      </span>
                      <span className="font-mono font-bold text-accent">
                        {data.season.seasonPct}% Complete
                      </span>
                    </div>
                    <div className="relative h-2.5 w-full overflow-hidden rounded-full bg-border">
                      <div
                        className="h-full bg-accent"
                        style={{ width: `${data.season.seasonPct}%` }}
                      />
                    </div>
                    <div className="flex items-center justify-between text-[11px] text-muted">
                      <span>
                        <Icon name="fa-snowflake" className="text-info mr-1" />
                        Winter &middot; {data.season.winterDays} days
                      </span>
                      {data.season.summerDays > 0 && (
                        <span>
                          Summer &middot; {data.season.summerDays} days
                          <Icon name="fa-sun" className="text-warning ml-1" />
                        </span>
                      )}
                    </div>
                  </div>
                )}

                {/* Weather Mini Widget */}
                {w.widget_key === 'weather_mini' && (
                  <div className="flex flex-wrap items-center justify-between gap-4">
                    <div className="flex items-center gap-3">
                      <Icon
                        name={data.weather_mini.icon}
                        className="text-2xl text-accent"
                      />
                      <div>
                        <div className="font-mono text-xl font-bold text-foreground">
                          {data.weather_mini.tempFormatted}
                        </div>
                        <div className="text-[11px] text-muted">
                          {data.weather_mini.condition_name} &middot; Wind{' '}
                          {data.weather_mini.windFormatted}
                        </div>
                      </div>
                    </div>
                    {data.weather_mini.forecast.length > 0 && (
                      <div className="flex items-center divide-x divide-border font-mono text-[11px]">
                        {data.weather_mini.forecast.map((fc, i) => (
                          <div key={i} className="px-2 text-center first:pl-0">
                            <div className="text-[10px] text-muted">{fc.label}</div>
                            <Icon name={fc.icon} className="my-0.5 text-accent" />
                            <div className="font-semibold">{fc.tempFormatted}</div>
                          </div>
                        ))}
                      </div>
                    )}
                  </div>
                )}

                {/* Manage Quick Actions Widget */}
                {w.widget_key === 'actions' && (
                  <div className="grid grid-cols-2 gap-1.5 sm:grid-cols-4 lg:grid-cols-6">
                    <a
                      href="/map"
                      className="flex items-center gap-2 rounded-lg p-2 text-foreground hover:bg-default"
                    >
                      <Icon name="fa-map" className="text-accent" />
                      <span>Trail Map</span>
                    </a>
                    <a
                      href="/resort"
                      className="flex items-center gap-2 rounded-lg p-2 text-foreground hover:bg-default"
                    >
                      <Icon name="fa-sliders" className="text-accent" />
                      <span>Resort</span>
                    </a>
                    <a
                      href="/finances"
                      className="flex items-center gap-2 rounded-lg p-2 text-foreground hover:bg-default"
                    >
                      <Icon name="fa-coins" className="text-accent" />
                      <span>Finances</span>
                    </a>
                    <a
                      href="/staff"
                      className="flex items-center gap-2 rounded-lg p-2 text-foreground hover:bg-default"
                    >
                      <Icon name="fa-users" className="text-accent" />
                      <span>Staff</span>
                    </a>
                    <a
                      href="/grooming"
                      className="flex items-center gap-2 rounded-lg p-2 text-foreground hover:bg-default"
                    >
                      <Icon name="fa-tractor" className="text-accent" />
                      <span>Grooming</span>
                    </a>
                    <a
                      href="/snowmaking"
                      className="flex items-center gap-2 rounded-lg p-2 text-foreground hover:bg-default"
                    >
                      <Icon name="fa-snowflake" className="text-accent" />
                      <span>Snowmaking</span>
                    </a>
                    <a
                      href="/buildings"
                      className="flex items-center gap-2 rounded-lg p-2 text-foreground hover:bg-default"
                    >
                      <Icon name="fa-hotel" className="text-accent" />
                      <span>Buildings</span>
                    </a>
                    <a
                      href="/parking"
                      className="flex items-center gap-2 rounded-lg p-2 text-foreground hover:bg-default"
                    >
                      <Icon name="fa-square-parking" className="text-accent" />
                      <span>Parking</span>
                    </a>
                    <a
                      href="/equipment"
                      className="flex items-center gap-2 rounded-lg p-2 text-foreground hover:bg-default"
                    >
                      <Icon name="fa-toolbox" className="text-accent" />
                      <span>Equipment</span>
                    </a>
                    <a
                      href="/marketing"
                      className="flex items-center gap-2 rounded-lg p-2 text-foreground hover:bg-default"
                    >
                      <Icon name="fa-bullhorn" className="text-accent" />
                      <span>Marketing</span>
                    </a>
                    <a
                      href="/alliances"
                      className="flex items-center gap-2 rounded-lg p-2 text-foreground hover:bg-default"
                    >
                      <Icon name="fa-handshake" className="text-accent" />
                      <span>Alliances</span>
                    </a>
                    <a
                      href="/vote"
                      className="flex items-center gap-2 rounded-lg p-2 text-foreground hover:bg-default"
                    >
                      <Icon name="fa-check-to-slot" className="text-accent" />
                      <span>Vote</span>
                    </a>
                  </div>
                )}

                {/* Achievements Alerts Widget */}
                {w.widget_key === 'achievements_mini' && (
                  <div>
                    {data.achievements_mini.unclaimed > 0 ? (
                      <div className="flex items-center justify-between gap-3">
                        <div>
                          <div className="font-semibold text-foreground">
                            {data.achievements_mini.unclaimed} reward
                            {data.achievements_mini.unclaimed > 1 ? 's' : ''} ready to claim
                          </div>
                          <div className="text-[11px] text-muted">
                            {data.achievements_mini.completed} achievements unlocked so far
                          </div>
                        </div>
                        <a
                          href="/achievements"
                          className="inline-flex min-h-[36px] items-center rounded-lg bg-warning px-3 font-bold text-black hover:opacity-90"
                        >
                          Claim Rewards
                        </a>
                      </div>
                    ) : (
                      <div className="grid grid-cols-3 gap-2 text-center">
                        <div>
                          <div className="text-muted">Completed</div>
                          <div className="font-mono text-base font-bold text-foreground">
                            {data.achievements_mini.completed}
                          </div>
                        </div>
                        <div>
                          <div className="text-muted">In Progress</div>
                          <div className="font-mono text-base font-bold text-foreground">
                            {data.achievements_mini.inProgressCount}
                          </div>
                        </div>
                        <div>
                          <div className="text-muted">Unclaimed</div>
                          <div className="font-mono text-base font-bold text-muted">0</div>
                        </div>
                      </div>
                    )}
                  </div>
                )}

                {/* Achievements in Progress Widget */}
                {w.widget_key === 'achievements' && (
                  <div>
                    {data.achievements.length === 0 ? (
                      <p className="text-muted">All achievements completed.</p>
                    ) : (
                      <div className="space-y-2">
                        {data.achievements.map((ach) => (
                          <div key={ach.id} className="space-y-1">
                            <div className="flex items-center justify-between">
                              <span className="truncate font-medium text-foreground">
                                {ach.name}
                              </span>
                              <span className="font-mono text-[11px] text-muted">
                                {ach.progressFormatted} / {ach.targetFormatted}
                              </span>
                            </div>
                            <div className="h-1.5 w-full overflow-hidden rounded-full bg-border">
                              <div
                                className="h-full bg-accent"
                                style={{ width: `${ach.progressPct}%` }}
                              />
                            </div>
                          </div>
                        ))}
                      </div>
                    )}
                  </div>
                )}

                {/* Activity Feed Widget */}
                {w.widget_key === 'activity' && (
                  <div className="space-y-1.5">
                    {activityLogs.length === 0 ? (
                      <p className="text-muted">No recent activity recorded.</p>
                    ) : (
                      activityLogs.map((log, i) => (
                        <div
                          key={log.id || i}
                          className="flex items-center justify-between gap-2 border-b border-border/40 pb-1 last:border-b-0"
                        >
                          <div className="flex items-center gap-2 truncate">
                            <Icon name={log.icon} className="text-xs text-accent shrink-0" />
                            <span className="truncate text-foreground">{log.message}</span>
                          </div>
                          <span className="font-mono text-[10px] text-muted shrink-0">
                            Day {log.game_day}
                          </span>
                        </div>
                      ))
                    )}
                  </div>
                )}

                {/* Parking Mini Widget */}
                {w.widget_key === 'parking_mini' && (
                  <div className="space-y-2">
                    <div className="grid grid-cols-3 gap-2">
                      <div>
                        <div className="text-muted">Lots</div>
                        <div className="font-mono font-bold text-foreground">
                          {data.parking_mini.lotsCount}
                        </div>
                      </div>
                      <div>
                        <div className="text-muted">Spaces</div>
                        <div className="font-mono font-bold text-foreground">
                          {data.parking_mini.spacesFormatted}
                        </div>
                      </div>
                      <div>
                        <div className="text-muted">Fullness</div>
                        <div
                          className={`font-mono font-bold ${
                            data.parking_mini.pct > 90
                              ? 'text-danger'
                              : data.parking_mini.pct > 70
                                ? 'text-warning'
                                : 'text-foreground'
                          }`}
                        >
                          {data.parking_mini.pct}%
                        </div>
                      </div>
                    </div>
                    <div className="h-1.5 w-full overflow-hidden rounded-full bg-border">
                      <div
                        className="h-full bg-accent"
                        style={{
                          width: `${Math.min(
                            100,
                            Math.round(
                              (data.parking_mini.totalOccupied / data.parking_mini.totalCap) * 100,
                            ),
                          )}%`,
                        }}
                      />
                    </div>
                  </div>
                )}

                {/* Terrain Parks Mini Widget */}
                {w.widget_key === 'terrain_parks_mini' && (
                  <div className="grid grid-cols-3 gap-2">
                    <div>
                      <div className="text-muted">Open</div>
                      <div className="font-mono font-bold text-foreground">
                        {data.terrain_parks_mini.openCount}
                      </div>
                    </div>
                    <div>
                      <div className="text-muted">Building</div>
                      <div className="font-mono font-bold text-foreground">
                        {data.terrain_parks_mini.buildingCount}
                      </div>
                    </div>
                    <div>
                      <div className="text-muted">Riders</div>
                      <div className="font-mono font-bold text-foreground">
                        {data.terrain_parks_mini.ridersFormatted}
                      </div>
                    </div>
                  </div>
                )}

                {/* Finances Mini Widget */}
                {w.widget_key === 'finances_mini' && (
                  <div className="space-y-1">
                    <div className="flex justify-between">
                      <span className="text-muted">Income</span>
                      <span className="font-mono font-medium">{data.finances_mini.incomeText}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-muted">Expenses</span>
                      <span className="font-mono font-medium">
                        {data.finances_mini.expensesText}
                      </span>
                    </div>
                    <div className="flex justify-between border-t border-border pt-1 font-semibold">
                      <span>Net Lifetime</span>
                      <span
                        className={`font-mono ${
                          data.finances_mini.isNetPositive ? 'text-success' : 'text-danger'
                        }`}
                      >
                        {data.finances_mini.netProfitText}
                      </span>
                    </div>
                  </div>
                )}

                {/* Staff Mini Widget */}
                {w.widget_key === 'staff_mini' && (
                  <div>
                    {data.staff_mini.staffCount === 0 ? (
                      <div className="text-center py-2">
                        <p className="text-muted">No one hired yet.</p>
                        <a
                          href="/staff/hire"
                          className="mt-2 inline-flex min-h-[32px] items-center rounded-lg bg-accent px-2.5 font-bold text-accent-foreground"
                        >
                          Hire Staff
                        </a>
                      </div>
                    ) : (
                      <div className="space-y-2">
                        <div className="grid grid-cols-2 gap-1.5 sm:grid-cols-3">
                          {data.staff_mini.roles.map((r) => (
                            <div key={r.role} className="flex justify-between text-[11px]">
                              <span className="text-muted truncate">{r.roleName}</span>
                              <span className="font-mono font-semibold">{r.count}</span>
                            </div>
                          ))}
                        </div>
                        <div className="flex items-center gap-2 border-t border-border pt-2 text-[11px]">
                          <span className="text-muted">Morale</span>
                          <div className="h-1.5 flex-1 overflow-hidden rounded-full bg-border">
                            <div
                              className={`h-full ${
                                data.staff_mini.moraleTone === 'success'
                                  ? 'bg-success'
                                  : data.staff_mini.moraleTone === 'warning'
                                    ? 'bg-warning'
                                    : 'bg-danger'
                              }`}
                              style={{ width: `${data.staff_mini.avgMorale}%` }}
                            />
                          </div>
                          <span className="font-mono font-bold">{data.staff_mini.avgMorale}%</span>
                        </div>
                      </div>
                    )}
                  </div>
                )}

                {/* Equipment Mini Widget */}
                {w.widget_key === 'equipment_mini' && (
                  <div className="flex items-center justify-between">
                    <div>
                      <div className="text-muted">Fleet Units</div>
                      <div className="font-mono text-xl font-bold text-foreground">
                        {data.equipment_mini.unitsCount}
                      </div>
                    </div>
                    <a href="/equipment" className="text-xs text-accent hover:underline">
                      View Fleet &rarr;
                    </a>
                  </div>
                )}

                {/* Insurance Mini Widget */}
                {w.widget_key === 'insurance_mini' && (
                  <div className="grid grid-cols-2 gap-2">
                    <div>
                      <div className="text-muted">Active Policies</div>
                      <div className="font-mono text-base font-bold text-success">
                        {data.insurance_mini.activeCount}
                      </div>
                    </div>
                    <div>
                      <div className="text-muted">Available</div>
                      <div className="font-mono text-base font-bold text-muted">
                        {data.insurance_mini.availableCount}
                      </div>
                    </div>
                  </div>
                )}

                {/* Loans Mini Widget */}
                {w.widget_key === 'loans_mini' && (
                  <div>
                    {data.loans_mini.activeCount === 0 ? (
                      <div className="text-muted">No active loans outstanding.</div>
                    ) : (
                      <div>
                        <div className="text-muted">
                          {data.loans_mini.activeCount} loan
                          {data.loans_mini.activeCount > 1 ? 's' : ''} outstanding
                        </div>
                        <div className="font-mono text-base font-bold text-danger">
                          {data.loans_mini.totalDebtText}
                        </div>
                      </div>
                    )}
                  </div>
                )}

                {/* Marketing Mini Widget */}
                {w.widget_key === 'marketing_mini' && (
                  <div className="flex items-center justify-between">
                    <div>
                      <div className="text-muted">Running Campaigns</div>
                      <div className="font-mono text-xl font-bold text-foreground">
                        {data.marketing_mini.campaignsCount}
                      </div>
                    </div>
                    <a href="/marketing" className="text-xs text-accent hover:underline">
                      Campaigns &rarr;
                    </a>
                  </div>
                )}
              </div>
            </div>
          );
        })}
      </div>

      {/* Hidden Widgets Tray (visible in Edit Mode) */}
      {editMode && hiddenWidgets.length > 0 && (
        <div className="rounded-2xl border border-dashed border-border bg-surface-raised p-4">
          <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h2 className="flex items-center gap-1.5 text-xs font-bold text-foreground">
              <Icon name="fa-eye-slash" className="text-muted" />
              <span>Hidden Widgets ({hiddenWidgets.length}) &middot; Click to Restore</span>
            </h2>
            <Button
              size="sm"
              variant="outline"
              onPress={() => setLibraryOpen(true)}
              className="h-7 text-xs font-semibold"
            >
              <Icon name="fa-table-cells" className="mr-1 text-accent text-xs" />
              Browse Full Library
            </Button>
          </div>
          <div className="flex flex-wrap gap-2">
            {hiddenWidgets.map((w) => (
              <Button
                key={w.widget_key}
                size="sm"
                variant="secondary"
                onPress={() => handleToggle(w.widget_key, true)}
                className="text-xs"
              >
                <Icon name="fa-plus" className="mr-1.5 text-accent" />
                {w.name}
              </Button>
            ))}
          </div>
        </div>
      )}

      {/* Slide-Over Widget Library Drawer */}
      {libraryOpen && (
        <div className="fixed inset-0 z-50 overflow-hidden">
          {/* Backdrop */}
          <div
            className="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity"
            onClick={() => setLibraryOpen(false)}
            aria-hidden="true"
          />

          {/* Slide-over panel */}
          <div className="fixed inset-y-0 right-0 flex max-w-full pl-6 sm:pl-10">
            <div className="w-screen max-w-md border-l border-border bg-surface shadow-2xl flex flex-col">
              {/* Drawer Header */}
              <div className="flex items-center justify-between border-b border-border p-4 sm:p-5">
                <div className="space-y-0.5">
                  <h2 className="flex items-center gap-2 text-base font-bold text-foreground">
                    <Icon name="fa-table-cells" className="text-accent" />
                    Widget Library
                  </h2>
                  <p className="text-xs text-muted">
                    Toggle widgets and configure your command dashboard.
                  </p>
                </div>
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  isIconOnly
                  onPress={() => setLibraryOpen(false)}
                  aria-label="Close widget library"
                  className="rounded-lg p-2 text-muted hover:text-foreground"
                >
                  <Icon name="fa-xmark" className="text-base" />
                </Button>
              </div>

              {/* Search & Category Filter */}
              <div className="border-b border-border bg-surface-raised/40 p-4 space-y-3">
                <div className="relative">
                  <Icon
                    name="fa-magnifying-glass"
                    className="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-muted"
                  />
                  <input
                    type="text"
                    value={librarySearch}
                    onChange={(e) => setLibrarySearch(e.target.value)}
                    placeholder="Search widgets by name or topic..."
                    className="w-full rounded-xl border border-border bg-surface py-2 pl-9 pr-3 text-xs text-foreground placeholder:text-muted focus:border-accent focus:outline-hidden"
                  />
                </div>

                <div className="flex flex-wrap gap-1">
                  {(
                    [
                      { id: 'all', label: 'All' },
                      { id: 'operations', label: 'Operations' },
                      { id: 'financial', label: 'Finances' },
                      { id: 'activity', label: 'Activity' },
                    ] as const
                  ).map((cat) => (
                    <Button
                      key={cat.id}
                      type="button"
                      size="sm"
                      variant={libraryCategory === cat.id ? 'primary' : 'ghost'}
                      onPress={() => setLibraryCategory(cat.id)}
                      className="rounded-lg px-2.5 py-1 text-xs font-semibold"
                    >
                      {cat.label}
                    </Button>
                  ))}
                </div>
              </div>

              {/* Scrollable Widget Cards */}
              <div className="flex-1 overflow-y-auto p-4 space-y-3">
                {filteredLibraryWidgets.length === 0 ? (
                  <div className="py-8 text-center text-xs text-muted">
                    No widgets match "{librarySearch}".
                  </div>
                ) : (
                  filteredLibraryWidgets.map((w) => {
                    const meta = WIDGET_META[w.widget_key];
                    return (
                      <div
                        key={w.widget_key}
                        className="flex flex-col justify-between rounded-xl border border-border bg-surface p-3.5 space-y-2.5 shadow-xs transition-colors hover:border-accent/40"
                      >
                        <div className="flex items-start justify-between gap-2">
                          <div className="flex items-start gap-2.5">
                            <div className="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-accent/10 text-accent">
                              <Icon name={w.icon} className="text-sm" />
                            </div>
                            <div>
                              <div className="flex items-center gap-2">
                                <span className="text-xs font-bold text-foreground">{w.name}</span>
                                <span className="rounded bg-surface-raised px-1.5 py-0.5 text-[10px] font-semibold text-muted uppercase">
                                  {meta?.categoryLabel || 'Widget'}
                                </span>
                              </div>
                              <p className="mt-0.5 text-[11px] text-muted leading-tight">
                                {meta?.description || 'Mountain management widget.'}
                              </p>
                            </div>
                          </div>
                        </div>

                        <div className="flex items-center justify-between pt-1 border-t border-border/50 text-[11px]">
                          <div className="flex items-center gap-1 text-muted">
                            <span>Size:</span>
                            <span className="font-mono font-bold uppercase text-foreground">
                              {w.size}
                            </span>
                          </div>

                          <Button
                            size="sm"
                            variant={w.visible ? 'outline' : 'primary'}
                            onPress={() => handleToggle(w.widget_key, !w.visible)}
                            className="h-7 text-xs font-semibold"
                          >
                            <Icon
                              name={w.visible ? 'fa-eye-slash' : 'fa-plus'}
                              className="mr-1 text-[11px]"
                            />
                            {w.visible ? 'Hide from Board' : 'Add to Board'}
                          </Button>
                        </div>
                      </div>
                    );
                  })
                )}
              </div>

              {/* Drawer Footer */}
              <div className="border-t border-border p-4 bg-surface-raised/40 flex items-center justify-between gap-2">
                <Button
                  size="sm"
                  variant="outline"
                  onPress={handleRestoreAll}
                  className="text-xs font-semibold"
                >
                  <Icon name="fa-arrows-rotate" className="mr-1 text-accent" />
                  Add All ({widgets.length})
                </Button>

                <Button
                  size="sm"
                  variant="primary"
                  onPress={() => setLibraryOpen(false)}
                  className="text-xs font-bold"
                >
                  Done
                </Button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Floating Toast Notification */}
      {toast && (
        <div
          role="status"
          className={`fixed bottom-5 right-5 z-50 flex items-center gap-2.5 rounded-xl border px-4 py-2.5 shadow-2xl text-xs font-medium animate-in fade-in slide-in-from-bottom-2 duration-150 ${
            toast.type === 'success'
              ? 'border-emerald-600/40 bg-emerald-950/95 text-emerald-200'
              : toast.type === 'error'
                ? 'border-red-600/40 bg-red-950/95 text-red-200'
                : 'border-accent/40 bg-slate-900/95 text-white'
          }`}
        >
          <Icon
            name={
              toast.type === 'success'
                ? 'fa-circle-check'
                : toast.type === 'error'
                  ? 'fa-triangle-exclamation'
                  : 'fa-circle-info'
            }
            className="text-sm shrink-0"
          />
          <span>{toast.msg}</span>
        </div>
      )}
    </div>
  );
}
