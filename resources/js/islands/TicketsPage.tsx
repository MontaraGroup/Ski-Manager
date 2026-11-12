import { useState } from 'react';
import { Button } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput, Figure, Page, Panel } from '@/components/Page';
import { Switch } from '@/components/Switch';

export type TicketItem = {
  id: number;
  ticket_type: string;
  name: string;
  icon: string;
  desc: string;
  price: number;
  priceText: string;
  active: boolean;
  refPrice: number | null;
  refPriceText: string | null;
  mixPct: number | null;
  priceDemandPct: number | null;
};

export type TicketsPageProps = {
  v2: boolean;
  stats: {
    todayRevenueText: string;
    todayCountText: string;
    gameDay: number;
  };
  tickets: TicketItem[];
  lastTick: {
    visitorsText: string;
    perVisitText: string;
    priceDemandPct: number;
    turnedAwayText: string | null;
  } | null;
  currencySymbol: string;
  rules: {
    maxDemand: number;
    priceElasticity: number;
  };
};

export default function TicketsPage({
  v2,
  stats,
  tickets,
  lastTick,
  currencySymbol,
  rules,
}: TicketsPageProps) {
  return (
    <Page
      title="Lift tickets & pricing"
      icon="fa-ticket"
      iconTone="text-accent"
      subtitle="Set your ticket prices to attract visitors and maximize revenue"
      width="max-w-5xl"
    >
      {/* 3 Summary figures */}
      <div className="grid grid-cols-2 gap-3 md:grid-cols-3">
        <Figure label="Today's revenue" value={stats.todayRevenueText} tone="text-success" />
        <Figure label="Tickets sold today" value={stats.todayCountText} />
        <Figure label="Game day" value={stats.gameDay} />
      </div>

      {/* Ticket pricing list */}
      <Panel title="Ticket pricing">
        <p className="text-xs text-muted mb-4">
          Set prices too high and visitors stay away. Set them too low and you lose profit. Watch your visitor count to
          find the sweet spot.
        </p>

        <div className="space-y-3">
          {tickets.map((ticket) => (
            <TicketRow
              key={ticket.id}
              ticket={ticket}
              v2={v2}
              currencySymbol={currencySymbol}
              rules={rules}
            />
          ))}
        </div>
      </Panel>

      {/* How pricing works explanation */}
      {v2 ? (
        <Panel
          title={
            <span className="flex items-center gap-2">
              <Icon name="fa-lightbulb" className="text-warning" />
              How pricing works
            </span>
          }
        >
          <ul className="text-sm text-muted space-y-1.5 list-disc pl-5">
            <li>
              Each skier buys one ticket. Charging more than the normal price scares some away: at double the price, about
              4 in 10 still come. Charging less brings more, up to 1.5×.
            </li>
            <li>Revenue usually peaks close to the normal price. Watch the demand figure next to each ticket.</li>
            <li>Multi-day passes earn per day used: a weekly pass covers 6 ski days, a season pass about 25.</li>
            <li>Taking a ticket off sale loses skiers: only half of those who wanted it buy a different one.</li>
            <li>
              Skiers cannot exceed what your open lifts carry each day. When they do, extra demand is lost: build more or
              bigger lifts.
            </li>
            {lastTick && (
              <li>
                Last tick: {lastTick.visitorsText} skiers, {lastTick.perVisitText} per skier, prices kept{' '}
                {lastTick.priceDemandPct}% of demand
                {lastTick.turnedAwayText ? `, ${lastTick.turnedAwayText} turned away by lift capacity` : ''}.
              </li>
            )}
          </ul>
        </Panel>
      ) : (
        <Panel
          title={
            <span className="flex items-center gap-2">
              <Icon name="fa-lightbulb" className="text-warning" />
              Pricing guide
            </span>
          }
        >
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <thead className="border-b border-border text-xs text-muted">
                <tr>
                  <th className="pb-2 font-medium">Price range</th>
                  <th className="pb-2 font-medium">Visitor impact</th>
                  <th className="pb-2 font-medium">Revenue per visitor</th>
                  <th className="pb-2 font-medium">Best for</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                <tr>
                  <td className="py-2.5 font-semibold text-success">Low (&lt; 30 €)</td>
                  <td className="py-2.5 text-success">+++ visitors</td>
                  <td className="py-2.5 text-danger">Low</td>
                  <td className="py-2.5 text-muted">New resorts, building reputation</td>
                </tr>
                <tr>
                  <td className="py-2.5 font-semibold text-accent">Medium (30–60 €)</td>
                  <td className="py-2.5 text-accent">++ visitors</td>
                  <td className="py-2.5 text-accent">Balanced</td>
                  <td className="py-2.5 text-muted">Growing resorts, steady income</td>
                </tr>
                <tr>
                  <td className="py-2.5 font-semibold text-warning">High (60–100 €)</td>
                  <td className="py-2.5 text-warning">+ visitors</td>
                  <td className="py-2.5 text-success">High</td>
                  <td className="py-2.5 text-muted">Popular resorts with many slopes</td>
                </tr>
                <tr>
                  <td className="py-2.5 font-semibold text-danger">Premium (100 €+)</td>
                  <td className="py-2.5 text-danger">Fewer visitors</td>
                  <td className="py-2.5 text-success">Very High</td>
                  <td className="py-2.5 text-muted">Luxury 5-star resorts only</td>
                </tr>
              </tbody>
            </table>
          </div>
        </Panel>
      )}
    </Page>
  );
}

