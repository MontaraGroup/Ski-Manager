import { useEffect, useState, type ReactNode } from 'react';
import { Alert, Button, Chip, Label, Meter, Spinner, Surface, toast } from '@heroui/react';
import { Checkbox } from '@/components/Checkbox';
import { Switch } from '@/components/Switch';
import { Slider } from '@/components/Slider';
import { Select } from '@/components/Select';
import { api, ApiError } from '@/lib/api';

// cashText is formatted by the server's currency(), which follows the player's unit setting
type Me = { id: number; username: string; isAdmin: boolean; cash: number; cashText: string; genepis: number; gameDay: number; seasonDay: number };

function Section({ title, note, children }: { title: string; note?: string; children: ReactNode }) {
  return (
    <section className="flex flex-col gap-3">
      <div>
        <h2 className="text-lg font-semibold">{title}</h2>
        {note && <p className="text-sm text-muted">{note}</p>}
      </div>
      {children}
    </section>
  );
}

/** Live check of the islands API: the signed-in player from GET /api/v1/me */
function SignedIn() {
  const [me, setMe] = useState<Me | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    api<Me>('me')
      .then(setMe)
      .catch((err: unknown) => setError(err instanceof ApiError ? err.message : 'Could not reach the API. Reload the page.'));
  }, []);

  if (error) {
    return (
      <Alert status="danger">
        <Alert.Indicator />
        <Alert.Content>
          <Alert.Title>API call failed</Alert.Title>
          <Alert.Description>{error}</Alert.Description>
        </Alert.Content>
      </Alert>
    );
  }
  if (!me) return <Spinner aria-label="Loading your player" />;
  return (
    <Surface className="rounded-xl p-4">
      <dl className="grid grid-cols-2 gap-x-6 gap-y-2 text-sm sm:grid-cols-4">
        <dt className="text-muted">Player</dt>
        <dd className="font-medium">{me.username}</dd>
        <dt className="text-muted">Cash</dt>
        <dd className="font-medium tabular-nums">{me.cashText}</dd>
        <dt className="text-muted">Génépis</dt>
        <dd className="font-medium tabular-nums">{me.genepis.toLocaleString('en-US')}</dd>
        <dt className="text-muted">Game day</dt>
        <dd className="font-medium tabular-nums">{me.gameDay} (season day {me.seasonDay})</dd>
      </dl>
    </Surface>
  );
}

/** Trail-sign difficulty marks, the identity motif in DESIGN.md */
const SIGNS = [
  { mark: '●', label: 'Green, easy', className: 'text-[var(--sign-green)]' },
  { mark: '■', label: 'Blue, intermediate', className: 'text-[var(--sign-blue)]' },
  { mark: '◆', label: 'Black, advanced', className: 'text-foreground' },
  { mark: '◆◆', label: 'Double black, expert', className: 'text-foreground' },
];

