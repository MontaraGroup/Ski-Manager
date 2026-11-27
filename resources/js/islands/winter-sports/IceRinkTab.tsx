import { useState } from 'react';
import { Button, Chip, Meter, Surface } from '@heroui/react';
import { CsrfInput, PostButton } from '@/components/Page';
import { Icon } from '@/components/Icon';
import { CompactPriceForm, meterColor, statusChipColor, VenueStatusActions } from './SharedParts';
import type { HydratedVenue, IceRinkSpecific } from './types';

function RinkSchematicSvg({
  iceQuality,
  hasLights,
  hasSkateShop,
  hasChiller,
  slots,
  rentalPrice,
}: {
  iceQuality: number;
  hasLights: boolean;
  hasSkateShop: boolean;
  hasChiller: boolean;
  slots: IceRinkSpecific['slots'];
  rentalPrice: number;
}) {
  const sheenOpacity = Math.max(0.18, Math.min(0.9, iceQuality / 100));
  return (
    <svg
      viewBox="0 0 640 245"
      className="w-full h-auto"
      role="img"
      aria-label="Top-down schematic of the Alpine Village Ice Rink"
    >
      <rect
        x="4"
        y="4"
        width="632"
        height="237"
        rx="20"
        fill="var(--surface-secondary)"
        stroke="var(--border)"
        strokeWidth="1.5"
      />

      {/* Plaza walkway apron */}
      <rect
        x="24"
        y="22"
        width="592"
        height="168"
        rx="68"
        fill="var(--surface)"
        stroke="var(--border)"
        strokeDasharray={hasLights ? undefined : '4 4'}
        strokeWidth="1.5"
      />

      {/* Ice sheet */}
      <rect
        x="42"
        y="36"
        width="556"
        height="140"
        rx="56"
        fill="var(--link)"
        fillOpacity={0.08 + sheenOpacity * 0.14}
        stroke="var(--link)"
        strokeWidth="2"
      />

      {/* Zamboni resurfacing lanes */}
      <path
        d="M110 56 H530 M95 82 H545 M90 106 H550 M95 130 H545 M110 156 H530"
        stroke="var(--link)"
        strokeOpacity={sheenOpacity * 0.35}
        strokeWidth="3"
        strokeLinecap="round"
      />

      {/* Rink markings: center & blue lines + faceoff rings */}
      <line x1="320" y1="38" x2="320" y2="174" stroke="var(--danger)" strokeOpacity="0.65" strokeWidth="2.5" />
      <line x1="215" y1="38" x2="215" y2="174" stroke="var(--link)" strokeOpacity="0.55" strokeWidth="2" />
      <line x1="425" y1="38" x2="425" y2="174" stroke="var(--link)" strokeOpacity="0.55" strokeWidth="2" />
      <circle cx="320" cy="106" r="24" fill="none" stroke="var(--link)" strokeOpacity="0.6" strokeWidth="2" />
      <circle cx="320" cy="106" r="3" fill="var(--link)" />
      <circle cx="145" cy="75" r="14" fill="none" stroke="var(--danger)" strokeOpacity="0.4" strokeWidth="1.5" />
      <circle cx="145" cy="137" r="14" fill="none" stroke="var(--danger)" strokeOpacity="0.4" strokeWidth="1.5" />
      <circle cx="495" cy="75" r="14" fill="none" stroke="var(--danger)" strokeOpacity="0.4" strokeWidth="1.5" />
      <circle cx="495" cy="137" r="14" fill="none" stroke="var(--danger)" strokeOpacity="0.4" strokeWidth="1.5" />

      {/* Perimeter floodlight towers */}
      {[96, 320, 544].map((x) => (
        <g key={`light-top-${x}`}>
          {hasLights && (
            <polygon
              points={`${x - 24},38 ${x + 24},38 ${x},14`}
              fill="var(--warning)"
              fillOpacity="0.16"
            />
          )}
          <circle
            cx={x}
            cy="14"
            r="5"
            fill={hasLights ? 'var(--warning)' : 'var(--surface-secondary)'}
            stroke="var(--border)"
            strokeWidth="1.5"
          />
        </g>
      ))}

      {/* Skate Rental Kiosk (bottom-left plaza) */}
      <g>
        <rect
          x="32"
          y="198"
          width="172"
          height="30"
          rx="8"
          fill={hasSkateShop ? 'var(--surface)' : 'transparent'}
          stroke={hasSkateShop ? 'var(--success)' : 'var(--border)'}
          strokeDasharray={hasSkateShop ? undefined : '4 3'}
          strokeWidth="1.5"
        />
        <circle
          cx="48"
          cy="213"
          r="4"
          fill={hasSkateShop ? 'var(--success)' : 'var(--muted)'}
        />
        <text
          x="60"
          y="217"
          fill="var(--foreground)"
          fontSize="11"
          fontWeight="600"
        >
          {hasSkateShop ? `Skate Shop ($${rentalPrice}/pair)` : 'Skate Kiosk (Built-in $' + rentalPrice + ')'}
        </text>
      </g>

      {/* Chiller Plant (bottom-right plaza) */}
      <g>
        <rect
          x="436"
          y="198"
          width="172"
          height="30"
          rx="8"
          fill={hasChiller ? 'var(--surface)' : 'transparent'}
          stroke={hasChiller ? 'var(--link)' : 'var(--border)'}
          strokeDasharray={hasChiller ? undefined : '4 3'}
          strokeWidth="1.5"
        />
        <circle
          cx="452"
          cy="213"
          r="4"
          fill={hasChiller ? 'var(--link)' : 'var(--muted)'}
        />
        <text x="464" y="217" fill="var(--foreground)" fontSize="11" fontWeight="600">
          {hasChiller ? 'Sub-Slab Chiller: ON' : 'Ambient Freeze Only'}
        </text>
      </g>

      {/* Center telemetry pill */}
      <g>
        <rect
          x="220"
          y="198"
          width="200"
          height="30"
          rx="8"
          fill="var(--surface)"
          stroke="var(--border)"
          strokeWidth="1"
        />
        <text
          x="320"
          y="217"
          textAnchor="middle"
          fill="var(--muted)"
          fontSize="10.5"
          fontWeight="600"
        >
          {`Ice ${iceQuality}% | ${slots.morning} / ${slots.afternoon} / ${slots.evening}`}
        </text>
      </g>
    </svg>
  );
}

