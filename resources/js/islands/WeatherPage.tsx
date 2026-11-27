import { Chip, Disclosure } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Page, Panel, PostButton } from '@/components/Page';

type Tone = 'success' | 'danger' | 'warning' | 'link';
type Hour = {
  hour: number; label: string; temp: string; value: number; canMake: boolean; warm: boolean; liftHour: boolean;
  /** Weather Pro only */
  wind: string | null; windValue: number | null; liftHold: boolean;
};
type BaseBar = { day: number; base: number; baseText: string; thin: boolean };

type Props = {
  subtitle: string;
  now: {
    icon: string; condition: string; temp: string; high: string; low: string; snowfall: string; snowBase: string;
    thinBase: boolean; thinLimit: string; wind: string; windHold: boolean; visibility: string; humidity: string;
    snowmakingHours: number; snowmakingTemp: string;
  };
  /** the game day tonight's tick settles */
  settle: {
    day: number; at: string; icon: string; condition: string; temp: string; snowfall: string | null; snowBase: string;
    wind: string; windHold: boolean; boosted: boolean;
  };
  effects: { icon: string; tone: Tone; title: string; text: string; link?: [string, string] }[];
  hourly: Hour[];
  hourlyInfo: { tempUnit: string; windUnit: string; gunLimit: string; liftHoldHours: number; liftHours: number; windRule: boolean };
  forecast: {
    day: number; tonight: boolean; exact: boolean; confidence: number; icon: string; condition: string; temp: string;
    snowfall: string | null; precipChance: number | null; wind: string; windHold: 'yes' | 'likely' | 'possible' | 'no';
    skiers: string; skiersUp: boolean;
  }[];
  callouts: { day: number; kind: 'storm' | 'wind' | 'thaw'; text: string }[];
  history: (BaseBar & { fresh: string | null })[];
  /** Weather Pro: projected natural base from the forecast */
  projection: BaseBar[];
  /** top of the snow base chart: at least twice the thin-base limit */
  maxBase: number;
  pro: {
    available: boolean; active: boolean; expiresAt: string | null; autoRenew: boolean;
    price: number; days: number; balance: number; canAfford: boolean; perks: string[];
  };
  help: string[];
};

const TONE = { success: 'text-success', danger: 'text-danger', warning: 'text-warning', link: 'text-link' };
const CALLOUT = {
  storm: { icon: 'fa-snowflake', tone: 'text-link' },
  wind: { icon: 'fa-wind', tone: 'text-warning' },
  thaw: { icon: 'fa-temperature-arrow-up', tone: 'text-warning' },
};
const WIND_HOLD = { yes: 'Wind hold', likely: 'Wind hold likely', possible: 'Wind hold possible', no: null };

const time = (iso: string) => new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
const date = (iso: string) => new Date(iso).toLocaleString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });

/**
 * /weather: the mountain now, the day tonight's tick settles and what it does to the resort, that
 * day hour by hour, the forecast (exact first, ranges after), the snow base and Weather Pro.
 */
