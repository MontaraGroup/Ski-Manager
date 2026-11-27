import { useState } from 'react';
import { Button, Chip, Surface } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput } from '@/components/Page';
import { AddonsGrid, VenueHeader } from './SharedParts';
import type { ActivityTab, HydratedVenue, SkiJumpSpecific } from './types';

export function SkiJumpTab({ tab, v, cash }: { tab: ActivityTab; v: HydratedVenue; cash: number }) {
  const spec = v.specific as SkiJumpSpecific;
  const [allocation, setAllocation] = useState(spec.allocation);

  const windSafe = spec.windKmh === null || spec.windKmh < spec.safeWindKmh - 10;
  const windHold = spec.windKmh !== null && spec.windKmh >= spec.safeWindKmh;

  return (
    <Surface className="flex flex-col gap-5 rounded-xl border border-border p-4 sm:p-5">
      <VenueHeader tab={tab} v={v} cash={cash} />

      {/* Wind Safety & Hill Record Bar */}
      <div className="grid grid-cols-1 gap-3 border-t border-border pt-4 sm:grid-cols-2">
        <div className="flex items-center justify-between rounded-xl border border-border bg-default/40 p-3.5">
          <div>
            <div className="text-xs text-muted">Wind Safety Gauge</div>
            <div className="mt-0.5 text-base font-semibold tabular-nums">
              {spec.windKmh !== null ? `${spec.windKmh} km/h` : 'Calm'} (Limit: {spec.safeWindKmh} km/h)
            </div>
          </div>
          <Chip size="sm" variant="soft" color={windHold ? 'danger' : windSafe ? 'success' : 'warning'}>
            <Icon name="fa-wind" /> {windHold ? 'Wind Hold' : windSafe ? 'Clear for Takeoff' : 'Gusty'}
          </Chip>
        </div>

        <div className="flex items-center justify-between rounded-xl border border-border bg-default/40 p-3.5">
          <div>
            <div className="text-xs text-muted">Official Hill Record</div>
            <div className="mt-0.5 text-base font-semibold tabular-nums text-warning">{spec.hillRecordM} m</div>
          </div>
          <Chip size="sm" variant="soft" color={spec.hasSummerMat ? 'success' : 'default'}>
            <Icon name="fa-sun" /> {spec.hasSummerMat ? 'Year-round Plastic Mat' : 'Winter snow landing'}
          </Chip>
        </div>
      </div>

      {/* Multi-Hill Complex Builder */}
      <div className="flex flex-col gap-3">
        <div>
          <h3 className="text-sm font-semibold">Multi-Hill Jump Complex</h3>
          <p className="text-xs text-muted">
            Construct larger hills from K60 up to HS225 Ski Flying to boost spectator capacity, camp fees, and hill records.
          </p>
        </div>
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
          {spec.hills.map((h) => (
            <div
              key={h.key}
              className={`flex flex-col justify-between gap-2 rounded-xl border p-3.5 text-sm ${
                h.built ? 'border-success/40 bg-success/5' : 'border-border bg-default/40'
              }`}
            >
              <div>
                <div className="flex items-center justify-between gap-2">
                  <span className="font-semibold">{h.name}</span>
                  <Chip size="sm" variant="soft" color={h.built ? 'success' : 'default'}>
                    HS {h.maxM} m
                  </Chip>
                </div>
                <p className="mt-1 text-xs text-muted">{h.desc}</p>
              </div>
              <div className="flex items-center justify-between text-xs text-muted">
                <span className="tabular-nums">
                  +{h.capacity} stands · +{h.draw} draw · {h.upkeepText}/day
                </span>
                {h.built ? (
                  <span className="font-semibold text-success">Operational</span>
                ) : (
                  <form action={`/winter-sports/expand/${v.id}`} method="post">
                    <CsrfInput />
                    <input type="hidden" name="expansion_key" value={h.key} />
                    <Button type="submit" size="sm" variant="primary" isDisabled={cash < h.cost}>
                      Build Hill ({h.costText})
                    </Button>
                  </form>
                )}
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Daily Allocation */}
      <form action={`/winter-sports/configure/${v.id}`} method="post" className="flex flex-col gap-3 border-t border-border pt-4">
        <CsrfInput />
        <div className="text-xs font-semibold uppercase tracking-wide text-muted">Daily Ramp Allocation</div>
        <div className="grid grid-cols-1 gap-2.5 sm:grid-cols-3">
          {spec.allocationOptions.map((a) => (
            <label
              key={a.key}
              className={`flex cursor-pointer flex-col gap-1 rounded-lg border p-3 text-xs ${
                allocation === a.key ? 'border-link bg-link/10' : 'border-border bg-default/40'
              }`}
            >
              <div className="flex items-center justify-between font-semibold">
                <span>{a.label}</span>
                <input
                  type="radio"
                  name="allocation"
                  value={a.key}
                  checked={allocation === a.key}
                  onChange={() => setAllocation(a.key)}
                />
              </div>
              <span className="text-muted">{a.desc}</span>
            </label>
          ))}
        </div>
        <div className="flex justify-end">
          <Button type="submit" size="sm" variant="primary">
            Update Ramp Allocation
          </Button>
        </div>
      </form>

      <AddonsGrid venueId={v.id} addons={v.addons} cash={cash} title="In-Run, Safety & Broadcast Upgrades" />
    </Surface>
  );
}
