import { Disclosure } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Page, Panel } from '@/components/Page';

type Props = {
  subtitle: string;
  now: {
    icon: string; condition: string; temp: string; high: string; low: string; snowfall: string; snowBase: string;
    thinBase: boolean; thinLimit: string; wind: string; windHold: boolean; visibility: string; humidity: string;
    snowmakingHours: number; snowmakingTemp: string;
  };
  effects: { icon: string; tone: 'success' | 'danger' | 'warning' | 'link'; title: string; text: string; link?: [string, string] }[];
  hourly: { label: string; isNow: boolean; temp: string; canMake: boolean; warm: boolean }[];
  forecast: { day: number; icon: string; condition: string; temp: string; snowfall: string | null; skiers: string; skiersUp: boolean }[];
  history: { day: number; base: number; baseText: string; fresh: string | null; thin: boolean }[];
  /** top of the snow base chart: at least twice the thin-base limit */
  maxBase: number;
  help: string[];
};

const TONE = { success: 'text-success', danger: 'text-danger', warning: 'text-warning', link: 'text-link' };

/** /weather: today and what it does to the resort, the day by hour, the next five days, the snow base */
export default function WeatherPage({ subtitle, now, effects, hourly, forecast, history, maxBase, help }: Props) {
  return (
    <Page title="Weather" icon="fa-cloud-sun" iconTone="text-link" subtitle={subtitle}>
      <Panel>
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
            <Stat label="Wind" value={now.wind} tone={now.windHold ? 'text-danger' : ''} note={now.windHold ? 'Lifts on wind hold' : undefined} />
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
      </Panel>

      <Panel title="What today's weather does to your resort">
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

      <Panel title="Today, hour by hour">
        <div className="overflow-x-auto">
          <ol className="flex min-w-[720px] gap-1">
            {hourly.map((h) => (
              <li key={h.label} aria-current={h.isNow ? 'time' : undefined} className={`flex-1 rounded-lg p-1.5 text-center ${h.isNow ? 'bg-accent-soft ring-1 ring-accent' : ''}`}>
                <div className={`text-xs ${h.isNow ? 'font-semibold text-accent-soft-foreground' : 'text-muted'}`}>{h.label}</div>
                <div className={`text-sm font-semibold tabular-nums ${h.canMake ? 'text-link' : h.warm ? 'text-warning' : ''}`}>{h.temp}</div>
                <div className={`mt-1 h-1 rounded ${h.canMake ? 'bg-link' : 'bg-default'}`} aria-hidden="true" />
              </li>
            ))}
          </ol>
        </div>
        <p className="mt-2 flex flex-wrap gap-4 text-sm text-muted">
          <span className="flex items-center gap-1"><span className="inline-block h-1 w-3 rounded bg-link" aria-hidden="true" />Cold enough for snow guns</span>
          <span className="flex items-center gap-1"><span className="inline-block h-1 w-3 rounded bg-default" aria-hidden="true" />Too warm</span>
        </p>
      </Panel>

      <Panel title="Next 5 days">
        <ol className="grid grid-cols-2 gap-2 sm:grid-cols-5">
          {forecast.map((f) => (
            <li key={f.day} className="rounded-lg bg-default p-2 text-center">
              <div className="text-sm text-muted">Day {f.day}</div>
              <Icon name={f.icon} className="my-1 text-xl" />
              <div className="font-semibold tabular-nums">{f.temp}</div>
              <div className="text-sm text-muted">{f.condition}</div>
              {f.snowfall && <div className="text-sm text-link">{f.snowfall}</div>}
              <div className={`text-sm ${f.skiersUp ? 'text-success' : 'text-danger'}`}>{f.skiers}</div>
            </li>
          ))}
        </ol>
        <p className="mt-2 text-sm text-muted">Mountain forecasts here are reliable: plan grooming and snowmaking around them.</p>
      </Panel>

      {history.length > 1 && (
        <Panel title={`Snow base, last ${history.length} days`}>
          <div className="flex h-28 items-end gap-1" role="img" aria-label={`Snow base from ${history[0].baseText} to ${history[history.length - 1].baseText}`}>
            {history.map((h) => (
              <div key={h.day} className="flex h-full flex-1 flex-col justify-end" title={`Day ${h.day}: ${h.baseText} base${h.fresh ? `, +${h.fresh} new` : ''}`}>
                <div className={`rounded-t ${h.thin ? 'bg-warning' : 'bg-link'}`} style={{ height: `${Math.max(2, Math.round((h.base / maxBase) * 100))}%` }} />
              </div>
            ))}
          </div>
          <div className="mt-1 flex justify-between text-sm text-muted">
            <span>Day {history[0].day}</span>
            <span>Today: {now.snowBase}</span>
          </div>
        </Panel>
      )}

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

function Stat({ label, value, tone = '', note }: { label: string; value: string; tone?: string; note?: string }) {
  return (
    <div className="rounded-lg bg-default p-3">
      <dt className="text-sm text-muted">{label}</dt>
      <dd className={`text-xl font-semibold tabular-nums ${tone}`}>{value}</dd>
      {note && <dd className={`text-sm ${tone || 'text-muted'}`}>{note}</dd>}
    </div>
  );
}