export function IceRinkBlueprintSvg() {
  return (
    <svg
      viewBox="0 0 520 200"
      className="w-full h-auto"
      role="img"
      aria-label="Ice Rink Blueprint Footprint"
    >
      <rect x="8" y="8" width="504" height="184" rx="14" fill="var(--surface-secondary)" stroke="var(--border)" strokeDasharray="6 4" />
      <rect x="44" y="34" width="432" height="132" rx="52" fill="var(--link)" fillOpacity="0.08" stroke="var(--link)" strokeWidth="2" />
      <line x1="260" y1="34" x2="260" y2="166" stroke="var(--danger)" strokeOpacity="0.5" strokeWidth="2" strokeDasharray="4 3" />
      <circle cx="260" cy="100" r="24" fill="none" stroke="var(--link)" strokeWidth="1.5" />
      <rect x="115" y="14" width="90" height="14" rx="4" fill="var(--surface)" stroke="var(--border)" />
      <text x="160" y="24" textAnchor="middle" fill="var(--muted)" fontSize="8.5" fontWeight="600">DASHERS</text>
      <rect x="315" y="14" width="90" height="14" rx="4" fill="var(--surface)" stroke="var(--border)" />
      <text x="360" y="24" textAnchor="middle" fill="var(--muted)" fontSize="8.5" fontWeight="600">CHILLER LOOP</text>
      <text x="260" y="104" textAnchor="middle" fill="var(--foreground)" fontSize="11" fontWeight="700">
        OPEN-AIR PLAZA OVAL
      </text>
    </svg>
  );
}

