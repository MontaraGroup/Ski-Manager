import { useState, useMemo } from 'react';
import { Button } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput, Figure, Page, Panel, PostButton } from '@/components/Page';

export type SeasonInfo = {
  name: string;
  season_number: number;
  resort_map: string;
  start_date: string;
  duration_days: number;
  game_day: number;
  days_left: number;
  progress_pct: number;
  maintenance: boolean;
};

export type TickInfo = {
  timeAgo: string;
  created_at: string | null;
  isStale: boolean;
};

export type KpiData = {
  totalUsers: string;
  newUsers7d: number;
  bannedUsers: number;
  onlineRecent: string;
  activePct: number;
  totalCashFormatted: string;
  avgCashFormatted: string;
  totalLoans: string;
  totalStaff: string;
  totalBuildings: string;
  totalItems: string;
  totalFacilities: string;
  totalParking: number;
  totalParks: number;
};

export type PlayerItem = {
  id: number;
  username: string;
  cashFormatted: string;
  cashRaw: number;
  staff_count: number;
  building_count: number;
  item_count: number;
  joinedFormatted: string;
  created_ts: number;
  last_active_ts: number;
  lastActiveFormatted: string;
  isOnline: boolean;
  isBanned: boolean;
  lastActivityMessage: string;
};

export type ActivityItem = {
  id: number;
  username: string;
  message: string;
  icon: string;
  game_day: number;
  timeAgo: string;
};

export type WeatherInfo = {
  condition_name: string;
  temp: number;
  tempFormatted: string;
  wind: number;
  snowfall: number;
  snow_base: number;
  conditions: string[];
};

export type ServerInfo = {
  diskUsedFormatted: string;
  diskTotalFormatted: string;
  diskPct: number;
  load: number[];
  cores: number;
  phpVersion: string;
  ciVersion: string;
  memPeak: number;
  memLimit: string;
  dbVersion: string;
  dbSize: number;
};

export type AdminIndexPageProps = {
  season: SeasonInfo;
  isStaging: boolean;
  isDev: boolean;
  envWritable: boolean;
  serverTime: string;
  serverTimezone: string;
  unreadSupport: number;
  lastTick: TickInfo;
  kpis: KpiData;
  players: PlayerItem[];
  recentLogs: ActivityItem[];
  weather: WeatherInfo;
  server: ServerInfo;
};

type SortField = 'id' | 'username' | 'cash' | 'staff' | 'buildings' | 'items' | 'created' | 'active';

