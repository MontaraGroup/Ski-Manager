import { useState } from 'react';
import { Button, Chip, Meter, Surface } from '@heroui/react';
import { CsrfInput, PostButton } from '@/components/Page';
import { Icon } from '@/components/Icon';
import { CompactPriceForm, meterColor, statusChipColor, VenueStatusActions } from './SharedParts';
import type { CrossCountrySpecific, HydratedVenue } from './types';

function DifficultyShape({ difficulty }: { difficulty: 'green' | 'blue' | 'black' }) {
  if (difficulty === 'green') {
    return (
      <svg viewBox="0 0 18 18" className="h-4 w-4 shrink-0" aria-label="Beginner Green Circle">
        <circle cx="9" cy="9" r="6.5" fill="var(--success)" />
      </svg>
    );
  }
  if (difficulty === 'blue') {
    return (
      <svg viewBox="0 0 18 18" className="h-4 w-4 shrink-0" aria-label="Intermediate Blue Square">
        <rect x="3" y="3" width="12" height="12" rx="1.5" fill="var(--link)" />
      </svg>
    );
  }
  return (
    <svg viewBox="0 0 18 18" className="h-4 w-4 shrink-0" aria-label="Expert Black Diamond">
      <polygon points="9,2 16,9 9,16 2,9" fill="var(--foreground)" />
    </svg>
  );
}

function NordicTrailMapSvg({
  loops,
  grooming,
  snowBaseCm,
}: {
  loops: CrossCountrySpecific['loops'];
  grooming: string;
  snowBaseCm: number | null;
}) {
  const pathMeta = [
    { d: 'M95 135 C 145 55, 250 55, 275 135 C 245 190, 145 190, 95 135 Z', lx: 190, ly: 66 },
    { d: 'M95 135 C 175 25, 425 25, 470 120 C 420 215, 190 225, 95 135 Z', lx: 355, ly: 42 },
    { d: 'M95 135 C 210 10, 585 15, 635 135 C 585 255, 220 250, 95 135 Z', lx: 560, ly: 76 },
  ];

  const colorFor = (diff: 'green' | 'blue' | 'black') => {
    if (diff === 'green') return 'var(--success)';
    if (diff === 'blue') return 'var(--link)';
    return 'var(--warning)';
  };

  return (
    <svg
      viewBox="0 0 680 265"
      className="w-full h-auto"
      role="img"
      aria-label="Topographic Cross-Country Loop Network Map"
    >
      <rect x="4" y="4" width="672" height="257" rx="16" fill="var(--surface-secondary)" stroke="var(--border)" strokeWidth="1.5" />

      {/* Topographic elevation contours */}
      <ellipse cx="430" cy="135" rx="220" ry="105" fill="none" stroke="var(--border)" strokeOpacity="0.45" strokeWidth="1" />
      <ellipse cx="450" cy="135" rx="165" ry="76" fill="none" stroke="var(--border)" strokeOpacity="0.45" strokeWidth="1" />
      <ellipse cx="470" cy="135" rx="105" ry="48" fill="none" stroke="var(--border)" strokeOpacity="0.45" strokeWidth="1" />

      {/* Nordic loops */}
      {loops.map((loop, idx) => {
        const meta = pathMeta[idx % pathMeta.length];
        const stroke = loop.built ? colorFor(loop.difficulty) : 'var(--muted)';
        return (
          <g key={loop.key}>
            <path
              d={meta.d}
              fill="none"
              stroke={stroke}
              strokeWidth={loop.built ? 4 : 2}
              strokeDasharray={loop.built ? (grooming === 'classic' ? '9 4' : undefined) : '5 5'}
              strokeOpacity={loop.built ? 0.92 : 0.45}
            />
            {loop.built && grooming === 'both' && (
              <path
                d={meta.d}
                fill="none"
                stroke="var(--surface)"
                strokeWidth="1.2"
                strokeDasharray="6 4"
              />
            )}
            <rect
              x={meta.lx - 66}
              y={meta.ly - 12}
              width="132"
              height="24"
              rx="6"
              fill="var(--surface)"
              stroke={stroke}
              strokeWidth="1"
            />
            <text
              x={meta.lx}
              y={meta.ly + 4}
              textAnchor="middle"
              fill={loop.built ? 'var(--foreground)' : 'var(--muted)'}
              fontSize="10"
              fontWeight="600"
            >
              {`${loop.name} (${loop.km} km)`}
            </text>
          </g>
        );
      })}

      {/* Trailhead Wax Shed Origin */}
      <g>
        <rect x="34" y="108" width="76" height="54" rx="10" fill="var(--surface)" stroke="var(--foreground)" strokeWidth="1.5" />
        <text x="72" y="131" textAnchor="middle" fill="var(--foreground)" fontSize="10" fontWeight="700">
          TRAILHEAD
        </text>
        <text x="72" y="147" textAnchor="middle" fill="var(--muted)" fontSize="9.5" fontWeight="600">
          {`${snowBaseCm ?? 0} cm base`}
        </text>
      </g>
    </svg>
  );
}