export default function WeatherPage({ subtitle, now, settle, effects, hourly, hourlyInfo, forecast, callouts, history, projection, maxBase, pro, help }: Props) {
  const isPro = pro.available && pro.active;
  const bars: (BaseBar & { future: boolean; fresh?: string | null })[] = [
    ...history.map((h) => ({ ...h, future: false })),
    ...projection.map((p) => ({ ...p, future: true })),
  ];

  return (
    <Page title="Weather" icon="fa-cloud-sun" iconTone="text-link" subtitle={subtitle}>
      <Panel title="On the mountain now">
        <div className="flex flex-col gap-6 md:flex-row md:items-center">
          <div className="shrink-0 text-center md:w-48 md:text-left">
            <Icon name={now.icon} className="mb-2 text-5xl text-link" />
            <div className="text-4xl font-semibold tabular-nums">{now.temp}</div>
            <div className="font-semibold">{now.condition}</div>
            <div className="text-sm text-muted">Day high {now.high} · night low {now.low}</div>
          </div>
          <dl className="grid flex-1 grid-cols-2 gap-3 md:grid-cols-3">
            <Stat label="New snow today" value={now.snowfall} />
            <Stat label="Snow base" value={now.snowBase} tone={now.thinBase ? 'text-warning' : ''} note={now.thinBase ? `Thin (under ${now.thinLimit})` : undefined} />
            <Stat label="Wind" value={now.wind} tone={now.windHold ? 'text-danger' : ''} note={now.windHold ? 'Wind hold' : undefined} />
            <Stat label="Visibility" value={now.visibility} />
            <Stat label="Humidity" value={now.humidity} />
            <Stat
              label="Snowmaking"
              value={now.snowmakingHours > 0 ? `${now.snowmakingHours} h` : 'Too warm'}
              tone={now.snowmakingHours > 0 ? 'text-success' : 'text-danger'}
              note={`at ${now.snowmakingTemp} or colder`}
            />
          </dl>
        </div>
        <p className="mt-3 text-sm text-muted">Today's weather already settled this morning. Plan for tonight's below.</p>
      </Panel>

      <Panel title={`Tonight's settle: day ${settle.day}`}>
        <div className="mb-4 flex flex-wrap items-center gap-x-6 gap-y-2">
          <div className="flex items-center gap-3">
            <Icon name={settle.icon} className="text-3xl text-link" />
            <div>
              <div className="text-2xl font-semibold tabular-nums">{settle.temp}</div>
              <div className="text-sm font-semibold">{settle.condition}</div>
            </div>
          </div>
          <dl className="flex flex-wrap gap-x-6 gap-y-1 text-sm">
            <div><dt className="inline text-muted">New snow </dt><dd className="inline font-semibold tabular-nums">{settle.snowfall ?? 'none'}</dd></div>
            <div><dt className="inline text-muted">Base </dt><dd className="inline font-semibold tabular-nums">{settle.snowBase}</dd></div>
            <div><dt className="inline text-muted">Wind </dt><dd className={`inline font-semibold tabular-nums ${settle.windHold ? 'text-danger' : ''}`}>{settle.wind}</dd></div>
            <div><dt className="inline text-muted">Settles at </dt><dd className="inline font-semibold tabular-nums">{time(settle.at)}</dd></div>
          </dl>
          {settle.boosted && <Chip size="sm" color="warning" variant="soft">Weather Control on</Chip>}
        </div>
        <ul className="flex flex-col gap-3 text-sm">
          {effects.map((e) => (
            <li key={e.title} className="flex items-start gap-2">
              <Icon name={e.icon} className={`mt-0.5 w-4 ${TONE[e.tone]}`} />
              <span>
                <b>{e.title}</b>: {e.text}{' '}
                {e.link && <a href={e.link[0]} className="text-link underline">{e.link[1]}</a>}
              </span>
            </li>
          ))}
        </ul>
      </Panel>

      {callouts.length > 0 && (
        <Panel title="Coming up">
          <ul className="flex flex-col gap-2 text-sm">
            {callouts.map((c) => (
              <li key={`${c.day}-${c.kind}`} className="flex items-start gap-2">
                <Icon name={CALLOUT[c.kind].icon} className={`mt-0.5 w-4 ${CALLOUT[c.kind].tone}`} />
                <span><b>Day {c.day}</b>: {c.text}</span>
              </li>
            ))}
          </ul>
        </Panel>
      )}

      <HourlyPanel hours={hourly} info={hourlyInfo} proOffered={pro.available && !isPro} />

      <Panel title={`Next ${forecast.length} days`}>
        {forecast.length === 0 ? (
          <p className="text-sm text-muted">The season ends tonight. The new season's forecast starts with its first day.</p>
        ) : (
          <ol className={`grid grid-cols-2 gap-2 ${forecast.length > 5 ? 'sm:grid-cols-4 lg:grid-cols-7' : 'sm:grid-cols-5'}`}>
            {forecast.map((f) => (
              <li key={f.day} className={`rounded-lg bg-default p-2 text-center ${f.tonight ? 'ring-1 ring-accent' : ''}`}>
                <div className="text-sm text-muted">{f.tonight ? `Tonight · day ${f.day}` : `Day ${f.day}`}</div>
                <Icon name={f.icon} className="my-1 text-xl" />
                <div className="font-semibold tabular-nums">{f.temp}</div>
                <div className="text-sm text-muted">{f.condition}</div>
                {f.precipChance !== null && <div className="text-sm text-muted">{f.precipChance}% snow or rain</div>}
                {f.snowfall && <div className="text-sm text-link">{f.snowfall}</div>}
                {WIND_HOLD[f.windHold] && <div className="text-sm text-danger">{WIND_HOLD[f.windHold]}</div>}
                <div className={`text-sm ${f.skiersUp ? 'text-success' : 'text-danger'}`}>{f.skiers}</div>
                <div className="mt-1 text-xs text-muted">{f.exact ? 'Exact' : `${f.confidence}% confidence`}</div>
              </li>
            ))}
          </ol>
        )}
        <p className="mt-2 text-sm text-muted">
          {isPro
            ? 'Weather Pro: exact for 3 days, then ranges that hold the real value.'
            : 'Exact for 2 days, then ranges that hold the real value and narrow as the day comes closer.'}
        </p>
      </Panel>

      {bars.length > 1 && (
        <Panel title={projection.length ? 'Snow base, recent days and forecast' : `Snow base, last ${history.length} days`}>
          <div className="flex h-28 items-end gap-1" role="img" aria-label={`Snow base from ${bars[0].baseText} to ${bars[bars.length - 1].baseText}`}>
            {bars.map((h) => (
              <div key={`${h.future ? 'f' : 'h'}${h.day}`} className="flex h-full flex-1 flex-col justify-end" title={`Day ${h.day}: ${h.baseText} base${h.fresh ? `, +${h.fresh} new` : ''}${h.future ? ' (forecast)' : ''}`}>
                <div
                  className={`rounded-t ${h.future ? 'border border-b-0 border-dashed ' + (h.thin ? 'border-warning' : 'border-link') : h.thin ? 'bg-warning' : 'bg-link'}`}
                  style={{ height: `${Math.max(2, Math.round((h.base / maxBase) * 100))}%` }}
                />
              </div>
            ))}
          </div>
          <div className="mt-1 flex justify-between text-sm text-muted">
            <span>Day {bars[0].day}</span>
            <span>{projection.length ? `Day ${projection[projection.length - 1].day}: ${projection[projection.length - 1].baseText}` : `Today: ${now.snowBase}`}</span>
          </div>
          {projection.length > 0 && <p className="mt-1 text-sm text-muted">Outlined bars are the forecast natural base, before snow guns.</p>}
        </Panel>
      )}

      {pro.available && <ProPanel pro={pro} />}

      <Disclosure className="rounded-xl border border-border bg-surface">
        <Disclosure.Heading>
          <Disclosure.Trigger className="flex min-h-11 w-full items-center justify-between px-4 text-sm font-semibold">
            How weather works
            <Disclosure.Indicator />
          </Disclosure.Trigger>
        </Disclosure.Heading>
        <Disclosure.Content>
          <ul className="list-disc space-y-1 px-4 pb-4 pl-9 text-sm">
            {help.map((line) => <li key={line}>{line}</li>)}
          </ul>
        </Disclosure.Content>
      </Disclosure>
    </Page>
  );
}

