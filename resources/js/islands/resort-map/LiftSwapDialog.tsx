import { useEffect, useMemo, useState } from 'react';
import { AlertDialog, Button, Spinner } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { LIFT_TYPES, formatCurrency } from './shared';

/** One system a built lift can be swapped to (LiftSwap::options on the server) */
export interface LiftSwapOption {
  lift_type: string;
  seats: number;
  capacity: number;
  run_cost: number;
  /** Paid when above 0, paid back when below */
  price: number;
  /** What demolish + rebuild would come to, same sign */
  rebuild: number;
}

export interface SwappableLift {
  name?: string;
  subtype?: string;
  seats?: number;
  capacity?: number;
  run_cost?: number;
  swaps?: LiftSwapOption[];
}

function seatWord(type: string, n: number): string {
  if (n === 1) return '1 seat';
  return `${n} ${type === 'gondola' || type === 'cable_car' ? 'people' : 'seats'}`;
}

function systemName(type?: string): string {
  return LIFT_TYPES.find((l) => l.id === type)?.name ?? (type || 'Lift').replace(/_/g, ' ');
}

/** "Fixed-grip chairlift, 4 seats", as ResortMap::liftLabel writes it */
export function liftLabel(type: string, seats: number): string {
  return `${systemName(type)}, ${seats} ${seats === 1 ? 'seat' : 'seats'}`;
}

/** The swap the dialog opens on: more seats on the same system, else the next bigger system */
function defaultOption(lift: SwappableLift, options: LiftSwapOption[]): LiftSwapOption | undefined {
  const cap = Number(lift.capacity) || 0;
  return (
    options.find((o) => o.lift_type === lift.subtype && o.capacity > cap) ??
    options.find((o) => o.capacity > cap) ??
    options[0]
  );
}

/**
 * Replace a built lift with another system on the same line. Shows the before and after numbers and
 * the price next to what demolish + rebuild would cost; the parent does the request.
 */
