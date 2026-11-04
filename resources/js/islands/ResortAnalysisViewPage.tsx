import { Chip } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Page, Panel } from '@/components/Page';

export type ResortAnalysisRecommendation = {
  type: string;
  tone: 'danger' | 'warning' | 'accent';
  label: string;
  icon: string;
  area: string;
  text: string;
  link: string;
};

export type ResortAnalysisSection = {
  key: string;
  name: string;
  icon: string;
  score: number | null;
  score_tone: 'success' | 'warning' | 'danger' | 'default';
  summary: string;
  metrics: {
    label: string;
    value: string;
  }[];
};

export type ResortAnalysisLegacyScore = {
  name: string;
  icon: string;
  score: number;
};

export type ResortAnalysisViewPageProps = {
  report: {
    id: number;
    game_day: number;
    created_at: string;
    created_date: string;
    measured_day: number | null;
  };
  overall_score: number;
  score_tone: 'success' | 'warning' | 'danger';
  headline: string;
  stats_summary: {
    urgent_count: number;
    warning_count: number;
    total_recommendations: number;
  };
  recommendations: ResortAnalysisRecommendation[];
  sections: ResortAnalysisSection[];
  legacyScores: ResortAnalysisLegacyScore[];
};

export default function ResortAnalysisViewPage({
  report,
  overall_score,
  score_tone,
  headline,
  stats_summary,
  recommendations,
  sections,
  legacyScores,
}: ResortAnalysisViewPageProps) {
  return (
    <Page
      title={`Resort Analysis &middot; Day ${report.game_day}`}
      icon="fa-clipboard-check"
      iconTone="text-accent"
      subtitle={
        <span>
          Ordered {report.created_date}
          {report.measured_day !== null && (
            <span> &middot; based on day {report.measured_day}&rsquo;s results</span>
          )}
        </span>
      }
      back="/resort-analysis"
      actions={
        <a
          href={`/resort-analysis/pdf/${report.id}`}
          className="inline-flex min-h-[44px] items-center gap-1.5 rounded-lg border border-border px-3 text-sm font-medium hover:bg-default"
        >
          <Icon name="fa-file-pdf" className="text-danger" />
          PDF Report
        </a>
      }
      width="max-w-5xl"
    >
      {/* Overall Score Card */}
      <Panel className="flex flex-col gap-4 sm:flex-row sm:items-center">
        <div className="flex size-20 shrink-0 flex-col items-center justify-center rounded-2xl border border-border bg-surface-raised font-mono font-bold">
          <span
            className={`text-2xl ${
              score_tone === 'success'
                ? 'text-success'
                : score_tone === 'warning'
                  ? 'text-warning'
                  : 'text-danger'
            }`}
          >
            {overall_score}
          </span>
          <span className="text-[10px] font-normal text-muted">/ 100</span>
        </div>
        <div>
          <h2 className="text-base font-bold text-foreground">{headline}</h2>
          <p className="mt-0.5 text-xs text-muted">
            {stats_summary.urgent_count > 0 && (
              <span className="font-semibold text-danger">
                {stats_summary.urgent_count} urgent issue
                {stats_summary.urgent_count === 1 ? '' : 's'},{' '}
              </span>
            )}
            {stats_summary.warning_count > 0 && (
              <span>{stats_summary.warning_count} important, </span>
            )}
            {stats_summary.total_recommendations} recommendation
            {stats_summary.total_recommendations === 1 ? '' : 's'} in total.
          </p>
        </div>
      </Panel>

      {/* Recommendations: What to do next */}
      <Panel
        title={
          <div className="flex items-center gap-2">
            <Icon name="fa-list-check" className="text-accent" />
            <span>What to Do Next</span>
          </div>
        }
      >
        {recommendations.length === 0 ? (
          <div className="rounded-xl border border-success/30 bg-success/10 p-3.5 text-xs text-foreground">
            <div className="flex items-center gap-2">
              <Icon name="fa-circle-check" className="text-success" />
              <span>Nothing stands out: your resort operations are in great shape.</span>
            </div>
          </div>
        ) : (
          <div className="space-y-2">
            {recommendations.map((rec, idx) => (
              <div
                key={idx}
                className="flex items-start gap-3 rounded-lg border border-border bg-surface-raised p-3 text-xs"
              >
                <Icon
                  name={rec.icon}
                  className={`mt-0.5 text-base shrink-0 ${
                    rec.type === 'critical'
                      ? 'text-danger'
                      : rec.type === 'warning'
                        ? 'text-warning'
                        : 'text-accent'
                  }`}
                />
                <div className="min-w-0 flex-1">
                  <div className="flex items-center gap-2">
                    <Chip size="sm" variant="soft" color={rec.tone} className="font-mono text-[10px]">
                      {rec.label}
                    </Chip>
                    <span className="text-[11px] font-medium text-muted">{rec.area}</span>
                  </div>
                  <p className="mt-1 text-foreground leading-relaxed">{rec.text}</p>
                </div>
                {rec.link && (
                  <a
                    href={rec.link}
                    className="inline-flex min-h-[32px] shrink-0 items-center gap-1 rounded-lg border border-border px-2.5 font-medium hover:bg-default"
                  >
                    <span>Go</span>
                    <Icon name="fa-arrow-right" />
                  </a>
                )}
              </div>
            ))}
          </div>
        )}
      </Panel>

      {/* Area by Area Breakdown */}
      <Panel
        title={
          <div className="flex items-center gap-2">
            <Icon name="fa-chart-pie" className="text-accent" />
            <span>Area by Area</span>
          </div>
        }
      >
        {sections.length > 0 ? (
          <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
            {sections.map((sec) => (
              <div
                key={sec.key}
                className="space-y-2.5 rounded-xl border border-border bg-surface-raised p-3.5 text-xs"
              >
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-2 font-semibold text-foreground">
                    <Icon name={sec.icon} className="text-accent" />
                    <span>{sec.name}</span>
                  </div>
                  <span
                    className={`font-mono font-bold ${
                      sec.score_tone === 'success'
                        ? 'text-success'
                        : sec.score_tone === 'warning'
                          ? 'text-warning'
                          : sec.score_tone === 'danger'
                            ? 'text-danger'
                            : 'text-muted'
                    }`}
                  >
                    {sec.score === null ? '&mdash;' : `${sec.score} / 100`}
                  </span>
                </div>

                {sec.score !== null && (
                  <div className="h-1.5 w-full overflow-hidden rounded-full bg-border">
                    <div
                      className={`h-full ${
                        sec.score_tone === 'success'
                          ? 'bg-success'
                          : sec.score_tone === 'warning'
                            ? 'bg-warning'
                            : 'bg-danger'
                      }`}
                      style={{ width: `${sec.score}%` }}
                    />
                  </div>
                )}

                <p className="text-muted leading-relaxed">{sec.summary}</p>

                {sec.metrics.length > 0 && (
                  <dl className="grid grid-cols-2 gap-x-3 gap-y-1 border-t border-border pt-2 text-[11px]">
                    {sec.metrics.map((m, mIdx) => (
                      <div key={mIdx} className="flex justify-between gap-1">
                        <dt className="text-muted truncate">{m.label}</dt>
                        <dd className="font-mono font-semibold text-foreground shrink-0">
                          {m.value}
                        </dd>
                      </div>
                    ))}
                  </dl>
                )}
              </div>
            ))}
          </div>
        ) : (
          <div>
            <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
              {legacyScores.map((ls, idx) => (
                <div
                  key={idx}
                  className="rounded-xl border border-border bg-surface-raised p-3 text-center"
                >
                  <Icon name={ls.icon} className="text-lg text-accent" />
                  <div className="mt-1 font-mono text-xl font-bold text-foreground">
                    {ls.score}
                  </div>
                  <div className="text-[11px] text-muted">{ls.name}</div>
                </div>
              ))}
            </div>
            <p className="mt-2 text-[11px] text-muted">
              Legacy report format. Future reports reflect your full daily operation metrics.
            </p>
          </div>
        )}
      </Panel>
    </Page>
  );
}
