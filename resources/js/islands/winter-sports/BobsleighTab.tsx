import { useState } from 'react';
import { Button, Chip, Meter, Surface } from '@heroui/react';
import { CsrfInput, PostButton } from '@/components/Page';
import { Icon } from '@/components/Icon';
import { CompactPriceForm, meterColor, statusChipColor, VenueStatusActions } from './SharedParts';
import type { BobsleighSpecific, HydratedVenue } from './types';

function BobsleighChuteSvg({
  tempC,
  topSpeedKmh,
  hasSunShades,
  cooling,
}: {
  tempC: number | null;
  topSpeedKmh: number;
  hasSunShades: boolean;
  cooling: string;
}) {
  const effectiveTemp = tempC ?? -4;
  const iceCold = effectiveTemp <= -2;
  const trackStroke = iceCold ? 'var(--link)' : effectiveTemp <= 2 ? 'var(--warning)' : 'var(--danger)';

  return (
    <svg
      viewBox="0 0 520 320"
      className="w-full h-auto"
      role="img"
      aria-label="S-Curve Bobsleigh Refrigerated Track Diagram"
    >
      <rect x="4" y="4" width="512" height="312" rx="16" fill="var(--surface-secondary)" stroke="var(--border)" strokeWidth="1.5" />

      {/* Outer refrigeration concrete shell */}
      <path
        d="M 70 52 C 250 48, 170 142, 330 132 C 455 124, 445 215, 250 222 C 95 228, 130 282, 445 278"
        fill="none"
        stroke="var(--border)"
        strokeWidth="34"
        strokeLinecap="round"
      />

      {/* Active ice chute surface */}
      <path
        d="M 70 52 C 250 48, 170 142, 330 132 C 455 124, 445 215, 250 222 C 95 228, 130 282, 445 278"
        fill="none"
        stroke={trackStroke}
        strokeOpacity="0.32"
        strokeWidth="22"
        strokeLinecap="round"
      />

      {/* Racing line */}
      <path
        d="M 70 52 C 250 48, 170 142, 330 132 C 455 124, 445 215, 250 222 C 95 228, 130 282, 445 278"
        fill="none"
        stroke={trackStroke}
        strokeWidth="2.5"
        strokeDasharray="8 5"
      />

      {/* Sunshade canopy roofs over exposed curves */}
      {hasSunShades && (
        <g opacity="0.85">
          <rect x="275" y="106" width="142" height="52" rx="14" fill="var(--surface)" fillOpacity="0.65" stroke="var(--success)" strokeDasharray="4 3" strokeWidth="1.5" />
          <rect x="115" y="198" width="150" height="48" rx="14" fill="var(--surface)" fillOpacity="0.65" stroke="var(--success)" strokeDasharray="4 3" strokeWidth="1.5" />
        </g>
      )}

      {/* Curve badges */}
      <g>
        <rect x="28" y="20" width="96" height="22" rx="6" fill="var(--surface)" stroke="var(--border)" />
        <text x="76" y="34" textAnchor="middle" fill="var(--foreground)" fontSize="9.5" fontWeight="700">
          START HOUSE
        </text>
      </g>
      <g>
        <rect x="336" y="84" width="110" height="22" rx="6" fill="var(--surface)" stroke={trackStroke} />
        <text x="391" y="98" textAnchor="middle" fill="var(--foreground)" fontSize="9.5" fontWeight="700">
          KREISEL 270 BANK
        </text>
      </g>
      <g>
        <rect x="348" y="242" width="136" height="22" rx="6" fill="var(--surface)" stroke="var(--border)" />
        <text x="416" y="256" textAnchor="middle" fill="var(--foreground)" fontSize="9.5" fontWeight="700">
          {`FINISH (${topSpeedKmh} km/h)`}
        </text>
      </g>

      {/* Bottom Cryo Telemetry Strip inside SVG */}
      <rect x="22" y="286" width="210" height="22" rx="6" fill="var(--surface)" stroke={trackStroke} />
      <text x="127" y="301" textAnchor="middle" fill="var(--foreground)" fontSize="10" fontWeight="700">
        {`SLAB ${tempC !== null ? `${tempC}°C` : 'N/A'} | COOLING: ${cooling.toUpperCase()}`}
      </text>
    </svg>
  );
}