export function LiftSwapDialog({
  lift,
  isOpen,
  onOpenChange,
  cash,
  isImperial,
  onConfirm,
}: {
  lift: SwappableLift;
  isOpen: boolean;
  onOpenChange: (open: boolean) => void;
  cash: number;
  isImperial: boolean;
  onConfirm: (option: LiftSwapOption, done: (ok: boolean) => void) => void;
}) {
  const options = useMemo(() => lift.swaps ?? [], [lift.swaps]);
  const [pick, setPick] = useState<LiftSwapOption | undefined>(() => defaultOption(lift, options));
  const [working, setWorking] = useState(false);

  useEffect(() => {
    if (isOpen) setPick(defaultOption(lift, options));
  }, [isOpen, lift, options]);

  const types = LIFT_TYPES.filter((t) => options.some((o) => o.lift_type === t.id));
  const seatsForType = options.filter((o) => o.lift_type === pick?.lift_type);
  const name = lift.name || 'this lift';
  const money = (n: number) => formatCurrency(n, isImperial);
  const short = pick ? pick.price > cash : false;

  function chooseType(type: string) {
    const list = options.filter((o) => o.lift_type === type);
    const cap = Number(lift.capacity) || 0;
    setPick(list.find((o) => o.capacity > cap) ?? list[0]);
  }

  function confirm() {
    if (!pick) return;
    setWorking(true);
    onConfirm(pick, (ok) => {
      setWorking(false);
      if (ok) onOpenChange(false);
    });
  }

  const rows: { label: string; before: string; after: string }[] = pick
    ? [
        { label: 'System', before: systemName(lift.subtype), after: systemName(pick.lift_type) },
        {
          label: 'Carrier',
          before: seatWord(lift.subtype || '', Number(lift.seats) || 0),
          after: seatWord(pick.lift_type, pick.seats),
        },
        {
          label: 'Capacity',
          before: `${(Number(lift.capacity) || 0).toLocaleString()}/hr`,
          after: `${pick.capacity.toLocaleString()}/hr`,
        },
        { label: 'Running cost', before: `${money(Number(lift.run_cost) || 0)}/day`, after: `${money(pick.run_cost)}/day` },
      ]
    : [];

  return (
    <AlertDialog.Backdrop isOpen={isOpen} onOpenChange={(open) => !working && onOpenChange(open)}>
      <AlertDialog.Container>
        <AlertDialog.Dialog className="max-w-lg">
          <AlertDialog.Header>
            <AlertDialog.Heading>Replace {name}</AlertDialog.Heading>
          </AlertDialog.Header>
          <AlertDialog.Body className="space-y-4 text-sm">
            <p className="text-muted">
              Put a different lift on the same line. It opens right away at 100% condition and keeps its name.
            </p>

            <fieldset>
              <legend className="mb-2 font-semibold text-foreground">System</legend>
              <div className="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                {types.map((t) => (
                  <Button
                    key={t.id}
                    type="button"
                    size="sm"
                    variant={pick?.lift_type === t.id ? 'primary' : 'ghost'}
                    aria-pressed={pick?.lift_type === t.id}
                    onPress={() => chooseType(t.id)}
                    className="min-h-11 justify-start px-3 text-sm font-medium md:min-h-9"
                  >
                    {t.name}
                    {t.id === lift.subtype && <span className="ml-1 font-normal opacity-70">(now)</span>}
                  </Button>
                ))}
              </div>
            </fieldset>

            <fieldset>
              <legend className="mb-2 font-semibold text-foreground">Carrier size</legend>
              <div className="flex gap-1.5">
                {seatsForType.map((o) => (
                  <Button
                    key={o.seats}
                    type="button"
                    size="sm"
                    variant={pick?.seats === o.seats ? 'primary' : 'ghost'}
                    aria-pressed={pick?.seats === o.seats}
                    onPress={() => setPick(o)}
                    className="min-h-11 flex-1 text-sm font-semibold tabular-nums md:min-h-9"
                  >
                    {seatWord(o.lift_type, o.seats)}
                  </Button>
                ))}
              </div>
            </fieldset>

            {pick && (
              <>
                <table className="w-full text-left">
                  <thead>
                    <tr className="text-muted">
                      <th className="py-1 pr-2 font-normal"></th>
                      <th className="py-1 pr-2 font-normal">Now</th>
                      <th className="py-1 font-normal">After</th>
                    </tr>
                  </thead>
                  <tbody>
                    {rows.map((r) => (
                      <tr key={r.label} className="border-t border-border">
                        <th scope="row" className="py-1.5 pr-2 font-normal text-muted">
                          {r.label}
                        </th>
                        <td className="py-1.5 pr-2 tabular-nums text-foreground">{r.before}</td>
                        <td className="py-1.5 tabular-nums font-semibold text-foreground">{r.after}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>

                <div className="rounded-lg bg-surface-secondary p-3">
                  <div className="flex items-baseline justify-between gap-3">
                    <span className="text-foreground">{pick.price < 0 ? 'You get back' : 'Cost'}</span>
                    <span className="text-lg font-semibold tabular-nums text-foreground">{money(Math.abs(pick.price))}</span>
                  </div>
                  <div className="flex items-baseline justify-between gap-3 text-muted">
                    <span>Demolish and rebuild</span>
                    <span className="tabular-nums">
                      {pick.rebuild < 0 ? `${money(-pick.rebuild)} back` : money(pick.rebuild)}
                    </span>
                  </div>
                  {short && <p className="mt-2 text-danger">You have {money(cash)}, not enough for this.</p>}
                </div>
              </>
            )}
          </AlertDialog.Body>
          <AlertDialog.Footer>
            <Button variant="tertiary" onPress={() => onOpenChange(false)} isDisabled={working}>
              Keep it
            </Button>
            <Button variant="primary" onPress={confirm} isDisabled={working || !pick || short} className="gap-2">
              {working ? <Spinner size="sm" /> : <Icon name="fa-arrows-rotate" />} Replace lift
            </Button>
          </AlertDialog.Footer>
        </AlertDialog.Dialog>
      </AlertDialog.Container>
    </AlertDialog.Backdrop>
  );
}