export function CrossCountryBlueprintSvg() {
  return (
    <svg
      viewBox="0 0 520 200"
      className="w-full h-auto"
      role="img"
      aria-label="Nordic Loop Network Blueprint"
    >
      <rect x="8" y="8" width="504" height="184" rx="14" fill="var(--surface-secondary)" stroke="var(--border)" strokeDasharray="6 4" />
      <path d="M88 100 C 130 45, 220 45, 240 100 C 215 150, 130 150, 88 100 Z" fill="none" stroke="var(--success)" strokeWidth="3" />
      <path d="M88 100 C 165 25, 360 25, 390 100 C 350 175, 175 175, 88 100 Z" fill="none" stroke="var(--link)" strokeWidth="2.5" strokeDasharray="6 4" />
      <path d="M88 100 C 200 12, 460 18, 480 100 C 460 182, 200 188, 88 100 Z" fill="none" stroke="var(--muted)" strokeWidth="1.5" strokeDasharray="4 4" />
      <rect x="38" y="82" width="60" height="36" rx="8" fill="var(--surface)" stroke="var(--border)" />
      <text x="68" y="104" textAnchor="middle" fill="var(--foreground)" fontSize="9" fontWeight="700">SHED</text>
      <text x="310" y="104" textAnchor="middle" fill="var(--foreground)" fontSize="11" fontWeight="700">
        MEADOW, RIDGE &amp; BACKCOUNTRY LOOPS
      </text>
    </svg>
  );
}

