import { Chip, Disclosure } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Page, Panel, PostButton } from '@/components/Page';

type Tone = 'success' | 'danger' | 'warning' | 'link';
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
  hourly: { label: string; isNow: boolean; temp: string; canMake: boolean; warm: boolean; wind: string | null; liftHold: boolean }[];
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
export default function WeatherPage({ subtitle, now, settle, effects, hourly, forecast, callouts, history, projection, maxBase, pro, help }: Props) {
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

      <Panel title="Tonight's settle, hour by hour">
        <div className="overflow-x-auto">
          <ol className="flex min-w-[720px] gap-1">
            {hourly.map((h) => (
              <li key={h.label} className={`flex-1 rounded-lg p-1.5 text-center ${h.liftHold ? 'bg-default ring-1 ring-danger' : ''}`}>
                <div className="text-xs text-muted">{h.label}</div>
                <div className={`text-sm font-semibold tabular-nums ${h.canMake ? 'text-link' : h.warm ? 'text-warning' : ''}`}>{h.temp}</div>
                {h.wind && <div className={`text-xs tabular-nums ${h.liftHold ? 'font-semibold text-danger' : 'text-muted'}`}>{h.wind.replace(' ', ' ')}</div>}
                <div className={`mt-1 h-1 rounded ${h.canMake ? 'bg-link' : 'bg-default'}`} aria-hidden="true" />
              </li>
            ))}
          </ol>
        </div>
        <p className="mt-2 flex flex-wrap gap-4 text-sm text-muted">
          <span className="flex items-center gap-1"><span className="inline-block h-1 w-3 rounded bg-link" aria-hidden="true" />Cold enough for snow guns</span>
          <span className="flex items-center gap-1"><span className="inline-block h-1 w-3 rounded bg-default" aria-hidden="true" />Too warm</span>
          {isPro && <span className="flex items-center gap-1"><span className="inline-block h-3 w-3 rounded ring-1 ring-danger" aria-hidden="true" />Chairlifts stop for wind</span>}
        </p>
      </Panel>

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
