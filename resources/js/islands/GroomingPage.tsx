import { Button, Chip } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput, Empty, Figure, Page, Panel, PostButton } from '@/components/Page';

export type GroomingSlope = {
  id: number;
  name: string;
  difficulty: string;
  sectorName: string;
  lengthText: string;
  condition_pct: number;
  draw_pct: number;
  decay: number;
  thin_base: boolean;
  snow_covered: boolean;
  status: string;
  groomed_today: boolean;
  isTonight: boolean;
  isGroomable: boolean;
  extraCost: number;
  extraCostText: string;
};

export type GroomingMachine = {
  id: number;
  name: string;
  capacity: number;
  condition_pct: number;
  status: string;
  stateLabel: string;
  stateTone: 'success' | 'warning' | 'danger' | 'neutral';
  fuelCostText: string;
};

export type GroomingOperator = {
  id: number;
  name: string;
  level: number;
};

export type GroomingSector = {
  name: string;
  count: number;
  avg: number;
  worst: number;
};

export type GroomingPageProps = {
  overview: {
    capacity: number;
    runsLeft: number;
    avgCondition: number;
    avgDecay: number;
    nightlyRestorePerSlope: number;
    netPositive: boolean;
    tonightCount: number;
    slopesCount: number;
    restore: number;
    idleMachines: number;
    activeMachines: number;
    brokenSlopes: number;
    machinesCount: number;
    extraRunCostPerKmText: string;
  };
  batch: {
    count: number;
    cost: number;
    costText: string;
    available: boolean;
  };
  slopes: GroomingSlope[];
  machines: GroomingMachine[];
  operators: GroomingOperator[];
  costs: {
    fuelText: string;
    crewText: string;
    totalText: string;
  };
  sectors: GroomingSector[];
  rules: {
    thinBaseThreshold: number;
    restoreBase: number;
    restoreMax: number;
    extraRunPerKmText: string;
  };
};

const DIFFICULTY_CLASSES: Record<string, string> = {
  green: 'bg-success',
  blue: 'bg-accent',
  red: 'bg-danger',
  black: 'bg-foreground',
  double_black: 'bg-foreground',
};