export default function AdminIndexPage({
  season,
  isStaging,
  isDev,
  envWritable,
  serverTime,
  serverTimezone,
  unreadSupport = 0,
  lastTick,
  kpis,
  players = [],
  recentLogs = [],
  weather,
  server,
}: AdminIndexPageProps) {
  // Player table filters & sorting
  const [filterMode, setFilterMode] = useState<'all' | 'online' | 'banned'>('all');
  const [searchQuery, setSearchQuery] = useState('');
  const [sortField, setSortField] = useState<SortField>('id');
  const [sortAsc, setSortAsc] = useState(false);

  // Weather inputs
  const [wCond, setWCond] = useState(weather?.condition_name ?? 'Light Snow');
  const [wTemp, setWTemp] = useState(weather?.temp ?? -5);
  const [wWind, setWWind] = useState(weather?.wind ?? 15);
  const [wSnow, setWSnow] = useState(weather?.snowfall ?? 0);
  const [wBase, setWBase] = useState(weather?.snow_base ?? 50);

  // Bulk actions inputs
  const [bulkCash, setBulkCash] = useState(50000);
  const [bulkGenepis, setBulkGenepis] = useState(10);
  const [achieveUserId, setAchieveUserId] = useState('');
  const [achieveId, setAchieveId] = useState('');

  const filteredPlayers = useMemo(() => {
    let list = players;

    if (filterMode === 'online') {
      list = list.filter((p) => p.isOnline);
    } else if (filterMode === 'banned') {
      list = list.filter((p) => p.isBanned);
    }

    const q = searchQuery.trim().toLowerCase();
    if (q) {
      list = list.filter(
        (p) =>
          p.username.toLowerCase().includes(q) ||
          String(p.id).includes(q.replace('#', ''))
      );
    }

    const sorted = [...list].sort((a, b) => {
      let cmp = 0;
      switch (sortField) {
        case 'id':
          cmp = a.id - b.id;
          break;
        case 'username':
          cmp = a.username.localeCompare(b.username);
          break;
        case 'cash':
          cmp = a.cashRaw - b.cashRaw;
          break;
        case 'staff':
          cmp = a.staff_count - b.staff_count;
          break;
        case 'buildings':
          cmp = a.building_count - b.building_count;
          break;
        case 'items':
          cmp = a.item_count - b.item_count;
          break;
        case 'created':
          cmp = a.created_ts - b.created_ts;
          break;
        case 'active':
          cmp = a.last_active_ts - b.last_active_ts;
          break;
      }
      return sortAsc ? cmp : -cmp;
    });

    return sorted;
  }, [players, filterMode, searchQuery, sortField, sortAsc]);

  const toggleSort = (field: SortField) => {
    if (sortField === field) {
      setSortAsc(!sortAsc);
    } else {
      setSortField(field);
      setSortAsc(false);
    }
  };

  const navLinks = [
    {
      group: 'Players',
      items: [
        { href: '/admin/broadcast', icon: 'fa-bullhorn', label: 'Broadcast' },
        { href: '/admin/support', icon: 'fa-headset', label: 'Support', badge: unreadSupport },
        { href: '/admin/compare', icon: 'fa-code-compare', label: 'Compare' },
        { href: '/admin/suspicious', icon: 'fa-triangle-exclamation', label: 'Suspicious' },
        { href: '/admin/export-players', icon: 'fa-download', label: 'Export' },
      ],
    },
    {
      group: 'Game',
      items: [
        { href: '/admin/settings', icon: 'fa-gear', label: 'Settings' },
        { href: '/admin/economy', icon: 'fa-chart-line', label: 'Economy' },
        { href: '/admin/seasons', icon: 'fa-calendar-plus', label: 'Seasons' },
        { href: '/admin/season-vote', icon: 'fa-check-to-slot', label: 'Season vote' },
        { href: '/admin/features', icon: 'fa-toggle-on', label: 'Flags' },
        { href: '/admin/changelogs', icon: 'fa-newspaper', label: 'Changelogs' },
      ],
    },
    {
      group: 'Logs',
      items: [
        { href: '/admin/activity', icon: 'fa-clock-rotate-left', label: 'Activity' },
        { href: '/admin/audit', icon: 'fa-shield-halved', label: 'Audit' },
        { href: '/admin/errors', icon: 'fa-bug', label: 'Errors' },
      ],
    },
  ];

  return (
    <Page
      title="Admin Panel"
      icon="fa-shield-halved"
      iconTone="text-secondary"
      back="/dashboard"
      width="max-w-7xl"
      subtitle={
        <div className="flex flex-wrap items-center gap-2">
          <span>
            {season?.name} · Day {season?.game_day} of {season?.duration_days} ({season?.days_left} days left)
          </span>
          <span
            className={`inline-flex rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider ${
              isStaging ? 'bg-warning/20 text-warning' : 'bg-danger/20 text-danger'
            }`}
          >
            {isStaging ? 'Staging' : 'Production'}
          </span>
          {season?.maintenance && (
            <span className="inline-flex rounded border border-danger bg-danger/10 px-1.5 py-0.5 text-[10px] font-bold uppercase text-danger">
              Maintenance ON
            </span>
          )}
        </div>
      }
      actions={
        <div className="flex flex-wrap items-center gap-2">
          {/* Tick indicator */}
          <div
            className="flex items-center gap-2 rounded-md border border-edge bg-surface-2 px-2.5 py-1 text-xs"
            title={lastTick?.created_at || 'Never'}
          >
            <span
              className={`h-2 w-2 rounded-full ${
                lastTick?.isStale ? 'bg-danger' : 'bg-success'
              }`}
            />
            <span className="text-muted">Last tick:</span>
            <span className={`font-medium ${lastTick?.isStale ? 'text-danger' : 'text-foreground'}`}>
              {lastTick?.timeAgo}
            </span>
          </div>

          {/* Trigger Tick */}
          <PostButton
            action="/admin/trigger-tick"
            variant="primary"
            size="sm"
            confirm={{
              title: `Run game tick on ${isStaging ? 'STAGING' : 'PRODUCTION'}?`,
              body: 'This will advance all player resorts, economics, weather, and simulation state by 1 game day.',
              confirmLabel: 'Run Tick Now',
            }}
          >
            <Icon name="fa-play" className="mr-1" />
            Run Tick
          </PostButton>

          {/* Maintenance toggle */}
          <PostButton
            action="/admin/maintenance"
            variant={season?.maintenance ? 'danger' : 'secondary'}
            size="sm"
            confirm={{
              title: season?.maintenance ? 'Reopen site to players?' : 'Turn Maintenance ON?',
              body: season?.maintenance
                ? 'Players will be able to access their resorts and play normally.'
                : 'Players will see a 503 maintenance page until reopened.',
              confirmLabel: season?.maintenance ? 'Turn Maintenance Off' : 'Turn Maintenance On',
            }}
          >
            <Icon name="fa-wrench" className="mr-1" />
            {season?.maintenance ? 'Maint ON' : 'Maint OFF'}
          </PostButton>

          {/* Dev / Prod Mode toggle */}
          <PostButton
            action="/admin/toggle-env"
            variant="secondary"
            size="sm"
            disabled={!envWritable}
          >
            <Icon name="fa-code" className="mr-1" />
            {isDev ? 'Dev Mode' : 'Prod Mode'}
          </PostButton>
        </div>
      }
    >
      <div className="space-y-6">
        {/* Navigation Quick Toolbar */}
        <Panel className="p-3">
          <div className="flex flex-wrap items-center justify-between gap-4">
            <div className="flex flex-wrap items-center gap-6">
              {navLinks.map((section) => (
                <div key={section.group} className="flex items-center gap-1.5">
                  <span className="text-[11px] font-bold uppercase tracking-wider text-muted/60 mr-1">
                    {section.group}
                  </span>
                  {section.items.map((item) => (
                    <a
                      key={item.href}
                      href={item.href}
                      className="inline-flex items-center gap-1.5 rounded-md border border-edge bg-surface-2 px-2.5 py-1 text-xs font-medium text-foreground hover:bg-surface-3 transition-colors"
                    >
                      <Icon name={item.icon} className="text-muted text-[11px]" />
                      <span>{item.label}</span>
                      {Boolean(item.badge && item.badge > 0) && (
                        <span className="inline-flex h-4 min-w-[16px] items-center justify-center rounded-full bg-danger px-1 text-[10px] font-bold text-white">
                          {item.badge}
                        </span>
                      )}
                    </a>
                  ))}
                </div>
              ))}
            </div>
            <div className="text-[11px] text-muted">
              Server: {serverTime} ({serverTimezone})
            </div>
          </div>
        </Panel>

        {/* KPI Strip */}
        <div className="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-8">
          <div className="rounded-lg border border-edge bg-surface-2 p-3">
            <div className="text-xs text-muted flex items-center gap-1.5">
              <Icon name="fa-users" className="opacity-70" /> Players
            </div>
            <div className="mt-1 text-lg font-bold tabular-nums text-foreground">{kpis.totalUsers}</div>
            <div className="text-[11px] text-muted truncate">+{kpis.newUsers7d} this wk</div>
          </div>

          <div className="rounded-lg border border-edge bg-surface-2 p-3">
            <div className="text-xs text-muted flex items-center gap-1.5">
              <Icon name="fa-signal" className="opacity-70" /> Active 24h
            </div>
            <div className="mt-1 text-lg font-bold tabular-nums text-foreground">{kpis.onlineRecent}</div>
            <div className="text-[11px] text-muted">{kpis.activePct}% of total</div>
          </div>

          <div className="rounded-lg border border-edge bg-surface-2 p-3">
            <div className="text-xs text-muted flex items-center gap-1.5">
              <Icon name="fa-sack-dollar" className="opacity-70 text-warning" /> Total Cash
            </div>
            <div className="mt-1 text-lg font-bold tabular-nums text-foreground">{kpis.totalCashFormatted}</div>
            <div className="text-[11px] text-muted truncate">{kpis.avgCashFormatted} avg</div>
          </div>

          <div className="rounded-lg border border-edge bg-surface-2 p-3">
            <div className="text-xs text-muted flex items-center gap-1.5">
              <Icon name="fa-file-invoice-dollar" className="opacity-70 text-danger" /> Loans
            </div>
            <div className="mt-1 text-lg font-bold tabular-nums text-foreground">{kpis.totalLoans}</div>
            <div className="text-[11px] text-muted">active loans</div>
          </div>

          <div className="rounded-lg border border-edge bg-surface-2 p-3">
            <div className="text-xs text-muted flex items-center gap-1.5">
              <Icon name="fa-user-group" className="opacity-70 text-secondary" /> Staff
            </div>
            <div className="mt-1 text-lg font-bold tabular-nums text-foreground">{kpis.totalStaff}</div>
            <div className="text-[11px] text-muted">employed</div>
          </div>

          <div className="rounded-lg border border-edge bg-surface-2 p-3">
            <div className="text-xs text-muted flex items-center gap-1.5">
              <Icon name="fa-hotel" className="opacity-70 text-warning" /> Buildings
            </div>
            <div className="mt-1 text-lg font-bold tabular-nums text-foreground">{kpis.totalBuildings}</div>
            <div className="text-[11px] text-muted">lodges & dining</div>
          </div>

          <div className="rounded-lg border border-edge bg-surface-2 p-3">
            <div className="text-xs text-muted flex items-center gap-1.5">
              <Icon name="fa-person-skiing" className="opacity-70 text-primary" /> Lifts/Slopes
            </div>
            <div className="mt-1 text-lg font-bold tabular-nums text-foreground">{kpis.totalItems}</div>
            <div className="text-[11px] text-muted">built items</div>
          </div>

          <div className="rounded-lg border border-edge bg-surface-2 p-3">
            <div className="text-xs text-muted flex items-center gap-1.5">
              <Icon name="fa-square-parking" className="opacity-70" /> Facilities
            </div>
            <div className="mt-1 text-lg font-bold tabular-nums text-foreground">{kpis.totalFacilities}</div>
            <div className="text-[11px] text-muted truncate">{kpis.totalParking} lots · {kpis.totalParks} parks</div>
          </div>
        </div>

        {/* Main Grid: Players Table + Recent Activity Feed */}
        <div className="grid grid-cols-1 gap-6 lg:grid-cols-12">
          {/* Players Table (8 cols on lg) */}
          <div className="lg:col-span-8">
            <Panel
              title={
                <span className="flex items-center gap-2">
                  <Icon name="fa-users" className="text-primary" />
                  Players ({filteredPlayers.length} shown of {kpis.totalUsers})
                </span>
              }
              actions={
                <div className="flex flex-wrap items-center gap-2">
                  <div className="inline-flex rounded-md border border-edge bg-surface-2 p-0.5">
                    <button
                      type="button"
                      onClick={() => setFilterMode('all')}
                      className={`rounded px-2.5 py-1 text-xs font-medium transition-colors ${
                        filterMode === 'all'
                          ? 'bg-surface-3 text-foreground font-semibold shadow-xs'
                          : 'text-muted hover:text-foreground'
                      }`}
                    >
                      All
                    </button>
                    <button
                      type="button"
                      onClick={() => setFilterMode('online')}
                      className={`rounded px-2.5 py-1 text-xs font-medium transition-colors ${
                        filterMode === 'online'
                          ? 'bg-surface-3 text-foreground font-semibold shadow-xs'
                          : 'text-muted hover:text-foreground'
                      }`}
                    >
                      Online
                    </button>
                    <button
                      type="button"
                      onClick={() => setFilterMode('banned')}
                      className={`rounded px-2.5 py-1 text-xs font-medium transition-colors ${
                        filterMode === 'banned'
                          ? 'bg-surface-3 text-foreground font-semibold shadow-xs'
                          : 'text-muted hover:text-foreground'
                      }`}
                    >
                      Banned
                    </button>
                  </div>

                  <input
                    type="search"
                    aria-label="Search players"
                    placeholder="Name or ID…"
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    className="w-32 sm:w-44 rounded-md border border-edge bg-surface-2 px-2.5 py-1 text-xs text-foreground placeholder:text-muted focus:border-focus focus:outline-none"
                  />
                </div>
              }
            >
              {filteredPlayers.length === 0 ? (
                <p className="py-12 text-center text-xs text-muted">No players match current filter or search.</p>
              ) : (
                <div className="overflow-x-auto max-h-[500px] -mx-4 -my-4 sm:-mx-6 sm:-my-6">
                  <table className="w-full text-left text-xs">
                    <thead className="sticky top-0 border-b border-edge bg-surface-2 text-muted uppercase tracking-wider font-semibold z-10">
                      <tr>
                        <th
                          scope="col"
                          onClick={() => toggleSort('id')}
                          className="px-3 py-2.5 sm:px-4 cursor-pointer hover:text-foreground select-none"
                        >
                          ID {sortField === 'id' && (sortAsc ? '▲' : '▼')}
                        </th>
                        <th
                          scope="col"
                          onClick={() => toggleSort('username')}
                          className="px-3 py-2.5 cursor-pointer hover:text-foreground select-none"
                        >
                          Player {sortField === 'username' && (sortAsc ? '▲' : '▼')}
                        </th>
                        <th
                          scope="col"
                          onClick={() => toggleSort('cash')}
                          className="px-3 py-2.5 text-right cursor-pointer hover:text-foreground select-none"
                        >
                          Cash {sortField === 'cash' && (sortAsc ? '▲' : '▼')}
                        </th>
                        <th
                          scope="col"
                          onClick={() => toggleSort('staff')}
                          className="px-2 py-2.5 text-right cursor-pointer hover:text-foreground select-none"
                        >
                          Staff
                        </th>
                        <th
                          scope="col"
                          onClick={() => toggleSort('buildings')}
                          className="px-2 py-2.5 text-right cursor-pointer hover:text-foreground select-none"
                        >
                          Bldgs
                        </th>
                        <th
                          scope="col"
                          onClick={() => toggleSort('items')}
                          className="px-2 py-2.5 text-right cursor-pointer hover:text-foreground select-none"
                        >
                          Items
                        </th>
                        <th
                          scope="col"
                          onClick={() => toggleSort('created')}
                          className="px-3 py-2.5 cursor-pointer hover:text-foreground select-none"
                        >
                          Joined
                        </th>
                        <th
                          scope="col"
                          onClick={() => toggleSort('active')}
                          className="px-3 py-2.5 cursor-pointer hover:text-foreground select-none"
                        >
                          Last Active
                        </th>
                        <th scope="col" className="px-3 py-2.5 text-right sm:pr-4">Actions</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-edge">
                      {filteredPlayers.map((p) => (
                        <tr
                          key={p.id}
                          className={`hover:bg-surface-2/60 transition-colors ${
                            p.isBanned ? 'opacity-50 bg-danger/5' : ''
                          }`}
                        >
                          <td className="whitespace-nowrap px-3 py-2 font-mono text-muted sm:px-4">
                            #{p.id}
                          </td>
                          <td className="whitespace-nowrap px-3 py-2 font-medium">
                            <a
                              href={`/admin/user/${p.id}`}
                              className="text-foreground hover:text-primary hover:underline"
                            >
                              {p.username}
                            </a>
                            {p.isBanned && (
                              <span className="ml-1.5 inline-flex rounded bg-danger/15 px-1.5 py-0.2 text-[10px] font-bold text-danger">
                                Banned
                              </span>
                            )}
                          </td>
                          <td className="whitespace-nowrap px-3 py-2 text-right font-mono font-medium">
                            {p.cashFormatted}
                          </td>
                          <td className="whitespace-nowrap px-2 py-2 text-right font-mono text-muted">
                            {p.staff_count}
                          </td>
                          <td className="whitespace-nowrap px-2 py-2 text-right font-mono text-muted">
                            {p.building_count}
                          </td>
                          <td className="whitespace-nowrap px-2 py-2 text-right font-mono text-muted">
                            {p.item_count}
                          </td>
                          <td className="whitespace-nowrap px-3 py-2 text-muted text-[11px]">
                            {p.joinedFormatted}
                          </td>
                          <td className="whitespace-nowrap px-3 py-2 text-[11px]" title={p.lastActivityMessage}>
                            <div className="flex items-center gap-1.5">
                              {p.isOnline && (
                                <span className="h-1.5 w-1.5 rounded-full bg-success" title="Online now" />
                              )}
                              <span className={p.isOnline ? 'font-medium text-success' : 'text-muted'}>
                                {p.lastActiveFormatted}
                              </span>
                            </div>
                          </td>
                          <td className="whitespace-nowrap px-3 py-2 text-right sm:pr-4">
                            <div className="flex items-center justify-end gap-1">
                              <a
                                href={`/admin/user/${p.id}`}
                                className="flex h-6 w-6 items-center justify-center rounded text-muted hover:bg-surface-2 hover:text-foreground text-[11px]"
                                title="Edit player"
                              >
                                <Icon name="fa-pen-to-square" />
                              </a>
                              {p.id !== 1 && (
                                <>
                                  {p.isBanned ? (
                                    <PostButton
                                      action={`/admin/unban/${p.id}`}
                                      variant="secondary"
                                      size="sm"
                                      className="inline-block"
                                    >
                                      <Icon name="fa-unlock" className="text-success text-[11px]" />
                                    </PostButton>
                                  ) : (
                                    <PostButton
                                      action={`/admin/ban/${p.id}`}
                                      variant="secondary"
                                      size="sm"
                                      confirm={{
                                        title: `Ban ${p.username}?`,
                                        body: `This will prevent ${p.username} from logging in.`,
                                        confirmLabel: 'Ban Player',
                                      }}
                                      className="inline-block"
                                    >
                                      <Icon name="fa-ban" className="text-warning text-[11px]" />
                                    </PostButton>
                                  )}
                                  <PostButton
                                    action={`/admin/delete/${p.id}`}
                                    variant="danger"
                                    size="sm"
                                    confirm={{
                                      title: `Delete ${p.username}?`,
                                      body: `Permanently delete ${p.username} and all resort assets? Irreversible.`,
                                      confirmLabel: 'Delete Player',
                                    }}
                                    className="inline-block"
                                  >
                                    <Icon name="fa-trash" className="text-[11px]" />
                                  </PostButton>
                                </>
                              )}
                            </div>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </Panel>
          </div>

          {/* Activity Feed (4 cols on lg) */}
          <div className="lg:col-span-4">
            <Panel
              title={
                <span className="flex items-center gap-2">
                  <Icon name="fa-clock-rotate-left" className="text-secondary" />
                  Live Activity
                </span>
              }
              actions={
                <a
                  href="/admin/activity"
                  className="text-xs font-semibold text-primary hover:underline"
                >
                  View All →
                </a>
              }
            >
              {recentLogs.length === 0 ? (
                <p className="py-8 text-center text-xs text-muted">No recent activity recorded.</p>
              ) : (
                <div className="divide-y divide-edge max-h-[500px] overflow-y-auto -mx-4 -my-4 sm:-mx-6 sm:-my-6 px-4 sm:px-6">
                  {recentLogs.map((log) => (
                    <div key={log.id} className="flex items-start gap-2.5 py-2.5 text-xs">
                      <span className="w-4 pt-0.5 text-center text-muted">
                        <Icon name={`fa-${log.icon || 'circle-dot'}`} />
                      </span>
                      <div className="flex-1 min-w-0">
                        <div className="truncate" title={log.message}>
                          <span className="font-semibold text-foreground">{log.username}</span>{' '}
                          <span className="text-muted">{log.message}</span>
                        </div>
                        <div className="mt-0.5 text-[11px] text-muted/70">
                          Day {log.game_day} · {log.timeAgo}
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </Panel>
          </div>
        </div>

        {/* Bottom Tools Grid: Weather Simulator, Bulk Actions, Server Telemetry */}
        <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
          {/* Weather Simulator */}
          <Panel
            title={
              <span className="flex items-center gap-2">
                <Icon name="fa-cloud-sun" className="text-primary" />
                Weather Simulator
              </span>
            }
          >
            <form action="/admin/set-weather" method="post" className="space-y-3">
              <CsrfInput />
              <div>
                <label className="text-[11px] font-semibold text-muted block mb-1">Condition</label>
                <select
                  name="condition"
                  value={wCond}
                  onChange={(e) => setWCond(e.target.value)}
                  className="w-full rounded-md border border-edge bg-surface-2 px-2.5 py-1.5 text-xs text-foreground focus:outline-none"
                >
                  {(weather?.conditions || []).map((cond) => (
                    <option key={cond} value={cond}>
                      {cond}
                    </option>
                  ))}
                </select>
              </div>

              <div className="grid grid-cols-2 gap-2">
                <div>
                  <label className="text-[11px] font-semibold text-muted block mb-1">Temp (°C)</label>
                  <input
                    type="number"
                    name="temp"
                    value={wTemp}
                    onChange={(e) => setWTemp(Number(e.target.value))}
                    min={-30}
                    max={30}
                    className="w-full rounded-md border border-edge bg-surface-2 px-2 py-1 text-xs text-foreground focus:outline-none"
                  />
                </div>
                <div>
                  <label className="text-[11px] font-semibold text-muted block mb-1">Wind (km/h)</label>
                  <input
                    type="number"
                    name="wind"
                    value={wWind}
                    onChange={(e) => setWWind(Number(e.target.value))}
                    min={0}
                    max={120}
                    className="w-full rounded-md border border-edge bg-surface-2 px-2 py-1 text-xs text-foreground focus:outline-none"
                  />
                </div>
                <div>
                  <label className="text-[11px] font-semibold text-muted block mb-1">Snowfall (cm)</label>
                  <input
                    type="number"
                    name="snowfall"
                    value={wSnow}
                    onChange={(e) => setWSnow(Number(e.target.value))}
                    min={0}
                    max={50}
                    className="w-full rounded-md border border-edge bg-surface-2 px-2 py-1 text-xs text-foreground focus:outline-none"
                  />
                </div>
                <div>
                  <label className="text-[11px] font-semibold text-muted block mb-1">Snow Base (cm)</label>
                  <input
                    type="number"
                    name="snow_base"
                    value={wBase}
                    onChange={(e) => setWBase(Number(e.target.value))}
                    min={0}
                    max={300}
                    className="w-full rounded-md border border-edge bg-surface-2 px-2 py-1 text-xs text-foreground focus:outline-none"
                  />
                </div>
              </div>

              <Button type="submit" variant="primary" size="sm" className="w-full mt-2">
                Apply Weather Override
              </Button>
            </form>
          </Panel>

          {/* Bulk Economy & Player Rewards */}
          <Panel
            title={
              <span className="flex items-center gap-2">
                <Icon name="fa-gift" className="text-warning" />
                Bulk Actions & Grants
              </span>
            }
          >
            <div className="space-y-4">
              {/* Cash to All */}
              <form action="/admin/add-cash-all" method="post" className="space-y-1.5">
                <CsrfInput />
                <label className="text-[11px] font-semibold text-muted block">
                  Give cash to all players
                </label>
                <div className="flex items-center gap-2">
                  <input
                    type="number"
                    name="amount"
                    aria-label="Cash amount for all"
                    value={bulkCash}
                    onChange={(e) => setBulkCash(Number(e.target.value))}
                    step={1000}
                    min={1}
                    className="w-full rounded-md border border-edge bg-surface-2 px-2.5 py-1.5 text-xs text-foreground focus:outline-none"
                  />
                  <Button
                    type="submit"
                    size="sm"
                    variant="secondary"
                    className="shrink-0"
                    onPress={(e) => {
                      if (!window.confirm(`Give $${bulkCash.toLocaleString()} to ALL players?`)) {
                        e.continuePropagation();
                      }
                    }}
                  >
                    Give Cash
                  </Button>
                </div>
              </form>

              {/* Genepis to All */}
              <form action="/admin/add-genepis-all" method="post" className="space-y-1.5 pt-3 border-t border-edge">
                <CsrfInput />
                <label className="text-[11px] font-semibold text-muted block">
                  Give Genepis to all players
                </label>
                <div className="flex items-center gap-2">
                  <input
                    type="number"
                    name="amount"
                    aria-label="Genepis amount for all"
                    value={bulkGenepis}
                    onChange={(e) => setBulkGenepis(Number(e.target.value))}
                    min={1}
                    className="w-full rounded-md border border-edge bg-surface-2 px-2.5 py-1.5 text-xs text-foreground focus:outline-none"
                  />
                  <Button
                    type="submit"
                    size="sm"
                    variant="secondary"
                    className="shrink-0"
                    onPress={(e) => {
                      if (!window.confirm(`Give ${bulkGenepis.toLocaleString()} Genepis to ALL players?`)) {
                        e.continuePropagation();
                      }
                    }}
                  >
                    Give Genepis
                  </Button>
                </div>
              </form>

              {/* Grant Achievement to single player */}
              <form action="/admin/grant-achievement" method="post" className="space-y-1.5 pt-3 border-t border-edge">
                <CsrfInput />
                <label className="text-[11px] font-semibold text-muted block">
                  Grant achievement to player
                </label>
                <div className="flex items-center gap-2">
                  <input
                    type="number"
                    name="user_id"
                    aria-label="User ID"
                    placeholder="User ID"
                    value={achieveUserId}
                    onChange={(e) => setAchieveUserId(e.target.value)}
                    required
                    className="w-1/2 rounded-md border border-edge bg-surface-2 px-2.5 py-1.5 text-xs text-foreground focus:outline-none"
                  />
                  <input
                    type="number"
                    name="achievement_id"
                    aria-label="Achievement ID"
                    placeholder="Achieve ID"
                    value={achieveId}
                    onChange={(e) => setAchieveId(e.target.value)}
                    required
                    className="w-1/2 rounded-md border border-edge bg-surface-2 px-2.5 py-1.5 text-xs text-foreground focus:outline-none"
                  />
                  <Button
                    type="submit"
                    size="sm"
                    variant="secondary"
                    className="shrink-0"
                  >
                    Grant
                  </Button>
                </div>
              </form>
            </div>
          </Panel>

          {/* Server Telemetry */}
          <Panel
            title={
              <span className="flex items-center gap-2">
                <Icon name="fa-server" className="text-secondary" />
                Server Telemetry
              </span>
            }
            actions={
              <a
                href="/admin/errors"
                className="text-xs font-semibold text-danger hover:underline"
              >
                Errors →
              </a>
            }
          >
            <div className="space-y-3 text-xs">
              {/* Disk Bar */}
              <div>
                <div className="flex justify-between pb-1">
                  <span className="text-muted">Disk Storage</span>
                  <span className="font-mono">{server?.diskUsedFormatted} / {server?.diskTotalFormatted}</span>
                </div>
                <div className="h-2 w-full rounded-full bg-surface-3 overflow-hidden">
                  <div
                    className={`h-full rounded-full ${
                      (server?.diskPct ?? 0) > 85
                        ? 'bg-danger'
                        : (server?.diskPct ?? 0) > 70
                        ? 'bg-warning'
                        : 'bg-success'
                    }`}
                    style={{ width: `${Math.min(100, server?.diskPct ?? 0)}%` }}
                  />
                </div>
              </div>

              <dl className="space-y-1.5 pt-2 border-t border-edge">
                <div className="flex justify-between">
                  <dt className="text-muted">Load Avg (1/5/15m)</dt>
                  <dd
                    className={`font-mono ${
                      (server?.load[0] ?? 0) > (server?.cores ?? 1) ? 'text-danger font-bold' : ''
                    }`}
                  >
                    {(server?.load || []).map((l) => l.toFixed(2)).join(' / ')}
                  </dd>
                </div>
                <div className="flex justify-between">
                  <dt className="text-muted">CPU Cores</dt>
                  <dd className="font-mono">{server?.cores}</dd>
                </div>
                <div className="flex justify-between">
                  <dt className="text-muted">PHP / Framework</dt>
                  <dd className="font-mono">{server?.phpVersion} / {server?.ciVersion}</dd>
                </div>
                <div className="flex justify-between">
                  <dt className="text-muted">Memory Peak / Limit</dt>
                  <dd className="font-mono">{server?.memPeak}M / {server?.memLimit}</dd>
                </div>
                <div className="flex justify-between">
                  <dt className="text-muted">Database Version</dt>
                  <dd className="font-mono truncate max-w-[150px]">{server?.dbVersion}</dd>
                </div>
                <div className="flex justify-between">
                  <dt className="text-muted">Database Size</dt>
                  <dd className="font-mono">{server?.dbSize} MB</dd>
                </div>
              </dl>
            </div>
          </Panel>
        </div>
      </div>
    </Page>
  );
}
