import { Chip, Meter, Surface } from '@heroui/react';
import { PostButton } from '@/components/Page';
import { Icon } from '@/components/Icon';
import { CompactPriceForm, meterColor, statusChipColor, VenueStatusActions } from './SharedParts';
import type { HockeyArenaSpecific, HydratedVenue } from './types';

function ArenaBowlSvg({
  fillPct,
  hasDasherSponsors,
  seats,
}: {
  fillPct: number;
  hasDasherSponsors: boolean;
  seats: number;
}) {
  const tierOpacity = Math.max(0.2, Math.min(0.95, fillPct / 100));
  return (
    <svg
      viewBox="0 0 420 185"
      className="w-full h-auto"
      role="img"
      aria-label="Ice Hockey Arena Bowl Crowd Fill Visualization"
    >
      {/* Outer stadium bowl */}
      <ellipse cx="210" cy="92" rx="196" ry="82" fill="var(--surface-secondary)" stroke="var(--border)" strokeWidth="1.5" />
      {/* Upper bowl seating tier */}
      <ellipse
        cx="210"
        cy="92"
        rx="172"
        ry="68"
        fill="var(--warning)"
        fillOpacity={fillPct > 65 ? tierOpacity * 0.25 : 0.06}
        stroke="var(--border)"
        strokeDasharray="4 3"
        strokeWidth="1"
      />
      {/* Lower bowl seating tier */}
      <ellipse
        cx="210"
        cy="92"
        rx="145"
        ry="54"
        fill="var(--link)"
        fillOpacity={tierOpacity * 0.3}
        stroke={hasDasherSponsors ? 'var(--warning)' : 'var(--border)'}
        strokeWidth={hasDasherSponsors ? 2.5 : 1.5}
      />

      {/* Hockey rink ice surface */}
      <rect
        x="96"
        y="52"
        width="228"
        height="80"
        rx="28"
        fill="var(--surface)"
        stroke={hasDasherSponsors ? 'var(--warning)' : 'var(--link)'}
        strokeWidth="2.5"
      />
      {/* Center red line, blue lines, goal lines */}
      <line x1="120" y1="54" x2="120" y2="130" stroke="var(--danger)" strokeOpacity="0.45" strokeWidth="1.5" />
      <line x1="170" y1="52" x2="170" y2="132" stroke="var(--link)" strokeOpacity="0.6" strokeWidth="2" />
      <line x1="210" y1="52" x2="210" y2="132" stroke="var(--danger)" strokeOpacity="0.7" strokeWidth="2.5" />
      <line x1="250" y1="52" x2="250" y2="132" stroke="var(--link)" strokeOpacity="0.6" strokeWidth="2" />
      <line x1="300" y1="54" x2="300" y2="130" stroke="var(--danger)" strokeOpacity="0.45" strokeWidth="1.5" />
      <circle cx="210" cy="92" r="14" fill="none" stroke="var(--link)" strokeWidth="1.5" />
      <circle cx="145" cy="74" r="8" fill="none" stroke="var(--danger)" strokeOpacity="0.55" strokeWidth="1.2" />
      <circle cx="145" cy="110" r="8" fill="none" stroke="var(--danger)" strokeOpacity="0.55" strokeWidth="1.2" />
      <circle cx="275" cy="74" r="8" fill="none" stroke="var(--danger)" strokeOpacity="0.55" strokeWidth="1.2" />
      <circle cx="275" cy="110" r="8" fill="none" stroke="var(--danger)" strokeOpacity="0.55" strokeWidth="1.2" />

      {/* Readout */}
      <rect x="148" y="156" width="124" height="20" rx="6" fill="var(--surface)" stroke="var(--border)" />
      <text x="210" y="170" textAnchor="middle" fill="var(--foreground)" fontSize="10" fontWeight="700">
        {`${seats.toLocaleString()} SEATS | ${fillPct}% FILL`}
      </text>
    </svg>
  );
}

