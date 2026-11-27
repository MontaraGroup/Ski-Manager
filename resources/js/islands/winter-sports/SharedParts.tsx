import type { ReactNode } from 'react';
import { Button, Chip, Surface } from '@heroui/react';
import { CsrfInput, PostButton } from '@/components/Page';
import { Icon } from '@/components/Icon';
import type { ActivityTab, HydratedVenue } from './types';

export function meterColor(pct: number): 'success' | 'warning' | 'danger' {
  if (pct >= 70) return 'success';
  if (pct >= 40) return 'warning';
  return 'danger';
}

export function statusChipColor(status: HydratedVenue['status']): 'success' | 'warning' | 'danger' {
  if (status === 'open') return 'success';
  if (status === 'closed') return 'warning';
  return 'danger';
}

export function VenueStatusActions({
  venue,
  showResurface = false,
  resurfaceLabel = 'Resurface',
  compact = false,
}: {
  venue: HydratedVenue;
  showResurface?: boolean;
  resurfaceLabel?: string;
  compact?: boolean;
}) {
  const isOpen = venue.status === 'open';
  return (
    <div className={`flex flex-wrap items-center gap-2 ${compact ? '' : 'justify-end'}`}>
      {showResurface && (
        <PostButton action={`/winter-sports/resurface/${venue.id}`} variant="secondary" size="sm">
          {resurfaceLabel} ({venue.resurfaceCostText})
        </PostButton>
      )}
      {venue.nextLevel !== null && venue.nextLevelName && (
        <PostButton action={`/winter-sports/upgrade/${venue.id}`} variant="primary" size="sm">
          Upgrade L{venue.nextLevel} ({venue.upgradeCostText})
        </PostButton>
      )}
      {venue.condition_pct < 92 && (
        <PostButton action={`/winter-sports/repair/${venue.id}`} variant="secondary" size="sm">
          Repair ({venue.repairCostText})
        </PostButton>
      )}
      <PostButton
        action={`/winter-sports/toggle/${venue.id}`}
        variant={isOpen ? 'tertiary' : 'primary'}
        size="sm"
      >
        {isOpen ? 'Close' : 'Reopen'}
      </PostButton>
      <PostButton
        action={`/winter-sports/demolish/${venue.id}`}
        variant="danger"
        size="sm"
        confirm={{
          title: `Demolish ${venue.name}?`,
          body: `Demolishing returns ${venue.salvageText} salvage (40% of construction). This cannot be undone.`,
          confirmLabel: 'Demolish Venue',
        }}
      >
        Demolish
      </PostButton>
    </div>
  );
}

export function CompactPriceForm({
  venue,
  label = 'Ticket',
  unitLabel = 'guest',
  buttonLabel = 'Set',
}: {
  venue: HydratedVenue;
  label?: string;
  unitLabel?: string;
  buttonLabel?: string;
}) {
  return (
    <form
      method="POST"
      action={`/winter-sports/price/${venue.id}`}
      className="flex flex-wrap items-center gap-2"
    >
      <CsrfInput />
      <label className="text-xs font-medium text-[var(--muted)]" htmlFor={`price-${venue.id}`}>
        {label}
      </label>
      <div className="flex items-center rounded-lg border border-[var(--border)] bg-[var(--surface-secondary)] px-2 py-1">
        <span className="text-xs text-[var(--muted)]">$</span>
        <input
          id={`price-${venue.id}`}
          type="number"
          name="ticket_price"
          defaultValue={venue.ticket_price}
          min={venue.min_price}
          max={venue.max_price}
          className="w-16 bg-transparent px-1 text-right text-sm font-semibold tabular-nums text-[var(--foreground)] focus:outline-none"
        />
        <span className="text-[11px] text-[var(--muted)]">/{unitLabel}</span>
      </div>
      <Button type="submit" size="sm" variant="secondary">
        {buttonLabel}
      </Button>
      <span className="text-[11px] tabular-nums text-[var(--muted)]">
        Range ${venue.min_price}-${venue.max_price}
      </span>
    </form>
  );
}

