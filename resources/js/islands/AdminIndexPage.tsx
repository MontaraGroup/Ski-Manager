import { useMemo, useState, type ReactNode } from 'react';
import { Button, Meter } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Page } from '@/components/Page';
import { Select } from '@/components/Select';
import { Section } from './admin/Section';
import { ConfirmForm, Field } from './admin/ConfirmForm';

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

export type AttentionInfo = {
  supportOpen: number;
  errors24h: number;
  signupsToday: number;
  signups7d: number;
  activeToday: number;
  active7d: number;
  roadmapToReview: number;
  suspicious: number;
  backupMonitorUrl: string;
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
  money?: { symbol: string; prefix: boolean };
  /** Always sent by Admin::index; missing only when stale PHP serves an older payload than this bundle expects. */
  attention?: AttentionInfo;
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

const n = (v: number) => v.toLocaleString('en-US');

/**
 * One thing to look at. "alert" cards say so in words and with an icon, not only with the
 * red border, and link to the page where it gets fixed.
 */
export default function AdminIndexPage(props: AdminIndexPageProps) {
  const {
    money = { symbol: '€', prefix: false },
    attention,
    season,
    isStaging,
    isDev,
    envWritable,
    serverTime,
    serverTimezone,
    lastTick,
    kpis,
    players = [],
    recentLogs = [],
    weather,
    server,
  } = props;
  const [filterMode, setFilterMode] = useState<'all' | 'online' | 'banned'>('all');
  const [searchQuery, setSearchQuery] = useState('');
  const [sortField, setSortField] = useState<SortField>('id');
  const [sortAsc, setSortAsc] = useState(false);
  const [wCond, setWCond] = useState(weather?.condition_name ?? 'Light Snow');

  const a = attention;
  const envName = isStaging ? 'staging' : 'production';

  const filteredPlayers = useMemo(() => {
    let list = players;
    if (filterMode === 'online') list = list.filter((p) => p.isOnline);
    else if (filterMode === 'banned') list = list.filter((p) => p.isBanned);
    const q = searchQuery.trim().toLowerCase();
    if (q) list = list.filter((p) => p.username.toLowerCase().includes(q) || String(p.id).includes(q.replace('#', '')));
    const key: Record<SortField, (p: PlayerItem) => number | string> = {
      id: (p) => p.id,
      username: (p) => p.username.toLowerCase(),
      cash: (p) => p.cashRaw,
      staff: (p) => p.staff_count,
      buildings: (p) => p.building_count,
      items: (p) => p.item_count,
      created: (p) => p.created_ts,
      active: (p) => p.last_active_ts,
    };
    return [...list].sort((x, y) => {
      const kx = key[sortField](x);
      const ky = key[sortField](y);
      const cmp = typeof kx === 'string' ? kx.localeCompare(String(ky)) : kx - (ky as number);
      return sortAsc ? cmp : -cmp;
    });
  }, [players, filterMode, searchQuery, sortField, sortAsc]);

  const toggleSort = (field: SortField) => {
    if (sortField === field) setSortAsc(!sortAsc);
    else {
      setSortField(field);
      setSortAsc(false);
    }
  };

  const SortHead = ({ field, children, right }: { field: SortField; children: ReactNode; right?: boolean }) => (
    <th
      scope="col"
      aria-sort={sortField === field ? (sortAsc ? 'ascending' : 'descending') : undefined}
      className={`px-2 py-1 font-semibold ${right ? 'text-right' : ''}`}
    >
      <button
        type="button"
        onClick={() => toggleSort(field)}
        className="inline-flex min-h-11 items-center gap-1 rounded px-1 hover:text-foreground focus-visible:outline-2 focus-visible:outline-focus sm:min-h-8"
      >
        {children}
        {sortField === field && <Icon name={sortAsc ? 'fa-arrow-up' : 'fa-arrow-down'} className="text-xs" />}
        {sortField === field && <span className="sr-only">{sortAsc ? ', lowest first' : ', highest first'}</span>}
      </button>
    </th>
  );

  if (!a) {
    // The server payload doesn't match this bundle (e.g. PHP-FPM not reloaded after a deploy).
    // Report it with what we did receive, then ask for a reload instead of crashing.
    console.error('AdminIndexPage: server props are missing "attention"; PHP and the JS bundle are out of sync', {
      receivedKeys: Object.keys(props),
    });
    return (
      <Page title="Admin overview" icon="fa-shield-halved" iconTone="text-accent" back={null} width="max-w-6xl">
        <p role="alert" className="text-sm">
          The admin data is out of date with this page. Reload the page; if this keeps happening, the server code was not reloaded after the last deploy.
        </p>
      </Page>
    );
  }

  return (
    <Page
      title="Admin overview"
      icon="fa-shield-halved"
      iconTone="text-accent"
      back={null}
      width="max-w-6xl"
      subtitle={
        <span className="flex flex-wrap items-center gap-2">
          <span>
            {season?.name}, day {season?.game_day} of {season?.duration_days} ({season?.days_left} days left)
          </span>
          <span className={`rounded-full px-2 text-sm font-semibold ${isStaging ? 'bg-warning/15' : 'bg-danger/15'}`}>
            {isStaging ? 'Staging' : 'Production'}
          </span>
          {season?.maintenance && <span className="rounded-full bg-danger/15 px-2 text-sm font-semibold">Maintenance is on</span>}
          <span>
            Server time {serverTime} ({serverTimezone})
          </span>
        </span>
      }
    >
      <section aria-labelledby="attention-h" className="flex flex-col gap-2">
        <h2 id="attention-h" className="text-lg font-semibold">
          Needs a look
        </h2>
        {(() => {
          const todo: { text: ReactNode; href: string; link: string }[] = [];
          if (a.supportOpen > 0) todo.push({ text: `${n(a.supportOpen)} ${a.supportOpen === 1 ? 'player is' : 'players are'} waiting for a support reply.`, href: '/admin/support', link: 'Open the inbox' });
          if (a.errors24h > 0) todo.push({ text: `${n(a.errors24h)} ${a.errors24h === 1 ? 'error' : 'errors'} logged in the last 24 hours.`, href: '/admin/errors', link: 'Read the log' });
          if (lastTick?.isStale) todo.push({ text: `The game tick is late: the last one ran ${lastTick.timeAgo}.`, href: '#system-tools', link: 'Run a tick' });
          if (a.suspicious > 0) todo.push({ text: `${n(a.suspicious)} ${a.suspicious === 1 ? 'account has' : 'accounts have'} numbers the ledger can't explain.`, href: '/admin/suspicious', link: 'Review' });
          if (a.roadmapToReview > 0) todo.push({ text: `${n(a.roadmapToReview)} roadmap ${a.roadmapToReview === 1 ? 'idea is' : 'ideas are'} waiting for a decision.`, href: '/roadmap', link: 'Review' });
          return todo.length === 0 ? (
            <p className="text-sm text-muted">Nothing needs you right now.</p>
          ) : (
            <ul className="flex flex-col divide-y divide-border border-y border-border">
              {todo.map((t) => (
                <li key={t.href} className="flex flex-wrap items-center justify-between gap-x-4 py-2 text-sm">
                  <span>{t.text}</span>
                  <a href={t.href} className="inline-flex min-h-11 items-center font-medium text-link hover:underline sm:min-h-0">
                    {t.link}
                  </a>
                </li>
              ))}
            </ul>
          );
        })()}
      </section>

      <section aria-labelledby="facts-h" className="flex flex-col gap-2">
        <h2 id="facts-h" className="text-lg font-semibold">
          At a glance
        </h2>
        <table className="w-full max-w-2xl text-sm">
          <tbody className="divide-y divide-border">
            {(
              [
                ['Sign-ups', `${n(a.signupsToday)} today, ${n(a.signups7d)} this week`],
                ['Active players', `${n(a.activeToday)} today, ${n(a.active7d)} this week`],
                ['Players', `${kpis.totalUsers} in total, ${kpis.bannedUsers} banned`],
                ['Cash held by players', `${kpis.totalCashFormatted} (${kpis.avgCashFormatted} on average)`],
                ['Last game tick', lastTick?.timeAgo ?? 'never'],
                [
                  'Nightly backup',
                  <a key="b" href={a.backupMonitorUrl} target="_blank" rel="noopener noreferrer" className="inline-flex min-h-11 items-center text-link hover:underline sm:min-h-0">
                    Check the Sentry monitor<span className="sr-only"> (opens in a new tab)</span>
                  </a>,
                ],
                ['Resorts', `${kpis.totalItems} lifts and slopes, ${kpis.totalBuildings} buildings, ${kpis.totalStaff} staff, ${kpis.totalLoans} active loans`],
              ] as [string, ReactNode][]
            ).map(([label, value]) => (
              <tr key={label}>
                <th scope="row" className="w-48 py-2 pr-4 text-left font-normal text-muted">
                  {label}
                </th>
                <td className="py-2 tabular-nums">{value}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </section>

      <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <Section
          className="min-w-0 lg:col-span-2"
          title={`Players (${filteredPlayers.length} shown of ${kpis.totalUsers})`}
        >
          <div className="mb-3 flex flex-wrap items-end gap-2">
            <div role="group" aria-label="Show" className="inline-flex rounded-lg border border-border p-0.5">
              {(['all', 'online', 'banned'] as const).map((m) => (
                <Button
                  key={m}
                  size="sm"
                  variant={filterMode === m ? 'primary' : 'ghost'}
                  aria-pressed={filterMode === m}
                  onPress={() => setFilterMode(m)}
                  className="min-h-11 sm:min-h-8"
                >
                  {m === 'all' ? 'All' : m === 'online' ? 'Online' : 'Banned'}
                </Button>
              ))}
            </div>
            <div className="min-w-40 flex-1">
              <Field
                label="Filter this list"
                type="search"
                placeholder="Name or ID"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
              />
            </div>
          </div>
          {filteredPlayers.length === 0 ? (
            <p className="py-8 text-center text-sm text-muted">No player matches this filter.</p>
          ) : (
            <div className="max-h-[32rem] overflow-auto">
              <table className="w-full text-left text-sm">
                <thead className="sticky top-0 z-10 bg-surface text-muted">
                  <tr className="border-b border-border">
                    <SortHead field="id">ID</SortHead>
                    <SortHead field="username">Player</SortHead>
                    <SortHead field="cash" right>
                      Cash
                    </SortHead>
                    <SortHead field="staff" right>
                      Staff
                    </SortHead>
                    <SortHead field="buildings" right>
                      Buildings
                    </SortHead>
                    <SortHead field="items" right>
                      Lifts and slopes
                    </SortHead>
                    <SortHead field="created">Joined</SortHead>
                    <SortHead field="active">Last active</SortHead>
                    <th scope="col" className="px-2 py-1 text-right font-semibold">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border">
                  {filteredPlayers.map((p) => (
                    <tr key={p.id} className={p.isBanned ? 'bg-danger/5' : ''}>
                      <td className="whitespace-nowrap px-2 py-1 tabular-nums text-muted">#{p.id}</td>
                      <td className="whitespace-nowrap px-2 py-1">
                        <a href={`/admin/user/${p.id}`} className="inline-flex min-h-11 items-center font-medium text-link hover:underline sm:min-h-0">
                          {p.username}
                        </a>
                        {p.isBanned && <span className="ml-1.5 rounded-full bg-danger/15 px-2 text-xs font-semibold">Banned</span>}
                      </td>
                      <td className="whitespace-nowrap px-2 py-1 text-right tabular-nums">{p.cashFormatted}</td>
                      <td className="px-2 py-1 text-right tabular-nums">{p.staff_count}</td>
                      <td className="px-2 py-1 text-right tabular-nums">{p.building_count}</td>
                      <td className="px-2 py-1 text-right tabular-nums">{p.item_count}</td>
                      <td className="whitespace-nowrap px-2 py-1 text-muted">{p.joinedFormatted}</td>
                      <td className="whitespace-nowrap px-2 py-1" title={p.lastActivityMessage}>
                        {p.isOnline ? (
                          <span className="font-medium">
                            <Icon name="fa-circle" className="mr-1 text-[0.5rem] text-success" />
                            Online
                          </span>
                        ) : (
                          <span className="text-muted">{p.lastActiveFormatted}</span>
                        )}
                      </td>
                      <td className="whitespace-nowrap px-2 py-1">
                        {p.id !== 1 && (
                          <div className="flex justify-end gap-1">
                            {p.isBanned ? (
                              <ConfirmForm
                                action={`/admin/unban/${p.id}`}
                                submitLabel={<>Unban<span className="sr-only"> {p.username}</span></>}
                                title={`Unban ${p.username}?`}
                                describe={() => `${p.username} will be able to log in again.`}
                                confirmLabel="Unban"
                              />
                            ) : (
                              <ConfirmForm
                                action={`/admin/ban/${p.id}`}
                                submitLabel={<>Ban<span className="sr-only"> {p.username}</span></>}
                                title={`Ban ${p.username}?`}
                                describe={() => `${p.username} will not be able to log in until you unban them.`}
                                confirmLabel="Ban"
                                danger
                              />
                            )}
                            <ConfirmForm
                              action={`/admin/delete/${p.id}`}
                              submitLabel={<>Delete<span className="sr-only"> {p.username}</span></>}
                              title={`Delete ${p.username}?`}
                              describe={() => `Deletes ${p.username} and everything in their resort. This cannot be undone.`}
                              confirmLabel="Delete for good"
                              danger
                            />
                          </div>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </Section>

        <Section
          className="min-w-0"
          title="Latest activity"
          actions={
            <a href="/admin/activity" className="inline-flex min-h-11 items-center text-sm font-medium text-link hover:underline sm:min-h-0">
              All activity
            </a>
          }
        >
          {recentLogs.length === 0 ? (
            <p className="py-8 text-center text-sm text-muted">No activity recorded yet.</p>
          ) : (
            <ul className="max-h-[32rem] divide-y divide-border overflow-y-auto">
              {recentLogs.map((log) => (
                <li key={log.id} className="flex items-start gap-2 py-2 text-sm">
                  <Icon name={`fa-${log.icon || 'circle-dot'}`} className="w-4 pt-0.5 text-center text-muted" />
                  <div className="min-w-0 flex-1">
                    <p className="break-words">
                      <span className="font-semibold">{log.username}</span> <span className="text-muted">{log.message}</span>
                    </p>
                    <p className="text-muted">
                      Day {log.game_day}, {log.timeAgo}
                    </p>
                  </div>
                </li>
              ))}
            </ul>
          )}
        </Section>
      </div>

      <section id="system-tools" aria-labelledby="system-tools-h" className="flex scroll-mt-4 flex-col gap-3">
        <div>
          <h2 id="system-tools-h" className="text-lg font-semibold">
            System tools
          </h2>
          <p className="text-sm text-muted">
            These act on the whole {envName} game. Each one asks before it runs.
          </p>
        </div>
        <div className="grid grid-cols-1 gap-x-8 gap-y-6 md:grid-cols-2 lg:grid-cols-3">
          <Section title="Game tick and maintenance">
            <div className="flex flex-col gap-3 text-sm">
              <p>
                Last tick: <strong>{lastTick?.timeAgo ?? 'never'}</strong>
                {lastTick?.isStale && ' (late)'}
              </p>
              <ConfirmForm
                action="/admin/trigger-tick"
                submitLabel="Run a tick now"
                title={`Run a game tick on ${envName}?`}
                describe={() => 'Every resort moves forward one game day: weather, visitors, revenue, salaries and loans.'}
                confirmLabel="Run the tick"
              />
              <p>
                Maintenance is <strong>{season?.maintenance ? 'on' : 'off'}</strong>.
              </p>
              <ConfirmForm
                action="/admin/maintenance"
                submitLabel={season?.maintenance ? 'Turn maintenance off' : 'Turn maintenance on'}
                title={season?.maintenance ? 'Turn maintenance off?' : 'Turn maintenance on?'}
                describe={() =>
                  season?.maintenance
                    ? 'Players can use their resorts again.'
                    : 'Players see the maintenance page until you turn it off.'
                }
                confirmLabel={season?.maintenance ? 'Turn it off' : 'Turn it on'}
                danger={!season?.maintenance}
              />
            </div>
          </Section>

          <Section title="Weather today">
            <ConfirmForm
              action="/admin/set-weather"
              className="flex flex-col gap-3"
              submitLabel="Set the weather"
              title={`Change today's weather on ${envName}?`}
              describe={(d) =>
                `Today becomes ${d.get('condition')}, ${d.get('temp')} °C, wind ${d.get('wind')} km/h, ${d.get('snowfall')} cm of new snow on a ${d.get('snow_base')} cm base.`
              }
              confirmLabel="Set it"
            >
              <div className="flex flex-col gap-1 text-sm">
                <span className="font-medium" aria-hidden="true">
                  Condition
                </span>
                <Select
                  name="condition"
                  aria-label="Condition"
                  value={wCond}
                  onChange={(val) => setWCond(String(val))}
                  options={(weather?.conditions || []).map((c) => ({ id: c, label: c }))}
                  className="w-full"
                />
              </div>
              <div className="grid grid-cols-2 gap-2">
                <Field label="Temperature (°C)" type="number" name="temp" defaultValue={weather?.temp ?? -5} min={-30} max={30} required />
                <Field label="Wind (km/h)" type="number" name="wind" defaultValue={weather?.wind ?? 15} min={0} max={120} required />
                <Field label="New snow (cm)" type="number" name="snowfall" defaultValue={weather?.snowfall ?? 0} min={0} max={50} required />
                <Field label="Snow base (cm)" type="number" name="snow_base" defaultValue={weather?.snow_base ?? 50} min={0} max={300} required />
              </div>
            </ConfirmForm>
          </Section>

          <Section title="Gifts to every player">
            <div className="flex flex-col gap-4">
              <ConfirmForm
                action="/admin/add-cash-all"
                className="flex flex-col gap-2"
                submitLabel="Give cash to everyone"
                title="Give cash to every player?"
                describe={(d) => `Adds ${money.prefix ? money.symbol : ''}${n(Number(d.get('amount')))}${money.prefix ? '' : ' ' + money.symbol} to the cash of all ${kpis.totalUsers} players. This cannot be undone in one step.`}
                confirmLabel="Give it"
                danger
              >
                <Field label={`Cash per player (${money.symbol})`} type="number" name="amount" defaultValue={50000} min={1} max={10000000} step={1} required />
              </ConfirmForm>
              <ConfirmForm
                action="/admin/add-genepis-all"
                className="flex flex-col gap-2 border-t border-border pt-4"
                submitLabel="Give Génépis to everyone"
                title="Give Génépis to every player?"
                describe={(d) => `Adds ${n(Number(d.get('amount')))} Génépis to every player who has a Génépis balance. This cannot be undone in one step.`}
                confirmLabel="Give them"
                danger
              >
                <Field label="Génépis per player" type="number" name="amount" defaultValue={10} min={1} max={100000} step={1} required />
              </ConfirmForm>
              <ConfirmForm
                action="/admin/grant-achievement"
                className="flex flex-col gap-2 border-t border-border pt-4"
                submitLabel="Grant achievement"
                title="Grant this achievement?"
                describe={(d) => `Marks achievement row #${d.get('achievement_id')} as completed for player #${d.get('user_id')}.`}
                confirmLabel="Grant it"
              >
                <div className="grid grid-cols-2 gap-2">
                  <Field label="Player ID" type="number" name="user_id" min={1} required />
                  <Field label="Achievement ID" type="number" name="achievement_id" min={1} required />
                </div>
              </ConfirmForm>
            </div>
          </Section>

          <Section title="Server" className="md:col-span-2 lg:col-span-3">
            <div className="grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
              <div className="flex flex-col gap-3">
                <Meter
                  value={Math.min(100, server?.diskPct ?? 0)}
                  color={(server?.diskPct ?? 0) > 85 ? 'danger' : (server?.diskPct ?? 0) > 70 ? 'warning' : 'success'}
                  aria-label="Disk used"
                >
                  <span className="text-muted">Disk</span>
                  <Meter.Output className="ml-auto">
                    {server?.diskUsedFormatted} of {server?.diskTotalFormatted} used ({server?.diskPct}%)
                  </Meter.Output>
                  <Meter.Track className="w-full">
                    <Meter.Fill />
                  </Meter.Track>
                </Meter>
                <dl className="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1">
                  <dt className="text-muted">Load (1, 5, 15 min)</dt>
                  <dd className="tabular-nums">
                    {(server?.load || []).map((l) => l.toFixed(2)).join(', ')}
                    {(server?.load[0] ?? 0) > (server?.cores ?? 1) && ' (above core count)'}
                  </dd>
                  <dt className="text-muted">CPU cores</dt>
                  <dd>{server?.cores}</dd>
                  <dt className="text-muted">PHP, CodeIgniter</dt>
                  <dd>
                    {server?.phpVersion}, {server?.ciVersion}
                  </dd>
                  <dt className="text-muted">Memory peak, limit</dt>
                  <dd>
                    {server?.memPeak} MB, {server?.memLimit}
                  </dd>
                  <dt className="text-muted">Database</dt>
                  <dd className="break-all">
                    {server?.dbVersion}, {server?.dbSize} MB
                  </dd>
                </dl>
              </div>
              <div className="flex flex-col gap-3">
                <ConfirmForm
                  action="/admin/optimize-storage"
                  submitLabel="Optimize storage"
                  title="Optimize storage?"
                  describe={() =>
                    `On ${envName}: deletes activity log rows older than 90 days and financial transactions older than 180 days, removes temporary PDF files, then optimizes the main tables. Deleted rows cannot be recovered.`
                  }
                  confirmLabel="Delete old rows and optimize"
                  danger
                />
                <p>
                  Debug mode is <strong>{isDev ? 'on (development)' : 'off (production)'}</strong>.
                  {!envWritable && ' The .env file is not writable, so it cannot be switched from here.'}
                </p>
                <ConfirmForm
                  action="/admin/toggle-env"
                  submitLabel={isDev ? 'Switch to production mode' : 'Switch to development mode'}
                  title={isDev ? 'Switch to production mode?' : 'Switch to development mode?'}
                  describe={() =>
                    isDev
                      ? `Turns debug output off on ${envName}.`
                      : `Turns debug output and the debug toolbar on for ${envName}. Error pages will show code and paths.`
                  }
                  confirmLabel="Switch"
                  danger={!isDev}
                  disabled={!envWritable}
                />
              </div>
            </div>
          </Section>
        </div>
      </section>
    </Page>
  );
}
