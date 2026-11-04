import { Meter } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Figure, Page, Panel, PostButton } from '@/components/Page';

export type GreenUpgrade = {
  name: string;
  icon: string;
  desc: string;
  field: string;
  boost: number;
  cost: number;
  costText: string;
};

export type EnvironmentPageProps = {
  env: {
    eco_score: number;
    renewable_pct: number;
    waste_management: number;
    wildlife_impact: number;
  };
  carbon: {
    total: number;
    fromCannons: number;
    fromLights: number;
    fromBuildings: number;
  };
  upgrades: GreenUpgrade[];
};

export default function EnvironmentPage({ env, carbon, upgrades }: EnvironmentPageProps) {
  const scoreTone =
    env.eco_score > 70 ? 'text-success' : env.eco_score > 40 ? 'text-warning' : 'text-danger';

  const meterColor =
    env.eco_score > 70 ? 'success' : env.eco_score > 40 ? 'warning' : 'danger';

  return (
    <Page
      title="Environment"
      icon="fa-leaf"
      iconTone="text-success"
      subtitle="Manage your resort's environmental footprint and sustainability"
      width="max-w-5xl"
    >
      {/* Eco score featured panel */}
      <Panel className="text-center sm:py-6">
        <div className="text-xs uppercase tracking-wider text-muted font-medium">Eco Score</div>
        <div className={`mt-1 text-5xl font-bold tabular-nums ${scoreTone}`}>
          {env.eco_score} <span className="text-2xl font-normal text-muted">/ 100</span>
        </div>
        <div className="mx-auto mt-4 max-w-xs">
          <Meter
            value={env.eco_score}
            maxValue={100}
            color={meterColor}
            aria-label="Resort eco score"
          />
        </div>
        <p className="mt-3 text-xs text-muted max-w-md mx-auto">
          {env.eco_score > 70
            ? 'Great! Eco-friendly resorts attract more visitors and qualify for daily Génépis rewards.'
            : env.eco_score > 40
            ? 'Average environmental standing. Invest in green upgrades to counteract heavy carbon emissions.'
            : 'Critical rating. High emissions expose the resort to government audits and visitor boycotts.'}
        </p>
      </Panel>

      {/* 4 Summary figures */}
      <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <Figure
          label="Carbon output"
          value={carbon.total}
          tone="text-danger"
          note="Gross emissions"
        />
        <Figure
          label="Renewable energy"
          value={`${env.renewable_pct}%`}
          tone="text-success"
          note="Clean power mix"
        />
        <Figure
          label="Waste management"
          value={env.waste_management}
          tone="text-accent"
          note="Mitigation index"
        />
        <Figure
          label="Wildlife score"
          value={env.wildlife_impact}
          tone="text-warning"
          note="Habitat preservation"
        />
      </div>

      {/* Carbon sources & Green upgrades */}
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {/* Carbon sources breakdown */}
        <Panel
          title={
            <div className="flex items-center gap-2">
              <Icon name="fa-smog" className="text-danger" />
              <span>Carbon emissions by source</span>
            </div>
          }
        >
          <div className="space-y-4">
            <div>
              <div className="flex justify-between text-xs mb-1.5">
                <span className="font-medium">Snowmaking cannons</span>
                <span className="tabular-nums font-mono text-muted">{carbon.fromCannons}</span>
              </div>
              <Meter
                value={Math.min(100, carbon.fromCannons)}
                maxValue={100}
                color="danger"
                aria-label="Cannons carbon"
              />
            </div>

            <div>
              <div className="flex justify-between text-xs mb-1.5">
                <span className="font-medium">Night skiing floodlights</span>
                <span className="tabular-nums font-mono text-muted">{carbon.fromLights}</span>
              </div>
              <Meter
                value={Math.min(100, carbon.fromLights)}
                maxValue={100}
                color="warning"
                aria-label="Night skiing lights carbon"
              />
            </div>

            <div>
              <div className="flex justify-between text-xs mb-1.5">
                <span className="font-medium">Resort buildings</span>
                <span className="tabular-nums font-mono text-muted">{carbon.fromBuildings}</span>
              </div>
              <Meter
                value={Math.min(100, carbon.fromBuildings)}
                maxValue={100}
                color="accent"
                aria-label="Buildings carbon"
              />
            </div>

            <div className="rounded-lg border border-border bg-surface-secondary/40 p-3 text-xs text-muted">
              <p>
                Each active snow gun adds 15 carbon, night light pole adds 10 carbon, and commercial building adds 5 carbon.
              </p>
            </div>
          </div>
        </Panel>

        {/* Green upgrades */}
        <Panel
          title={
            <div className="flex items-center gap-2">
              <Icon name="fa-seedling" className="text-success" />
              <span>Green infrastructure upgrades</span>
            </div>
          }
        >
          <div className="space-y-3">
            {upgrades.map((up) => {
              const currentVal =
                up.field === 'renewable_pct'
                  ? env.renewable_pct
                  : up.field === 'waste_management'
                  ? env.waste_management
                  : env.wildlife_impact;
              const isMaxed = currentVal >= 100;

              return (
                <div
                  key={up.name}
                  className="flex flex-col gap-3 rounded-lg border border-border bg-surface-secondary/40 p-3 sm:flex-row sm:items-center sm:justify-between"
                >
                  <div className="flex items-center gap-3">
                    <div className="flex size-10 shrink-0 items-center justify-center rounded-lg border border-success/30 bg-success/10 text-success">
                      <Icon name={up.icon || 'fa-seedling'} className="text-lg" />
                    </div>
                    <div>
                      <div className="font-semibold text-sm">{up.name}</div>
                      <div className="text-xs text-muted">{up.desc}</div>
                    </div>
                  </div>

                  <div className="flex items-center justify-between gap-3 sm:justify-end">
                    <span className="tabular-nums font-semibold text-sm text-foreground">
                      {up.costText}
                    </span>
                    <PostButton
                      action="/environment/upgrade"
                      data={{ name: up.name, field: up.field, boost: up.boost }}
                      variant="primary"
                      size="sm"
                      disabled={isMaxed}
                      confirm={{
                        title: `Purchase ${up.name}`,
                        body: `Invest ${up.costText} in ${up.name} (${up.desc})?`,
                        confirmLabel: `Buy for ${up.costText}`,
                      }}
                    >
                      {isMaxed ? 'Maxed' : 'Purchase'}
                    </PostButton>
                  </div>
                </div>
              );
            })}
          </div>
        </Panel>
      </div>
    </Page>
  );
}
