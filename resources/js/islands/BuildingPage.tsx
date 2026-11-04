import { Chip, Meter } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Empty, Figure, Page, Panel, PostButton } from '@/components/Page';

export type BuildingItem = {
  id: number;
  name: string;
  level: number;
  capacity: number;
  revenue_per_day: number;
  revenueText: string;
  upkeep_per_day: number;
  upkeepText: string;
  condition_pct: number;
  status: 'open' | 'closed' | string;
  nextLevel: {
    level: number;
    name: string;
    cost: number;
    costText: string;
  } | null;
  refundText: string;
};

export type CatalogItem = {
  level: number;
  name: string;
  capacity: number;
  revenue: number;
  revenueText: string;
  upkeep: number;
  upkeepText: string;
  cost: number;
  costText: string;
};

export type BuildingPageProps = {
  type: 'hotel' | 'rental' | 'restaurant' | 'retail' | 'ski_patrol' | 'transportation' | string;
  def: {
    label: string;
    singular: string;
    icon: string;
    desc: string;
    route: string;
  };
  stats: {
    totalCapacity: number;
    totalRevenueText: string;
    totalUpkeepText: string;
    netProfitText: string;
    openCount: number;
    totalCount: number;
  };
  buildings: BuildingItem[];
  catalog: CatalogItem[];
  domain: Record<string, any>;
};