export default function UiKit() {
  const [switchVal, setSwitchVal] = useState(true);
  const [checkVal, setCheckVal] = useState(false);
  const [sliderVal, setSliderVal] = useState(45);
  const [selectVal, setSelectVal] = useState('groomed');

  return (
    <div className="flex flex-col gap-8 text-foreground">
      <header>
        <h1 className="text-2xl font-semibold">UI kit</h1>
        <p className="text-sm text-muted">
          HeroUI 3.2.7 in the Ski Manager theme (DESIGN.md). Switch the theme in the navbar to check both.
        </p>
      </header>

      <Section title="Signed in" note="Live data from GET /api/v1/me.">
        <SignedIn />
      </Section>

      <Section title="Buttons" note="One primary action per view. Labels name the action.">
        <div className="flex flex-wrap items-center gap-3">
          <Button variant="primary">Hire ski patrol</Button>
          <Button variant="secondary">Assign to a slope</Button>
          <Button variant="tertiary">View roster</Button>
          <Button variant="ghost">Cancel</Button>
          <Button variant="danger">Demolish lift</Button>
          <Button variant="primary" isDisabled>
            Needs a mechanic
          </Button>
        </div>
        <div className="flex flex-wrap items-center gap-3">
          <Button size="sm">Small</Button>
          <Button size="md">Medium</Button>
          <Button size="lg">Large</Button>
        </div>
      </Section>

      <Section title="Form controls" note="Accessible HeroUI switches, checkboxes, sliders, and selects.">
        <div className="flex flex-col gap-4 sm:max-w-md">
          <Switch isSelected={switchVal} onChange={setSwitchVal}>
            Night skiing enabled
          </Switch>
          <Checkbox isSelected={checkVal} onChange={setCheckVal}>
            Send safety patrol on severe weather
          </Checkbox>
          <div className="flex flex-col gap-1.5">
            <Label className="text-sm font-medium">Daily ticket base price</Label>
            <Slider
              aria-label="Ticket price"
              value={sliderVal}
              onChange={(v) => setSliderVal(typeof v === 'number' ? v : v[0])}
              minValue={10}
              maxValue={120}
              step={5}
            />
          </div>
          <div className="flex flex-col gap-1.5">
            <Label className="text-sm font-medium">Morning slope status</Label>
            <Select
              aria-label="Slope status"
              value={selectVal}
              onChange={(val) => setSelectVal(String(val))}
              options={[
                { id: 'groomed', label: 'Groomed and open' },
                { id: 'powder', label: 'Fresh powder' },
                { id: 'ice', label: 'Icy conditions' },
                { id: 'closed', label: 'Closed for grooming' },
              ]}
            />
          </div>
        </div>
      </Section>

      <Section title="Status chips" note="Text first, color second.">
        <div className="flex flex-wrap gap-2">
          <Chip color="success" variant="soft">Open</Chip>
          <Chip color="warning" variant="soft">Maintenance due</Chip>
          <Chip color="danger" variant="soft">Broken</Chip>
          <Chip color="default" variant="soft">Closed</Chip>
        </div>
      </Section>

      <Section title="Gauges" note="Sample values, not your resort's data.">
        <div className="grid gap-4 sm:grid-cols-3">
          <Meter value={72} color="success">
            <Label>Lift condition</Label>
            <Meter.Output />
            <Meter.Track>
              <Meter.Fill />
            </Meter.Track>
          </Meter>
          <Meter value={38} color="warning">
            <Label>Snow base</Label>
            <Meter.Output />
            <Meter.Track>
              <Meter.Fill />
            </Meter.Track>
          </Meter>
          <Meter value={12} color="danger">
            <Label>Staff morale</Label>
            <Meter.Output />
            <Meter.Track>
              <Meter.Fill />
            </Meter.Track>
          </Meter>
        </div>
      </Section>

      <Section title="Messages" note="Alert for a state on the page, toast for the result of an action.">
        <Alert status="warning">
          <Alert.Indicator />
          <Alert.Content>
            <Alert.Title>Cash is low</Alert.Title>
            <Alert.Description>Sample: tomorrow's salaries need 3,200 € more than you have.</Alert.Description>
          </Alert.Content>
        </Alert>
        <div className="flex flex-wrap gap-3">
          <Button variant="secondary" onPress={() => toast.success('Sample: hired Lucas Martin as ski patrol. Salary 180 € a day.')}>
            Show a success toast
          </Button>
          <Button variant="secondary" onPress={() => toast.danger('Sample: could not hire. You need 3,200 € more.')}>
            Show an error toast
          </Button>
        </div>
      </Section>

      <Section title="Trail signs" note="Shape and color together, so color is never the only signal.">
        <ul className="flex flex-wrap gap-6 text-sm">
          {SIGNS.map((s) => (
            <li key={s.label} className="flex items-center gap-2">
              <span aria-hidden="true" className={`text-lg leading-none ${s.className}`}>
                {s.mark}
              </span>
              {s.label}
            </li>
          ))}
        </ul>
      </Section>
    </div>
  );
}
