import { Chip } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Empty, Page, Panel } from '@/components/Page';

export type AllianceLeaderboardItem = {
  rank: number;
  id: number;
  name: string;
  tag: string;
  founder_name: string;
  crest_icon: string;
  crest_color: string;
  level: number;
  member_count: number;
  max_members: number;
  total_slopes: number;
  total_lifts: number;
  total_slope_meters: number;
  slopeKm: number;
  total_reputation: number;
  valuation: number;
  valuationText: string;
  pass_tier: number;
  passTierName: string;
  passVisitorPct: number;
  passTierBadge: string;
  isMyAlliance: boolean;
};

export type AllianceLeaderboardPageProps = {
  alliances: AllianceLeaderboardItem[];
  myAllianceId: number;
};

export default function AllianceLeaderboardPage({
  alliances,
  myAllianceId,
}: AllianceLeaderboardPageProps) {
  const topThree = alliances.slice(0, 3);

  const podiumStyles = [
    {
      border: 'border-warning/40 bg-warning/5',
      badgeColor: 'warning' as const,
      label: '1st Place &middot; World Champion',
      icon: 'fa-crown',
      iconColor: 'text-warning',
    },
    {
      border: 'border-border bg-surface',
      badgeColor: 'default' as const,
      label: '2nd Place',
      icon: 'fa-medal',
      iconColor: 'text-muted',
    },
    {
      border: 'border-border bg-surface',
      badgeColor: 'default' as const,
      label: '3rd Place',
      icon: 'fa-award',
      iconColor: 'text-amber-600',
    },
  ];

  return (
    <Page
      title="Alliance Standings"
      icon="fa-ranking-star"
      iconTone="text-warning"
      subtitle="Ranking the world's premier alpine syndicates by combined valuation and mountain infrastructure."
      back="/alliances"
      actions={
        <div className="flex flex-wrap items-center gap-2">
          <a
            href="/leaderboard"
            className="inline-flex min-h-[44px] items-center gap-1.5 rounded-lg border border-border px-3 text-sm font-medium hover:bg-default"
          >
            <Icon name="fa-person-skiing" />
            Individual Leaderboard
          </a>
          <a
            href="/alliances"
            className="inline-flex min-h-[44px] items-center gap-1.5 rounded-lg border border-border bg-accent px-3 text-sm font-bold text-accent-foreground hover:opacity-90"
          >
            <Icon name="fa-handshake" />
            My Alliance
          </a>
        </div>
      }
      width="max-w-7xl"
    >
      {/* Top 3 Podium Cards */}
      {topThree.length > 0 && (
        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
          {topThree.map((a, idx) => {
            const style = podiumStyles[idx] || podiumStyles[1];
            return (
              <div
                key={a.id}
                className={`rounded-xl border p-5 space-y-3 relative overflow-hidden shadow-sm ${style.border}`}
              >
                <div className="flex items-center justify-between">
                  <Chip
                    size="sm"
                    color={style.badgeColor}
                    variant="soft"
                    className="font-mono font-bold"
                  >
                    <span dangerouslySetInnerHTML={{ __html: style.label }} />
                  </Chip>
                  <Icon name={style.icon} className={`text-lg ${style.iconColor}`} />
                </div>

                <div className="flex items-center gap-3">
                  <span
                    className="flex size-10 shrink-0 items-center justify-center rounded-xl text-sm font-bold text-white shadow-sm"
                    style={{ backgroundColor: a.crest_color }}
                  >
                    <Icon name={a.crest_icon} />
                  </span>
                  <div className="min-w-0 flex-1">
                    <a
                      href={`/alliances/view/${a.id}`}
                      className="block truncate text-base font-bold text-foreground hover:text-accent hover:underline"
                    >
                      {a.name}
                    </a>
                    <div className="font-mono text-xs text-muted">
                      [{a.tag}] &middot; Lvl {a.level}
                    </div>
                  </div>
                </div>

                <div className="grid grid-cols-2 gap-2 border-t border-border pt-2 text-xs">
                  <div>
                    <div className="text-muted">Combined Valuation</div>
                    <div className="font-mono text-sm font-bold text-success">
                      {a.valuationText}
                    </div>
                  </div>
                  <div>
                    <div className="text-muted">Infrastructure</div>
                    <div className="font-mono text-sm font-semibold text-foreground">
                      {a.total_slopes} Slopes &middot; {a.total_lifts} Lifts
                    </div>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      )}

      {/* Complete Rankings Table */}
      <Panel
        title={
          <div className="flex items-center gap-2">
            <span>Complete Syndicate Rankings</span>
            <Chip size="sm" variant="soft" className="font-mono">
              {alliances.length} Alliances
            </Chip>
          </div>
        }
      >
        {alliances.length === 0 ? (
          <Empty
            icon="fa-ranking-star"
            title="No alliances ranked yet"
          >
            <p>Standings will update as soon as alliances are chartered.</p>
          </Empty>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead>
                <tr className="border-b border-border text-muted">
                  <th className="w-12 pb-2 text-center font-medium">Rank</th>
                  <th className="pb-2 font-medium">Alliance</th>
                  <th className="pb-2 font-medium">Syndicate Pass</th>
                  <th className="pb-2 font-medium">Roster</th>
                  <th className="pb-2 font-medium">Slopes &amp; Lifts</th>
                  <th className="pb-2 font-medium">Trail Network</th>
                  <th className="pb-2 font-medium">Reputation</th>
                  <th className="pb-2 text-right font-medium">Valuation</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {alliances.map((a) => (
                  <tr
                    key={a.id}
                    className={
                      a.isMyAlliance
                        ? 'bg-accent/10 font-medium'
                        : 'hover:bg-default/40'
                    }
                  >
                    <td className="py-2.5 text-center font-mono font-bold">
                      {a.rank === 1 ? (
                        <Icon name="fa-crown" className="text-warning" />
                      ) : a.rank === 2 ? (
                        <span className="text-muted">2</span>
                      ) : a.rank === 3 ? (
                        <span className="text-amber-600">3</span>
                      ) : (
                        <span className="text-muted">{a.rank}</span>
                      )}
                    </td>
                    <td className="py-2.5">
                      <div className="flex items-center gap-2">
                        <span
                          className="flex size-6 shrink-0 items-center justify-center rounded-md text-[10px] font-bold text-white"
                          style={{ backgroundColor: a.crest_color }}
                        >
                          <Icon name={a.crest_icon} />
                        </span>
                        <div>
                          <a
                            href={`/alliances/view/${a.id}`}
                            className="font-bold text-foreground hover:text-accent hover:underline"
                          >
                            {a.name}
                          </a>
                          <span className="ml-1 font-mono text-[11px] text-muted">[{a.tag}]</span>
                          {a.isMyAlliance && (
                            <Chip size="sm" variant="soft" color="accent" className="ml-1 text-[10px]">
                              Your Alliance
                            </Chip>
                          )}
                        </div>
                      </div>
                    </td>
                    <td className="py-2.5">
                      <Chip size="sm" variant="soft" color="accent" className="font-mono">
                        Tier {a.pass_tier} (+{a.passVisitorPct}%)
                      </Chip>
                    </td>
                    <td className="py-2.5 font-mono">
                      {a.member_count} / {a.max_members}
                    </td>
                    <td className="py-2.5 font-mono text-muted">
                      <span className="text-foreground">{a.total_slopes}</span>S &middot;{' '}
                      <span className="text-foreground">{a.total_lifts}</span>L
                    </td>
                    <td className="py-2.5 font-mono text-info">{a.slopeKm} km</td>
                    <td className="py-2.5 font-mono">{a.total_reputation.toLocaleString()}</td>
                    <td className="py-2.5 text-right font-mono font-bold text-success">
                      {a.valuationText}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </Panel>
    </Page>
  );
}