export function UnbuiltVenueFrame({
  tab,
  illustration,
  blueprintTitle,
  specs,
  highlightNote,
}: {
  tab: ActivityTab;
  illustration: ReactNode;
  blueprintTitle: string;
  specs: { label: string; value: string }[];
  highlightNote: string;
}) {
  return (
    <Surface variant="secondary" className="rounded-2xl border border-[var(--border)] p-5 sm:p-6">
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-12 lg:items-center">
        <div className="min-w-0 lg:col-span-6">
          <div className="mb-3 flex flex-wrap items-center gap-2">
            <Chip size="sm" variant="soft" color="accent">
              Unbuilt Blueprint
            </Chip>
            <span className="text-xs font-medium text-[var(--muted)]">{blueprintTitle}</span>
          </div>

          <div className="flex items-center gap-3">
            <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-[var(--border)] bg-[var(--surface)] text-[var(--link)]">
              <Icon name={tab.icon} className="text-xl" />
            </div>
            <div className="min-w-0">
              <h2 className="text-xl font-bold tracking-tight text-[var(--foreground)]">
                {tab.name}
              </h2>
              <p className="text-sm text-[var(--muted)]">{tab.tagline}</p>
            </div>
          </div>

          <p className="mt-3 text-sm leading-relaxed text-[var(--foreground)]">{tab.desc}</p>

          <dl className="mt-4 grid grid-cols-2 gap-2.5 sm:grid-cols-3">
            {specs.map((s) => (
              <div
                key={s.label}
                className="rounded-xl border border-[var(--border)] bg-[var(--surface)] px-3 py-2"
              >
                <dt className="text-[10px] font-semibold uppercase tracking-wider text-[var(--muted)]">
                  {s.label}
                </dt>
                <dd className="mt-0.5 text-sm font-semibold tabular-nums text-[var(--foreground)]">
                  {s.value}
                </dd>
              </div>
            ))}
          </dl>

          <div className="mt-4 rounded-xl border border-[var(--border)] bg-[var(--surface)] px-3.5 py-2.5 text-xs text-[var(--muted)]">
            <span className="font-semibold text-[var(--foreground)]">Events &amp; Perks: </span>
            {highlightNote}
          </div>

          <div className="mt-5 flex flex-wrap items-center gap-3">
            <form method="POST" action="/winter-sports/build">
              <CsrfInput />
              <input type="hidden" name="facility_type" value={tab.type} />
              <Button type="submit" variant="primary" size="md" isDisabled={!tab.canBuild}>
                Construct {tab.name} ({tab.buildCostText})
              </Button>
            </form>
            <span className="text-xs tabular-nums text-[var(--muted)]">
              Base upkeep {tab.upkeepText}/day
            </span>
          </div>
          {!tab.canBuild && (
            <p className="mt-2 text-xs text-[var(--danger)]">
              Insufficient resort funds to break ground.
            </p>
          )}
        </div>

        <div className="min-w-0 lg:col-span-6">
          <div className="rounded-2xl border border-[var(--border)] bg-[var(--surface)] p-4">
            {illustration}
            <div className="mt-3 border-t border-[var(--border)] pt-3">
              <div className="mb-1.5 text-[11px] font-semibold uppercase tracking-wider text-[var(--muted)]">
                Available Add-ons After Construction
              </div>
              <div className="flex flex-wrap gap-1.5">
                {tab.upgradePreviews.map((u) => (
                  <span
                    key={u.name}
                    className="inline-flex items-center gap-1.5 rounded-md border border-[var(--border)] bg-[var(--surface-secondary)] px-2 py-1 text-[11px] text-[var(--foreground)]"
                  >
                    <Icon name={u.icon} className="text-[11px]" />
                    <span>{u.name}</span>
                    <span className="tabular-nums text-[var(--muted)]">{u.costText}</span>
                  </span>
                ))}
              </div>
            </div>
          </div>
        </div>
      </div>
    </Surface>
  );
}
