import { useState } from 'react';
import { Button, Chip, Meter } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Empty, Page, Panel, PostButton } from '@/components/Page';

export type CompliancePageProps = {
  tab: 'government' | 'insurance' | 'environment';
  summary: {
    activeInsCount: number;
    totalInsCount: number;
    totalPremiumText: string;
    complianceScore: number;
    compliantCount: number;
    totalRegCount: number;
    ecoScore: number;
  };
  insurance: {
    policies: Array<{
      id: number;
      name: string;
      premium_per_day: number;
      premiumText: string;
      coverage_amount: number;
      coverageText: string;
      active: boolean;
      icon: string;
    }>;
    totalPremiumText: string;
    totalCoverageText: string;
  };
  government: {
    regulations: Array<{
      id: number;
      name: string;
      description: string;
      compliant: boolean;
      compliance_cost: number;
      costText: string;
      icon: string;
    }>;
    inspections: Array<{
      id: number;
      dateText: string;
      message: string;
      passed: boolean;
      fine: number;
      fineText: string | null;
    }>;
  };
  environment: {
    eco_score: number;
    renewable_pct: number;
    waste_management: number;
    wildlife_impact: number;
  };
};

export default function CompliancePage({
  tab,
  summary,
  insurance,
  government,
  environment,
}: CompliancePageProps) {
  const [activeTab, setActiveTab] = useState<'government' | 'insurance' | 'environment'>(
    tab || 'government'
  );

  return (
    <Page
      title="Compliance & Risk"
      icon="fa-scale-balanced"
      iconTone="text-primary"
      subtitle="Insurance policies, government regulations, and environmental management"
      width="max-w-6xl"
    >
      {/* 3 Interactive summary cards acting as tab selectors */}
      <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
        {/* Insurance tab button */}
        <Button
          type="button"
          variant="ghost"
          onPress={() => setActiveTab('insurance')}
          className={`h-auto flex flex-col items-center justify-center rounded-xl border p-4 text-center transition-all ${
            activeTab === 'insurance'
              ? 'border-primary bg-primary/10 shadow-sm ring-1 ring-primary'
              : 'border-border bg-surface hover:bg-surface-secondary/60'
          }`}
        >
          <Icon name="fa-shield-halved" className="text-xl text-success mb-1" />
          <div className="text-lg font-bold tabular-nums">
            {summary.activeInsCount} / {summary.totalInsCount}
          </div>
          <div className="text-xs text-muted font-medium">Insurance</div>
          <div className="text-xs text-success tabular-nums mt-0.5">
            {summary.totalPremiumText}/day
          </div>
        </Button>

        {/* Government tab button */}
        <Button
          type="button"
          variant="ghost"
          onPress={() => setActiveTab('government')}
          className={`h-auto flex flex-col items-center justify-center rounded-xl border p-4 text-center transition-all ${
            activeTab === 'government'
              ? 'border-primary bg-primary/10 shadow-sm ring-1 ring-primary'
              : 'border-border bg-surface hover:bg-surface-secondary/60'
          }`}
        >
          <Icon name="fa-building-columns" className="text-xl text-warning mb-1" />
          <div
            className={`text-lg font-bold tabular-nums ${
              summary.complianceScore >= 80
                ? 'text-success'
                : summary.complianceScore >= 50
                ? 'text-warning'
                : 'text-danger'
            }`}
          >
            {summary.complianceScore}%
          </div>
          <div className="text-xs text-muted font-medium">Government</div>
          <div className="text-xs text-muted tabular-nums mt-0.5">
            {summary.compliantCount} / {summary.totalRegCount} compliant
          </div>
        </Button>

        {/* Environment tab button */}
        <Button
          type="button"
          variant="ghost"
          onPress={() => setActiveTab('environment')}
          className={`h-auto flex flex-col items-center justify-center rounded-xl border p-4 text-center transition-all ${
            activeTab === 'environment'
              ? 'border-primary bg-primary/10 shadow-sm ring-1 ring-primary'
              : 'border-border bg-surface hover:bg-surface-secondary/60'
          }`}
        >
          <Icon name="fa-leaf" className="text-xl text-success mb-1" />
          <div
            className={`text-lg font-bold tabular-nums ${
              summary.ecoScore >= 70
                ? 'text-success'
                : summary.ecoScore >= 40
                ? 'text-warning'
                : 'text-danger'
            }`}
          >
            {summary.ecoScore}
          </div>
          <div className="text-xs text-muted font-medium">Eco Score</div>
          <div className="text-xs text-muted mt-0.5">Environmental impact</div>
        </Button>
      </div>

      {/* Tab 1: Insurance */}
      {activeTab === 'insurance' && (
        <Panel
          title={
            <div className="flex items-center gap-2">
              <Icon name="fa-shield-halved" className="text-success" />
              <span>Insurance policies</span>
            </div>
          }
          actions={
            <a
              href="/insurance"
              className="inline-flex items-center gap-1.5 rounded-lg border border-border px-3 py-1.5 text-xs font-medium text-foreground hover:bg-default"
            >
              <span>Full insurance page</span>
              <Icon name="fa-arrow-right" className="text-[10px]" />
            </a>
          }
        >
          {insurance.policies.length === 0 ? (
            <Empty icon="fa-shield-halved" title="No insurance policies">
              No policies configured for your resort.
            </Empty>
          ) : (
            <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
              {insurance.policies.map((p) => (
                <div
                  key={p.id}
                  className="flex flex-col justify-between rounded-lg border border-border bg-surface-secondary/40 p-4"
                >
                  <div>
                    <div className="flex items-center justify-between mb-2">
                      <div className="flex items-center gap-2 font-semibold text-sm">
                        <Icon
                          name={p.icon || 'fa-shield-halved'}
                          className={p.active ? 'text-success' : 'text-muted'}
                        />
                        <span>{p.name}</span>
                      </div>
                      <Chip size="sm" variant="soft" color={p.active ? 'success' : 'default'}>
                        {p.active ? 'Active' : 'Inactive'}
                      </Chip>
                    </div>

                    <div className="flex items-center gap-3 text-xs text-muted mb-3">
                      <span>
                        Premium: <span className="tabular-nums font-medium text-foreground">{p.premiumText}</span>/day
                      </span>
                      <span>
                        Coverage: <span className="tabular-nums font-medium text-foreground">{p.coverageText}</span>
                      </span>
                    </div>
                  </div>

                  <PostButton
                    action={`/insurance/toggle/${p.id}`}
                    variant={p.active ? 'tertiary' : 'primary'}
                    size="sm"
                    className="w-full"
                  >
                    <Icon name={p.active ? 'fa-pause' : 'fa-play'} className="mr-1 text-xs" />
                    {p.active ? 'Deactivate policy' : 'Activate policy'}
                  </PostButton>
                </div>
              ))}
            </div>
          )}
        </Panel>
      )}

      {/* Tab 2: Government */}
      {activeTab === 'government' && (
        <div className="space-y-6">
          <Panel
            title={
              <div className="flex items-center gap-2">
                <Icon name="fa-building-columns" className="text-warning" />
                <span>Municipal regulations</span>
              </div>
            }
            actions={
              <a
                href="/government"
                className="inline-flex items-center gap-1.5 rounded-lg border border-border px-3 py-1.5 text-xs font-medium text-foreground hover:bg-default"
              >
                <span>Full regulations page</span>
                <Icon name="fa-arrow-right" className="text-[10px]" />
              </a>
            }
          >
            {government.regulations.length === 0 ? (
              <Empty icon="fa-building-columns" title="No regulations active">
                All regulations are in order.
              </Empty>
            ) : (
              <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                {government.regulations.map((r) => (
                  <div
                    key={r.id}
                    className={`flex flex-col justify-between rounded-lg border bg-surface-secondary/40 p-4 ${
                      !r.compliant ? 'border-danger/40' : 'border-border'
                    }`}
                  >
                    <div>
                      <div className="flex items-center justify-between mb-2">
                        <div className="flex items-center gap-2 font-semibold text-sm">
                          <Icon
                            name={r.icon || (r.compliant ? 'fa-circle-check' : 'fa-circle-xmark')}
                            className={r.compliant ? 'text-success' : 'text-danger'}
                          />
                          <span>{r.name}</span>
                        </div>
                        <Chip
                          size="sm"
                          variant="soft"
                          color={r.compliant ? 'success' : 'danger'}
                        >
                          {r.compliant ? 'Compliant' : 'Violation'}
                        </Chip>
                      </div>

                      {r.description && (
                        <p className="text-xs text-muted mb-2">{r.description}</p>
                      )}

                      <div className="text-xs text-muted mb-3">
                        Cost: <span className="tabular-nums font-medium text-foreground">{r.costText}</span>/day
                      </div>
                    </div>

                    <PostButton
                      action={`/government/toggle/${r.id}`}
                      variant={r.compliant ? 'tertiary' : 'primary'}
                      size="sm"
                      className="w-full"
                    >
                      <Icon
                        name={r.compliant ? 'fa-toggle-on' : 'fa-check'}
                        className="mr-1 text-xs"
                      />
                      {r.compliant ? 'Opt out' : `Comply (${r.costText}/day)`}
                    </PostButton>
                  </div>
                ))}
              </div>
            )}
          </Panel>

          {/* Recent inspections table */}
          {government.inspections.length > 0 && (
            <Panel
              title={
                <div className="flex items-center gap-2">
                  <Icon name="fa-magnifying-glass" className="text-warning" />
                  <span>Recent inspections</span>
                </div>
              }
            >
              <div className="overflow-x-auto">
                <table className="w-full text-left text-xs">
                  <thead>
                    <tr className="border-b border-border text-muted">
                      <th className="pb-2 font-medium">Date</th>
                      <th className="pb-2 font-medium">Result</th>
                      <th className="pb-2 font-medium">Report</th>
                      <th className="pb-2 font-medium text-right">Fine</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-border">
                    {government.inspections.map((i) => (
                      <tr key={i.id} className="py-2">
                        <td className="py-2.5 tabular-nums text-muted">{i.dateText}</td>
                        <td className="py-2.5">
                          <Chip
                            size="sm"
                            variant="soft"
                            color={i.passed ? 'success' : 'danger'}
                          >
                            {i.passed ? 'Passed' : 'Failed'}
                          </Chip>
                        </td>
                        <td className="py-2.5 font-medium">{i.message}</td>
                        <td className="py-2.5 text-right tabular-nums">
                          {i.fineText ? (
                            <span className="font-semibold text-danger">{i.fineText}</span>
                          ) : (
                            <span className="text-muted">–</span>
                          )}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </Panel>
          )}
        </div>
      )}

      {/* Tab 3: Environment */}
      {activeTab === 'environment' && (
        <Panel
          title={
            <div className="flex items-center gap-2">
              <Icon name="fa-leaf" className="text-success" />
              <span>Environmental standing</span>
            </div>
          }
          actions={
            <a
              href="/environment"
              className="inline-flex items-center gap-1.5 rounded-lg border border-border px-3 py-1.5 text-xs font-medium text-foreground hover:bg-default"
            >
              <span>Full environment page</span>
              <Icon name="fa-arrow-right" className="text-[10px]" />
            </a>
          }
        >
          <div className="space-y-6">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
              <div className="w-32">
                <Meter
                  value={environment.eco_score}
                  maxValue={100}
                  color={
                    environment.eco_score >= 70
                      ? 'success'
                      : environment.eco_score >= 40
                      ? 'warning'
                      : 'danger'
                  }
                  aria-label="Eco score"
                />
              </div>
              <div className="min-w-0 flex-1 border-t border-border pt-3 sm:border-t-0 sm:border-l sm:pl-4 sm:pt-0">
                <div className="text-lg font-bold tabular-nums">
                  Eco Score: {environment.eco_score} / 100
                </div>
                <p className="text-xs text-muted mt-0.5">
                  {environment.eco_score >= 80
                    ? 'Excellent sustainability rating. Earning daily Génépis bonus.'
                    : environment.eco_score >= 50
                    ? 'Moderate standing. Invest in solar, wind, and waste reduction to improve.'
                    : 'Low score. High carbon footprint exposes the resort to regulatory fines.'}
                </p>
              </div>
            </div>

            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
              <div className="rounded-lg border border-border bg-surface-secondary/40 p-3">
                <div className="text-xs text-muted">Renewable energy</div>
                <div className="text-lg font-bold tabular-nums text-success mt-0.5">
                  {environment.renewable_pct}%
                </div>
              </div>
              <div className="rounded-lg border border-border bg-surface-secondary/40 p-3">
                <div className="text-xs text-muted">Waste management</div>
                <div className="text-lg font-bold tabular-nums text-accent mt-0.5">
                  {environment.waste_management}
                </div>
              </div>
              <div className="rounded-lg border border-border bg-surface-secondary/40 p-3">
                <div className="text-xs text-muted">Wildlife score</div>
                <div className="text-lg font-bold tabular-nums text-warning mt-0.5">
                  {environment.wildlife_impact}
                </div>
              </div>
            </div>
          </div>
        </Panel>
      )}
    </Page>
  );
}