export function HockeyArenaBlueprintSvg() {
  return (
    <svg
      viewBox="0 0 520 200"
      className="w-full h-auto"
      role="img"
      aria-label="Ice Hockey Arena Bowl Blueprint"
    >
      <rect x="8" y="8" width="504" height="184" rx="14" fill="var(--surface-secondary)" stroke="var(--border)" strokeDasharray="6 4" />
      <ellipse cx="260" cy="100" rx="190" ry="74" fill="var(--surface)" stroke="var(--border)" strokeWidth="1.5" />
      <ellipse cx="260" cy="100" rx="155" ry="56" fill="none" stroke="var(--link)" strokeDasharray="4 3" strokeWidth="1.5" />
      <rect x="146" y="60" width="228" height="80" rx="28" fill="var(--link)" fillOpacity="0.1" stroke="var(--link)" strokeWidth="2" />
      <line x1="260" y1="60" x2="260" y2="140" stroke="var(--danger)" strokeWidth="2" />
      <circle cx="260" cy="100" r="14" fill="none" stroke="var(--link)" strokeWidth="1.5" />
      <text x="260" y="184" textAnchor="middle" fill="var(--muted)" fontSize="9.5" fontWeight="700">
        CONCENTRIC TIERED BOWL + DASHER BOARDS
      </text>
    </svg>
  );
}