export function BobsleighBlueprintSvg() {
  return (
    <svg
      viewBox="0 0 520 200"
      className="w-full h-auto"
      role="img"
      aria-label="Bobsleigh Chute Blueprint"
    >
      <rect x="8" y="8" width="504" height="184" rx="14" fill="var(--surface-secondary)" stroke="var(--border)" strokeDasharray="6 4" />
      <path
        d="M 55 44 C 210 40, 175 110, 315 100 C 440 92, 430 162, 235 162 C 125 162, 175 176, 465 168"
        fill="none"
        stroke="var(--link)"
        strokeWidth="20"
        strokeOpacity="0.2"
        strokeLinecap="round"
      />
      <path
        d="M 55 44 C 210 40, 175 110, 315 100 C 440 92, 430 162, 235 162 C 125 162, 175 176, 465 168"
        fill="none"
        stroke="var(--link)"
        strokeWidth="2.5"
        strokeDasharray="6 4"
      />
      <text x="260" y="30" textAnchor="middle" fill="var(--foreground)" fontSize="10.5" fontWeight="700">
        AMMONIA-COOLED S-CURVE CHUTE + TIMING GATES
      </text>
    </svg>
  );
}

export function BobsleighTab({ venue }: { venue: HydratedVenue }) {
  const sp = venue.specific as BobsleighSpecific;
  const [mode, setMode] = useState(sp.mode);
  const [cooling, setCooling] = useState(sp.cooling);
  const iceAtRisk = sp.tempC !== null && sp.tempC > 0;

  return (
    <div className="space-y-5">
      {/* Control Room Header + Dense 5-Metric Engineering Console Strip */}
      <Surface variant="secondary" className="overflow-hidden rounded-2xl border border-[var(--border)]">
        <div className="flex flex-wrap items-center justify-between gap-4 border-b border-[var(--border)] p-4 sm:p-5">
          <div className="flex min-w-0 items-center gap-3">
            <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-[var(--border)] bg-[var(--surface)] text-[var(--link)]">
              <Icon name="fa-sled" className="text-xl" />
            </div>
            <div className="min-w-0">
              <div className="flex flex-wrap items-center gap-2">
                <h2 className="text-lg font-bold text-[var(--foreground)]">{venue.name}</h2>
                <Chip size="sm" variant="soft" color="accent">
                  L{venue.level} {venue.levelName}
                </Chip>
                <Chip size="sm" variant="soft" color={statusChipColor(venue.status)}>
                  {venue.status === 'open' ? 'Track Live' : venue.status === 'closed' ? 'Closed' : 'Damaged'}
                </Chip>
              </div>
              <p className="mt-0.5 text-xs text-[var(--muted)]">
                {venue.est.highlight} | {venue.est.weatherNote}
              </p>
            </div>
          </div>
          <VenueStatusActions venue={venue} showResurface resurfaceLabel="Re-ice Track" />
        </div>

        {/* 5-Cell Telemetry Console */}
        <div className="grid grid-cols-2 gap-px bg-[var(--border)] sm:grid-cols-5">
          <div className="bg-[var(--surface)] px-3.5 py-2.5">
            <div className="text-[10px] font-bold uppercase tracking-wider text-[var(--muted)]">Chute Ice Temp</div>
            <div className={`mt-0.5 text-lg font-bold tabular-nums ${iceAtRisk ? 'text-[var(--danger)]' : 'text-[var(--link)]'}`}>
              {sp.tempC !== null ? `${sp.tempC}°C` : 'N/A'}
            </div>
          </div>
          <div className="bg-[var(--surface)] px-3.5 py-2.5">
            <div className="text-[10px] font-bold uppercase tracking-wider text-[var(--muted)]">Peak Trap Speed</div>
            <div className="mt-0.5 text-lg font-bold tabular-nums text-[var(--foreground)]">
              {sp.topSpeedKmh} km/h
            </div>
          </div>
          <div className="bg-[var(--surface)] px-3.5 py-2.5">
            <div className="text-[10px] font-bold uppercase tracking-wider text-[var(--muted)]">Cryo Load</div>
            <div className="mt-0.5 text-lg font-bold tabular-nums text-[var(--foreground)]">
              {venue.est.kwh} kWh
            </div>
          </div>
          <div className="bg-[var(--surface)] px-3.5 py-2.5">
            <div className="text-[10px] font-bold uppercase tracking-wider text-[var(--muted)]">Ice Quality</div>
            <div className="mt-0.5 text-lg font-bold tabular-nums text-[var(--foreground)]">
              {venue.ice_quality}%
            </div>
          </div>
          <div className="col-span-2 bg-[var(--surface)] px-3.5 py-2.5 sm:col-span-1">
            <div className="text-[10px] font-bold uppercase tracking-wider text-[var(--muted)]">Projected Net</div>
            <div className="mt-0.5 text-lg font-bold tabular-nums text-[var(--success)]">
              {venue.est.netText}
            </div>
          </div>
        </div>
      </Surface>

      {/* 2-Column Control Room: S-Curve Chute Diagram (6 cols) / Dispatch & Ammonia Throttle Console (6 cols) */}
      <div className="grid grid-cols-1 gap-5 lg:grid-cols-12">
        <div className="min-w-0 space-y-5 lg:col-span-6">
          <Surface variant="default" className="rounded-2xl border border-[var(--border)] p-4">
            <div className="mb-2.5 flex items-center justify-between gap-2">
              <span className="text-xs font-bold uppercase tracking-wider text-[var(--muted)]">
                Chute Thermals &amp; Racing Line
              </span>
              <Chip size="sm" variant="soft" color={sp.hasSunShades ? 'success' : 'warning'}>
                {sp.hasSunShades ? 'Sunshades Fitted' : 'Exposed Curves'}
              </Chip>
            </div>
            <BobsleighChuteSvg
              tempC={sp.tempC}
              topSpeedKmh={sp.topSpeedKmh}
              hasSunShades={sp.hasSunShades}
              cooling={cooling}
            />
          </Surface>

          {/* Slab Condition & Gate Form */}
          <Surface variant="secondary" className="rounded-2xl border border-[var(--border)] p-4 space-y-3">
            <CompactPriceForm venue={venue} label="Ride / Pass" unitLabel="guest" />
            <div className="grid grid-cols-1 gap-3 border-t border-[var(--border)] pt-3 sm:grid-cols-2">
              <div>
                <div className="mb-1 flex justify-between text-xs">
                  <span className="font-medium text-[var(--muted)]">Glaze Density</span>
                  <span className="font-bold tabular-nums text-[var(--foreground)]">{venue.ice_quality}%</span>
                </div>
                <Meter value={venue.ice_quality} color={meterColor(venue.ice_quality)} aria-label="Chute Ice Quality">
                  <Meter.Track><Meter.Fill /></Meter.Track>
                </Meter>
              </div>
              <div>
                <div className="mb-1 flex justify-between text-xs">
                  <span className="font-medium text-[var(--muted)]">Chute Concrete</span>
                  <span className="font-bold tabular-nums text-[var(--foreground)]">{venue.condition_pct}%</span>
                </div>
                <Meter value={venue.condition_pct} color={meterColor(venue.condition_pct)} aria-label="Concrete Integrity">
                  <Meter.Track><Meter.Fill /></Meter.Track>
                </Meter>
              </div>
            </div>
            <div className="flex flex-wrap justify-between gap-2 text-xs tabular-nums text-[var(--muted)]">
              <span>Gate {venue.est.ticketText} | Rides {venue.est.addonText}</span>
              <span>Upkeep {venue.est.upkeepText} | Draw {venue.est.visitorBoost}</span>
            </div>
          </Surface>
        </div>

        {/* Right Control Console: Dispatch Mode + 3-Step Ammonia Cooling Throttle */}
        <div className="min-w-0 lg:col-span-6">
          <Surface variant="secondary" className="h-full rounded-2xl border border-[var(--border)] p-4 sm:p-5">
            <form method="POST" action={`/winter-sports/configure/${venue.id}`} className="space-y-4">
              <CsrfInput />
              <input type="hidden" name="mode" value={mode} />
              <input type="hidden" name="cooling" value={cooling} />

              <div>
                <h3 className="text-sm font-bold text-[var(--foreground)]">Track Dispatch Mode</h3>
                <p className="text-xs text-[var(--muted)]">
                  Balance public taxi-bob thrills against elite athlete training and prestige.
                </p>
                <div className="mt-2.5 grid grid-cols-1 gap-2">
                  {sp.modeOptions.map((opt) => {
                    const selected = mode === opt.key;
                    return (
                      <button
                        key={opt.key}
                        type="button"
                        onClick={() => setMode(opt.key)}
                        className={`min-h-[44px] cursor-pointer rounded-xl border px-3 py-2.5 text-left transition-colors ${
                          selected
                            ? 'border-[var(--link)] bg-[var(--link)]/10 text-[var(--foreground)]'
                            : 'border-[var(--border)] bg-[var(--surface)] text-[var(--muted)] hover:text-[var(--foreground)]'
                        }`}
                      >
                        <div className="flex items-center justify-between text-xs font-bold">
                          <span>{opt.label}</span>
                          {selected && <Icon name="fa-check" className="text-xs text-[var(--link)]" />}
                        </div>
                        <div className="mt-0.5 text-[11px] leading-snug text-[var(--muted)]">{opt.desc}</div>
                      </button>
                    );
                  })}
                </div>
              </div>

              <div className="border-t border-[var(--border)] pt-4">
                <h3 className="text-sm font-bold text-[var(--foreground)]">Ammonia Cooling Throttle</h3>
                <p className="text-xs text-[var(--muted)]">
                  Set chiller plant stage to hold fast racing ice through warm afternoon highs.
                </p>
                <div className="mt-2.5 grid grid-cols-1 gap-2 sm:grid-cols-3">
                  {sp.coolingOptions.map((opt) => {
                    const selected = cooling === opt.key;
                    return (
                      <button
                        key={opt.key}
                        type="button"
                        onClick={() => setCooling(opt.key)}
                        className={`flex min-h-[44px] cursor-pointer flex-col justify-between rounded-xl border p-3 text-left transition-colors ${
                          selected
                            ? 'border-[var(--link)] bg-[var(--link)]/10 text-[var(--foreground)]'
                            : 'border-[var(--border)] bg-[var(--surface)] text-[var(--muted)] hover:text-[var(--foreground)]'
                        }`}
                      >
                        <div>
                          <div className="text-xs font-bold">{opt.label}</div>
                          <div className="mt-0.5 text-[10.5px] leading-tight text-[var(--muted)]">{opt.desc}</div>
                        </div>
                        <div className="mt-2 border-t border-[var(--border)] pt-1.5 text-[10.5px] font-semibold tabular-nums text-[var(--foreground)]">
                          Max {opt.maxTemp}°C | {opt.costText}/d
                        </div>
                      </button>
                    );
                  })}
                </div>
              </div>

              <Button type="submit" variant="primary" size="md" className="w-full">
                Engage Track Configuration
              </Button>
            </form>
          </Surface>
        </div>
      </div>

      {/* Bottom Engineering Upgrades Strip */}
      <Surface variant="secondary" className="rounded-2xl border border-[var(--border)] p-4 sm:p-5">
        <h3 className="mb-3 text-xs font-bold uppercase tracking-wider text-[var(--muted)]">
          Chute Engineering Upgrades
        </h3>
        <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
          {venue.addons.map((addon) => (
            <div
              key={addon.key}
              className="flex flex-col justify-between gap-2.5 rounded-xl border border-[var(--border)] bg-[var(--surface)] p-3.5"
            >
              <div>
                <div className="flex items-center justify-between gap-2">
                  <span className="flex items-center gap-1.5 text-xs font-bold text-[var(--foreground)]">
                    <Icon name={addon.icon} className="text-xs text-[var(--link)]" />
                    {addon.name}
                  </span>
                  {addon.installed && (
                    <Chip size="sm" variant="soft" color="success">Online</Chip>
                  )}
                </div>
                <p className="mt-1 text-[11px] leading-snug text-[var(--muted)]">{addon.desc}</p>
              </div>
              <div className="flex items-center justify-between gap-2 border-t border-[var(--border)] pt-2">
                <span className="text-[11px] tabular-nums text-[var(--muted)]">
                  {addon.upkeepText}/day {addon.kwh > 0 ? `| +${addon.kwh} kWh` : ''}
                </span>
                {!addon.installed && (
                  <PostButton
                    action={`/winter-sports/install-upgrade/${venue.id}`}
                    variant="primary"
                    size="sm"
                    data={{ upgrade: addon.key }}
                  >
                    Install ({addon.costText})
                  </PostButton>
                )}
              </div>
            </div>
          ))}
        </div>
      </Surface>
    </div>
  );
}
