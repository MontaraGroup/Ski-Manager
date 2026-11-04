import { Chip } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Figure, Page, Panel, PostButton } from '@/components/Page';

export type InsurancePolicy = {
  id: number;
  name: string;
  policy_type: string;
  premium_per_day: number;
  premiumText: string;
  coverage_amount: number;
  coverageText: string;
  active: boolean;
  icon: string;
};

export type InsurancePageProps = {
  stats: {
    activeCount: number;
    totalCount: number;
    totalPremiumText: string;
    totalCoverageText: string;
  };
  policies: InsurancePolicy[];
};

export default function InsurancePage({ stats, policies }: InsurancePageProps) {
  return (
    <Page
      title="Insurance"
      icon="fa-shield-halved"
      iconTone="text-accent"
      subtitle="Protect your resort from unforeseen risks and liabilities"
      width="max-w-5xl"
    >
      {/* Summary stats */}
      <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <Figure
          label="Active policies"
          value={`${stats.activeCount} / ${stats.totalCount}`}
          note={stats.activeCount === stats.totalCount ? 'Full coverage' : 'Partial coverage'}
        />
        <Figure
          label="Daily premiums"
          value={stats.totalPremiumText}
          tone="text-warning"
          note="Deducted each nightly tick"
        />
        <Figure
          label="Total coverage"
          value={stats.totalCoverageText}
          tone="text-success"
          note="Maximum claim payout pool"
        />
      </div>

      {/* Policies list */}
      <Panel title="Available policies">
        <div className="space-y-3">
          {policies.map((p) => (
            <div
              key={p.id}
              className="flex flex-col gap-3 rounded-lg border border-border bg-surface-secondary/40 p-4 sm:flex-row sm:items-center sm:justify-between"
            >
              <div className="flex items-center gap-3">
                <div className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-surface border border-border">
                  <Icon
                    name={p.icon || 'fa-shield'}
                    className={`text-lg ${p.active ? 'text-accent' : 'text-muted'}`}
                  />
                </div>
                <div>
                  <div className="flex items-center gap-2">
                    <span className="font-semibold text-sm">{p.name}</span>
                    <Chip size="sm" variant="soft" color={p.active ? 'success' : 'default'}>
                      {p.active ? 'Active' : 'Inactive'}
                    </Chip>
                  </div>
                  <div className="mt-0.5 text-xs text-muted">
                    Premium: <span className="tabular-nums font-medium text-foreground">{p.premiumText}</span>/day · Coverage: <span className="tabular-nums font-medium text-foreground">{p.coverageText}</span>
                  </div>
                </div>
              </div>

              <div className="flex items-center gap-2 self-end sm:self-center">
                <PostButton
                  action={`/insurance/toggle/${p.id}`}
                  variant={p.active ? 'tertiary' : 'primary'}
                  size="sm"
                >
                  <Icon name={p.active ? 'fa-pause' : 'fa-play'} className="mr-1 text-xs" />
                  {p.active ? 'Cancel policy' : 'Activate'}
                </PostButton>
              </div>
            </div>
          ))}
        </div>
      </Panel>

      {/* Info card */}
      <Panel title="How insurance works">
        <div className="grid grid-cols-1 gap-3 text-xs text-muted sm:grid-cols-2">
          <div className="space-y-1.5">
            <div className="flex items-center gap-2">
              <Icon name="fa-shield" className="text-accent" />
              <span>Policies protect against guest injuries, lift breakdowns, and natural events.</span>
            </div>
            <div className="flex items-center gap-2">
              <Icon name="fa-coins" className="text-warning" />
              <span>Premiums are deducted automatically during the nightly game tick.</span>
            </div>
          </div>
          <div className="space-y-1.5">
            <div className="flex items-center gap-2">
              <Icon name="fa-circle-check" className="text-success" />
              <span>Coverage amounts are capped per incident up to the policy limit.</span>
            </div>
            <div className="flex items-center gap-2">
              <Icon name="fa-sliders" className="text-muted" />
              <span>You can activate or cancel policies at any time without extra fees.</span>
            </div>
          </div>
        </div>
      </Panel>
    </Page>
  );
}