export function IceRinkTab({ venue }: { venue: HydratedVenue }) {
  const sp = venue.specific as IceRinkSpecific;
  const [morning, setMorning] = useState(sp.slots.morning);
  const [afternoon, setAfternoon] = useState(sp.slots.afternoon);
  const [evening, setEvening] = useState(sp.slots.evening);
  const [rentalPrice, setRentalPrice] = useState(sp.rentalPrice);

  const slotRows: {
    key: 'morning' | 'afternoon' | 'evening';
    title: string;
    hours: string;
    value: string;
    setter: (v: string) => void;
    inputName: string;
  }[] = [
    { key: 'morning', title: 'Morning Sheet', hours: '09:00 - 12:00', value: morning, setter: setMorning, inputName: 'slot_morning' },
    { key: 'afternoon', title: 'Afternoon Sheet', hours: '12:30 - 17:00', value: afternoon, setter: setAfternoon, inputName: 'slot_afternoon' },
    { key: 'evening', title: 'Evening Sheet', hours: '18:00 - 22:00', value: evening, setter: setEvening, inputName: 'slot_evening' },
  ];

  return (
    <div className="space-y-5">
      {/* Plaza Top Identity & Action Bar */}
      <Surface variant="secondary" className="rounded-2xl border border-[var(--border)] p-4 sm:p-5">
        <div className="flex flex-wrap items-center justify-between gap-4">
          <div className="flex min-w-0 items-center gap-3">
            <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-[var(--border)] bg-[var(--surface)] text-[var(--link)]">
              <Icon name="fa-person-skating" className="text-xl" />
            </div>
            <div className="min-w-0">
              <div className="flex flex-wrap items-center gap-2">
                <h2 className="text-lg font-bold text-[var(--foreground)]">{venue.name}</h2>
                <Chip size="sm" variant="soft" color="accent">
                  L{venue.level} {venue.levelName}
                </Chip>
                <Chip size="sm" variant="soft" color={statusChipColor(venue.status)}>
                  {venue.status === 'open' ? 'Open' : venue.status === 'closed' ? 'Closed' : 'Damaged'}
                </Chip>
              </div>
              <p className="mt-0.5 text-xs text-[var(--muted)]">
                {venue.est.highlight} | {venue.est.weatherNote}
              </p>
            </div>
          </div>
          <VenueStatusActions venue={venue} showResurface resurfaceLabel="Run Zamboni" />
        </div>
      </Surface>

      {/* 12-Column Asymmetric Plaza Split: Sheet Diagram + Session Board (7 cols) / Cash Ledger + Hardware (5 cols) */}
      <div className="grid grid-cols-1 gap-5 lg:grid-cols-12">
        <div className="min-w-0 space-y-5 lg:col-span-7">
          {/* Interactive Oval Rink Diagram */}
          <Surface variant="default" className="rounded-2xl border border-[var(--border)] p-4">
            <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
              <span className="text-xs font-semibold uppercase tracking-wider text-[var(--muted)]">
                Live Rink Layout
              </span>
              <span className="text-xs tabular-nums text-[var(--foreground)]">
                Expected Crowd: <strong className="font-semibold">{venue.est.guests}</strong> / {venue.est.capacity} skaters
              </span>
            </div>
            <RinkSchematicSvg
              iceQuality={venue.ice_quality}
              hasLights={sp.hasLights}
              hasSkateShop={sp.hasSkateShop}
              hasChiller={sp.hasChiller}
              slots={{ morning, afternoon, evening }}
              rentalPrice={rentalPrice}
            />
          </Surface>

          {/* 3-Slot Daily Sheet Scheduler */}
          <Surface variant="secondary" className="rounded-2xl border border-[var(--border)] p-4 sm:p-5">
            <form method="POST" action={`/winter-sports/configure/${venue.id}`} className="space-y-4">
              <CsrfInput />
              <div className="flex flex-wrap items-center justify-between gap-2">
                <div>
                  <h3 className="text-sm font-bold text-[var(--foreground)]">Daily Ice Timetable</h3>
                  <p className="text-xs text-[var(--muted)]">
                    Assign each session block. Evening Ice Disco requires perimeter floodlights.
                  </p>
                </div>
                <Chip size="sm" variant="soft" color={sp.hasLights ? 'success' : 'warning'}>
                  {sp.hasLights ? 'Floodlights Active' : 'No Floodlights'}
                </Chip>
              </div>

              <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
                {slotRows.map((slot) => (
                  <div
                    key={slot.key}
                    className="flex flex-col rounded-xl border border-[var(--border)] bg-[var(--surface)] p-3"
                  >
                    <input type="hidden" name={slot.inputName} value={slot.value} />
                    <div className="border-b border-[var(--border)] pb-2">
                      <div className="text-xs font-bold text-[var(--foreground)]">{slot.title}</div>
                      <div className="text-[11px] tabular-nums text-[var(--muted)]">{slot.hours}</div>
                    </div>
                    <div className="mt-2.5 flex flex-col gap-1.5">
                      {sp.sessionOptions.map((opt) => {
                        const disabled =
                          opt.locked || (opt.eveningOnly && slot.key !== 'evening');
                        const selected = slot.value === opt.key;
                        return (
                          <button
                            key={opt.key}
                            type="button"
                            disabled={disabled}
                            onClick={() => slot.setter(opt.key)}
                            className={`min-h-[40px] rounded-lg border px-2.5 py-1.5 text-left transition-colors ${
                              selected
                                ? 'border-[var(--link)] bg-[var(--link)]/10 text-[var(--foreground)]'
                                : 'border-[var(--border)] bg-[var(--surface-secondary)] text-[var(--muted)] hover:text-[var(--foreground)]'
                            } ${disabled ? 'cursor-not-allowed opacity-45' : 'cursor-pointer'}`}
                          >
                            <div className="flex items-center justify-between gap-1 text-xs font-semibold">
                              <span>{opt.label}</span>
                              {opt.locked && <Icon name="fa-lock" className="text-[10px]" />}
                            </div>
                            <div className="mt-0.5 text-[10px] leading-tight text-[var(--muted)]">
                              {opt.desc}
                            </div>
                          </button>
                        );
                      })}
                    </div>
                  </div>
                ))}
              </div>

              <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-[var(--border)] bg-[var(--surface)] px-3.5 py-3">
                <div className="flex flex-wrap items-center gap-2.5">
                  <label
                    htmlFor={`rink-rental-${venue.id}`}
                    className="text-xs font-semibold text-[var(--foreground)]"
                  >
                    Skate Rental Fee
                  </label>
                  <div className="flex items-center rounded-lg border border-[var(--border)] bg-[var(--surface-secondary)] px-2 py-1">
                    <span className="text-xs text-[var(--muted)]">$</span>
                    <input
                      id={`rink-rental-${venue.id}`}
                      type="number"
                      name="rental_price"
                      value={rentalPrice}
                      min={0}
                      max={25}
                      onChange={(e) => setRentalPrice(Number(e.target.value) || 0)}
                      className="w-12 bg-transparent px-1 text-right text-sm font-bold tabular-nums text-[var(--foreground)] focus:outline-none"
                    />
                    <span className="text-[11px] text-[var(--muted)]">/pair</span>
                  </div>
                  <span className="text-[11px] text-[var(--muted)]">
                    {sp.hasSkateShop ? 'Pro shop triples attachment rate' : 'Add Skate Shop to boost rentals'}
                  </span>
                </div>
                <Button type="submit" variant="primary" size="sm">
                  Save Schedule &amp; Rentals
                </Button>
              </div>
            </form>
          </Surface>
        </div>

        {/* Right Column: Paper Receipt Cash Ledger + Condition + Vertical Equipment List */}
        <div className="min-w-0 space-y-5 lg:col-span-5">
          {/* Paper-Receipt Daily Ledger */}
          <Surface variant="default" className="rounded-2xl border border-[var(--border)] p-4 sm:p-5">
            <div className="flex items-center justify-between border-b border-dashed border-[var(--border)] pb-2.5">
              <span className="text-xs font-bold uppercase tracking-wider text-[var(--foreground)]">
                Plaza Admission Register
              </span>
              <span className="text-xs tabular-nums text-[var(--muted)]">
                {venue.est.visitorBoost} resort draw
              </span>
            </div>

            <div className="mt-3 border-b border-dashed border-[var(--border)] pb-3">
              <CompactPriceForm venue={venue} label="Session Gate" unitLabel="skater" />
            </div>

            <dl className="mt-3 space-y-2 text-sm">
              <div className="flex items-center justify-between">
                <dt className="text-[var(--muted)]">Session Admissions</dt>
                <dd className="font-medium tabular-nums text-[var(--foreground)]">{venue.est.ticketText}</dd>
              </div>
              <div className="flex items-center justify-between">
                <dt className="text-[var(--muted)]">Skate Rentals &amp; cocoa</dt>
                <dd className="font-medium tabular-nums text-[var(--foreground)]">{venue.est.addonText}</dd>
              </div>
              <div className="flex items-center justify-between">
                <dt className="text-[var(--muted)]">Upkeep &amp; Chiller Power</dt>
                <dd className="font-medium tabular-nums text-[var(--danger)]">
                  -{venue.est.upkeepText} ({venue.est.kwh} kWh)
                </dd>
              </div>
              <div className="flex items-center justify-between border-t-2 border-double border-[var(--border)] pt-2">
                <dt className="font-bold text-[var(--foreground)]">Projected Daily Net</dt>
                <dd className="text-base font-bold tabular-nums text-[var(--success)]">{venue.est.netText}</dd>
              </div>
            </dl>

            <div className="mt-3 rounded-lg bg-[var(--surface-secondary)] px-3 py-2 text-[11px] tabular-nums text-[var(--muted)]">
              Yesterday: {venue.lastGuests} skaters ({venue.lastTicketText} gate + {venue.lastAddonText} shop).
              Lifetime: {venue.totalRevenueText}.
            </div>
          </Surface>

          {/* Ice Sheet & Facility Wear Meters */}
          <Surface variant="secondary" className="rounded-2xl border border-[var(--border)] p-4">
            <h3 className="mb-3 text-xs font-bold uppercase tracking-wider text-[var(--muted)]">
              Ice Sheet &amp; Structure
            </h3>
            <div className="space-y-3">
              <div>
                <div className="mb-1 flex justify-between text-xs">
                  <span className="font-medium text-[var(--foreground)]">Ice Smoothness</span>
                  <span className="font-semibold tabular-nums text-[var(--foreground)]">{venue.ice_quality}%</span>
                </div>
                <Meter value={venue.ice_quality} color={meterColor(venue.ice_quality)} aria-label="Ice Smoothness">
                  <Meter.Track><Meter.Fill /></Meter.Track>
                </Meter>
              </div>
              <div>
                <div className="mb-1 flex justify-between text-xs">
                  <span className="font-medium text-[var(--foreground)]">Boards &amp; Pavilion</span>
                  <span className="font-semibold tabular-nums text-[var(--foreground)]">{venue.condition_pct}%</span>
                </div>
                <Meter value={venue.condition_pct} color={meterColor(venue.condition_pct)} aria-label="Facility Condition">
                  <Meter.Track><Meter.Fill /></Meter.Track>
                </Meter>
              </div>
            </div>
          </Surface>

          {/* Vertical Rink Equipment List */}
          <Surface variant="secondary" className="rounded-2xl border border-[var(--border)] p-4">
            <h3 className="mb-3 text-xs font-bold uppercase tracking-wider text-[var(--muted)]">
              Rink Equipment &amp; Kiosks
            </h3>
            <div className="space-y-2.5">
              {venue.addons.map((addon) => (
                <div
                  key={addon.key}
                  className="flex flex-wrap items-center justify-between gap-2.5 rounded-xl border border-[var(--border)] bg-[var(--surface)] px-3 py-2.5"
                >
                  <div className="flex min-w-0 flex-1 items-start gap-2.5">
                    <div className="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[var(--surface-secondary)] text-[var(--link)]">
                      <Icon name={addon.icon} className="text-sm" />
                    </div>
                    <div className="min-w-0">
                      <div className="text-xs font-bold text-[var(--foreground)]">{addon.name}</div>
                      <div className="text-[11px] leading-snug text-[var(--muted)]">{addon.desc}</div>
                      <div className="mt-0.5 text-[10px] tabular-nums text-[var(--muted)]">
                        {addon.upkeepText}/day {addon.kwh > 0 ? `| +${addon.kwh} kWh` : ''}
                      </div>
                    </div>
                  </div>
                  {addon.installed ? (
                    <Chip size="sm" variant="soft" color="success">
                      Active
                    </Chip>
                  ) : (
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
              ))}
            </div>
          </Surface>
        </div>
      </div>
    </div>
  );
}
