import { useState } from 'react';
import { Button, Chip, Meter, Surface } from '@heroui/react';
import { CsrfInput, PostButton } from '@/components/Page';
import { Icon } from '@/components/Icon';
import { CompactPriceForm, meterColor, statusChipColor, VenueStatusActions } from './SharedParts';
import type { HydratedVenue, SkiJumpSpecific } from './types';

function HillElevationSvg({
  hills,
  hillRecordM,
  hasSummerMat,
}: {
  hills: SkiJumpSpecific['hills'];
  hillRecordM: number;
  hasSummerMat: boolean;
}) {
  const specs = [
    { startX: 85, startY: 54, takeX: 250, takeY: 120, landX: 430, landY: 186, arcX: 350, arcY: 108 },
    { startX: 60, startY: 34, takeX: 250, takeY: 112, landX: 560, landY: 194, arcX: 415, arcY: 90 },
    { startX: 36, startY: 16, takeX: 250, takeY: 102, landX: 745, landY: 202, arcX: 515, arcY: 70 },
  ];

  return (
    <svg
      viewBox="0 0 820 225"
      className="w-full h-auto"
      role="img"
      aria-label="Ski Jump Inrun and Flight Elevation Profile"
    >
      <rect x="4" y="4" width="812" height="217" rx="14" fill="var(--surface-secondary)" stroke="var(--border)" strokeWidth="1.5" />

      {/* Natural landing slope fill */}
      <polygon
        points="250,120 790,206 790,217 250,217"
        fill={hasSummerMat ? 'var(--success)' : 'var(--link)'}
        fillOpacity={hasSummerMat ? 0.14 : 0.08}
      />
      <line x1="250" y1="120" x2="790" y2="206" stroke="var(--border)" strokeWidth="2" />

      {/* Draw each jump hill */}
      {hills.map((hill, idx) => {
        const g = specs[idx % specs.length];
        const color = hill.built ? (idx === 2 ? 'var(--warning)' : 'var(--link)') : 'var(--muted)';
        return (
          <g key={hill.key} opacity={hill.built ? 1 : 0.45}>
            {/* Inrun ramp */}
            <path
              d={`M ${g.startX} ${g.startY} Q ${(g.startX + g.takeX) * 0.5} ${g.takeY} ${g.takeX} ${g.takeY}`}
              fill="none"
              stroke={color}
              strokeWidth={hill.built ? 3.5 : 2}
              strokeDasharray={hill.built ? undefined : '5 4'}
            />
            {/* Tower stanchion */}
            <line
              x1={g.startX}
              y1={g.startY}
              x2={g.startX}
              y2="204"
              stroke={color}
              strokeWidth="1.5"
              strokeDasharray="3 3"
            />
            {/* Flight trajectory parabola */}
            <path
              d={`M ${g.takeX} ${g.takeY} Q ${g.arcX} ${g.arcY} ${g.landX} ${g.landY}`}
              fill="none"
              stroke={color}
              strokeWidth={hill.built ? 2 : 1.2}
              strokeDasharray="4 4"
            />
            {/* K-Point badge */}
            <rect
              x={g.landX - 42}
              y={g.landY - 12}
              width="84"
              height="20"
              rx="6"
              fill="var(--surface)"
              stroke={color}
              strokeWidth="1"
            />
            <text
              x={g.landX}
              y={g.landY + 2}
              textAnchor="middle"
              fill={hill.built ? 'var(--foreground)' : 'var(--muted)'}
              fontSize="9.5"
              fontWeight="700"
            >
              {`K${hill.kPoint} / HS${hill.maxM}`}
            </text>
          </g>
        );
      })}

      {/* Hill Record Flag */}
      <g>
        <line x1="650" y1="125" x2="650" y2="184" stroke="var(--danger)" strokeWidth="2" />
        <rect x="650" y="125" width="122" height="24" rx="5" fill="var(--danger)" />
        <text x="711" y="141" textAnchor="middle" fill="#ffffff" fontSize="10" fontWeight="700">
          {`HILL REC ${hillRecordM}m`}
        </text>
      </g>
    </svg>
  );
}

