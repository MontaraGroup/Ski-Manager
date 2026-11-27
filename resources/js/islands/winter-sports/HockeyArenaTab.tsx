import { Button, Chip, Surface } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput } from '@/components/Page';
import { AddonsGrid, VenueHeader } from './SharedParts';
import type { ActivityTab, HockeyArenaSpecific, HydratedVenue } from './types';

export function HockeyArenaTab({ tab, v, cash }: { tab: ActivityTab; v: HydratedVenue; cash: number }) {
  const spec = v.specific as HockeyArenaSpecific;

  return (
    <Surface className="flex flex-col gap-5 rounded-xl border border-border p-4 sm:p-5">
      <VenueHeader tab={tab} v={v} cash={cash} />

      <div className="flex flex-col gap-3 border-t border-border pt-4">
        <div className="flex flex-wrap items-center justify-between gap-2">
          <div>
            <h3 className="text-sm font-semibold">Matchday Booking Board</h3>
            <p className="text-xs text-muted">
              Book tonight&apos;s fixture to fill your {spec.seats.toLocaleString('en-US')}-seat bowl and earn matchday reputation. Reverts to Club Ice Rental after matchday.
            </p>
          </div>
          <div className="flex items-center gap-2">
            <Chip size="sm" variant="soft" color="accent">
              <Icon name="fa-chair" /> {spec.seats.toLocaleString('en-US')} seats
            </Chip>
            <Chip size="sm" variant="soft" color="default">
              <Icon name="fa-trophy" /> {spec.matchesHosted} matches hosted
            </Chip>
          </div>
        </div>

        <div className="grid grid-cols-1 gap-2.5 md:grid-cols-2">
          {spec.matchOptions.map((m) => (
            <div
              key={m.key}
              className={`flex flex-col justify-between gap-2.5 rounded-xl border p-3.5 text-sm ${
                m.active ? 'border-link bg-link/10' : m.locked ? 'border-border bg-default/20 opacity-75' : 'border-border bg-default/40'
              }`}
            >
              <div>
                <div className="flex items-center justify-between gap-2">
                  <span className="font-semibold">{m.name}</span>
                  {m.active ? (
                    <Chip size="sm" variant="soft" color="success">
                      Scheduled tonight
                    </Chip>
                  ) : (
                    <span className="text-xs font-medium tabular-nums text-muted">~{m.fillPct}% fill</span>
                  )}
                </div>
                <p className="mt-1 text-xs text-muted">{m.desc}</p>
              </div>

              <div className="flex items-center justify-between gap-2 text-xs">
                <span className="tabular-nums text-muted">
                  {m.rep > 0 ? `+${m.rep} Reputation` : 'Off-day steady rental'}
                </span>
                {m.locked ? (
                  <span className="font-medium text-warning">{m.lockReason}</span>
                ) : m.active ? (
                  <span className="font-semibold text-link">Booked</span>
                ) : (
                  <form action={`/winter-sports/book-match/${v.id}`} method="post">
                    <CsrfInput />
                    <input type="hidden" name="match_key" value={m.key} />
                    <Button type="submit" size="sm" variant="primary" isDisabled={cash < m.bookingCost}>
                      Book ({m.costText})
                    </Button>
                  </form>
                )}
              </div>
            </div>
          ))}
        </div>
      </div>

      <AddonsGrid venueId={v.id} addons={v.addons} cash={cash} title="Arena Commercial & Broadcast Upgrades" />
    </Surface>
  );
}
