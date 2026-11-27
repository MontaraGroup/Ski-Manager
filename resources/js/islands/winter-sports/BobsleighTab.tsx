import { useState } from 'react';
import { Button, Chip, Surface } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput } from '@/components/Page';
import { AddonsGrid, VenueHeader } from './SharedParts';
import type { ActivityTab, BobsleighSpecific, HydratedVenue } from './types';

export function BobsleighTab({ tab, v, cash }: { tab: ActivityTab; v: HydratedVenue; cash: number }) {
  const spec = v.specific as BobsleighSpecific;
  const [mode, setMode] = useState(spec.mode);
  const [cooling, setCooling] = useState(spec.cooling);

  return (
    <Surface className="flex flex-col gap-5 rounded-xl border border-border p-4 sm:p-5">
      <VenueHeader tab={tab} v={v} cash={cash} />

      {/* Top Speed & Cooling Status */}
      <div className="grid grid-cols-1 gap-3 border-t border-border pt-4 sm:grid-cols-2">
        <div className="flex items-center justify-between rounded-xl border border-border bg-default/40 p-3.5">
          <div>
            <div className="text-xs text-muted">Track Top Speed Rating</div>
            <div className="mt-0.5 text-base font-semibold tabular-nums text-link">{spec.topSpeedKmh} km/h</div>
          </div>
          <Chip size="sm" variant="soft" color="accent">
            <Icon name="fa-gauge-high" /> Fast Ice
          </Chip>
        </div>
        <div className="flex items-center justify-between rounded-xl border border-border bg-default/40 p-3.5">
          <div>
            <div className="text-xs text-muted">Outdoor Temp vs. Cooling</div>
            <div className="mt-0.5 text-base font-semibold tabular-nums">
              {spec.tempC !== null ? `${spec.tempC}°C` : 'Cold'}
            </div>
          </div>
          <Chip size="sm" variant="soft" color={spec.hasSunShades ? 'success' : 'default'}>
            <Icon name="fa-bolt" /> {v.est.kwh} kWh/day
          </Chip>
        </div>
      </div>

      <form action={`/winter-sports/configure/${v.id}`} method="post" className="flex flex-col gap-4 border-t border-border pt-4">
        <CsrfInput />
        <div>
          <div className="mb-2 text-xs font-semibold uppercase tracking-wide text-muted">Sliding Operations Mix</div>
          <div className="grid grid-cols-1 gap-2.5 sm:grid-cols-3">
            {spec.modeOptions.map((m) => (
              <label
                key={m.key}
                className={`flex cursor-pointer flex-col gap-1 rounded-lg border p-3 text-xs ${
                  mode === m.key ? 'border-link bg-link/10' : 'border-border bg-default/40'
                }`}
              >
                <div className="flex items-center justify-between font-semibold">
                  <span>{m.label}</span>
                  <input
                    type="radio"
                    name="mode"
                    value={m.key}
                    checked={mode === m.key}
                    onChange={() => setMode(m.key)}
                  />
                </div>
                <span className="text-muted">{m.desc}</span>
              </label>
            ))}
          </div>
        </div>

        <div>
          <div className="mb-2 text-xs font-semibold uppercase tracking-wide text-muted">Ammonia Chute Cooling Power</div>
          <div className="grid grid-cols-1 gap-2.5 sm:grid-cols-3">
            {spec.coolingOptions.map((c) => (
              <label
                key={c.key}
                className={`flex cursor-pointer flex-col gap-1 rounded-lg border p-3 text-xs ${
                  cooling === c.key ? 'border-link bg-link/10' : 'border-border bg-default/40'
                }`}
              >
                <div className="flex items-center justify-between font-semibold">
                  <span>{c.label}</span>
                  <input
                    type="radio"
                    name="cooling"
                    value={c.key}
                    checked={cooling === c.key}
                    onChange={() => setCooling(c.key)}
                  />
                </div>
                <span className="font-medium tabular-nums text-foreground">
                  {c.costText}/day · {c.kwh} kWh · up to {c.maxTemp}°C
                </span>
                <span className="text-muted">{c.desc}</span>
              </label>
            ))}
          </div>
        </div>

        <div className="flex justify-end">
          <Button type="submit" size="sm" variant="primary">
            Save Sliding Operations
          </Button>
        </div>
      </form>

      <AddonsGrid venueId={v.id} addons={v.addons} cash={cash} title="Chute Fleet & Cooling Upgrades" />
    </Surface>
  );
}
