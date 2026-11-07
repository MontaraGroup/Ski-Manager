import { Icon } from '@/components/Icon';
import { Page, Panel } from '@/components/Page';

export type IncidentLog = {
  id: number;
  category: string;
  message: string;
  created_at: string;
  timeAgo: string;
};

export type SafetyCheckItem = {
  id: string;
  text: string;
  done: boolean;
  link: string | null;
};

export type EmergencyPageProps = {
  safetyScore: number;
  riskLevel: 'Low' | 'Moderate' | 'High';
  coverageRatio: number;
  totalCoverage: number;
  slopes: number;
  uncoveredSlopes: number;
  activeStations: number;
  totalStations: number;
  patrolStaff: number;
  medicsCount: number;
  insuranceCount: number;
  checklist: SafetyCheckItem[];
  incidents: IncidentLog[];
};

export default function EmergencyPage({
  safetyScore = 100,
  riskLevel = 'Low',
  coverageRatio = 100,
  totalCoverage = 0,
  slopes = 0,
  uncoveredSlopes = 0,
  activeStations = 0,
  totalStations = 0,
  patrolStaff = 0,
  medicsCount = 0,
  insuranceCount = 0,
  checklist = [],
  incidents = [],
}: EmergencyPageProps) {
  const isHighRisk = riskLevel === 'High';
  const isModerateRisk = riskLevel === 'Moderate';

  const riskTone = isHighRisk
    ? 'text-danger'
    : isModerateRisk
    ? 'text-warning'
    : 'text-success';

  const riskBg = isHighRisk
    ? 'border-danger/30 bg-gradient-to-br from-danger/10 via-base-100 to-base-100'
    : isModerateRisk
    ? 'border-warning/30 bg-gradient-to-br from-warning/10 via-base-100 to-base-100'
    : 'border-success/30 bg-gradient-to-br from-success/10 via-base-100 to-base-100';

  const services = [
    {
      title: 'Ski Patrol',
      desc: 'Stations & slope coverage',
      icon: 'fa-shield-halved',
      tone: 'text-danger',
      href: '/ski-patrol',
    },
    {
      title: 'Medical Staff',
      desc: `${medicsCount} medics on duty`,
      icon: 'fa-user-doctor',
      tone: 'text-primary',
      href: '/staff',
    },
    {
      title: 'Liability Insurance',
      desc: `${insuranceCount} policies active`,
      icon: 'fa-file-shield',
      tone: 'text-info',
      href: '/insurance',
    },
  ];

  return (
    <Page
      title="Emergency & Rescue"
      icon="fa-truck-medical"
      iconTone="text-danger"
      back="/dashboard"
      width="max-w-6xl"
      subtitle="Safety operations, medical response, patrol stations, and resort risk mitigation"
    >
      <div className="space-y-6">
        {/* Safety Overview Banner */}
        <div className={`rounded-2xl border p-5 shadow-sm ${riskBg}`}>
          <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
            <div className="text-center sm:text-left flex flex-col justify-center">
              <div className="text-xs text-muted mb-1 font-medium">Safety Score</div>
              <div className={`text-4xl font-black tabular-nums ${riskTone}`}>
                {safetyScore}
                <span className="text-sm font-normal text-muted">/100</span>
              </div>
              <div className={`text-xs font-bold uppercase tracking-wider mt-1 ${riskTone}`}>
                {riskLevel} Risk
              </div>
            </div>

            <div>
              <div className="text-xs text-muted mb-1 font-medium">Slope Patrol Coverage</div>
              <div className="text-2xl sm:text-3xl font-extrabold text-foreground tabular-nums">
                {coverageRatio}%
              </div>
              <div className="text-xs text-muted tabular-nums">
                {totalCoverage} of {slopes} slopes covered
              </div>
              <div className="w-full bg-base-200 rounded-full h-1.5 overflow-hidden mt-1.5">
                <div
                  className={`h-full rounded-full transition-all ${
                    coverageRatio >= 100
                      ? 'bg-success'
                      : coverageRatio >= 50
                      ? 'bg-warning'
                      : 'bg-danger'
                  }`}
                  style={{ width: `${Math.min(100, coverageRatio)}%` }}
                />
              </div>
            </div>

            <div>
              <div className="text-xs text-muted mb-1 font-medium">Patrol Stations</div>
              <div className="text-2xl sm:text-3xl font-extrabold text-foreground tabular-nums">
                {activeStations}
              </div>
              <div className="text-xs text-muted tabular-nums">
                {totalStations} total constructed
              </div>
            </div>

            <div>
              <div className="text-xs text-muted mb-1 font-medium">Response Team</div>
              <div className="text-2xl sm:text-3xl font-extrabold text-foreground tabular-nums">
                {patrolStaff + medicsCount}
              </div>
              <div className="text-xs text-muted tabular-nums">
                {patrolStaff} patrol · {medicsCount} medics
              </div>
            </div>

            <div>
              <div className="text-xs text-muted mb-1 font-medium">Insurance Coverage</div>
              <div className="text-2xl sm:text-3xl font-extrabold text-foreground tabular-nums">
                {insuranceCount}
              </div>
              <div className="text-xs text-muted">active liability policies</div>
            </div>
          </div>
        </div>

        {/* Warning alerts */}
        {uncoveredSlopes > 0 && (
          <div className="flex items-center gap-3 rounded-xl border border-warning/50 bg-warning/10 p-4 text-xs text-foreground">
            <Icon name="fa-triangle-exclamation" className="text-warning text-lg shrink-0" />
            <div>
              <span className="font-bold">Incomplete Slope Patrol: </span>
              {uncoveredSlopes} {uncoveredSlopes === 1 ? 'slope lacks' : 'slopes lack'} patrol
              coverage. Accidents on unmonitored slopes trigger severe reputation loss and legal
              penalties. Build more ski patrol stations to protect your guests.
            </div>
          </div>
        )}

        {patrolStaff === 0 && (
          <div className="flex items-center gap-3 rounded-xl border border-danger/50 bg-danger/10 p-4 text-xs text-foreground">
            <Icon name="fa-user-shield" className="text-danger text-lg shrink-0" />
            <div>
              <span className="font-bold">No Ski Patrol Staff On Duty: </span>
              Your stations cannot respond to emergencies without qualified personnel.{' '}
              <a href="/staff/hire" className="font-bold text-primary underline">
                Hire ski patrol crew now
              </a>
              .
            </div>
          </div>
        )}

        {/* Two-column layout */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Left Column: Services & Recent Incidents */}
          <div className="space-y-6 lg:col-span-2">
            {/* Quick Actions */}
            <Panel
              title={
                <span className="flex items-center gap-2">
                  <Icon name="fa-bolt" className="text-warning" />
                  Emergency Operations
                </span>
              }
            >
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                {services.map((svc) => (
                  <a
                    key={svc.title}
                    href={svc.href}
                    className="p-4 rounded-xl border border-border bg-base-200/30 hover:bg-base-200/70 hover:border-border/80 transition-all text-center flex flex-col items-center justify-center group"
                  >
                    <div className="w-12 h-12 rounded-xl bg-base-100 flex items-center justify-center mb-2.5 group-hover:scale-105 transition-transform">
                      <Icon name={svc.icon} className={`${svc.tone} text-xl`} />
                    </div>
                    <div className="font-bold text-xs text-foreground">{svc.title}</div>
                    <div className="text-xs text-muted mt-0.5">{svc.desc}</div>
                  </a>
                ))}
              </div>
            </Panel>

            {/* Incidents Table */}
            <Panel
              title={
                <span className="flex items-center gap-2">
                  <Icon name="fa-clock-rotate-left" className="text-primary" />
                  Recent Incidents & Rescues
                </span>
              }
            >
              {incidents.length === 0 ? (
                <div className="text-center py-8 text-xs text-muted">
                  <Icon name="fa-shield-heart" className="text-success text-2xl mb-2" />
                  <div>No emergency incidents or accidents on record. Safe operations!</div>
                </div>
              ) : (
                <div className="overflow-x-auto -mx-4 sm:mx-0">
                  <table className="w-full text-left text-xs">
                    <thead>
                      <tr className="border-b border-border text-muted uppercase text-xs font-bold">
                        <th className="py-2.5 px-3">Time</th>
                        <th className="py-2.5 px-3">Category</th>
                        <th className="py-2.5 px-3">Incident Log</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-border/40">
                      {incidents.map((inc) => {
                        const isAccident =
                          inc.category.includes('accident') || inc.category.includes('emergency');

                        return (
                          <tr key={inc.id} className="hover:bg-base-200/40 transition-colors">
                            <td className="py-2.5 px-3 text-muted tabular-nums whitespace-nowrap">
                              {inc.timeAgo}
                            </td>
                            <td className="py-2.5 px-3 whitespace-nowrap">
                              <span
                                className={`px-2 py-0.5 rounded text-xs font-bold uppercase tracking-wider ${
                                  isAccident
                                    ? 'bg-danger/15 text-danger'
                                    : 'bg-primary/15 text-primary'
                                }`}
                              >
                                {inc.category}
                              </span>
                            </td>
                            <td className="py-2.5 px-3 text-foreground leading-relaxed">
                              {inc.message}
                            </td>
                          </tr>
                        );
                      })}
                    </tbody>
                  </table>
                </div>
              )}
            </Panel>
          </div>

          {/* Right Column: Safety Checklist & Related Links */}
          <div className="space-y-6">
            <Panel
              title={
                <span className="flex items-center gap-2">
                  <Icon name="fa-list-check" className="text-primary" />
                  Safety Checklist
                </span>
              }
            >
              <div className="space-y-2">
                {checklist.map((item) => (
                  <div key={item.id} className="flex min-h-11 sm:min-h-7 items-center gap-2.5 text-xs">
                    {item.done ? (
                      <Icon name="fa-circle-check" className="text-success text-sm shrink-0" />
                    ) : (
                      <Icon name="fa-circle" className="text-muted/30 text-xs shrink-0" />
                    )}

                    {item.done ? (
                      <span className="line-through text-muted">{item.text}</span>
                    ) : item.link ? (
                      <a href={item.link} className="inline-flex min-h-11 sm:min-h-7 items-center text-primary font-medium hover:underline">
                        {item.text}
                      </a>
                    ) : (
                      <span className="text-foreground">{item.text}</span>
                    )}
                  </div>
                ))}
              </div>
            </Panel>

            <Panel
              title={
                <span className="flex items-center gap-2">
                  <Icon name="fa-link" className="text-muted" />
                  Related Departments
                </span>
              }
            >
              <div className="space-y-2">
                <a
                  href="/ski-patrol"
                  className="flex min-h-11 items-center justify-between p-2.5 rounded-lg border border-border/50 bg-base-200/40 hover:bg-base-200 transition-colors group"
                >
                  <div className="flex items-center gap-2.5">
                    <Icon name="fa-shield-halved" className="text-danger text-sm" />
                    <div>
                      <div className="text-xs font-semibold text-foreground">Patrol Stations</div>
                      <div className="text-xs text-muted">Construct & upgrade facilities</div>
                    </div>
                  </div>
                  <Icon
                    name="fa-chevron-right"
                    className="text-muted/40 text-xs group-hover:text-foreground transition-colors"
                  />
                </a>

                <a
                  href="/staff/hire"
                  className="flex min-h-11 items-center justify-between p-2.5 rounded-lg border border-border/50 bg-base-200/40 hover:bg-base-200 transition-colors group"
                >
                  <div className="flex items-center gap-2.5">
                    <Icon name="fa-user-plus" className="text-primary text-sm" />
                    <div>
                      <div className="text-xs font-semibold text-foreground">Hire Crew</div>
                      <div className="text-xs text-muted">Recruit patrollers and medics</div>
                    </div>
                  </div>
                  <Icon
                    name="fa-chevron-right"
                    className="text-muted/40 text-xs group-hover:text-foreground transition-colors"
                  />
                </a>

                <a
                  href="/insurance"
                  className="flex min-h-11 items-center justify-between p-2.5 rounded-lg border border-border/50 bg-base-200/40 hover:bg-base-200 transition-colors group"
                >
                  <div className="flex items-center gap-2.5">
                    <Icon name="fa-file-shield" className="text-info text-sm" />
                    <div>
                      <div className="text-xs font-semibold text-foreground">Insurance Policies</div>
                      <div className="text-xs text-muted">Manage liability coverage</div>
                    </div>
                  </div>
                  <Icon
                    name="fa-chevron-right"
                    className="text-muted/40 text-xs group-hover:text-foreground transition-colors"
                  />
                </a>

                <a
                  href="/government"
                  className="flex min-h-11 items-center justify-between p-2.5 rounded-lg border border-border/50 bg-base-200/40 hover:bg-base-200 transition-colors group"
                >
                  <div className="flex items-center gap-2.5">
                    <Icon name="fa-building-columns" className="text-warning text-sm" />
                    <div>
                      <div className="text-xs font-semibold text-foreground">Safety Regulations</div>
                      <div className="text-xs text-muted">Maintain legal compliance</div>
                    </div>
                  </div>
                  <Icon
                    name="fa-chevron-right"
                    className="text-muted/40 text-xs group-hover:text-foreground transition-colors"
                  />
                </a>
              </div>
            </Panel>
          </div>
        </div>
      </div>
    </Page>
  );
}