export function SkiJumpBlueprintSvg() {
  return (
    <svg
      viewBox="0 0 520 200"
      className="w-full h-auto"
      role="img"
      aria-label="Ski Jump Complex Profile Blueprint"
    >
      <rect x="8" y="8" width="504" height="184" rx="14" fill="var(--surface-secondary)" stroke="var(--border)" strokeDasharray="6 4" />
      <path d="M 55 34 Q 140 105 215 105" fill="none" stroke="var(--link)" strokeWidth="3.5" />
      <line x1="55" y1="34" x2="55" y2="178" stroke="var(--muted)" strokeDasharray="3 3" strokeWidth="2" />
      <line x1="215" y1="105" x2="475" y2="178" stroke="var(--border)" strokeWidth="2" />
      <path d="M 215 105 Q 325 78 410 158" fill="none" stroke="var(--warning)" strokeWidth="2" strokeDasharray="5 4" />
      <circle cx="410" cy="158" r="5" fill="var(--warning)" />
      <text x="330" y="54" textAnchor="middle" fill="var(--foreground)" fontSize="11" fontWeight="700">
        INRUN TOWER, TAKEOFF &amp; K-POINT APRON
      </text>
    </svg>
  );
}

export function SkiJumpTab({ venue }: { venue: HydratedVenue }) {
  const sp = venue.specific as SkiJumpSpecific;
  const [allocation, setAllocation] = useState(sp.allocation);
  const windKmh = sp.windKmh ?? 0;
  const windOverLimit = windKmh > sp.safeWindKmh;
  const windPct = Math.min(100, Math.round((windKmh / Math.max(1, sp.safeWindKmh * 1.4)) * 100));

  return (
    <div className="space-y-5">
      {/* Compact Operations Bar */}
      <Surface variant="secondary" className="rounded-2xl border border-[var(--border)] p-4 sm:p-5">
        <div className="flex flex-wrap items-center justify-between gap-4">
          <div className="flex min-w-0 items-center gap-3">
            <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-[var(--border)] bg-[var(--surface)] text-[var(--link)]">
              <Icon name="fa-person-skiing" className="text-xl" />
            </div>
            <div className="min-w-0">
              <div className="flex flex-wrap items-center gap-2">
                <h2 className="text-lg font-bold text-[var(--foreground)]">{venue.name}</h2>
                <Chip size="sm" variant="soft" color="accent">
                  L{venue.level} {venue.levelName}
                </Chip>
                <Chip size="sm" variant="soft" color={statusChipColor(venue.status)}>
                  {venue.status === 'open' ? 'Flight Open' : venue.status === 'closed' ? 'Held' : 'Damaged'}
                </Chip>
                {windOverLimit && (
                  <Chip size="sm" variant="soft" color="danger">
                    Wind Hold {windKmh} km/h
                  </Chip>
                )}
              </div>
              <p className="mt-0.5 text-xs text-[var(--muted)]">
                {venue.est.highlight} | {venue.est.weatherNote}
              </p>
            </div>
          </div>
          <VenueStatusActions venue={venue} />
        </div>
      </Surface>

      {/* Full-Width Elevation Profile Hero */}
      <Surface variant="default" className="rounded-2xl border border-[var(--border)] p-4">
        <div className="mb-2.5 flex flex-wrap items-center justify-between gap-2">
          <span className="text-xs font-bold uppercase tracking-wider text-[var(--muted)]">
            Multi-Hill Elevation Profile
          </span>
          <span className="text-xs tabular-nums text-[var(--muted)]">
            Hill Record: <strong className="text-[var(--foreground)]">{sp.hillRecordM} m</strong>
            {sp.hasSummerMat ? ' | Plastic Summer Turf Active' : ''}
          </span>
        </div>
        <HillElevationSvg
          hills={sp.hills}
          hillRecordM={sp.hillRecordM}
          hasSummerMat={sp.hasSummerMat}
        />
      </Surface>

      {/* 3-Instrument Flight-Control Telemetry Strip */}
      <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
        {/* Instrument 1: Anemometer & Wind Safety Gauge */}
        <Surface variant="secondary" className="rounded-2xl border border-[var(--border)] p-4 flex flex-col justify-between gap-3">
          <div>
            <div className="flex items-center justify-between">
              <span className="text-xs font-bold uppercase tracking-wider text-[var(--muted)]">
                Ridge Anemometer
              </span>
              <Chip size="sm" variant="soft" color={windOverLimit ? 'danger' : 'success'}>
                {windOverLimit ? 'Red Flag Hold' : 'Green Corridor'}
              </Chip>
            </div>
            <div className="mt-2 flex items-baseline gap-2">
              <span className="text-2xl font-bold tabular-nums text-[var(--foreground)]">
                {windKmh} km/h
              </span>
              <span className="text-xs tabular-nums text-[var(--muted)]">
                / {sp.safeWindKmh} km/h max safe
              </span>
            </div>
            <div className="mt-2.5">
              <Meter
                value={windPct}
                color={windOverLimit ? 'danger' : windKmh > sp.safeWindKmh * 0.75 ? 'warning' : 'success'}
                aria-label="Crosswind Speed Gauge"
              >
                <Meter.Track><Meter.Fill /></Meter.Track>
              </Meter>
            </div>
          </div>
          <p className="text-[11px] leading-snug text-[var(--muted)]">
            Install Wind Nets to raise jump clearance threshold above gusty alpine fronts.
          </p>
        </Surface>

        {/* Instrument 2: Flight Schedule Mode */}
        <Surface variant="secondary" className="rounded-2xl border border-[var(--border)] p-4">
          <form method="POST" action={`/winter-sports/configure/${venue.id}`} className="flex h-full flex-col justify-between gap-3">
            <CsrfInput />
            <input type="hidden" name="allocation" value={allocation} />
            <div>
              <div className="mb-2 text-xs font-bold uppercase tracking-wider text-[var(--muted)]">
                Tower Flight Schedule
              </div>
              <div className="space-y-1.5">
                {sp.allocationOptions.map((opt) => {
                  const selected = allocation === opt.key;
                  return (
                    <button
                      key={opt.key}
                      type="button"
                      onClick={() => setAllocation(opt.key)}
                      className={`w-full min-h-[38px] cursor-pointer rounded-lg border px-2.5 py-1.5 text-left transition-colors ${
                        selected
                          ? 'border-[var(--link)] bg-[var(--link)]/10 text-[var(--foreground)]'
                          : 'border-[var(--border)] bg-[var(--surface)] text-[var(--muted)] hover:text-[var(--foreground)]'
                      }`}
                    >
                      <div className="text-xs font-bold">{opt.label}</div>
                      <div className="text-[10.5px] leading-tight text-[var(--muted)]">{opt.desc}</div>
                    </button>
                  );
                })}
              </div>
            </div>
            <Button type="submit" variant="primary" size="sm" className="w-full">
              Set Tower Schedule
            </Button>
          </form>
        </Surface>

        {/* Instrument 3: Spectator Gate & Economics */}
        <Surface variant="default" className="rounded-2xl border border-[var(--border)] p-4 flex flex-col justify-between gap-3">
          <div>
            <div className="mb-2 text-xs font-bold uppercase tracking-wider text-[var(--muted)]">
              Grandstand Economics
            </div>
            <CompactPriceForm venue={venue} label="Spectator" unitLabel="fan" />
            <dl className="mt-3 space-y-1.5 border-t border-[var(--border)] pt-2.5 text-xs">
              <div className="flex justify-between">
                <dt className="text-[var(--muted)]">Spectators</dt>
                <dd className="font-semibold tabular-nums text-[var(--foreground)]">
                  {venue.est.guests} / {venue.est.capacity}
                </dd>
              </div>
              <div className="flex justify-between">
                <dt className="text-[var(--muted)]">Gate + Broadcast</dt>
                <dd className="font-semibold tabular-nums text-[var(--foreground)]">
                  {venue.est.ticketText} / {venue.est.addonText}
                </dd>
              </div>
              <div className="flex justify-between">
                <dt className="text-[var(--muted)]">Daily Net</dt>
                <dd className="font-bold tabular-nums text-[var(--success)]">{venue.est.netText}</dd>
              </div>
            </dl>
          </div>
          <div className="text-[11px] tabular-nums text-[var(--muted)]">
            Draw {venue.est.visitorBoost} | Rep {venue.est.repBonus} | {venue.est.upkeepText} upkeep
          </div>
        </Surface>
      </div>

      {/* Bottom Asymmetric Split: Jump Hill Expansion Roster (7 cols) / Tower Safety Systems (5 cols) */}
      <div className="grid grid-cols-1 gap-5 lg:grid-cols-12">
        <Surface variant="secondary" className="rounded-2xl border border-[var(--border)] p-4 sm:p-5 lg:col-span-7">
          <h3 className="mb-3 text-sm font-bold text-[var(--foreground)]">Jump Hills &amp; K-Points</h3>
          <div className="space-y-2.5">
            {sp.hills.map((hill) => (
              <div
                key={hill.key}
                className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-[var(--border)] bg-[var(--surface)] px-3.5 py-3"
              >
                <div className="min-w-0 flex-1">
                  <div className="flex flex-wrap items-center gap-2">
                    <span className="text-sm font-bold text-[var(--foreground)]">{hill.name}</span>
                    <Chip size="sm" variant="soft" color="accent">
                      K{hill.kPoint} / HS{hill.maxM}
                    </Chip>
                  </div>
                  <p className="mt-0.5 text-xs text-[var(--muted)]">{hill.desc}</p>
                  <div className="mt-1 flex flex-wrap gap-3 text-[11px] tabular-nums text-[var(--muted)]">
                    <span>+{hill.capacity} fans</span>
                    <span>+{hill.draw}% draw</span>
                    <span>{hill.upkeepText}/day</span>
                  </div>
                </div>
                {hill.built ? (
                  <Chip size="sm" variant="soft" color="success">Operational</Chip>
                ) : (
                  <PostButton
                    action={`/winter-sports/expand/${venue.id}`}
                    variant="primary"
                    size="sm"
                    data={{ expansion_key: hill.key }}
                  >
                    Build Hill ({hill.costText})
                  </PostButton>
                )}
              </div>
            ))}
          </div>
        </Surface>

        <Surface variant="secondary" className="rounded-2xl border border-[var(--border)] p-4 sm:p-5 lg:col-span-5 space-y-4">
          <div>
            <div className="mb-1 flex justify-between text-xs">
              <span className="font-bold uppercase tracking-wider text-[var(--muted)]">Tower Integrity</span>
              <span className="font-bold tabular-nums text-[var(--foreground)]">{venue.condition_pct}%</span>
            </div>
            <Meter value={venue.condition_pct} color={meterColor(venue.condition_pct)} aria-label="Tower Structural Condition">
              <Meter.Track><Meter.Fill /></Meter.Track>
            </Meter>
          </div>

          <div className="space-y-2.5">
            <h3 className="text-xs font-bold uppercase tracking-wider text-[var(--muted)]">
              Tower &amp; Landing Systems
            </h3>
            {venue.addons.map((addon) => (
              <div
                key={addon.key}
                className="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-[var(--border)] bg-[var(--surface)] px-3 py-2.5"
              >
                <div className="min-w-0 flex-1">
                  <div className="flex items-center gap-1.5 text-xs font-bold text-[var(--foreground)]">
                    <Icon name={addon.icon} className="text-xs text-[var(--link)]" />
                    <span>{addon.name}</span>
                  </div>
                  <p className="mt-0.5 text-[11px] leading-snug text-[var(--muted)]">{addon.desc}</p>
                </div>
                {addon.installed ? (
                  <Chip size="sm" variant="soft" color="success">Active</Chip>
                ) : (
                  <PostButton
                    action={`/winter-sports/install-upgrade/${venue.id}`}
                    variant="primary"
                    size="sm"
                    data={{ upgrade: addon.key }}
                  >
                    {addon.costText}
                  </PostButton>
                )}
              </div>
            ))}
          </div>
        </Surface>
      </div>
    </div>
  );
}