export default function GroomingPage({
  overview,
  batch,
  slopes,
  machines,
  operators,
  costs,
  sectors,
  rules,
}: GroomingPageProps) {
  const conditionTone = (c: number): 'text-success' | 'text-warning' | 'text-danger' =>
    c >= 70 ? 'text-success' : c >= 40 ? 'text-warning' : 'text-danger';

  const conditionBg = (c: number): string =>
    c >= 70 ? 'bg-success' : c >= 40 ? 'bg-warning' : 'bg-danger';

  return (
    <Page
      title="Grooming"
      icon="fa-tractor"
      iconTone="text-success"
      subtitle="Slopes wear down every day. Your machines groom the worst ones overnight; worn slopes draw fewer skiers."
      width="max-w-6xl"
      actions={
        batch.available ? (
          <PostButton
            action="/grooming/groom-all"
            variant="primary"
            size="sm"
            confirm={{
              title: `Groom worst ${batch.count} slopes now?`,
              body: `Cost will be ${batch.costText} in fuel. Each groomed slope will restore +${overview.restore}% condition.`,
              confirmLabel: 'Groom now',
            }}
          >
            <Icon name="fa-tractor" className="mr-1 text-xs" />
            Groom worst {batch.count} now · {batch.costText}
          </PostButton>
        ) : undefined
      }
    >
      {/* Notices & Warnings */}
      {overview.machinesCount === 0 ? (
        <div className="flex items-center gap-2 rounded-xl border border-warning/40 bg-surface p-4 text-sm text-foreground">
          <Icon name="fa-circle-info" className="text-warning shrink-0" />
          <span>
            No grooming machines yet, so nothing restores your slopes.{' '}
            <a href="/equipment" className="text-link font-medium hover:underline">
              Buy a groomer
            </a>{' '}
            and{' '}
            <a href="/staff/hire" className="text-link font-medium hover:underline">
              hire an operator
            </a>{' '}
            to run it.
          </span>
        </div>
      ) : overview.capacity === 0 ? (
        <div className="flex items-center gap-2 rounded-xl border border-warning/40 bg-surface p-4 text-sm text-foreground">
          <Icon name="fa-circle-info" className="text-warning shrink-0" />
          <span>
            {overview.activeMachines === 0 ? (
              <>
                All your machines are off or broken.{' '}
                <a href="/equipment" className="text-link font-medium hover:underline">
                  Switch one on or repair it
                </a>
                .
              </>
            ) : (
              <>
                Your machines have no operators.{' '}
                <a href="/staff/hire" className="text-link font-medium hover:underline">
                  Hire a groomer operator
                </a>
                : each one runs one machine.
              </>
            )}
          </span>
        </div>
      ) : (
        overview.idleMachines > 0 && (
          <div className="flex items-center gap-2 rounded-xl border border-border bg-surface p-4 text-sm text-foreground">
            <Icon name="fa-user-plus" className="text-accent shrink-0" />
            <span>
              {overview.idleMachines} {overview.idleMachines === 1 ? 'machine is' : 'machines are'} switched on but idle
              for lack of an operator.{' '}
              <a href="/staff/hire" className="text-link font-medium hover:underline">
                Hire groomer operators
              </a>{' '}
              to use them.
            </span>
          </div>
        )
      )}

      {overview.brokenSlopes > 0 && (
        <div className="flex items-center gap-2 rounded-xl border border-danger/40 bg-surface p-4 text-sm text-foreground">
          <Icon name="fa-ban" className="text-danger shrink-0" />
          <span>
            {overview.brokenSlopes} {overview.brokenSlopes === 1 ? 'slope has' : 'slopes have'} worn down to 0% and
            closed. Grooming reopens {overview.brokenSlopes === 1 ? 'it' : 'them'}: closed slopes go first tonight, or
            groom {overview.brokenSlopes === 1 ? 'it' : 'them'} now.
          </span>
        </div>
      )}

      {/* 4 Summary Figures */}
      <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <Figure
          label="Average condition"
          value={`${overview.avgCondition}%`}
          tone={conditionTone(overview.avgCondition)}
          note="Higher condition draws more visitors"
        />
        <Figure
          label="Tonight's night shift"
          value={`${overview.tonightCount} of ${overview.slopesCount} slopes`}
          note={overview.capacity > 0 ? `+${overview.restore}% each, worst first` : 'No machine can run'}
        />
        <Figure
          label="Wear vs grooming"
          value={overview.netPositive ? 'Keeping up' : 'Falling behind'}
          tone={overview.netPositive ? 'text-success' : 'text-danger'}
          note={`−${overview.avgDecay}% wear, +${overview.nightlyRestorePerSlope}% grooming / slope`}
        />
        <Figure
          label="Daytime runs left today"
          value={`${overview.runsLeft} of ${overview.capacity}`}
          note={`Fuel: ${overview.extraRunCostPerKmText} per km`}
        />
      </div>

      {/* Slopes roster */}
      <section className="space-y-3">
        <div className="flex items-center justify-between">
          <h2 className="text-lg font-bold text-foreground flex items-center gap-2">
            <Icon name="fa-mountain" className="text-accent text-base" />
            Slopes <span className="text-xs font-normal text-muted">(worst condition first)</span>
          </h2>
        </div>

        {slopes.length === 0 ? (
          <Empty icon="fa-mountain" title="No slopes yet">
            Build slopes on the trail map to start operations.
            <div className="mt-4">
              <a
                href="/map"
                className="inline-flex min-h-11 items-center gap-1.5 rounded-lg bg-accent px-4 py-2 text-sm font-medium text-accent-foreground hover:bg-accent-hover"
              >
                Trail map
              </a>
            </div>
          </Empty>
        ) : (
          <div className="overflow-x-auto rounded-xl border border-border bg-surface">
            <table className="w-full text-left text-sm">
              <thead className="border-b border-border bg-default text-xs text-muted">
                <tr>
                  <th className="py-2.5 px-4 font-medium">Slope</th>
                  <th className="py-2.5 px-4 font-medium w-48">Condition</th>
                  <th className="py-2.5 px-4 font-medium">Draws</th>
                  <th className="py-2.5 px-4 font-medium">Wear / day</th>
                  <th className="py-2.5 px-4 font-medium">Status</th>
                  <th className="py-2.5 px-4 font-medium text-right">Extra run</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {slopes.map((s) => {
                  const isOpen = s.status === 'open';
                  const cond = s.condition_pct;

                  return (
                    <tr key={s.id} className={isOpen ? '' : 'opacity-60'}>
                      <td className="py-3 px-4">
                        <div className="flex items-center gap-2">
                          {s.difficulty && (
                            <span
                              className={`size-2 shrink-0 rounded-full ${
                                DIFFICULTY_CLASSES[s.difficulty] ?? 'bg-muted'
                              }`}
                            />
                          )}
                          <span className="font-semibold text-foreground">{s.name}</span>
                        </div>
                        <div className="text-xs text-muted mt-0.5">
                          {s.sectorName} · {s.lengthText}
                        </div>
                      </td>

                      <td className="py-3 px-4">
                        <div className="flex items-center gap-2">
                          <div className="relative flex-1 h-2 rounded-full bg-default overflow-hidden">
                            <div
                              className={`h-full rounded-full ${conditionBg(cond)}`}
                              style={{ width: `${cond}%` }}
                            />
                          </div>
                          <span className={`text-xs font-mono font-bold w-9 text-right ${conditionTone(cond)}`}>
                            {cond}%
                          </span>
                        </div>
                      </td>

                      <td className="py-3 px-4 text-xs">
                        {isOpen ? (
                          <span className="text-foreground">{s.draw_pct}% of skiers</span>
                        ) : (
                          <span className="text-danger font-medium">Closed</span>
                        )}
                      </td>

                      <td className="py-3 px-4 text-xs">
                        {isOpen ? (
                          <div className="flex items-center gap-1.5">
                            <span className="text-foreground">−{s.decay}%</span>
                            {s.thin_base ? (
                              <Chip variant="soft" color="warning" size="sm">
                                Thin base
                              </Chip>
                            ) : s.snow_covered ? (
                              <Chip variant="soft" color="accent" size="sm">
                                Snowmaking
                              </Chip>
                            ) : null}
                          </div>
                        ) : (
                          <span className="text-muted">–</span>
                        )}
                      </td>

                      <td className="py-3 px-4 text-xs">
                        {s.status !== 'open' ? (
                          <Chip variant="soft" color="danger" size="sm" className="capitalize">
                            {s.status}
                          </Chip>
                        ) : s.groomed_today ? (
                          <span className="text-success font-medium flex items-center gap-1">
                            <Icon name="fa-check" className="text-xs" />
                            Groomed today
                          </span>
                        ) : s.isTonight ? (
                          <span className="text-accent font-medium flex items-center gap-1">
                            <Icon name="fa-moon" className="text-xs" />
                            Tonight
                          </span>
                        ) : cond >= 100 ? (
                          <span className="text-muted">Perfect</span>
                        ) : (
                          <span className="text-muted">Waiting</span>
                        )}
                      </td>

                      <td className="py-3 px-4 text-right">
                        {s.isGroomable && (
                          <form action="/grooming/groom-single" method="post" className="inline-block">
                            <CsrfInput />
                            <input type="hidden" name="slope_id" value={s.id} />
                            <Button
                              type="submit"
                              variant="ghost"
                              size="sm"
                              className="text-success hover:bg-success/10"
                            >
                              <Icon name="fa-tractor" className="mr-1 text-xs" />
                              {s.extraCostText}
                            </Button>
                          </form>
                        )}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </section>

      {/* Fleet & Operators 2-column grid */}
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {/* Fleet roster */}
        <div className="space-y-3">
          <div className="flex items-center justify-between">
            <h2 className="text-lg font-bold text-foreground flex items-center gap-2">
              <Icon name="fa-truck-monster" className="text-accent text-base" />
              Fleet
            </h2>
            <a href="/equipment" className="text-sm font-medium text-link hover:underline">
              Equipment shop →
            </a>
          </div>

          {machines.length === 0 ? (
            <div className="rounded-xl border border-border bg-surface p-6 text-center text-sm text-muted">
              No machines.{' '}
              <a href="/equipment" className="text-link font-medium hover:underline">
                Buy a groomer
              </a>
              .
            </div>
          ) : (
            <div className="space-y-2.5">
              {machines.map((m) => (
                <div key={m.id} className="rounded-xl border border-border bg-surface p-3.5 space-y-2">
                  <div className="flex items-center justify-between gap-2">
                    <div className="min-w-0">
                      <div className="font-semibold text-sm truncate text-foreground">{m.name}</div>
                      <div className="text-xs text-muted">
                        {m.capacity} slopes / night · {m.fuelCostText} / day fuel
                      </div>
                    </div>
                    <Chip
                      variant="soft"
                      size="sm"
                      color={
                        m.stateTone === 'success'
                          ? 'success'
                          : m.stateTone === 'warning'
                            ? 'warning'
                            : m.stateTone === 'danger'
                              ? 'danger'
                              : 'default'
                      }
                      className="shrink-0"
                    >
                      {m.stateLabel}
                    </Chip>
                  </div>

                  <div className="flex items-center gap-2">
                    <span className="text-xs text-muted w-12">Health</span>
                    <div className="relative flex-1 h-1.5 rounded-full bg-default overflow-hidden">
                      <div
                        className={`h-full rounded-full ${conditionBg(m.condition_pct)}`}
                        style={{ width: `${m.condition_pct}%` }}
                      />
                    </div>
                    <span className="text-xs font-mono w-9 text-right text-foreground">{m.condition_pct}%</span>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Operators, Costs & Sectors */}
        <div className="space-y-4">
          <Panel
            title={
              <span className="flex items-center gap-2">
                <Icon name="fa-user-gear" className="text-accent" />
                Operators
              </span>
            }
            actions={
              <a href="/staff/hire" className="text-xs font-medium text-link hover:underline">
                Hire →
              </a>
            }
          >
            {operators.length === 0 ? (
              <p className="text-sm text-muted">No groomer operators. Each operator runs one machine.</p>
            ) : (
              <div className="space-y-2.5">
                <p className="text-sm text-foreground">
                  {operators.length} {operators.length === 1 ? 'operator' : 'operators'} in service. Each night pass
                  restores <span className="font-semibold">+{overview.restore}%</span> condition.
                </p>
                <div className="flex flex-wrap gap-1.5">
                  {operators.map((o) => (
                    <Chip key={o.id} variant="soft" size="sm">
                      {o.name} · Lv.{o.level}
                    </Chip>
                  ))}
                </div>
              </div>
            )}
          </Panel>

          <Panel title="Daily cost">
            <div className="grid grid-cols-3 gap-2 text-center">
              <div className="rounded-lg bg-default p-2.5">
                <div className="text-xs text-muted">Fuel</div>
                <div className="font-bold text-foreground mt-0.5">{costs.fuelText}</div>
              </div>
              <div className="rounded-lg bg-default p-2.5">
                <div className="text-xs text-muted">Operators</div>
                <div className="font-bold text-foreground mt-0.5">{costs.crewText}</div>
              </div>
              <div className="rounded-lg bg-default p-2.5">
                <div className="text-xs text-muted">Total</div>
                <div className="font-bold text-danger mt-0.5">{costs.totalText}</div>
              </div>
            </div>
            <p className="text-xs text-muted mt-2">
              Fuel is charged for every switched-on machine, crewed or not. Daytime runs cost extra.
            </p>
          </Panel>

          {sectors.length > 1 && (
            <Panel title="By sector">
              <div className="divide-y divide-border">
                {sectors.map((sec) => (
                  <div key={sec.name} className="flex items-center justify-between py-2 text-sm">
                    <span className="text-foreground">
                      {sec.name}{' '}
                      <span className="text-xs text-muted">
                        ({sec.count} {sec.count === 1 ? 'slope' : 'slopes'})
                      </span>
                    </span>
                    <span className="font-mono text-xs text-foreground">
                      Avg <span className={conditionTone(sec.avg)}>{sec.avg}%</span> · Worst{' '}
                      <span className={conditionTone(sec.worst)}>{sec.worst}%</span>
                    </span>
                  </div>
                ))}
              </div>
            </Panel>
          )}
        </div>
      </div>

      {/* Rules explanation details */}
      <details className="rounded-xl border border-border bg-surface">
        <summary className="p-4 cursor-pointer font-semibold text-sm select-none">How grooming works</summary>
        <div className="px-4 pb-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-muted">
          <ul className="space-y-1.5 list-disc pl-5">
            <li>Open slopes wear every day: green −2%, blue −3%, red/black −4%, double black −5%.</li>
            <li>Above 0°C or in rain they wear 2% faster; 5 cm or more of fresh snow takes 2% off the wear.</li>
            <li>
              When the natural snow base is under {rules.thinBaseThreshold} cm, slopes wear another 2% unless a snow gun
              covers them.
            </li>
            <li>A slope at 100% draws all its skiers; at 50% it draws 80%; at 0% it closes until groomed again.</li>
            <li>Slopes groomed overnight count as groomed snow, which attracts more visitors.</li>
          </ul>
          <ul className="space-y-1.5 list-disc pl-5">
            <li>Every night each running machine grooms as many slopes as its capacity, worst first.</li>
            <li>A machine runs only when switched on and with an operator; one operator per machine.</li>
            <li>
              Each pass restores +{rules.restoreBase}% plus 2% per average operator level (up to +{rules.restoreMax}%).
            </li>
            <li>
              During the day the same fleet can do one extra pass per slope, up to its nightly capacity, for{' '}
              {rules.extraRunPerKmText} of fuel per km.
            </li>
          </ul>
        </div>
      </details>
    </Page>
  );
}