export default function BuildingPage({
  type,
  def,
  stats,
  buildings,
  catalog,
  domain,
}: BuildingPageProps) {
  // Label for capacity depending on building type
  const capacityLabel =
    type === 'hotel'
      ? 'Beds'
      : type === 'rental'
      ? 'Gear sets'
      : type === 'restaurant'
      ? 'Seats'
      : type === 'ski_patrol'
      ? 'Sectors'
      : 'Capacity';

  return (
    <Page
      title={def.label}
      icon={def.icon}
      iconTone="text-accent"
      subtitle={def.desc}
      width="max-w-6xl"
    >
      {/* Top Telemetry / Summary Figures */}
      <div className="grid grid-cols-2 gap-3 md:grid-cols-4 lg:grid-cols-5">
        {type === 'hotel' && (
          <>
            <Figure
              label="Star rating"
              value={`${domain.starRating} / 5`}
              tone="text-accent"
              note="Resort hotel average"
            />
            <Figure
              label="Tonight's occupancy"
              value={`${domain.occupancyRate}%`}
              tone={domain.occupancyRate >= 70 ? 'text-success' : 'text-foreground'}
              note={`${domain.occupiedBeds} / ${stats.totalCapacity} beds filled`}
            />
            <Figure
              label="Daily revenue"
              value={stats.totalRevenueText}
              tone="text-success"
              note={`Net: ${stats.netProfitText} / day`}
            />
            <Figure
              label="Front desk staff"
              value={domain.receptionists}
              tone={domain.receptionists >= stats.openCount ? 'text-success' : 'text-warning'}
              note={domain.receptionists >= stats.openCount ? 'Fully staffed' : `Need ${stats.openCount - domain.receptionists} more`}
            />
            <Figure
              label="Open properties"
              value={`${stats.openCount} / ${stats.totalCount}`}
              note="Active accommodations"
            />
          </>
        )}

        {type === 'rental' && (
          <>
            <Figure
              label="Rental shops"
              value={`${stats.openCount} / ${stats.totalCount}`}
              note="Active shops"
            />
            <Figure
              label="Gear sets"
              value={stats.totalCapacity}
              note={`${domain.totalSetsInUse} currently rented`}
            />
            <Figure
              label="Daily revenue"
              value={stats.totalRevenueText}
              tone="text-success"
              note={`Net: ${stats.netProfitText} / day`}
            />
            <Figure
              label="Utilization"
              value={`${domain.utilization}%`}
              tone={domain.utilization >= 70 ? 'text-accent' : 'text-foreground'}
              note="Rental gear in use"
            />
            <Figure
              label="Operating upkeep"
              value={stats.totalUpkeepText}
              tone="text-danger"
              note="Daily maintenance"
            />
          </>
        )}

        {type === 'restaurant' && (
          <>
            <Figure
              label="Venues"
              value={`${stats.openCount} / ${stats.totalCount}`}
              note="Active dining"
            />
            <Figure
              label="Total seats"
              value={stats.totalCapacity.toLocaleString('en-US')}
              note="Across all venues"
            />
            <Figure
              label="Food quality"
              value={`${domain.foodQuality}%`}
              tone={domain.foodQuality >= 70 ? 'text-success' : domain.foodQuality >= 40 ? 'text-warning' : 'text-danger'}
              note="Rating boost factor"
            />
            <Figure
              label="Kitchen chefs"
              value={domain.chefCount}
              note={`Avg Level ${domain.avgChefLevel}`}
            />
            <Figure
              label="Net daily profit"
              value={stats.netProfitText}
              tone="text-success"
              note="Revenue minus food/staff"
            />
          </>
        )}

        {type === 'retail' && (
          <>
            <Figure
              label="Stores"
              value={`${stats.openCount} / ${stats.totalCount}`}
              note={`${domain.totalProducts} product lines`}
            />
            <Figure
              label="Daily sales"
              value={stats.totalRevenueText}
              tone="text-success"
              note="Merchandise turnover"
            />
            <Figure
              label="Profit margin"
              value={`${domain.avgMargin}%`}
              tone="text-accent"
              note="Average margin"
            />
            <Figure
              label="Net daily profit"
              value={stats.netProfitText}
              tone="text-success"
              note="Daily store return"
            />
            <Figure
              label="Upkeep cost"
              value={stats.totalUpkeepText}
              tone="text-danger"
              note="Daily inventory/ops"
            />
          </>
        )}

        {type === 'ski_patrol' && (
          <>
            <Figure
              label="Safety score"
              value={`${domain.safetyScore} / 100`}
              tone={domain.safetyScore >= 80 ? 'text-success' : domain.safetyScore >= 50 ? 'text-warning' : 'text-danger'}
              note={`Risk level: ${domain.riskLevel}`}
            />
            <Figure
              label="Slope coverage"
              value={`${domain.coverageRatio}%`}
              tone={domain.coverageRatio >= 100 ? 'text-success' : 'text-warning'}
              note={`${stats.totalCapacity} / ${domain.slopeCount} slopes`}
            />
            <Figure
              label="Patrol on duty"
              value={domain.patrolCount}
              note="Field responders"
            />
            <Figure
              label="Medics standby"
              value={domain.medicCount}
              note="Clinic personnel"
            />
            <Figure
              label="Daily upkeep"
              value={stats.totalUpkeepText}
              tone="text-danger"
              note={`${stats.openCount} active stations`}
            />
          </>
        )}

        {type === 'transportation' && (
          <>
            <Figure
              label="Active routes"
              value={`${stats.openCount} / ${stats.totalCount}`}
              note="Transit lines"
            />
            <Figure
              label="Daily riders"
              value={stats.totalCapacity.toLocaleString('en-US')}
              note="System passenger cap"
            />
            <Figure
              label="Fare revenue"
              value={stats.totalRevenueText}
              tone="text-success"
              note="Ticket receipts"
            />
            <Figure
              label="Operating cost"
              value={stats.totalUpkeepText}
              tone="text-danger"
              note="Fuel & vehicle ops"
            />
            <Figure
              label="Network score"
              value={`${domain.networkScore} / 100`}
              tone="text-accent"
              note="Connectivity rating"
            />
          </>
        )}
      </div>

      {/* Critical Status Alerts */}
      {type === 'hotel' && domain.receptionists < stats.openCount && (
        <div className="rounded-xl border border-warning/40 bg-warning/10 p-4 text-sm flex items-start gap-3">
          <Icon name="fa-bell-concierge" className="text-warning mt-0.5 text-base shrink-0" />
          <div>
            <div className="font-semibold text-foreground">Understaffed reception desks</div>
            <div className="text-muted">
              Some hotels lack a receptionist.{' '}
              <a href="/staff/hire" className="text-link underline hover:no-underline font-medium">
                Hire receptionists
              </a>{' '}
              to improve check-in speed, guest satisfaction, and overnight revenue.
            </div>
          </div>
        </div>
      )}

      {type === 'ski_patrol' && domain.coverageRatio < 100 && (
        <div className="rounded-xl border border-warning/40 bg-warning/10 p-4 text-sm flex items-start gap-3">
          <Icon name="fa-triangle-exclamation" className="text-warning mt-0.5 text-base shrink-0" />
          <div>
            <div className="font-semibold text-foreground">Insufficient slope coverage</div>
            <div className="text-muted">
              {domain.uncoveredSlopes} slope{domain.uncoveredSlopes !== 1 ? 's have' : ' has'} no patrol coverage.
              Accidents on unpatrolled terrain lead to reputation penalties and liability costs.
            </div>
          </div>
        </div>
      )}

      {type === 'ski_patrol' && domain.patrolCount === 0 && stats.totalCount > 0 && (
        <div className="rounded-xl border border-danger/40 bg-danger/10 p-4 text-sm flex items-start gap-3">
          <Icon name="fa-user-shield" className="text-danger mt-0.5 text-base shrink-0" />
          <div>
            <div className="font-semibold text-foreground">Stations built without ski patrol staff</div>
            <div className="text-muted">
              Your patrol stations are unmanned.{' '}
              <a href="/staff/hire" className="text-link underline hover:no-underline font-medium">
                Hire ski patrol
              </a>{' '}
              immediately to respond to mountain emergencies.
            </div>
          </div>
        </div>
      )}

      {/* Restaurant Meal Periods Bar */}
      {type === 'restaurant' && domain.mealPeriods && (
        <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
          {domain.mealPeriods.map((period: any) => (
            <div
              key={period.name}
              className="rounded-xl border border-border bg-surface p-3 text-center flex flex-col items-center gap-1"
            >
              <Icon name={period.icon} className="text-accent text-base" />
              <div className="font-semibold text-xs text-foreground">{period.name}</div>
              <div className="text-[11px] text-muted">{period.time}</div>
            </div>
          ))}
        </div>
      )}

      {/* 2-Column Layout */}
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {/* Left column: Building instances */}
        <div className="lg:col-span-2 space-y-4">
          <h2 className="text-lg font-bold text-foreground flex items-center gap-2">
            <Icon name={def.icon} className="text-accent" />
            Your {def.label.toLowerCase()}
          </h2>

          {buildings.length === 0 ? (
            <Panel>
              <Empty icon={def.icon} title={`No ${def.label.toLowerCase()} built yet`}>
                Construct your first {def.singular.toLowerCase()} from the catalog on the right to expand your resort.
              </Empty>
            </Panel>
          ) : (
            <div className="space-y-3">
              {buildings.map((b) => {
                const isOpen = b.status === 'open';
                return (
                  <div
                    key={b.id}
                    className={`rounded-xl border border-border bg-surface p-4 flex flex-col gap-3 transition-opacity ${
                      !isOpen ? 'opacity-70' : ''
                    }`}
                  >
                    <div className="flex items-start justify-between gap-3">
                      <div className="flex items-start gap-3 min-w-0">
                        <div
                          className={`flex size-12 shrink-0 items-center justify-center rounded-xl ${
                            isOpen ? 'bg-accent/15 text-accent' : 'bg-surface-secondary text-muted'
                          }`}
                        >
                          <Icon name={def.icon} className="text-xl" />
                        </div>
                        <div className="min-w-0">
                          <div className="flex flex-wrap items-center gap-2">
                            <span className="font-semibold text-foreground text-sm truncate">{b.name}</span>
                            <Chip
                              size="sm"
                              variant="soft"
                              color={isOpen ? 'success' : 'default'}
                            >
                              {isOpen ? 'Open' : 'Closed'}
                            </Chip>
                            <Chip size="sm" variant="secondary">
                              Level {b.level}
                            </Chip>
                          </div>

                          {/* Domain-specific visual cues */}
                          {type === 'hotel' && (
                            <div className="flex items-center gap-1 mt-1 text-xs text-muted">
                              {Array.from({ length: 3 }).map((_, i) => (
                                <Icon
                                  key={i}
                                  name="fa-star"
                                  className={i < b.level ? 'text-warning text-xs' : 'text-muted/40 text-xs'}
                                />
                              ))}
                              <span className="ml-1 text-[11px]">
                                {b.level === 1 ? 'Budget Lodge' : b.level === 2 ? 'Comfort Hotel' : 'Luxury Resort'}
                              </span>
                            </div>
                          )}

                          {type === 'rental' && domain.gearLevels?.[b.level] && (
                            <div className="flex flex-wrap gap-1 mt-1.5">
                              {domain.gearLevels[b.level].items.map((item: any) => (
                                <span
                                  key={item.name}
                                  className="inline-flex items-center gap-1 rounded-full bg-surface-secondary px-2 py-0.5 text-[11px] text-muted border border-border"
                                >
                                  <Icon name={item.icon} className="text-accent text-[10px]" />
                                  {item.name}
                                </span>
                              ))}
                            </div>
                          )}

                          {type === 'restaurant' && domain.cuisines?.[b.level] && (
                            <div className="flex flex-wrap gap-1 mt-1.5">
                              <span className="inline-flex items-center gap-1 rounded-full bg-surface-secondary px-2 py-0.5 text-[11px] font-medium text-foreground border border-border">
                                <Icon name={domain.cuisines[b.level].icon} className="text-accent text-[10px]" />
                                {domain.cuisines[b.level].name}
                              </span>
                              {domain.cuisines[b.level].menu.slice(0, 3).map((dish: string) => (
                                <span
                                  key={dish}
                                  className="inline-flex items-center rounded-full bg-surface-secondary px-2 py-0.5 text-[10px] text-muted border border-border"
                                >
                                  {dish}
                                </span>
                              ))}
                            </div>
                          )}

                          {type === 'retail' && domain.merchLevels?.[b.level] && (
                            <div className="flex flex-wrap gap-1 mt-1.5">
                              <span className="inline-flex items-center gap-1 rounded-full bg-surface-secondary px-2 py-0.5 text-[11px] font-medium text-foreground border border-border">
                                {domain.merchLevels[b.level].name}
                              </span>
                              {domain.merchLevels[b.level].products.map((p: any) => (
                                <span
                                  key={p.name}
                                  className="inline-flex items-center gap-1 rounded-full bg-surface-secondary px-2 py-0.5 text-[10px] text-muted border border-border"
                                >
                                  <Icon name={p.icon} className="text-[9px]" />
                                  {p.name}
                                </span>
                              ))}
                            </div>
                          )}

                          {type === 'ski_patrol' && domain.stationTypes?.[b.level] && (
                            <p className="text-xs text-muted mt-1 leading-relaxed">
                              {domain.stationTypes[b.level].desc}
                            </p>
                          )}

                          {type === 'transportation' && domain.routes?.[b.level] && (
                            <div className="flex flex-wrap items-center gap-3 text-xs text-muted mt-1">
                              <span>
                                <Icon name="fa-clock" className="mr-1 text-[10px]" />
                                {domain.routes[b.level].freq}
                              </span>
                              <span>
                                <Icon name="fa-location-dot" className="mr-1 text-[10px]" />
                                {domain.routes[b.level].range}
                              </span>
                            </div>
                          )}
                        </div>
                      </div>

                      {/* Card Action Buttons */}
                      <div className="flex items-center gap-1.5 shrink-0">
                        <PostButton
                          action={`/buildings/toggle/${b.id}`}
                          variant={isOpen ? 'ghost' : 'secondary'}
                          size="sm"
                        >
                          <Icon name="fa-power-off" className={isOpen ? 'text-warning' : ''} />
                          <span className="sr-only">{isOpen ? 'Close' : 'Open'}</span>
                        </PostButton>

                        {b.nextLevel && (
                          <PostButton
                            action={`/buildings/upgrade/${b.id}`}
                            variant="secondary"
                            size="sm"
                            confirm={{
                              title: `Upgrade ${b.name}`,
                              body: `Upgrade to ${b.nextLevel.name} for ${b.nextLevel.costText}?`,
                              confirmLabel: 'Upgrade',
                            }}
                          >
                            <Icon name="fa-arrow-up" className="mr-1" />
                            <span className="text-xs">Upgrade</span>
                          </PostButton>
                        )}

                        <PostButton
                          action={`/buildings/sell/${b.id}`}
                          variant="ghost"
                          size="sm"
                          confirm={{
                            title: `Demolish ${b.name}`,
                            body: `Demolish ${b.name}? You will receive ${b.refundText} salvage refund (25%).`,
                            confirmLabel: 'Demolish',
                          }}
                        >
                          <Icon name="fa-trash" className="text-danger" />
                          <span className="sr-only">Demolish</span>
                        </PostButton>
                      </div>
                    </div>

                    {/* 4-Stat Grid */}
                    <div className="grid grid-cols-4 gap-2 text-center text-xs pt-2 border-t border-border">
                      <div className="rounded-lg bg-surface-secondary p-1.5 border border-border">
                        <div className="font-semibold tabular-nums text-foreground">{b.capacity}</div>
                        <div className="text-muted text-[10px]">{capacityLabel}</div>
                      </div>
                      <div className="rounded-lg bg-surface-secondary p-1.5 border border-border">
                        <div className="font-semibold tabular-nums text-success">{b.revenueText}</div>
                        <div className="text-muted text-[10px]">Revenue / day</div>
                      </div>
                      <div className="rounded-lg bg-surface-secondary p-1.5 border border-border">
                        <div className="font-semibold tabular-nums text-danger">{b.upkeepText}</div>
                        <div className="text-muted text-[10px]">Upkeep / day</div>
                      </div>
                      <div className="rounded-lg bg-surface-secondary p-1.5 border border-border">
                        <div
                          className={`font-semibold tabular-nums ${
                            b.condition_pct >= 60
                              ? 'text-success'
                              : b.condition_pct >= 30
                              ? 'text-warning'
                              : 'text-danger'
                          }`}
                        >
                          {b.condition_pct}%
                        </div>
                        <div className="text-muted text-[10px]">Condition</div>
                      </div>
                    </div>

                    {/* Condition Meter */}
                    <div className="space-y-1">
                      <Meter
                        value={b.condition_pct}
                        color={
                          b.condition_pct >= 60 ? 'success' : b.condition_pct >= 30 ? 'warning' : 'danger'
                        }
                        aria-label={`${b.name} condition`}
                      />
                    </div>
                  </div>
                );
              })}
            </div>
          )}

          {/* Ski Patrol Incident Log */}
          {type === 'ski_patrol' && domain.recentIncidents && domain.recentIncidents.length > 0 && (
            <Panel
              title={
                <span className="flex items-center gap-2">
                  <Icon name="fa-clipboard-list" className="text-accent" />
                  Recent incidents
                </span>
              }
            >
              <div className="overflow-x-auto -mx-4 -mb-4 px-4 pb-4">
                <table className="w-full text-left text-xs">
                  <thead className="border-b border-border text-muted">
                    <tr>
                      <th className="py-2 pr-3 font-medium">Time</th>
                      <th className="py-2 px-3 font-medium">Category</th>
                      <th className="py-2 pl-3 font-medium">Details</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-border">
                    {domain.recentIncidents.map((inc: any) => (
                      <tr key={inc.id} className="hover:bg-surface-secondary/50">
                        <td className="py-2.5 pr-3 text-muted tabular-nums whitespace-nowrap">{inc.timeAgo}</td>
                        <td className="py-2.5 px-3">
                          <Chip size="sm" variant="soft">
                            {inc.category}
                          </Chip>
                        </td>
                        <td className="py-2.5 pl-3 text-foreground">{inc.details}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </Panel>
          )}
        </div>

        {/* Right column: Construct Catalog & Support Panels */}
        <div className="space-y-4">
          <Panel
            title={
              <span className="flex items-center gap-2">
                <Icon name="fa-plus" className="text-accent" />
                Construct {def.singular.toLowerCase()}
              </span>
            }
          >
            <div className="space-y-3">
              {catalog.map((tier) => (
                <div
                  key={tier.level}
                  className="rounded-xl border border-border bg-surface-secondary p-3 flex flex-col gap-2.5"
                >
                  <div className="flex items-center justify-between gap-2">
                    <span className="font-semibold text-sm text-foreground">{tier.name}</span>
                    <Chip size="sm" variant="soft">
                      Level {tier.level}
                    </Chip>
                  </div>

                  <div className="grid grid-cols-3 gap-1.5 text-center text-xs">
                    <div className="rounded-lg bg-surface p-1.5 border border-border">
                      <div className="font-semibold tabular-nums">{tier.capacity}</div>
                      <div className="text-muted text-[10px]">{capacityLabel}</div>
                    </div>
                    <div className="rounded-lg bg-surface p-1.5 border border-border">
                      <div className="font-semibold tabular-nums text-success">{tier.revenueText}</div>
                      <div className="text-muted text-[10px]">Rev / day</div>
                    </div>
                    <div className="rounded-lg bg-surface p-1.5 border border-border">
                      <div className="font-semibold tabular-nums text-danger">{tier.upkeepText}</div>
                      <div className="text-muted text-[10px]">Upkeep / day</div>
                    </div>
                  </div>

                  <PostButton
                    action="/buildings/build"
                    data={{ type, level: tier.level }}
                    variant="primary"
                    size="sm"
                    className="w-full mt-1"
                  >
                    <Icon name="fa-plus" className="mr-1" />
                    Construct for {tier.costText}
                  </PostButton>
                </div>
              ))}
            </div>
          </Panel>

          {/* Ski Patrol Safety Team */}
          {type === 'ski_patrol' && (
            <Panel
              title={
                <span className="flex items-center gap-2">
                  <Icon name="fa-user-shield" className="text-accent" />
                  Safety team
                </span>
              }
              actions={
                <a
                  href="/staff/hire"
                  className="text-xs text-link flex items-center gap-1 hover:underline pointer-coarse:min-h-11 pointer-coarse:flex pointer-coarse:items-center"
                >
                  Hire staff
                  <Icon name="fa-arrow-right" className="text-[10px]" />
                </a>
              }
            >
              <div className="space-y-3">
                <div className="rounded-xl bg-surface-secondary p-3 border border-border">
                  <div className="flex items-center justify-between">
                    <div>
                      <div className="font-semibold text-xs text-foreground">Ski patrol</div>
                      <div className="text-[11px] text-muted">{domain.patrolCount} on duty</div>
                    </div>
                    <a
                      href="/staff/hire"
                      className="inline-flex min-h-8 items-center rounded-lg border border-border px-2.5 text-xs font-medium hover:bg-default"
                    >
                      Hire
                    </a>
                  </div>
                  {domain.patrolStaff && domain.patrolStaff.length > 0 && (
                    <div className="flex flex-wrap gap-1 mt-2">
                      {domain.patrolStaff.map((p: any) => (
                        <span
                          key={p.name}
                          className="inline-flex items-center rounded-full bg-surface px-2 py-0.5 text-[10px] text-muted border border-border"
                        >
                          {p.name} Lv.{p.level}
                        </span>
                      ))}
                    </div>
                  )}
                </div>

                <div className="rounded-xl bg-surface-secondary p-3 border border-border">
                  <div className="flex items-center justify-between">
                    <div>
                      <div className="font-semibold text-xs text-foreground">Medics</div>
                      <div className="text-[11px] text-muted">{domain.medicCount} on standby</div>
                    </div>
                    <a
                      href="/staff/hire"
                      className="inline-flex min-h-8 items-center rounded-lg border border-border px-2.5 text-xs font-medium hover:bg-default"
                    >
                      Hire
                    </a>
                  </div>
                  {domain.medics && domain.medics.length > 0 && (
                    <div className="flex flex-wrap gap-1 mt-2">
                      {domain.medics.map((m: any) => (
                        <span
                          key={m.name}
                          className="inline-flex items-center rounded-full bg-surface px-2 py-0.5 text-[10px] text-muted border border-border"
                        >
                          {m.name} Lv.{m.level}
                        </span>
                      ))}
                    </div>
                  )}
                </div>
              </div>
            </Panel>
          )}

          {/* Operational Tips & Synergy Guide */}
          <Panel
            title={
              <span className="flex items-center gap-2">
                <Icon name="fa-lightbulb" className="text-accent" />
                Operational notes
              </span>
            }
          >
            <ul className="text-xs text-muted space-y-2">
              {type === 'hotel' && (
                <>
                  <li className="flex items-start gap-2">
                    <Icon name="fa-bell-concierge" className="text-accent text-[11px] mt-0.5" />
                    <span>Receptionists speed up check-in and lift occupancy ratings</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <Icon name="fa-utensils" className="text-accent text-[11px] mt-0.5" />
                    <span>Nearby restaurants boost overnight hotel bookings</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <Icon name="fa-bus" className="text-accent text-[11px] mt-0.5" />
                    <span>Transit lines draw travelers from airport hubs and towns</span>
                  </li>
                </>
              )}
              {type === 'rental' && (
                <>
                  <li className="flex items-start gap-2">
                    <Icon name="fa-person-skiing" className="text-accent text-[11px] mt-0.5" />
                    <span>About 40% of resort visitors rent equipment on site</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <Icon name="fa-arrow-up" className="text-accent text-[11px] mt-0.5" />
                    <span>Higher tier gear commands substantially higher daily rental fees</span>
                  </li>
                </>
              )}
              {type === 'restaurant' && (
                <>
                  <li className="flex items-start gap-2">
                    <Icon name="fa-kitchen-set" className="text-accent text-[11px] mt-0.5" />
                    <span>Experienced chefs directly raise food quality and resort ratings</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <Icon name="fa-clock" className="text-accent text-[11px] mt-0.5" />
                    <span>Dining generates steady revenue across all 4 daily service windows</span>
                  </li>
                </>
              )}
              {type === 'retail' && (
                <>
                  <li className="flex items-start gap-2">
                    <Icon name="fa-store" className="text-accent text-[11px] mt-0.5" />
                    <span>Retail operates at high profit margins with minimal maintenance</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <Icon name="fa-gem" className="text-accent text-[11px] mt-0.5" />
                    <span>Luxury boutiques capture high-spending guest demographics</span>
                  </li>
                </>
              )}
              {type === 'ski_patrol' && (
                <>
                  <li className="flex items-start gap-2">
                    <Icon name="fa-mountain" className="text-accent text-[11px] mt-0.5" />
                    <span>Every open slope requires active patrol station coverage</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <Icon name="fa-file-shield" className="text-accent text-[11px] mt-0.5" />
                    <span>High safety scores reduce accident risk and regulatory fines</span>
                  </li>
                </>
              )}
              {type === 'transportation' && (
                <>
                  <li className="flex items-start gap-2">
                    <Icon name="fa-van-shuttle" className="text-accent text-[11px] mt-0.5" />
                    <span>Town shuttles serve local lodging and beginner skier access</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <Icon name="fa-cable-car" className="text-accent text-[11px] mt-0.5" />
                    <span>High-capacity transit links ease parking lot pressure</span>
                  </li>
                </>
              )}
            </ul>
          </Panel>
        </div>
      </div>
    </Page>
  );
}