/** Runs of consecutive hours, as "12a to 8a" */
function runs(hours: Hour[], test: (h: Hour) => boolean): string[] {
  const out: string[] = [];
  let start: Hour | null = null;
  hours.forEach((h, i) => {
    if (test(h) && !start) start = h;
    const next = hours[i + 1];
    if (start && test(h) && (!next || !test(next))) {
      out.push(start === h ? h.label : `${start.label} to ${h.label}`);
      start = null;
    }
  });
  return out;
}

/** Only every third hour is labelled on phones; every hour from md up */
const third = (h: Hour) => (h.hour % 3 === 0 ? '' : 'hidden md:block');
const COLS = { gridTemplateColumns: 'repeat(24, minmax(0, 1fr))' };

/**
 * Tonight's settle day hour by hour: a summary first (what the hours mean for guns and lifts),
 * then the temperature curve with lift hours marked, and the wind for Weather Pro.
 */
function HourlyPanel({ hours, info, proOffered }: { hours: Hour[]; info: Props['hourlyInfo']; proOffered: boolean }) {
  if (hours.length === 0) return null;
  const values = hours.map((h) => h.value);
  const lo = Math.min(...values) - 2;
  const hi = Math.max(...values) + 1;
  const warmest = hours.reduce((a, b) => (b.value > a.value ? b : a));
  const coldest = hours.reduce((a, b) => (b.value < a.value ? b : a));
  const gunHours = hours.filter((h) => h.canMake).length;
  const gunRuns = runs(hours, (h) => h.canMake);
  const liftRun = runs(hours, (h) => h.liftHour)[0] ?? '';
  const hasWind = hours.some((h) => h.windValue !== null);
  const hold = info.liftHoldHours;

  return (
    <Panel title="Tonight's settle, hour by hour">
      <dl className="mb-5 grid grid-cols-2 gap-3 md:grid-cols-4">
        <Stat
          label="Snow guns"
          value={gunHours === 0 ? 'Too warm' : gunHours === 24 ? 'All day' : `${gunHours} h`}
          tone={gunHours > 0 ? 'text-link' : 'text-danger'}
          note={gunHours > 0 && gunHours < 24 ? gunRuns.join(', ') : `at ${info.gunLimit} or colder`}
        />
        <Stat label="Warmest" value={warmest.temp} tone={warmest.warm ? 'text-warning' : ''} note={`at ${warmest.label}`} />
        <Stat label="Coldest" value={coldest.temp} note={`at ${coldest.label}`} />
        <Stat
          label={`Lifts, ${liftRun}`}
          value={hold === 0 ? 'Running' : hold >= info.liftHours ? 'Wind hold' : `${hold} of ${info.liftHours} h on hold`}
          tone={hold > 0 ? 'text-danger' : 'text-success'}
          note={hold > 0 ? (info.windRule ? 'Chairlifts and gondolas stop; button lifts run' : 'Fewer skiers all day') : undefined}
        />
      </dl>

      <div aria-hidden="true">
        <div className="grid gap-px" style={COLS}>
          {hours.map((h) => (
            <div key={h.hour} className={`flex h-36 flex-col justify-end px-px pt-5 ${h.liftHour ? 'bg-default' : ''} ${h.hour === 10 ? 'rounded-tl-lg' : ''} ${h.hour === 15 ? 'rounded-tr-lg' : ''}`}>
              <div className={`mb-1 text-center text-xs font-semibold tabular-nums ${third(h)} ${h.canMake ? 'text-link' : h.warm ? 'text-warning' : ''}`}>
                {h.value}°
              </div>
              <div
                className={`rounded-t ${h.canMake ? 'bg-link' : h.warm ? 'bg-warning' : 'bg-muted/40'}`}
                style={{ height: `${Math.round(12 + (88 * (h.value - lo)) / Math.max(1, hi - lo))}%` }}
                title={`${h.label}: ${h.temp}${h.wind ? `, wind ${h.wind}` : ''}`}
              />
            </div>
          ))}
        </div>
        <div className="grid gap-px border-t border-border pt-1" style={COLS}>
          {hours.map((h) => (
            <div key={h.hour} className={`text-center text-xs text-muted ${third(h)}`}>{h.label}</div>
          ))}
        </div>

        {hasWind && (
          <>
            <div className="mt-4 mb-1 text-sm font-semibold">Wind, {info.windUnit}</div>
            <div className="grid gap-px" style={COLS}>
              {hours.map((h) => (
                <div
                  key={h.hour}
                  className={`rounded py-1 text-center text-xs tabular-nums ${h.liftHold ? 'bg-danger/15 font-semibold text-danger' : `text-muted ${third(h)}`} ${h.liftHour && !h.liftHold ? 'bg-default' : ''}`}
                >
                  {h.windValue}
                </div>
              ))}
            </div>
          </>
        )}
      </div>

      {/* The chart is hidden from screen readers; this table carries the same numbers */}
      <table className="sr-only">
        <caption>Tonight's settle, hour by hour</caption>
        <thead>
          <tr><th scope="col">Hour</th><th scope="col">Temperature</th>{hasWind && <th scope="col">Wind</th>}<th scope="col">Snow guns</th></tr>
        </thead>
        <tbody>
          {hours.map((h) => (
            <tr key={h.hour}>
              <th scope="row">{h.label}</th><td>{h.temp}</td>{hasWind && <td>{h.wind}{h.liftHold ? ', chairlifts stop' : ''}</td>}<td>{h.canMake ? 'can run' : 'too warm'}</td>
            </tr>
          ))}
        </tbody>
      </table>

      <ul className="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted">
        <li className="flex items-center gap-1.5"><span className="inline-block h-3 w-3 rounded-sm bg-link" aria-hidden="true" />Cold enough for snow guns</li>
        <li className="flex items-center gap-1.5"><span className="inline-block h-3 w-3 rounded-sm bg-muted/40" aria-hidden="true" />Too warm for guns</li>
        {hours.some((h) => h.warm) && <li className="flex items-center gap-1.5"><span className="inline-block h-3 w-3 rounded-sm bg-warning" aria-hidden="true" />Above freezing</li>}
        <li className="flex items-center gap-1.5"><span className="inline-block h-3 w-3 rounded-sm border border-border bg-default" aria-hidden="true" />Lift hours ({liftRun})</li>
        {hasWind && <li className="flex items-center gap-1.5"><span className="inline-block h-3 w-3 rounded-sm bg-danger/15" aria-hidden="true" />Chairlifts stop for wind</li>}
      </ul>
      {proOffered && <p className="mt-2 text-sm text-muted">Weather Pro adds the wind for each hour.</p>}
    </Panel>
  );
}

