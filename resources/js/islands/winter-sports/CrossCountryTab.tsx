import { useState } from 'react';
import { Button, Chip, Surface } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput } from '@/components/Page';
import { AddonsGrid, VenueHeader } from './SharedParts';
import type { ActivityTab, CrossCountrySpecific, HydratedVenue } from './types';

const DIFF_BADGE: Record<string, { label: string; dotClass: string }> = {
  green: { label: 'Green', dotClass: 'bg-success' },
  blue: { label: 'Blue', dotClass: 'bg-link' },
  black: { label: 'Black', dotClass: 'bg-foreground' },
};

export function CrossCountryTab({ tab, v, cash }: { tab: ActivityTab; v: HydratedVenue; cash: number }) {
  const spec = v.specific as CrossCountrySpecific;
  const [grooming, setGrooming] = useState(spec.grooming);
  const [rentalPrice, setRentalPrice] = useState(String(spec.rentalPrice));

  return (
    <Surface className="flex flex-col gap-5 rounded-xl border border-border p-4 sm:p-5">
      <VenueHeader tab={tab} v={v} cash={cash} />

      {/* Nordic Loop Network & Map Trails */}
      <div className="flex flex-col gap-3 border-t border-border pt-4">
        <div className="flex flex-wrap items-center justify-between gap-2">
          <div>
            <h3 className="text-sm font-semibold">Nordic Trail Network ({spec.totalKm} km open)</h3>
            <p className="text-xs text-muted">
              Expand loop trails for higher capacity &amp; draw. Any cross-country runs built on the Trail Map also connect automatically.
            </p>
          </div>
          {spec.snowBaseCm !== null && (
            <Chip size="sm" variant="soft" color={spec.snowBaseCm >= 20 || spec.hasSnowSupport ? 'success' : 'warning'}>
              <Icon name="fa-snowflake" /> Snow base: {spec.snowBaseCm} cm
            </Chip>
          )}
        </div>

        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
          {spec.loops.map((loop) => {
            const d = DIFF_BADGE[loop.difficulty] ?? DIFF_BADGE.green;
            return (
              <div
                key={loop.key}
                className={`flex flex-col justify-between gap-2.5 rounded-xl border p-3.5 text-sm ${
                  loop.built ? 'border-success/40 bg-success/5' : 'border-border bg-default/40'
                }`}
              >
                <div>
                  <div className="flex items-center justify-between gap-2">
                    <span className="flex items-center gap-2 font-semibold">
                      <span className={`inline-block size-2.5 rounded-full ${d.dotClass}`} title={d.label} />
                      {loop.name}
                    </span>
                    <Chip size="sm" variant="soft" color={loop.built ? 'success' : 'default'}>
                      {loop.km} km
                    </Chip>
                  </div>
                  <p className="mt-1 text-xs text-muted">{loop.desc}</p>
                </div>
                <div className="flex items-center justify-between text-xs text-muted">
                  <span className="tabular-nums">
                    +{loop.capacity} cap · +{loop.draw} draw · {loop.upkeepText}/day
                  </span>
                  {loop.built ? (
                    <span className="font-semibold text-success">Open</span>
                  ) : (
                    <form action={`/winter-sports/expand/${v.id}`} method="post">
                      <CsrfInput />
                      <input type="hidden" name="expansion_key" value={loop.key} />
                      <Button type="submit" size="sm" variant="primary" isDisabled={cash < loop.cost}>
                        Cut Loop ({loop.costText})
                      </Button>
                    </form>
                  )}
                </div>
              </div>
            );
          })}
        </div>

        {spec.mapTrails.length > 0 && (
          <div className="rounded-lg border border-border bg-default/30 px-3 py-2 text-xs text-muted">
            <span className="font-semibold text-foreground">Connected Trail Map Nordic runs ({spec.mapNordicKm} km):</span>{' '}
            {spec.mapTrails.map((t) => `${t.name} (${t.km} km)`).join(', ')}
          </div>
        )}
      </div>

      {/* Grooming Style & Nordic Rental Pricing */}
      <form action={`/winter-sports/configure/${v.id}`} method="post" className="flex flex-col gap-3 border-t border-border pt-4">
        <CsrfInput />
        <div className="text-xs font-semibold uppercase tracking-wide text-muted">Track Grooming Setting</div>
        <div className="grid grid-cols-1 gap-2.5 sm:grid-cols-3">
          {spec.groomingOptions.map((g) => (
            <label
              key={g.key}
              className={`flex cursor-pointer flex-col gap-1 rounded-lg border p-3 text-xs ${
                grooming === g.key ? 'border-link bg-link/10' : 'border-border bg-default/40'
              }`}
            >
              <div className="flex items-center justify-between font-semibold">
                <span>{g.label}</span>
                <input
                  type="radio"
                  name="grooming"
                  value={g.key}
                  checked={grooming === g.key}
                  onChange={() => setGrooming(g.key)}
                />
              </div>
              <span className="text-muted">{g.desc}</span>
            </label>
          ))}
        </div>

        <div className="flex flex-wrap items-center justify-between gap-3">
          {spec.hasWaxBench ? (
            <div className="flex items-center gap-2">
              <label htmlFor={`xc-rental-${v.id}`} className="text-xs text-muted">
                Nordic gear &amp; wax fee (5-40)
              </label>
              <input
                id={`xc-rental-${v.id}`}
                name="rental_price"
                type="number"
                min={5}
                max={40}
                value={rentalPrice}
                onChange={(e) => setRentalPrice(e.target.value)}
                className="h-9 w-20 rounded-lg border border-border bg-background px-2 text-sm font-semibold tabular-nums"
              />
            </div>
          ) : (
            <span className="text-xs text-muted">Install the Nordic Rental &amp; Wax Bench below to rent skis and wax.</span>
          )}
          <Button type="submit" size="sm" variant="primary">
            Apply Grooming Settings
          </Button>
        </div>
      </form>

      <AddonsGrid venueId={v.id} addons={v.addons} cash={cash} title="Trailhead Lodge & Nordic Upgrades" />
    </Surface>
  );
}