function TicketRow({
  ticket,
  v2,
  currencySymbol,
  rules,
}: {
  ticket: TicketItem;
  v2: boolean;
  currencySymbol: string;
  rules: TicketsPageProps['rules'];
}) {
  const [price, setPrice] = useState<number>(ticket.price);
  const [active, setActive] = useState<boolean>(ticket.active);

  const calculateDemand = (currentPrice: number): number => {
    if (!ticket.refPrice) return 100;
    const ratio = currentPrice / ticket.refPrice - 1;
    const d = Math.min(rules.maxDemand, Math.exp(-rules.priceElasticity * ratio));
    return Math.round(d * 100);
  };

  const currentDemand = calculateDemand(price);

  return (
    <form
      action="/tickets/update"
      method="post"
      className="flex flex-col gap-3 rounded-lg border border-border bg-default p-3 md:flex-row md:items-center"
    >
      <CsrfInput />
      <input type="hidden" name="id" value={ticket.id} />

      <div className="flex items-center gap-3 flex-1 min-w-0">
        <div className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-surface">
          <Icon name={ticket.icon} className="text-base text-accent" />
        </div>
        <div className="min-w-0">
          <div className="font-semibold text-sm text-foreground">{ticket.name}</div>
          <div className="text-xs text-muted">{ticket.desc}</div>
          {v2 && ticket.refPrice && (
            <div className="text-xs text-muted mt-0.5">
              Normal price {ticket.refPriceText} · {ticket.mixPct}% of skiers want this · demand at your price{' '}
              <span
                className={`font-semibold ${
                  currentDemand >= 100
                    ? 'text-success'
                    : currentDemand >= 70
                      ? 'text-foreground'
                      : 'text-warning'
                }`}
              >
                {currentDemand}%
              </span>
            </div>
          )}
        </div>
      </div>

      <div className="flex items-center gap-3 shrink-0 flex-wrap sm:flex-nowrap">
        <div className="flex items-center gap-1.5">
          <input
            type="number"
            name="price"
            min={0}
            max={9999}
            value={price}
            onChange={(e) => setPrice(Number(e.target.value))}
            className="w-24 min-h-11 sm:min-h-8 rounded-lg border border-border bg-surface px-2.5 py-1 text-right font-mono text-base sm:text-sm text-foreground outline-none focus:ring-1 focus:ring-accent"
          />
          <span className="text-sm font-medium text-muted">{currencySymbol}</span>
        </div>

        <Switch
          name="active"
          isSelected={active}
          onChange={setActive}
          aria-label={active ? 'On sale' : 'Off'}
          className="min-h-11 sm:min-h-8"
        >
          <span className={`inline-flex min-h-11 items-center text-xs font-medium sm:min-h-8 ${active ? 'text-success' : 'text-muted'}`}>
            {active ? 'On sale' : 'Off'}
          </span>
        </Switch>

        <Button type="submit" variant="primary" size="sm" isIconOnly aria-label="Save ticket price" className="size-11 sm:size-8 flex items-center justify-center">
          <Icon name="fa-floppy-disk" />
        </Button>
      </div>
    </form>
  );
}