export function HockeyArenaTab({ venue }: { venue: HydratedVenue }) {
  const sp = venue.specific as HockeyArenaSpecific;
  const activeMatch = sp.matchOptions.find((m) => m.active) ?? sp.matchOptions[0];
  const fillPct = activeMatch
    ? Math.min(100, Math.max(0, activeMatch.fillPct))
    : Math.min(100, Math.round((venue.est.guests / Math.max(1, sp.seats)) * 100));

  return (
    <div className="space-y-5">
      {/* Jumbotron Scoreboard Top Panel */}
      <Surface variant="secondary" className="overflow-hidden rounded-2xl border border-[var(--border)]">
        {/* Jumbotron Header Strip */}
        <div className="flex flex-wrap items-center justify-between gap-3 border-b border-[var(--border)] bg-[var(--surface)] px-4 py-3 sm:px-5">
          <div className="flex min-w-0 items-center gap-3">
            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-[var(--border)] bg-[var(--surface-secondary)] text-[var(--warning)]">
              <Icon name="fa-hockey-puck" className="text-lg" />
            </div>
            <div className="min-w-0">
              <div className="flex flex-wrap items-center gap-2">
                <h2 className="text-lg font-bold text-[var(--foreground)]">{venue.name}</h2>
                <Chip size="sm" variant="soft" color="accent">
                  L{venue.level} {venue.levelName}
                </Chip>
                <Chip size="sm" variant="soft" color={statusChipColor(venue.status)}>
                  {venue.status === 'open' ? 'Game Ready' : venue.status === 'closed' ? 'Dark' : 'Maintenance'}
                </Chip>
              </div>
              <p className="text-xs text-[var(--muted)]">
                {venue.est.highlight} | {venue.est.weatherNote}
              </p>
            </div>
          </div>
          <VenueStatusActions venue={venue} showResurface resurfaceLabel="Clean Ice" />
        </div>

        {/* 3-Zone Jumbotron Center Display */}
        <div className="grid grid-cols-1 gap-4 p-4 sm:p-5 lg:grid-cols-12 lg:items-center">
          {/* Left Scoreboard Readout */}
          <div className="grid grid-cols-2 gap-2.5 lg:col-span-3 lg:grid-cols-1">
            <div className="rounded-xl border border-[var(--border)] bg-[var(--surface)] px-3.5 py-2.5">
              <div className="text-[10px] font-bold uppercase tracking-wider text-[var(--muted)]">
                Booked Matchup
              </div>
              <div className="mt-0.5 text-base font-bold text-[var(--foreground)]">
                {activeMatch ? activeMatch.name : 'Open Skate'}
              </div>
              <div className="text-xs tabular-nums text-[var(--muted)]">
                {sp.matchesHosted} total fixtures hosted
              </div>
            </div>
            <div className="rounded-xl border border-[var(--border)] bg-[var(--surface)] px-3.5 py-2.5">
              <div className="text-[10px] font-bold uppercase tracking-wider text-[var(--muted)]">
                Expected Crowd
              </div>
              <div className="mt-0.5 text-xl font-bold tabular-nums text-[var(--foreground)]">
                {venue.est.guests.toLocaleString()}{' '}
                <span className="text-xs font-normal text-[var(--muted)]">/ {sp.seats.toLocaleString()}</span>
              </div>
              <div className="text-xs tabular-nums text-[var(--success)]">{venue.est.repBonus} rep gain</div>
            </div>
          </div>

          {/* Center Oval Bowl Visualization */}
          <div className="min-w-0 lg:col-span-6">
            <ArenaBowlSvg
              fillPct={fillPct}
              hasDasherSponsors={sp.hasDasherSponsors}
              seats={sp.seats}
            />
          </div>

          {/* Right Rink & Bowl Health */}
          <div className="space-y-3 rounded-xl border border-[var(--border)] bg-[var(--surface)] p-3.5 lg:col-span-3">
            <div>
              <div className="mb-1 flex justify-between text-xs">
                <span className="font-medium text-[var(--muted)]">Game Ice Sheet</span>
                <span className="font-bold tabular-nums text-[var(--foreground)]">{venue.ice_quality}%</span>
              </div>
              <Meter value={venue.ice_quality} color={meterColor(venue.ice_quality)} aria-label="Game Ice Sheet">
                <Meter.Track><Meter.Fill /></Meter.Track>
              </Meter>
            </div>
            <div>
              <div className="mb-1 flex justify-between text-xs">
                <span className="font-medium text-[var(--muted)]">Bowl &amp; Boards</span>
                <span className="font-bold tabular-nums text-[var(--foreground)]">{venue.condition_pct}%</span>
              </div>
              <Meter value={venue.condition_pct} color={meterColor(venue.condition_pct)} aria-label="Bowl and Boards Condition">
                <Meter.Track><Meter.Fill /></Meter.Track>
              </Meter>
            </div>
            <div className="border-t border-[var(--border)] pt-2 text-xs tabular-nums text-[var(--muted)]">
              Yesterday: {venue.lastGuests.toLocaleString()} fans ({venue.lastTicketText})
            </div>
          </div>
        </div>
      </Surface>

      {/* Matchday Fixture Selector: Perforated Ticket-Stub Cards */}
      <Surface variant="secondary" className="rounded-2xl border border-[var(--border)] p-4 sm:p-5">
        <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
          <div>
            <h3 className="text-sm font-bold text-[var(--foreground)]">Matchday Fixture Slate</h3>
            <p className="text-xs text-[var(--muted)]">
              Book tonight&apos;s matchup to fill the bowl, lift ticket demand, and build resort prestige.
            </p>
          </div>
          <span className="text-xs font-semibold tabular-nums text-[var(--muted)]">
            {sp.seats.toLocaleString()} Seat Capacity
          </span>
        </div>

        <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
          {sp.matchOptions.map((opt) => (
            <div
              key={opt.key}
              className={`flex flex-col justify-between gap-3 rounded-xl border p-3.5 sm:flex-row sm:items-center ${
                opt.active
                  ? 'border-[var(--link)] bg-[var(--link)]/10'
                  : 'border-[var(--border)] bg-[var(--surface)]'
              } ${opt.locked ? 'opacity-65' : ''}`}
            >
              <div className="min-w-0 flex-1">
                <div className="flex flex-wrap items-center gap-2">
                  <span className="text-sm font-bold text-[var(--foreground)]">{opt.name}</span>
                  {opt.active && (
                    <Chip size="sm" variant="soft" color="accent">
                      Scheduled
                    </Chip>
                  )}
                  {opt.locked && (
                    <Chip size="sm" variant="soft" color="warning">
                      Locked
                    </Chip>
                  )}
                </div>
                <p className="mt-1 text-xs text-[var(--muted)]">{opt.desc}</p>
                <div className="mt-1.5 flex flex-wrap gap-3 text-[11px] font-medium tabular-nums text-[var(--muted)]">
                  <span>Est. Fill {opt.fillPct}%</span>
                  <span>Rep +{opt.rep}</span>
                  <span>Fee {opt.costText}</span>
                </div>
                {opt.locked && opt.lockReason && (
                  <p className="mt-1 text-[11px] font-medium text-[var(--warning)]">{opt.lockReason}</p>
                )}
              </div>

              <div className="border-t border-dashed border-[var(--border)] pt-2.5 sm:border-l sm:border-t-0 sm:pl-3.5 sm:pt-0">
                {opt.active ? (
                  <span className="inline-flex min-h-[36px] items-center gap-1.5 text-xs font-bold text-[var(--link)]">
                    <Icon name="fa-check" className="text-xs" />
                    Tonight&apos;s Card
                  </span>
                ) : (
                  <PostButton
                    action={`/winter-sports/book-match/${venue.id}`}
                    variant="primary"
                    size="sm"
                    disabled={opt.locked}
                    data={{ match_key: opt.key }}
                  >
                    Book ({opt.costText})
                  </PostButton>
                )}
              </div>
            </div>
          ))}
        </div>
      </Surface>

      {/* Bottom 2-Column Split: Box Office Gate + Dasher/Stadium Add-ons */}
      <div className="grid grid-cols-1 gap-5 lg:grid-cols-12">
        <Surface variant="default" className="rounded-2xl border border-[var(--border)] p-4 sm:p-5 lg:col-span-5">
          <h3 className="mb-3 text-xs font-bold uppercase tracking-wider text-[var(--muted)]">
            Box Office &amp; Bowl P&amp;L
          </h3>
          <CompactPriceForm venue={venue} label="Match Seat" unitLabel="seat" />
          <dl className="mt-4 space-y-2 border-t border-[var(--border)] pt-3 text-sm">
            <div className="flex justify-between">
              <dt className="text-[var(--muted)]">Ticket Gate</dt>
              <dd className="font-semibold tabular-nums text-[var(--foreground)]">{venue.est.ticketText}</dd>
            </div>
            <div className="flex justify-between">
              <dt className="text-[var(--muted)]">Concessions &amp; Jerseys</dt>
              <dd className="font-semibold tabular-nums text-[var(--foreground)]">{venue.est.addonText}</dd>
            </div>
            <div className="flex justify-between">
              <dt className="text-[var(--muted)]">Dasher Sponsors</dt>
              <dd className="font-semibold tabular-nums text-[var(--link)]">{venue.est.contractText}</dd>
            </div>
            <div className="flex justify-between">
              <dt className="text-[var(--muted)]">Ice Plant &amp; Upkeep</dt>
              <dd className="font-semibold tabular-nums text-[var(--danger)]">
                -{venue.est.upkeepText} ({venue.est.kwh} kWh)
              </dd>
            </div>
            <div className="flex justify-between border-t border-[var(--border)] pt-2">
              <dt className="font-bold text-[var(--foreground)]">Projected Net</dt>
              <dd className="text-base font-bold tabular-nums text-[var(--success)]">{venue.est.netText}</dd>
            </div>
          </dl>
        </Surface>

        <Surface variant="secondary" className="rounded-2xl border border-[var(--border)] p-4 sm:p-5 lg:col-span-7">
          <h3 className="mb-3 text-xs font-bold uppercase tracking-wider text-[var(--muted)]">
            Arena Concourse &amp; Commercial Upgrades
          </h3>
          <div className="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
            {venue.addons.map((addon) => (
              <div
                key={addon.key}
                className="flex flex-col justify-between gap-2.5 rounded-xl border border-[var(--border)] bg-[var(--surface)] p-3"
              >
                <div>
                  <div className="flex items-center justify-between gap-2">
                    <span className="flex items-center gap-1.5 text-xs font-bold text-[var(--foreground)]">
                      <Icon name={addon.icon} className="text-xs text-[var(--warning)]" />
                      {addon.name}
                    </span>
                    {addon.installed && (
                      <Chip size="sm" variant="soft" color="success">
                        Installed
                      </Chip>
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
                      Add ({addon.costText})
                    </PostButton>
                  )}
                </div>
              </div>
            ))}
          </div>
        </Surface>
      </div>
    </div>
  );
}
