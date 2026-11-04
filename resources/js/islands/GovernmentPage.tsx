import { Chip, Meter } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Figure, Page, Panel, PostButton } from '@/components/Page';

export type RegulationItem = {
  id: number;
  name: string;
  regulation_type: string;
  compliant: boolean;
  compliance_cost: number;
  costText: string;
  penalty_risk: number;
  penaltyText: string;
  benefit: string;
  icon: string;
};

export type RegulationTier = {
  tier: number;
  title: string;
  subtitle: string;
  regulations: RegulationItem[];
};

export type InspectionItem = {
  id: number;
  message: string;
  game_day: number;
  icon: string;
  dateText: string;
};

export type GovernmentPageProps = {
  stats: {
    compliantCount: number;
    totalCount: number;
    complianceScore: number;
    totalCostText: string;
    totalRiskText: string;
    totalPossibleCostText: string;
    inspectionChance: number;
    reputationPenalty: number;
    visitorPenalty: number;
  };
  tiers: RegulationTier[];
  inspections: InspectionItem[];
};

export default function GovernmentPage({ stats, tiers, inspections }: GovernmentPageProps) {
  const isFullyCompliant = stats.compliantCount === stats.totalCount;

  return (
    <Page
      title="Government & Regulations"
      icon="fa-building-columns"
      iconTone="text-primary"
      subtitle="Stay compliant to avoid inspections, fines, and reputation damage"
      width="max-w-5xl"
      actions={
        !isFullyCompliant ? (
          <PostButton
            action="/government/comply-all"
            variant="primary"
            size="sm"
            confirm={{
              title: 'Comply with all regulations',
              body: `Comply with all regulations now? Your total daily compliance cost will be ${stats.totalPossibleCostText}.`,
              confirmLabel: 'Comply with all',
            }}
          >
            <Icon name="fa-check-double" className="mr-1.5" />
            Comply all
          </PostButton>
        ) : undefined
      }
    >
      {/* Compliance score header card */}
      <Panel>
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
          <div className="flex items-center gap-4">
            <div className="w-32">
              <Meter
                value={stats.complianceScore}
                maxValue={100}
                color={
                  stats.complianceScore >= 80
                    ? 'success'
                    : stats.complianceScore >= 50
                    ? 'warning'
                    : 'danger'
                }
                formatOptions={{ style: 'percent' }}
                aria-label="Compliance score"
              />
            </div>
            <div className="text-2xl font-bold tabular-nums">
              {stats.complianceScore}%
            </div>
          </div>
          <div className="min-w-0 flex-1 border-t border-border pt-3 sm:border-t-0 sm:border-l sm:pl-4 sm:pt-0">
            <div className="font-semibold text-base">Compliance Score</div>
            <p className="text-xs text-muted mt-0.5">
              {stats.complianceScore >= 100
                ? 'Perfect standing. All municipal and safety standards are satisfied.'
                : stats.complianceScore >= 75
                ? 'Good standing with minor regulatory gaps.'
                : stats.complianceScore >= 50
                ? 'Moderate risk: several regulations not met. Inspections likely.'
                : 'High risk: severe violations. Expect heavy fines and visitor loss.'}
            </p>
          </div>
        </div>
      </Panel>

      {/* 5 Stats figures */}
      <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        <Figure
          label="Compliant"
          value={`${stats.compliantCount} / ${stats.totalCount}`}
          tone={isFullyCompliant ? 'text-success' : 'text-warning'}
        />
        <Figure
          label="Daily cost"
          value={stats.totalCostText}
          tone="text-warning"
          note="Active compliance"
        />
        <Figure
          label="Inspection risk"
          value={`${stats.inspectionChance}%`}
          tone={
            stats.inspectionChance > 20
              ? 'text-danger'
              : stats.inspectionChance > 0
              ? 'text-warning'
              : 'text-success'
          }
          note="Daily probability"
        />
        <Figure
          label="Max fine"
          value={stats.totalRiskText}
          tone="text-danger"
          note="Total exposure"
        />
        <Figure
          label="Visitor penalty"
          value={`-${stats.visitorPenalty}%`}
          tone="text-danger"
          note="Turnout impact"
        />
      </div>

      {/* Status banner */}
      {isFullyCompliant ? (
        <div className="flex items-center gap-2.5 rounded-lg border border-success/30 bg-success/10 p-3 text-xs text-success font-medium">
          <Icon name="fa-circle-check" className="text-sm shrink-0" />
          <span>Fully compliant. All regulatory bonuses are active with zero inspection risk.</span>
        </div>
      ) : stats.inspectionChance > 20 ? (
        <div className="flex items-start gap-2.5 rounded-lg border border-danger/30 bg-danger/10 p-3 text-xs text-danger">
          <Icon name="fa-triangle-exclamation" className="text-sm shrink-0 mt-0.5" />
          <div>
            <div className="font-semibold">High risk of government inspection</div>
            <div className="text-muted mt-0.5">
              {stats.inspectionChance}% daily chance. Potential fines up to {stats.totalRiskText}. -{stats.reputationPenalty} reputation/day and -{stats.visitorPenalty}% visitor penalty.
            </div>
          </div>
        </div>
      ) : (
        <div className="flex items-start gap-2.5 rounded-lg border border-warning/30 bg-warning/10 p-3 text-xs text-warning">
          <Icon name="fa-circle-exclamation" className="text-sm shrink-0 mt-0.5" />
          <div>
            <div className="font-semibold">Non-compliance detected</div>
            <div className="text-muted mt-0.5">
              {stats.inspectionChance}% inspection risk. -{stats.reputationPenalty} reputation/day and -{stats.visitorPenalty}% visitors.
            </div>
          </div>
        </div>
      )}

      {/* Tiers of regulations */}
      <div className="space-y-6">
        {tiers.map((tierGroup) => {
          if (!tierGroup.regulations.length) return null;
          return (
            <Panel
              key={tierGroup.tier}
              title={
                <div className="flex items-center gap-2">
                  <span>{tierGroup.title}</span>
                  <Chip size="sm" variant="secondary">
                    Tier {tierGroup.tier}
                  </Chip>
                  {tierGroup.subtitle && (
                    <span className="text-xs font-normal text-muted">
                      · {tierGroup.subtitle}
                    </span>
                  )}
                </div>
              }
            >
              <div className="space-y-2.5">
                {tierGroup.regulations.map((reg) => (
                  <div
                    key={reg.id}
                    className="flex flex-col gap-3 rounded-lg border border-border bg-surface-secondary/40 p-4 sm:flex-row sm:items-center sm:justify-between"
                  >
                    <div className="flex items-start gap-3 flex-1 min-w-0">
                      <div
                        className={`flex size-10 shrink-0 items-center justify-center rounded-lg border ${
                          reg.compliant
                            ? 'border-success/30 bg-success/10 text-success'
                            : 'border-danger/30 bg-danger/10 text-danger'
                        }`}
                      >
                        <Icon name={reg.icon || 'fa-gavel'} className="text-lg" />
                      </div>
                      <div className="min-w-0 flex-1">
                        <div className="flex items-center gap-2">
                          <span className="font-semibold text-sm">{reg.name}</span>
                          <Chip
                            size="sm"
                            variant="soft"
                            color={reg.compliant ? 'success' : 'danger'}
                          >
                            {reg.compliant ? 'Compliant' : 'Violation'}
                          </Chip>
                        </div>
                        {reg.compliant ? (
                          <div className="mt-0.5 flex items-center gap-1 text-xs text-success">
                            <Icon name="fa-check" className="text-[10px]" />
                            <span>{reg.benefit}</span>
                          </div>
                        ) : (
                          <div className="mt-0.5 flex items-center gap-1 text-xs text-danger">
                            <Icon name="fa-xmark" className="text-[10px]" />
                            <span>Risk: {reg.penaltyText} fine per inspection</span>
                          </div>
                        )}
                        <div className="mt-1 flex items-center gap-3 text-xs text-muted">
                          <span>
                            Cost: <span className="tabular-nums font-medium text-foreground">{reg.costText}</span>/day
                          </span>
                          <span>
                            Fine risk: <span className="tabular-nums font-medium text-foreground">{reg.penaltyText}</span>
                          </span>
                        </div>
                      </div>
                    </div>

                    <div className="flex items-center gap-2 self-end sm:self-center">
                      <PostButton
                        action={`/government/toggle/${reg.id}`}
                        variant={reg.compliant ? 'tertiary' : 'primary'}
                        size="sm"
                        confirm={
                          reg.compliant
                            ? {
                                title: `Opt out of ${reg.name}?`,
                                body: 'Opting out will expose your resort to inspection fines and reputation penalties.',
                                confirmLabel: 'Opt out',
                              }
                            : undefined
                        }
                      >
                        <Icon
                          name={reg.compliant ? 'fa-toggle-on' : 'fa-toggle-off'}
                          className="mr-1 text-xs"
                        />
                        {reg.compliant ? 'Opt out' : 'Comply'}
                      </PostButton>
                    </div>
                  </div>
                ))}
              </div>
            </Panel>
          );
        })}
      </div>

      {/* Inspection history */}
      {inspections.length > 0 && (
        <Panel
          title={
            <div className="flex items-center gap-2">
              <Icon name="fa-clipboard-list" className="text-warning" />
              <span>Inspection history</span>
            </div>
          }
        >
          <div className="divide-y divide-border">
            {inspections.map((insp) => (
              <div
                key={insp.id}
                className="flex items-center justify-between py-2.5 text-xs first:pt-0 last:pb-0"
              >
                <div className="flex items-center gap-2.5">
                  <Icon name={insp.icon || 'fa-gavel'} className="text-warning w-4 text-center" />
                  <span className="font-medium text-foreground">{insp.message}</span>
                </div>
                <div className="flex items-center gap-2 text-muted shrink-0 tabular-nums">
                  {insp.dateText && <span>{insp.dateText}</span>}
                  <span>· Day {insp.game_day}</span>
                </div>
              </div>
            ))}
          </div>
        </Panel>
      )}

      {/* How it works info card */}
      <Panel
        title={
          <div className="flex items-center gap-2">
            <Icon name="fa-circle-info" className="text-accent" />
            <span>How regulations work</span>
          </div>
        }
      >
        <div className="grid grid-cols-1 gap-3 text-xs text-muted md:grid-cols-2">
          <div className="space-y-1.5">
            <div className="flex items-center gap-2">
              <Icon name="fa-gavel" className="text-warning" />
              <span>Each violation adds <strong>5%</strong> daily inspection chance.</span>
            </div>
            <div className="flex items-center gap-2">
              <Icon name="fa-star" className="text-danger" />
              <span>Each violation costs <strong>15</strong> reputation per day.</span>
            </div>
            <div className="flex items-center gap-2">
              <Icon name="fa-users" className="text-danger" />
              <span>Each violation reduces visitor turnout by <strong>3%</strong>.</span>
            </div>
          </div>
          <div className="space-y-1.5">
            <div className="flex items-center gap-2">
              <Icon name="fa-coins" className="text-warning" />
              <span>If inspected, you pay fines for <strong>each</strong> non-compliant item.</span>
            </div>
            <div className="flex items-center gap-2">
              <Icon name="fa-circle-check" className="text-success" />
              <span>Full compliance yields <strong>0%</strong> risk and unlocks all tier perks.</span>
            </div>
            <div className="flex items-center gap-2">
              <Icon name="fa-seedling" className="text-success" />
              <span>Environmental compliance earns daily <strong>Génépis</strong>.</span>
            </div>
          </div>
        </div>
      </Panel>
    </Page>
  );
}
