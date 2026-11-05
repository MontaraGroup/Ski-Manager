import { Icon } from '@/components/Icon';
import { Page, Panel } from '@/components/Page';

export type ActiveVip = {
  id: number;
  name: string;
  vip_type: string;
  typeName: string;
  status: string;
  rewardFormatted: string;
  reputation_bonus: number;
  requirements: string;
  days_remaining: number;
  game_day_arrived: number;
  stayPct: number;
  icon: string;
  color: string;
};

export type PastVip = {
  id: number;
  name: string;
  vip_type: string;
  typeName: string;
  status: string;
  isSatisfied: boolean;
  rewardFormatted: string;
  reputation_bonus: string;
  game_day_arrived: number;
  icon: string;
  color: string;
};

export type VipTypeCatalogItem = {
  key: string;
  name: string;
  rarityLabel: string;
  rarity: number;
  rewardFormatted: string;
  rep_bonus: number;
  duration: number;
  requirements: string;
  icon: string;
  color: string;
};

export type VipGuestsPageProps = {
  activeCount: number;
  satisfiedCount: number;
  totalEarnedFormatted: string;
  activeVips: ActiveVip[];
  pastVips: PastVip[];
  typesCatalog: VipTypeCatalogItem[];
};

export default function VipGuestsPage({
  activeCount = 0,
  satisfiedCount = 0,
  totalEarnedFormatted = '€0',
  activeVips = [],
  pastVips = [],
  typesCatalog = [],
}: VipGuestsPageProps) {
  const getRarityBadge = (label: string) => {
    switch (label) {
      case 'Legendary':
        return 'bg-danger/15 text-danger border-danger/30';
      case 'Rare':
        return 'bg-warning/15 text-warning border-warning/30';
      case 'Uncommon':
        return 'bg-primary/15 text-primary border-primary/30';
      default:
        return 'bg-success/15 text-success border-success/30';
    }
  };

  return (
    <Page
      title="VIP Guests"
      icon="fa-star"
      iconTone="text-warning"
      back="/dashboard"
      width="max-w-6xl"
      subtitle="High-profile visitors who pay big and boost your reputation - if you meet their standards"
    >
      <div className="space-y-6">
        {/* KPI Banner */}
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div className="rounded-xl border border-warning/30 bg-warning/10 p-4 text-center">
            <div className="text-xs text-muted mb-1 flex items-center justify-center gap-1.5 font-medium">
              <Icon name="fa-user-clock" className="text-warning text-xs" />
              Active VIPs
            </div>
            <div className="text-3xl font-extrabold tabular-nums text-warning">{activeCount}</div>
          </div>

          <div className="rounded-xl border border-success/30 bg-success/10 p-4 text-center">
            <div className="text-xs text-muted mb-1 flex items-center justify-center gap-1.5 font-medium">
              <Icon name="fa-circle-check" className="text-success text-xs" />
              Satisfied
            </div>
            <div className="text-3xl font-extrabold tabular-nums text-success">{satisfiedCount}</div>
          </div>

          <div className="rounded-xl border border-success/30 bg-success/10 p-4 text-center">
            <div className="text-xs text-muted mb-1 flex items-center justify-center gap-1.5 font-medium">
              <Icon name="fa-sack-dollar" className="text-success text-xs" />
              Total Earned
            </div>
            <div className="text-3xl font-extrabold tabular-nums text-success">
              {totalEarnedFormatted}
            </div>
          </div>
        </div>

        {/* Active VIPs */}
        <Panel
          title={
            <span className="flex items-center gap-2">
              <Icon name="fa-user-tie" className="text-warning" />
              Active Visitors ({activeVips.length})
            </span>
          }
        >
          {activeVips.length === 0 ? (
            <div className="text-center py-10">
              <div className="w-14 h-14 rounded-full bg-base-200 flex items-center justify-center mx-auto mb-3 text-muted/40">
                <Icon name="fa-star" className="text-2xl" />
              </div>
              <h3 className="font-semibold text-foreground text-sm">No VIP guests right now</h3>
              <p className="text-xs text-muted mt-1 max-w-md mx-auto leading-relaxed">
                Build more slopes, hire staff, and construct prestige buildings. VIPs arrive
                automatically when your resort meets their high standards.
              </p>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {activeVips.map((vip) => {
                const isVisiting = vip.status === 'visiting';
                const iconName = vip.icon.startsWith('fa-') ? vip.icon : `fa-${vip.icon}`;

                return (
                  <div
                    key={vip.id}
                    className={`rounded-xl border p-4 bg-base-100 shadow-sm transition-all ${
                      isVisiting
                        ? 'border-success/60 ring-1 ring-success/20'
                        : 'border-warning/60 ring-1 ring-warning/20'
                    }`}
                  >
                    <div className="flex items-start justify-between gap-2 mb-2">
                      <div className="flex items-center gap-2.5 min-w-0">
                        <div className="w-9 h-9 rounded-lg bg-base-200 flex items-center justify-center shrink-0">
                          <Icon name={iconName} className={vip.color || 'text-warning'} />
                        </div>
                        <div className="min-w-0">
                          <h3 className="font-bold text-sm text-foreground truncate">{vip.name}</h3>
                          <p className="text-xs text-muted truncate">{vip.typeName}</p>
                        </div>
                      </div>

                      <span
                        className={`px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider ${
                          isVisiting
                            ? 'bg-success/20 text-success'
                            : 'bg-warning/20 text-warning'
                        }`}
                      >
                        {vip.status}
                      </span>
                    </div>

                    <div className="grid grid-cols-2 gap-2 my-3">
                      <div className="bg-base-200/60 rounded-lg p-2 text-center">
                        <div className="font-bold text-success text-sm tabular-nums">
                          {vip.rewardFormatted}
                        </div>
                        <div className="text-[10px] text-muted uppercase font-medium">Reward</div>
                      </div>
                      <div className="bg-base-200/60 rounded-lg p-2 text-center">
                        <div className="font-bold text-warning text-sm tabular-nums">
                          +{vip.reputation_bonus}
                        </div>
                        <div className="text-[10px] text-muted uppercase font-medium">
                          Reputation
                        </div>
                      </div>
                    </div>

                    <div className="space-y-1.5 pt-2 border-t border-border/40 text-xs">
                      <div className="flex items-center justify-between text-muted text-[11px]">
                        <span>Stay Duration</span>
                        <span className="font-semibold text-foreground">
                          {vip.days_remaining} {vip.days_remaining === 1 ? 'day' : 'days'} left
                        </span>
                      </div>
                      <div className="w-full bg-base-200 rounded-full h-2 overflow-hidden">
                        <div
                          className={`h-full transition-all rounded-full ${
                            isVisiting ? 'bg-success' : 'bg-warning'
                          }`}
                          style={{ width: `${Math.min(100, Math.max(0, vip.stayPct))}%` }}
                        />
                      </div>
                      {vip.requirements && (
                        <div className="text-[11px] text-muted mt-2">
                          <span className="font-semibold text-foreground">Demands: </span>
                          {vip.requirements}
                        </div>
                      )}
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </Panel>

        {/* VIP Catalog */}
        <Panel
          title={
            <span className="flex items-center gap-2">
              <Icon name="fa-address-book" className="text-primary" />
              VIP Profiles You Can Attract
            </span>
          }
        >
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            {typesCatalog.map((type) => {
              const iconName = type.icon.startsWith('fa-') ? type.icon : `fa-${type.icon}`;

              return (
                <div
                  key={type.key}
                  className="rounded-xl border border-border bg-base-200/30 p-3.5 hover:bg-base-200/60 transition-colors flex flex-col justify-between"
                >
                  <div>
                    <div className="flex items-center justify-between gap-2 mb-2">
                      <div className="flex items-center gap-2 min-w-0">
                        <div className="w-8 h-8 rounded-lg bg-base-100 flex items-center justify-center shrink-0">
                          <Icon name={iconName} className={type.color || 'text-warning'} />
                        </div>
                        <div className="font-bold text-xs text-foreground truncate">{type.name}</div>
                      </div>
                      <span
                        className={`px-2 py-0.5 rounded text-[10px] font-bold border uppercase tracking-wider ${getRarityBadge(
                          type.rarityLabel
                        )}`}
                      >
                        {type.rarityLabel}
                      </span>
                    </div>

                    <div className="grid grid-cols-3 gap-1.5 text-center my-2.5 p-2 rounded-lg bg-base-100 border border-border/40">
                      <div>
                        <div className="text-[10px] text-muted">Reward</div>
                        <div className="text-xs font-bold text-success tabular-nums">
                          {type.rewardFormatted}
                        </div>
                      </div>
                      <div>
                        <div className="text-[10px] text-muted">Rep</div>
                        <div className="text-xs font-bold text-warning tabular-nums">
                          +{type.rep_bonus}
                        </div>
                      </div>
                      <div>
                        <div className="text-[10px] text-muted">Stay</div>
                        <div className="text-xs font-bold text-foreground tabular-nums">
                          {type.duration}d
                        </div>
                      </div>
                    </div>
                  </div>

                  <div className="text-[11px] text-muted pt-2 border-t border-border/40">
                    <span className="font-semibold text-foreground">Needs: </span>
                    {type.requirements || 'Standard facilities'}
                  </div>
                </div>
              );
            })}
          </div>
        </Panel>

        {/* Past Visits Table */}
        {pastVips.length > 0 && (
          <Panel
            title={
              <span className="flex items-center gap-2">
                <Icon name="fa-clock-rotate-left" className="text-muted" />
                Past Visits
              </span>
            }
          >
            <div className="overflow-x-auto -mx-4 sm:mx-0">
              <table className="w-full text-left text-xs">
                <thead>
                  <tr className="border-b border-border text-muted uppercase text-[10px] font-bold">
                    <th className="py-2.5 px-3">Guest</th>
                    <th className="py-2.5 px-3">Type</th>
                    <th className="py-2.5 px-3">Result</th>
                    <th className="py-2.5 px-3">Reward</th>
                    <th className="py-2.5 px-3">Rep</th>
                    <th className="py-2.5 px-3 text-right">Arrived</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border/40">
                  {pastVips.map((vip) => {
                    const iconName = vip.icon.startsWith('fa-') ? vip.icon : `fa-${vip.icon}`;

                    return (
                      <tr key={vip.id} className="hover:bg-base-200/40 transition-colors">
                        <td className="py-2.5 px-3 font-semibold text-foreground flex items-center gap-2">
                          <Icon name={iconName} className={vip.color || 'text-warning'} />
                          {vip.name}
                        </td>
                        <td className="py-2.5 px-3 text-muted">{vip.typeName}</td>
                        <td className="py-2.5 px-3">
                          <span
                            className={`px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider ${
                              vip.isSatisfied
                                ? 'bg-success/20 text-success'
                                : 'bg-danger/20 text-danger'
                            }`}
                          >
                            {vip.status}
                          </span>
                        </td>
                        <td className="py-2.5 px-3 font-bold text-success tabular-nums">
                          {vip.rewardFormatted}
                        </td>
                        <td className="py-2.5 px-3 text-warning font-semibold tabular-nums">
                          {vip.reputation_bonus}
                        </td>
                        <td className="py-2.5 px-3 text-right text-muted tabular-nums">
                          Day {vip.game_day_arrived}
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          </Panel>
        )}

        {/* Informational Guide */}
        <div className="rounded-xl border border-border bg-base-200/30 p-4">
          <h3 className="font-semibold text-xs text-foreground mb-3 flex items-center gap-2">
            <Icon name="fa-circle-info" className="text-primary" />
            How VIP Guests Work
          </h3>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2.5 text-xs text-muted">
            <div className="flex items-center gap-2">
              <Icon name="fa-dice" className="text-primary shrink-0" />
              <span>VIPs arrive randomly based on resort quality</span>
            </div>
            <div className="flex items-center gap-2">
              <Icon name="fa-list-check" className="text-warning shrink-0" />
              <span>Each demands specific lifts, slopes, or staff</span>
            </div>
            <div className="flex items-center gap-2">
              <Icon name="fa-check" className="text-success shrink-0" />
              <span>Meet standards throughout visit for payout</span>
            </div>
            <div className="flex items-center gap-2">
              <Icon name="fa-xmark" className="text-danger shrink-0" />
              <span>Neglect them and they leave disappointed</span>
            </div>
            <div className="flex items-center gap-2">
              <Icon name="fa-gem" className="text-secondary shrink-0" />
              <span>Rarer VIPs pay larger sums but demand more</span>
            </div>
            <div className="flex items-center gap-2">
              <Icon name="fa-arrow-trend-up" className="text-success shrink-0" />
              <span>High reputation attracts elite personalities</span>
            </div>
          </div>
        </div>
      </div>
    </Page>
  );
}
