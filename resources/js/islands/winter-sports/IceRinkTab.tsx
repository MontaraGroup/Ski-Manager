import { useState } from 'react';
import { Button, Chip, Surface } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput } from '@/components/Page';
import { AddonsGrid, VenueHeader } from './SharedParts';
import type { ActivityTab, HydratedVenue, IceRinkSpecific } from './types';

const SLOT_META = [
  { key: 'morning', label: 'Morning Session', icon: 'fa-sun', hours: '09:00 - 12:30' },
  { key: 'afternoon', label: 'Afternoon Session', icon: 'fa-cloud-sun', hours: '13:00 - 17:00' },
  { key: 'evening', label: 'Evening Session', icon: 'fa-moon', hours: '18:00 - 22:00' },
] as const;

export function IceRinkTab({ tab, v, cash }: { tab: ActivityTab; v: HydratedVenue; cash: number }) {
  const spec = v.specific as IceRinkSpecific;
  const [slots, setSlots] = useState(spec.slots);
  const [rentalPrice, setRentalPrice] = useState(String(spec.rentalPrice));

  return (
    <Surface className="flex flex-col gap-5 rounded-xl border border-border p-4 sm:p-5">
      <VenueHeader tab={tab} v={v} cash={cash} />

      <form action={`/winter-sports/configure/${v.id}`} method="post" className="flex flex-col gap-4 border-t border-border pt-4">
        <CsrfInput />
        <div className="flex flex-wrap items-center justify-between gap-2">
          <div>
            <h3 className="text-sm font-semibold">Daily 3-Slot Ice Schedule</h3>
            <p className="text-xs text-muted">
              Balance crowd volume (Open Skate), higher-fee Lessons, Evening Ice Disco, or dedicated Ice Maintenance blocks.
            </p>
          </div>
          <Chip size="sm" variant="soft" color={spec.hasChiller ? 'success' : 'warning'}>
            <Icon name="fa-snowflake" /> {spec.hasChiller ? 'Refrigerated Ice Chiller active' : 'Unchilled outdoor ice'}
          </Chip>
        </div>

        <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
          {SLOT_META.map((slot) => {
            const current = slots[slot.key];
            return (
              <div key={slot.key} className="flex flex-col gap-2 rounded-xl border border-border bg-default/40 p-3">
                <div className="flex items-center justify-between text-xs">
                  <span className="flex items-center gap-1.5 font-semibold">
                    <Icon name={slot.icon} className="text-link" />
                    {slot.label}
                  </span>
                  <span className="tabular-nums text-muted">{slot.hours}</span>
                </div>
                <select
                  name={`slot_${slot.key}`}
                  value={current}
                  onChange={(e) => setSlots({ ...slots, [slot.key]: e.target.value })}
                  className="h-9 rounded-lg border border-border bg-background px-2.5 text-sm font-medium"
                  aria-label={slot.label}
                >
                  {spec.sessionOptions
                    .filter((opt) => !opt.eveningOnly || slot.key === 'evening')
                    .map((opt) => (
                      <option key={opt.key} value={opt.key} disabled={opt.locked}>
                        {opt.label}
                        {opt.locked ? ' (Needs String Lights)' : ''}
                      </option>
                    ))}
                </select>
                <p className="text-xs text-muted">
                  {spec.sessionOptions.find((o) => o.key === current)?.desc}
                </p>
              </div>
            );
          })}
        </div>

        <div className="flex flex-wrap items-center justify-between gap-3">
          {spec.hasSkateShop ? (
            <div className="flex items-center gap-2">
              <label htmlFor={`rental-${v.id}`} className="text-xs text-muted">
                Skate rental fee (3-30)
              </label>
              <input
                id={`rental-${v.id}`}
                name="rental_price"
                type="number"
                min={3}
                max={30}
                value={rentalPrice}
                onChange={(e) => setRentalPrice(e.target.value)}
                className="h-9 w-20 rounded-lg border border-border bg-background px-2 text-sm font-semibold tabular-nums"
              />
            </div>
          ) : (
            <span className="text-xs text-muted">Install the Skate Rental Shop below to charge skate rental fees.</span>
          )}
          <Button type="submit" size="sm" variant="primary">
            Save Ice Schedule
          </Button>
        </div>
      </form>

      <AddonsGrid venueId={v.id} addons={v.addons} cash={cash} title="Rink Equipment & Upgrades" />
    </Surface>
  );
}
