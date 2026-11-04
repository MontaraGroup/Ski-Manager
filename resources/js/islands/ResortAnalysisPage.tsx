import { Button, Chip } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Empty, Page, Panel, PostButton } from '@/components/Page';

export type ResortAnalysisReportItem = {
  id: number;
  game_day: number;
  created_at: string;
  created_date: string;
  score: number;
  score_tone: 'success' | 'warning' | 'danger';
  is_latest: boolean;
  recommendations_count: number;
  urgent_count: number;
  top_recommendations: {
    type: string;
    text: string;
    area: string;
    link: string;
  }[];
};

export type ResortAnalysisArea = {
  key: string;
  name: string;
  icon: string;
  color: string;
};

export type ResortAnalysisTodayReport = {
  id: number;
  game_day: number;
} | null;

export type ResortAnalysisPageProps = {
  balance: number;
  cost: number;
  canAfford: boolean;
  gameDay: number;
  todayReport: ResortAnalysisTodayReport;
  areas: ResortAnalysisArea[];
  reports: ResortAnalysisReportItem[];
};

export default function ResortAnalysisPage({
  balance,
  cost,
  canAfford,
  gameDay,
  todayReport,
  areas,
  reports,
}: ResortAnalysisPageProps) {
  return (
    <Page
      title="Resort Analysis"
      icon="fa-clipboard-check"
      iconTone="text-accent"
      subtitle="A consultant reads yesterday's results and tells you what to fix first"
      back="/dashboard"
      width="max-w-4xl"
    >
      {/* Order Panel */}
      <Panel className="space-y-4">
        {todayReport ? (
          <div className="flex flex-wrap items-center justify-between gap-3">
            <div>
              <h2 className="text-sm font-bold text-foreground">Today's report is ready</h2>
              <p className="mt-0.5 text-xs text-muted">
                You can order the next report tomorrow (day {gameDay + 1}).
              </p>
            </div>
            <a
              href={`/resort-analysis/view/${todayReport.id}`}
              className="inline-flex min-h-[44px] items-center gap-1.5 rounded-lg border border-border bg-accent px-3 text-sm font-bold text-accent-foreground hover:opacity-90"
            >
              <Icon name="fa-eye" />
              Open Today's Report
            </a>
          </div>
        ) : (
          <div className="space-y-3">
            <div className="flex flex-wrap items-center justify-between gap-3">
              <div>
                <h2 className="text-sm font-bold text-foreground">Order today's report</h2>
                <p className="mt-0.5 text-xs text-muted">
                  {cost} G&eacute;n&eacute;pis &middot; you have{' '}
                  <span className="font-mono font-semibold text-foreground">{balance}</span>{' '}
                  <Icon name="fa-seedling" className="text-success" /> &middot; one report per game
                  day
                </p>
              </div>
              <PostButton
                action="/resort-analysis/order"
                variant="primary"
                disabled={!canAfford}
                confirm={{
                  title: "Order Today's Resort Analysis?",
                  body: `Ordering today's consultant report costs ${cost} Génépis. It analyzes yesterday's operations and ranks urgent fixes.`,
                  confirmLabel: `Order for ${cost} Génépis`,
                }}
              >
                <Icon name="fa-seedling" className="mr-1.5" />
                Order for {cost} G&eacute;n&eacute;pis
              </PostButton>
            </div>
            {!canAfford && (
              <p className="text-xs text-danger">
                You need {cost - balance} more G&eacute;n&eacute;pis.{' '}
                <a href="/genepis" className="font-semibold text-accent hover:underline">
                  Get G&eacute;n&eacute;pis
                </a>
              </p>
            )}
          </div>
        )}

        {/* 7 Analyzed Areas */}
        <div className="border-t border-border pt-3">
          <div className="grid grid-cols-2 gap-2 text-xs sm:grid-cols-4">
            {areas.map((area) => (
              <div key={area.key} className="flex items-center gap-2 text-muted">
                <Icon name={area.icon} className={area.color} />
                <span>{area.name}</span>
              </div>
            ))}
          </div>
        </div>
      </Panel>

      {/* Reports History */}
      <Panel
        title={
          <div className="flex items-center gap-2">
            <span>Your Reports</span>
            <Chip size="sm" variant="soft" className="font-mono">
              {reports.length}
            </Chip>
          </div>
        }
      >
        {reports.length === 0 ? (
          <Empty
            icon="fa-clipboard-check"
            title="No reports yet"
          >
            <p>Your first report shows where your resort is losing skiers and money.</p>
          </Empty>
        ) : (
          <div className="space-y-3">
            {reports.map((r) => (
              <div
                key={r.id}
                className="space-y-3 rounded-xl border border-border bg-surface-raised p-4 text-xs"
              >
                <div className="flex items-center gap-3">
                  {/* Score Chip */}
                  <div className="flex size-11 shrink-0 flex-col items-center justify-center rounded-xl border border-border bg-surface font-mono font-bold">
                    <span
                      className={
                        r.score_tone === 'success'
                          ? 'text-success'
                          : r.score_tone === 'warning'
                            ? 'text-warning'
                            : 'text-danger'
                      }
                    >
                      {r.score}
                    </span>
                    <span className="text-[9px] font-normal text-muted">/100</span>
                  </div>

                  {/* Meta */}
                  <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2">
                      <span className="font-bold text-foreground">Day {r.game_day}</span>
                      {r.is_latest && (
                        <Chip size="sm" variant="soft" color="accent" className="font-mono text-[10px]">
                          Latest
                        </Chip>
                      )}
                    </div>
                    <div className="mt-0.5 text-muted">
                      {r.created_date} &middot; {r.recommendations_count} recommendation
                      {r.recommendations_count === 1 ? '' : 's'}
                      {r.urgent_count > 0 && (
                        <span className="ml-1.5 font-bold text-danger">
                          &middot; {r.urgent_count} urgent
                        </span>
                      )}
                    </div>
                  </div>

                  {/* Actions */}
                  <div className="flex items-center gap-1.5">
                    <a
                      href={`/resort-analysis/view/${r.id}`}
                      className="inline-flex min-h-[36px] items-center rounded-lg border border-border px-2.5 font-medium hover:bg-default"
                    >
                      Open
                    </a>
                    <a
                      href={`/resort-analysis/pdf/${r.id}`}
                      aria-label={`Download day ${r.game_day} report as PDF`}
                      className="inline-flex min-h-[36px] items-center rounded-lg border border-border px-2.5 text-danger hover:bg-default"
                    >
                      <Icon name="fa-file-pdf" />
                    </a>
                  </div>
                </div>

                {/* Top Recommendations on latest report */}
                {r.is_latest && r.top_recommendations.length > 0 && (
                  <div className="border-t border-border pt-2.5">
                    <ul className="space-y-1.5">
                      {r.top_recommendations.map((rec, i) => (
                        <li key={i} className="flex items-start gap-2">
                          <Icon
                            name={
                              rec.type === 'critical'
                                ? 'fa-circle-xmark'
                                : rec.type === 'warning'
                                  ? 'fa-triangle-exclamation'
                                  : 'fa-lightbulb'
                            }
                            className={`mt-0.5 ${
                              rec.type === 'critical'
                                ? 'text-danger'
                                : rec.type === 'warning'
                                  ? 'text-warning'
                                  : 'text-accent'
                            }`}
                          />
                          <span className="text-foreground">{rec.text}</span>
                        </li>
                      ))}
                    </ul>
                  </div>
                )}
              </div>
            ))}
          </div>
        )}
      </Panel>
    </Page>
  );
}