function ProPanel({ pro }: { pro: Props['pro'] }) {
  const buy = `${pro.active ? `Add ${pro.days} days` : `Get ${pro.days} days`} for ${pro.price} Génépis`;
  return (
    <Panel title="Weather Pro">
      {pro.active && pro.expiresAt ? (
        <p className="mb-3 text-sm">
          Active until <b className="tabular-nums">{date(pro.expiresAt)}</b>.{' '}
          {pro.autoRenew ? `Renews for ${pro.price} Génépis when it runs out.` : 'Auto-renew is off.'}
        </p>
      ) : (
        <ul className="mb-3 list-disc space-y-1 pl-5 text-sm">
          {pro.perks.map((p) => <li key={p}>{p}</li>)}
        </ul>
      )}
      <div className="flex flex-wrap items-center gap-3">
        <PostButton action="/weather/pro" variant={pro.active ? 'secondary' : 'primary'} disabled={!pro.canAfford}>
          {buy}
        </PostButton>
        {pro.active && (
          <PostButton action="/weather/pro/renew" data={{ on: pro.autoRenew ? 0 : 1 }} variant="tertiary">
            {pro.autoRenew ? 'Turn auto-renew off' : 'Turn auto-renew on'}
          </PostButton>
        )}
        <span className="text-sm text-muted">
          You have {pro.balance} Génépis.{' '}
          {!pro.canAfford && <a href="/genepis" className="text-link underline">How to earn more</a>}
        </span>
      </div>
      <p className="mt-2 text-sm text-muted">Information only: Weather Pro never changes the weather, your income or your skiers.</p>
    </Panel>
  );
}

function Stat({ label, value, tone = '', note }: { label: string; value: string; tone?: string; note?: string }) {
  return (
    <div className="rounded-lg bg-default p-3">
      <dt className="text-sm text-muted">{label}</dt>
      <dd className={`text-xl font-semibold tabular-nums ${tone}`}>{value}</dd>
      {note && <dd className={`text-sm ${tone || 'text-muted'}`}>{note}</dd>}
    </div>
  );
}