export function CrossCountryTab({ venue }: { venue: HydratedVenue }) {
  const sp = venue.specific as CrossCountrySpecific;
  const [grooming, setGrooming] = useState(sp.grooming);
  const [rentalPrice, setRentalPrice] = useState(sp.rentalPrice);

  return (
    <div className="space-y-5">
      {/* Nordic Trailhead Snow-Report Banner */}
      <Surface variant="secondary" className="rounded-2xl border border-[var(--border)] p-4 sm:p-5">
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div className="flex min-w-0 items-center gap-3">
            <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-[var(--border)] bg-[var(--surface)] text-[var(--success)]">
              <Icon name="fa-person-skiing-nordic" className="text-xl" />
            </div>
            <div className="min-w-0">
              <div className="flex flex-wrap items-center gap-2">
                <h2 className="text-lg font-bold text-[var(--foreground)]">{venue.name}</h2>
                <Chip size="sm" variant="soft" color="success">
                  {sp.totalKm.toFixed(1)} km groomed network
                </Chip>
                <Chip size="sm" variant="soft" color={statusChipColor(venue.status)}>
                  {venue.status === 'open' ? 'Trails Open' : venue.status === 'closed' ? 'Closed' : 'Damaged'}
                </Chip>
              </div>
              <p className="mt-0.5 text-xs text-[var(--muted)]">
                {venue.est.weatherNote} | {venue.est.highlight}
              </p>
            </div>
          </div>
          <VenueStatusActions venue={venue} />
        </div>

        {/* Trailhead Key Metrics Bar */}
        <div className="mt-4 grid grid-cols-2 gap-2.5 border-t border-[var(--border)] pt-3.5 sm:grid-cols-4">
          <div className="rounded-xl border border-[var(--border)] bg-[var(--surface)] px-3 py-2">
            <div className="text-[10px] font-semibold uppercase tracking-wider text-[var(--muted)]">Snow Base</div>
            <div className="mt-0.5 text-base font-bold tabular-nums text-[var(--foreground)]">{sp.snowBaseCm ?? 0} cm</div>
          </div>
          <div className="rounded-xl border border-[var(--border)] bg-[var(--surface)] px-3 py-2">
            <div className="text-[10px] font-semibold uppercase tracking-wider text-[var(--muted)]">Built Loops</div>
            <div className="mt-0.5 text-base font-bold tabular-nums text-[var(--foreground)]">
              {sp.builtKm.toFixed(1)} km {sp.mapNordicKm > 0 ? `+ ${sp.mapNordicKm.toFixed(1)} map` : ''}
            </div>
          </div>
          <div className="rounded-xl border border-[var(--border)] bg-[var(--surface)] px-3 py-2">
            <div className="text-[10px] font-semibold uppercase tracking-wider text-[var(--muted)]">Daily Skiers</div>
            <div className="mt-0.5 text-base font-bold tabular-nums text-[var(--foreground)]">
              {venue.est.guests} / {venue.est.capacity}
            </div>
          </div>
          <div className="rounded-xl border border-[var(--border)] bg-[var(--surface)] px-3 py-2">
            <div className="text-[10px] font-semibold uppercase tracking-wider text-[var(--muted)]">Net Daily Return</div>
            <div className="mt-0.5 text-base font-bold tabular-nums text-[var(--success)]">{venue.est.netText}</div>
          </div>
        </div>
      </Surface>

      {/* Main Split: Topographic Map + Trail Roster (8 cols) / Grooming Shed + Add-ons (4 cols) */}
      <div className="grid grid-cols-1 gap-5 lg:grid-cols-12">
        <div className="min-w-0 space-y-5 lg:col-span-8">
          {/* Topographic Nordic Map */}
          <Surface variant="default" className="rounded-2xl border border-[var(--border)] p-4">
            <div className="mb-2.5 flex flex-wrap items-center justify-between gap-2">
              <span className="text-xs font-bold uppercase tracking-wider text-[var(--muted)]">
                Topographic Loop Chart
              </span>
              <span className="text-xs text-[var(--muted)]">
                Dashed loops remain uncut until cleared
              </span>
            </div>
            <NordicTrailMapSvg loops={sp.loops} grooming={grooming} snowBaseCm={sp.snowBaseCm} />
          </Surface>

          {/* Trail Loop Expansion Roster Table */}
          <Surface variant="secondary" className="rounded-2xl border border-[var(--border)] p-4 sm:p-5">
            <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
              <div>
                <h3 className="text-sm font-bold text-[var(--foreground)]">Nordic Loop Roster</h3>
                <p className="text-xs text-[var(--muted)]">
                  Cut new mountain loops to expand capacity and draw endurance guests.
                </p>
              </div>
              <span className="text-xs font-medium text-[var(--muted)]">
                {sp.loops.filter((l) => l.built).length} of {sp.loops.length} loops active
              </span>
            </div>

            <div className="divide-y divide-[var(--border)] rounded-xl border border-[var(--border)] bg-[var(--surface)]">
              {sp.loops.map((loop) => (
                <div
                  key={loop.key}
                  className="flex flex-wrap items-center justify-between gap-3 px-3.5 py-3"
                >
                  <div className="flex min-w-0 flex-1 items-start gap-3">
                    <div className="mt-1">
                      <DifficultyShape difficulty={loop.difficulty} />
                    </div>
                    <div className="min-w-0">
                      <div className="flex flex-wrap items-center gap-2">
                        <span className="text-sm font-bold text-[var(--foreground)]">{loop.name}</span>
                        <span className="rounded bg-[var(--surface-secondary)] px-1.5 py-0.5 text-[11px] font-semibold tabular-nums text-[var(--foreground)]">
                          {loop.km} km
                        </span>
                      </div>
                      <p className="mt-0.5 text-xs text-[var(--muted)]">{loop.desc}</p>
                      <div className="mt-1 flex flex-wrap gap-3 text-[11px] tabular-nums text-[var(--muted)]">
                        <span>+{loop.capacity} skiers/day</span>
                        <span>+{loop.draw}% visitor draw</span>
                        <span>{loop.upkeepText}/day upkeep</span>
                      </div>
                    </div>
                  </div>
                  {loop.built ? (
                    <Chip size="sm" variant="soft" color="success">
                      Groomed &amp; Open
                    </Chip>
                  ) : (
                    <PostButton
                      action={`/winter-sports/expand/${venue.id}`}
                      variant="primary"
                      size="sm"
                      data={{ expansion_key: loop.key }}
                    >
                      Cut Loop ({loop.costText})
                    </PostButton>
                  )}
                </div>
              ))}
            </div>

            {sp.mapTrails.length > 0 && (
              <div className="mt-3 flex flex-wrap items-center gap-2 rounded-xl border border-[var(--border)] bg-[var(--surface)] px-3 py-2 text-xs">
                <span className="font-semibold text-[var(--foreground)]">Linked Map Runs:</span>
                {sp.mapTrails.map((t) => (
                  <Chip key={t.name} size="sm" variant="soft" color="accent">
                    {t.name} ({t.km} km)
                  </Chip>
                ))}
              </div>
            )}
          </Surface>
        </div>

        {/* Right Column: Grooming & Waxing Shed + Trail Pass + Support Upgrades */}
        <div className="min-w-0 space-y-5 lg:col-span-4">
          <Surface variant="secondary" className="rounded-2xl border border-[var(--border)] p-4">
            <form method="POST" action={`/winter-sports/configure/${venue.id}`} className="space-y-3.5">
              <CsrfInput />
              <div>
                <h3 className="text-sm font-bold text-[var(--foreground)]">Grooming &amp; Wax Shed</h3>
                <p className="text-xs text-[var(--muted)]">
                  Set track profile and Nordic rental rate.
                </p>
              </div>
              <input type="hidden" name="grooming" value={grooming} />
              <div className="space-y-2">
                {sp.groomingOptions.map((opt) => {
                  const selected = grooming === opt.key;
                  return (
                    <button
                      key={opt.key}
                      type="button"
                      onClick={() => setGrooming(opt.key)}
                      className={`w-full min-h-[44px] cursor-pointer rounded-xl border px-3 py-2.5 text-left transition-colors ${
                        selected
                          ? 'border-[var(--success)] bg-[var(--success)]/10 text-[var(--foreground)]'
                          : 'border-[var(--border)] bg-[var(--surface)] text-[var(--muted)] hover:text-[var(--foreground)]'
                      }`}
                    >
                      <div className="flex items-center justify-between text-xs font-bold">
                        <span>{opt.label}</span>
                        {selected && <Icon name="fa-check" className="text-xs text-[var(--success)]" />}
                      </div>
                      <div className="mt-0.5 text-[11px] leading-snug text-[var(--muted)]">{opt.desc}</div>
                    </button>
                  );
                })}
              </div>

              <div className="flex items-center justify-between gap-2 rounded-xl border border-[var(--border)] bg-[var(--surface)] px-3 py-2">
                <label htmlFor={`xc-rental-${venue.id}`} className="text-xs font-semibold text-[var(--foreground)]">
                  Wax &amp; Ski Rental
                </label>
                <div className="flex items-center gap-1">
                  <span className="text-xs text-[var(--muted)]">$</span>
                  <input
                    id={`xc-rental-${venue.id}`}
                    type="number"
                    name="rental_price"
                    value={rentalPrice}
                    min={0}
                    max={30}
                    onChange={(e) => setRentalPrice(Number(e.target.value) || 0)}
                    className="w-12 rounded border border-[var(--border)] bg-[var(--surface-secondary)] px-1.5 py-0.5 text-right text-sm font-bold tabular-nums text-[var(--foreground)] focus:outline-none"
                  />
                </div>
              </div>

              <Button type="submit" variant="primary" size="sm" className="w-full">
                Apply Grooming &amp; Rates
              </Button>
            </form>
          </Surface>

          {/* Trail Pass & Financial Summary */}
          <Surface variant="default" className="rounded-2xl border border-[var(--border)] p-4 space-y-3">
            <h3 className="text-xs font-bold uppercase tracking-wider text-[var(--muted)]">
              Trail Pass &amp; Upkeep
            </h3>
            <CompactPriceForm venue={venue} label="Day Pass" unitLabel="skier" />
            <dl className="space-y-1.5 border-t border-[var(--border)] pt-2.5 text-xs">
              <div className="flex justify-between">
                <dt className="text-[var(--muted)]">Trail Passes</dt>
                <dd className="font-semibold tabular-nums text-[var(--foreground)]">{venue.est.ticketText}</dd>
              </div>
              <div className="flex justify-between">
                <dt className="text-[var(--muted)]">Wax &amp; Rentals</dt>
                <dd className="font-semibold tabular-nums text-[var(--foreground)]">{venue.est.addonText}</dd>
              </div>
              <div className="flex justify-between">
                <dt className="text-[var(--muted)]">Upkeep</dt>
                <dd className="font-semibold tabular-nums text-[var(--danger)]">-{venue.est.upkeepText}</dd>
              </div>
              <div className="flex justify-between border-t border-[var(--border)] pt-1.5">
                <dt className="font-bold text-[var(--foreground)]">Condition</dt>
                <dd className="font-bold tabular-nums text-[var(--foreground)]">{venue.condition_pct}%</dd>
              </div>
            </dl>
            <Meter value={venue.condition_pct} color={meterColor(venue.condition_pct)} aria-label="Trail Infrastructure Condition">
              <Meter.Track><Meter.Fill /></Meter.Track>
            </Meter>
          </Surface>

          {/* Nordic Add-ons */}
          <Surface variant="secondary" className="rounded-2xl border border-[var(--border)] p-4 space-y-2.5">
            <h3 className="text-xs font-bold uppercase tracking-wider text-[var(--muted)]">
              Trailhead Outposts
            </h3>
            {venue.addons.map((addon) => (
              <div
                key={addon.key}
                className="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-[var(--border)] bg-[var(--surface)] px-3 py-2.5"
              >
                <div className="min-w-0 flex-1">
                  <div className="flex items-center gap-1.5 text-xs font-bold text-[var(--foreground)]">
                    <Icon name={addon.icon} className="text-xs text-[var(--success)]" />
                    <span>{addon.name}</span>
                  </div>
                  <div className="mt-0.5 text-[11px] leading-snug text-[var(--muted)]">{addon.desc}</div>
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
          </Surface>
        </div>
      </div>
    </div>
  );
}
