import { useState } from 'react';
import { Button } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput, Page, Panel } from '@/components/Page';

export type AdminSeason = {
  id: number;
  name: string;
  season_number: number;
  resort_map: string;
  start_date: string;
  duration_days: number;
  winter_days: number;
} | null;

export type AdminSector = {
  id: number;
  name: string;
  color: string;
  visible: boolean;
  released: boolean;
};

export type AdminWeather = {
  game_day: number;
  condition_name: string;
  tempFormatted: string;
  windFormatted: string;
  snowBaseFormatted: string;
};

export type AdminTournament = {
  id: number;
  name: string;
  status: string;
};

export type AdminSettingsPageProps = {
  season: AdminSeason;
  sectors: AdminSector[];
  segmentCount: number;
  weather: AdminWeather[];
  tournaments: AdminTournament[];
  gameDay: number;
};

export default function AdminSettingsPage({
  season,
  sectors,
  segmentCount,
  weather,
  tournaments,
  gameDay,
}: AdminSettingsPageProps) {
  const [seasonForm, setSeasonForm] = useState({
    name: season?.name || '',
    start_date: season?.start_date || '',
    duration_days: season?.duration_days || 135,
    winter_days: season?.winter_days || 100,
  });

  return (
    <Page
      title="Game Settings"
      icon="fa-gear"
      iconTone="text-accent"
      back="/admin"
      subtitle={`Global game settings · Current Game Day ${gameDay}`}
    >
      <div className="grid grid-cols-1 gap-5 lg:grid-cols-2">
        {/* Season Configuration */}
        <Panel
          title={
            <div className="flex items-center gap-2">
              <Icon name="fa-calendar-days" className="text-accent" />
              <span>Season Settings</span>
            </div>
          }
        >
          {season ? (
            <form action="/admin/season" method="post" className="space-y-4">
              <CsrfInput />
              <div className="grid grid-cols-2 gap-3">
                <div className="space-y-1">
                  <label className="text-xs font-semibold text-muted">Season Name</label>
                  <input
                    type="text"
                    name="name"
                    value={seasonForm.name}
                    onChange={(e) => setSeasonForm({ ...seasonForm, name: e.target.value })}
                    className="w-full rounded-xl border border-border bg-surface-raised px-3 py-2 text-xs font-semibold text-foreground focus:border-accent focus:outline-hidden"
                    required
                  />
                </div>
                <div className="space-y-1">
                  <label className="text-xs font-semibold text-muted">Start Date</label>
                  <input
                    type="date"
                    name="start_date"
                    value={seasonForm.start_date}
                    onChange={(e) => setSeasonForm({ ...seasonForm, start_date: e.target.value })}
                    className="w-full rounded-xl border border-border bg-surface-raised px-3 py-2 text-xs font-semibold text-foreground focus:border-accent focus:outline-hidden"
                    required
                  />
                </div>
                <div className="space-y-1">
                  <label className="text-xs font-semibold text-muted">Total Days</label>
                  <input
                    type="number"
                    name="duration_days"
                    value={seasonForm.duration_days}
                    onChange={(e) =>
                      setSeasonForm({ ...seasonForm, duration_days: Number(e.target.value) })
                    }
                    className="w-full rounded-xl border border-border bg-surface-raised px-3 py-2 text-xs font-semibold text-foreground focus:border-accent focus:outline-hidden"
                    required
                  />
                </div>
                <div className="space-y-1">
                  <label className="text-xs font-semibold text-muted">Winter Days</label>
                  <input
                    type="number"
                    name="winter_days"
                    value={seasonForm.winter_days}
                    onChange={(e) =>
                      setSeasonForm({ ...seasonForm, winter_days: Number(e.target.value) })
                    }
                    className="w-full rounded-xl border border-border bg-surface-raised px-3 py-2 text-xs font-semibold text-foreground focus:border-accent focus:outline-hidden"
                    required
                  />
                </div>
              </div>

              <div className="flex items-center justify-between text-xs text-muted">
                <span>
                  Resort Map: <strong className="text-foreground">{season.resort_map}</strong>
                </span>
                <span>
                  Season <strong className="font-mono text-foreground">#{season.season_number}</strong>
                </span>
              </div>

              <Button type="submit" variant="primary" className="w-full text-xs font-bold">
                <Icon name="fa-floppy-disk" className="mr-1.5" />
                Save Season Settings
              </Button>
            </form>
          ) : (
            <p className="text-xs text-muted">No active season found.</p>
          )}
        </Panel>

        {/* Sectors Release */}
        <Panel
          title={
            <div className="flex items-center gap-2">
              <Icon name="fa-layer-group" className="text-warning" />
              <span>Resort Sectors</span>
            </div>
          }
        >
          {sectors.length === 0 ? (
            <p className="text-xs text-muted">No sectors defined for current map.</p>
          ) : (
            <div className="space-y-2">
              {sectors.map((sec) => (
                <div
                  key={sec.id}
                  className="flex items-center justify-between rounded-xl border border-border bg-surface-raised p-2.5"
                >
                  <div className="flex items-center gap-2.5">
                    <span
                      className="inline-block size-3 rounded-full shrink-0 shadow-xs"
                      style={{ backgroundColor: sec.color }}
                    />
                    <span className="text-xs font-bold text-foreground">{sec.name}</span>
                  </div>

                  <div className="flex items-center gap-2">
                    <span
                      className={`rounded px-1.5 py-0.5 text-[10px] font-bold uppercase ${
                        sec.visible
                          ? 'bg-emerald-600/15 text-emerald-400'
                          : 'bg-surface text-muted'
                      }`}
                    >
                      {sec.visible ? 'Visible' : 'Hidden'}
                    </span>

                    <form
                      action={`/admin/sector-release/${sec.id}`}
                      method="post"
                      className="inline"
                    >
                      <CsrfInput />
                      <Button
                        type="submit"
                        size="sm"
                        variant={sec.released ? 'primary' : 'outline'}
                        className="h-7 text-xs font-semibold"
                      >
                        <Icon
                          name={sec.released ? 'fa-lock-open' : 'fa-lock'}
                          className="mr-1 text-[11px]"
                        />
                        {sec.released ? 'Released' : 'Locked'}
                      </Button>
                    </form>
                  </div>
                </div>
              ))}
            </div>
          )}
        </Panel>

        {/* Recent Weather */}
        <Panel
          title={
            <div className="flex items-center gap-2">
              <Icon name="fa-cloud-sun" className="text-accent" />
              <span>Recent Weather History</span>
            </div>
          }
        >
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="border-b border-border text-[11px] font-semibold text-muted">
                <tr>
                  <th className="pb-2">Day</th>
                  <th className="pb-2">Condition</th>
                  <th className="pb-2">Temp</th>
                  <th className="pb-2">Wind</th>
                  <th className="pb-2">Base</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border/60 font-mono">
                {weather.map((w) => (
                  <tr key={w.game_day} className="hover:bg-surface-raised/40">
                    <td className="py-2 font-bold text-foreground">Day {w.game_day}</td>
                    <td className="py-2 font-sans font-medium text-foreground">
                      {w.condition_name}
                    </td>
                    <td className="py-2 text-foreground">{w.tempFormatted}</td>
                    <td className="py-2 text-muted">{w.windFormatted}</td>
                    <td className="py-2 text-accent font-bold">{w.snowBaseFormatted}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </Panel>

        {/* Map Segments & Tournaments */}
        <div className="space-y-5">
          <Panel
            title={
              <div className="flex items-center gap-2">
                <Icon name="fa-map" className="text-accent" />
                <span>Map Segments</span>
              </div>
            }
            actions={
              <a
                href="/map"
                className="inline-flex items-center gap-1.5 rounded-lg border border-border bg-surface px-3 py-1 text-xs font-semibold text-foreground hover:bg-surface-raised"
              >
                <span>Manage Map</span>
                <Icon name="fa-arrow-right" className="text-[10px]" />
              </a>
            }
          >
            <p className="text-xs text-muted">
              Current resort map has{' '}
              <strong className="font-mono text-foreground font-bold">{segmentCount}</strong> active
              terrain segments configured.
            </p>
          </Panel>

          <Panel
            title={
              <div className="flex items-center gap-2">
                <Icon name="fa-trophy" className="text-warning" />
                <span>Tournaments</span>
              </div>
            }
            actions={
              <a
                href="/tournaments"
                className="text-xs font-semibold text-accent hover:underline"
              >
                View all &rarr;
              </a>
            }
          >
            {tournaments.length === 0 ? (
              <p className="text-xs text-muted">No tournaments created.</p>
            ) : (
              <div className="space-y-1.5">
                {tournaments.map((t) => (
                  <div
                    key={t.id}
                    className="flex items-center justify-between rounded-lg bg-surface-raised/60 p-2 text-xs"
                  >
                    <span className="font-medium text-foreground truncate mr-2">{t.name}</span>
                    <span
                      className={`rounded px-1.5 py-0.5 text-[10px] font-bold uppercase shrink-0 ${
                        t.status === 'active'
                          ? 'bg-emerald-600/15 text-emerald-400'
                          : t.status === 'upcoming'
                            ? 'bg-accent/15 text-accent'
                            : 'bg-surface text-muted'
                      }`}
                    >
                      {t.status}
                    </span>
                  </div>
                ))}
              </div>
            )}
          </Panel>

          <Panel
            title={
              <div className="flex items-center gap-2">
                <Icon name="fa-link" className="text-muted" />
                <span>Quick Administration Links</span>
              </div>
            }
          >
            <div className="flex flex-wrap gap-2">
              <a
                href="/admin/errors"
                className="inline-flex items-center gap-1.5 rounded-xl border border-border bg-surface px-3 py-1.5 text-xs font-semibold text-foreground hover:bg-surface-raised"
              >
                <Icon name="fa-bug" className="text-danger" />
                <span>Error Log</span>
              </a>
              <a
                href="/admin/broadcast"
                className="inline-flex items-center gap-1.5 rounded-xl border border-border bg-surface px-3 py-1.5 text-xs font-semibold text-foreground hover:bg-surface-raised"
              >
                <Icon name="fa-bullhorn" className="text-warning" />
                <span>Broadcast</span>
              </a>
              <a
                href="/admin/economy"
                className="inline-flex items-center gap-1.5 rounded-xl border border-border bg-surface px-3 py-1.5 text-xs font-semibold text-foreground hover:bg-surface-raised"
              >
                <Icon name="fa-chart-line" className="text-accent" />
                <span>Economy</span>
              </a>
              <a
                href="/admin/activity"
                className="inline-flex items-center gap-1.5 rounded-xl border border-border bg-surface px-3 py-1.5 text-xs font-semibold text-foreground hover:bg-surface-raised"
              >
                <Icon name="fa-clock-rotate-left" className="text-muted" />
                <span>Activity Logs</span>
              </a>
            </div>
          </Panel>
        </div>
      </div>
    </Page>
  );
}
