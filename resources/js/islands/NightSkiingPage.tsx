import { Chip, Meter } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Empty, Figure, Page, Panel, PostButton } from '@/components/Page';

export type LightItem = {
  id: number;
  name: string;
  light_type: string;
  coverage: number;
  energy_cost: number;
  energyCostText: string;
  status: 'active' | 'off' | string;
  condition_pct: number;
  repairCostText: string;
  refundText: string;
};

export type LightTypeItem = {
  key: string;
  name: string;
  icon: string;
  desc: string;
  coverage: number;
  energy: number;
  energyText: string;
  cost: number;
  costText: string;
};

export type NightSkiingPageProps = {
  stats: {
    totalCoverage: number;
    extraRevenue: number;
    nightHours: number;
    activeCount: number;
    totalCount: number;
    mechanics: number;
    totalEnergyText: string;
  };
  lights: LightItem[];
  lightTypes: LightTypeItem[];
};

export default function NightSkiingPage({ stats, lights, lightTypes }: NightSkiingPageProps) {
  return (
    <Page
      title="Night skiing"
      icon="fa-moon"
      iconTone="text-accent"
      subtitle="Extend operating hours with lighting systems to boost visitors and revenue"
      width="max-w-6xl"
      actions={
        stats.totalCount > 0 ? (
          <div className="flex items-center gap-2">
            <PostButton action="/night-skiing/toggle-all" data={{ action: 'on' }} variant="secondary" size="sm">
              <Icon name="fa-power-off" className="mr-1 text-warning" />
              All on
            </PostButton>
            <PostButton action="/night-skiing/toggle-all" data={{ action: 'off' }} variant="ghost" size="sm">
              <Icon name="fa-power-off" className="mr-1" />
              All off
            </PostButton>
          </div>
        ) : null
      }
    >
      {/* 4 Summary figures */}
      <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
        <Figure
          label="Slope coverage"
          value={`${stats.totalCoverage}%`}
          tone={stats.totalCoverage >= 100 ? 'text-success' : stats.totalCoverage >= 50 ? 'text-foreground' : 'text-warning'}
          note={stats.totalCoverage >= 100 ? 'Full coverage' : `${Math.max(0, 100 - stats.totalCoverage)}% unlit`}
        />
        <Figure
          label="Revenue boost"
          value={`+${stats.extraRevenue}%`}
          tone="text-success"
          note={`${stats.nightHours}h extra per day`}
        />
        <Figure
          label="Lights active"
          value={`${stats.activeCount} / ${stats.totalCount}`}
          note={`${stats.mechanics} mechanic${stats.mechanics !== 1 ? 's' : ''} available`}
        />
        <Figure
          label="Daily energy cost"
          value={stats.totalEnergyText}
          tone="text-danger"
          note="From active lights"
        />
      </div>

      {/* Coverage Status Alerts */}
      {stats.totalCoverage < 50 && stats.totalCount > 0 && (
        <div className="rounded-xl border border-warning/40 bg-warning/10 p-4 text-sm flex items-start gap-3">
          <Icon name="fa-triangle-exclamation" className="text-warning mt-0.5 text-base shrink-0" />
          <div>
            <div className="font-semibold text-foreground">Low coverage</div>
            <div className="text-muted">Coverage below 50% limits night skiing. Install more lights to attract evening visitors.</div>
          </div>
        </div>
      )}
      {stats.totalCoverage >= 100 && (
        <div className="rounded-xl border border-success/40 bg-success/10 p-4 text-sm flex items-start gap-3">
          <Icon name="fa-circle-check" className="text-success mt-0.5 text-base shrink-0" />
          <div>
            <div className="font-semibold text-foreground">Full coverage</div>
            <div className="text-muted">Your slopes are fully lit for maximum night skiing revenue.</div>
          </div>
        </div>
      )}

      {/* 2-Column Layout */}
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {/* Left column: Installed Lights */}
        <div className="lg:col-span-2 space-y-3">
          <h2 className="text-lg font-bold text-foreground flex items-center gap-2">
            <Icon name="fa-lightbulb" className="text-accent" />
            Installed lights
          </h2>

          {lights.length === 0 ? (
            <Panel>
              <Empty icon="fa-moon" title="No lights installed">
                Install your first light from the shop to start night skiing operations.
              </Empty>
            </Panel>
          ) : (
            <div className="space-y-3">
              {lights.map((light) => {
                const isOn = light.status === 'active';
                const isBroken = light.condition_pct <= 0;
                return (
                  <div key={light.id} className="rounded-xl border border-border bg-surface p-4 flex flex-col gap-3">
                    <div className="flex items-start justify-between gap-3">
                      <div className="flex items-center gap-3 min-w-0">
                        <div
                          className={`flex size-10 shrink-0 items-center justify-center rounded-lg ${
                            isOn ? 'bg-warning/15 text-warning' : 'bg-surface-secondary text-muted'
                          }`}
                        >
                          <Icon name="fa-lightbulb" className="text-lg" />
                        </div>
                        <div className="min-w-0">
                          <div className="flex items-center gap-2">
                            <span className="font-semibold truncate">{light.name}</span>
                            <Chip
                              size="sm"
                              variant="soft"
                              color={isBroken ? 'danger' : isOn ? 'warning' : 'default'}
                            >
                              {isBroken ? 'Broken' : isOn ? 'On' : 'Off'}
                            </Chip>
                          </div>
                          <div className="flex flex-wrap items-center gap-3 text-xs text-muted mt-1">
                            <span>
                              <Icon name="fa-signal" className="mr-1" />
                              {light.coverage}% coverage
                            </span>
                            <span>
                              <Icon name="fa-bolt" className="mr-1" />
                              {light.energyCostText} / day
                            </span>
                          </div>
                        </div>
                      </div>

                      <div className="flex items-center gap-1.5 shrink-0">
                        <PostButton
                          action={`/night-skiing/toggle/${light.id}`}
                          variant={isOn ? 'ghost' : 'secondary'}
                          size="sm"
                          disabled={isBroken}
                        >
                          <Icon name="fa-power-off" className={isOn ? 'text-warning' : ''} />
                          <span className="sr-only">{isOn ? 'Turn off' : 'Turn on'}</span>
                        </PostButton>
                        {light.condition_pct < 100 && (
                          <PostButton
                            action={`/night-skiing/repair/${light.id}`}
                            variant="secondary"
                            size="sm"
                            confirm={{
                              title: 'Repair light',
                              body: `Repair ${light.name} for ${light.repairCostText}? Condition will be restored to 100%.`,
                              confirmLabel: 'Repair',
                            }}
                          >
                            <Icon name="fa-wrench" className="mr-1" />
                            <span className="text-xs">{light.repairCostText}</span>
                          </PostButton>
                        )}
                        <PostButton
                          action={`/night-skiing/sell/${light.id}`}
                          variant="ghost"
                          size="sm"
                          confirm={{
                            title: 'Remove light',
                            body: `Remove ${light.name}? You will receive ${light.refundText} salvage refund (25%).`,
                            confirmLabel: 'Remove',
                          }}
                        >
                          <Icon name="fa-trash" className="text-danger" />
                          <span className="sr-only">Remove</span>
                        </PostButton>
                      </div>
                    </div>

                    {/* Condition Meter */}
                    <div>
                      <div className="flex items-center justify-between text-xs mb-1">
                        <span className="text-muted">Condition</span>
                        <span
                          className={`font-medium tabular-nums ${
                            light.condition_pct >= 60
                              ? 'text-success'
                              : light.condition_pct >= 30
                              ? 'text-warning'
                              : 'text-danger'
                          }`}
                        >
                          {light.condition_pct}%
                        </span>
                      </div>
                      <Meter
                        value={light.condition_pct}
                        color={
                          light.condition_pct >= 60 ? 'success' : light.condition_pct >= 30 ? 'warning' : 'danger'
                        }
                        aria-label={`${light.name} condition`}
                      />
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>

        {/* Right column: Light Shop */}
        <div>
          <Panel title="Light shop" className="space-y-3">
            {lightTypes.map((lt) => (
              <div key={lt.key} className="rounded-xl border border-border bg-surface-secondary p-3 flex flex-col gap-2.5">
                <div className="flex items-center gap-2">
                  <Icon name={lt.icon} className="text-accent text-base" />
                  <span className="font-semibold text-sm">{lt.name}</span>
                </div>
                <p className="text-xs text-muted leading-relaxed">{lt.desc}</p>
                <div className="grid grid-cols-3 gap-1.5 text-center text-xs">
                  <div className="rounded-lg bg-surface p-1.5 border border-border">
                    <div className="font-semibold tabular-nums">{lt.coverage}%</div>
                    <div className="text-muted text-[11px]">Coverage</div>
                  </div>
                  <div className="rounded-lg bg-surface p-1.5 border border-border">
                    <div className="font-semibold tabular-nums">{lt.energyText}</div>
                    <div className="text-muted text-[11px]">Energy</div>
                  </div>
                  <div className="rounded-lg bg-surface p-1.5 border border-border">
                    <div className="font-semibold tabular-nums">{lt.costText}</div>
                    <div className="text-muted text-[11px]">Cost</div>
                  </div>
                </div>
                <PostButton
                  action="/night-skiing/buy"
                  data={{ type: lt.key }}
                  variant="primary"
                  size="sm"
                  className="w-full mt-1"
                >
                  <Icon name="fa-plus" className="mr-1" />
                  Install for {lt.costText}
                </PostButton>
              </div>
            ))}
          </Panel>
        </div>
      </div>
    </Page>
  );
}
