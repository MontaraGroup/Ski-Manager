import React from 'react';
import { parkCityHasWebp } from '@/lib/webp';
import { Button, Chip, buttonVariants } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput } from '@/components/Page';

interface HomeWeather {
  condition: string;
  conditionName: string;
  temp: string;
  snowBase: string;
  snowfallText: string;
  windText: string;
}

interface HomeTopPlayer {
  username: string;
  cashText: string;
}

interface HomeStanding {
  rank: number;
  username: string;
  resortName: string;
  totalLifts: number;
  totalSlopes: number;
  cashText: string;
}

type Difficulty = 'green' | 'blue' | 'black' | 'double_black';

interface HomeRecent {
  username: string;
  lifts: number;
  runs: Record<Difficulty, number>;
  timeAgoText: string;
}

interface HomePageProps {
  isLoggedIn: boolean;
  username: string;
  playerCount: number;
  playerCountFormatted: string;
  totalSlopes: number;
  totalSlopesFormatted: string;
  topPlayer?: HomeTopPlayer | null;
  weather?: HomeWeather | null;
  season: {
    current: number;
    day: number;
    length: number;
    daysRemaining: number;
    startDateFormatted: string;
  };
  s2: {
    live: boolean;
    justOpened: boolean;
    runs: number;
    lifts: number;
    steep: number;
  };
  standings: HomeStanding[];
  recent: HomeRecent[];
}

const SEASONS = [
  {
    num: 1,
    title: 'Park City base',
    desc: 'Green and blue terrain on the lower mountain. First lifts, ski patrol, and a tight opening budget.',
    signs: ['green', 'blue'] as Difficulty[],
  },
  {
    num: 2,
    title: 'Upper mountain',
    desc: 'Sector 2: alpine bowls and high ridgelines, with black and double-black runs.',
    signs: ['black', 'double_black'] as Difficulty[],
  },
  {
    num: 3,
    title: 'Beyond Park City',
    desc: 'Backcountry terrain, terrain parks, and new mountains picked by player vote.',
    signs: [] as Difficulty[],
  },
];

const DIFFICULTY_LABEL: Record<Difficulty, string> = {
  green: 'Green',
  blue: 'Blue',
  black: 'Black',
  double_black: 'Double black',
};

function plural(n: number, one: string, many: string) {
  return `${n} ${n === 1 ? one : many}`;
}

export default function HomePage({
  isLoggedIn,
  username,
  playerCountFormatted,
  totalSlopesFormatted,
  weather,
  season,
  s2,
  standings,
  recent,
}: HomePageProps) {
  const seasonLeft =
    season.daysRemaining > 0
      ? `${plural(season.daysRemaining, 'day', 'days')} left in Season ${season.current}`
      : `Season ${season.current} ends today`;

  return (
    <div className="flex flex-col">
      {/* Hero: the trail map itself is the picture */}
      <section className="relative isolate overflow-hidden bg-[#141b24] text-white">
        <div aria-hidden="true" className="absolute inset-0 -z-20 bg-parkcity-map bg-cover bg-center opacity-75" />
        <div aria-hidden="true" className="absolute inset-0 -z-10 bg-gradient-to-t lg:bg-gradient-to-r from-[#0a0e14]/95 via-[#0a0e14]/80 to-[#0a0e14]/45" />

        <div className="mx-auto grid max-w-6xl grid-cols-1 items-center gap-10 px-4 py-12 sm:px-6 sm:py-20 lg:grid-cols-12 lg:px-8 lg:py-24">
          <div className="flex flex-col gap-5 lg:col-span-7">
            {isLoggedIn ? (
              <>
                <h1 className="text-[2rem] font-bold leading-tight tracking-tight text-white sm:text-5xl">
                  Welcome back{username ? `, ${username}` : ''}.
                </h1>
                <p className="max-w-lg text-base leading-relaxed text-white/85 sm:text-lg">
                  {seasonLeft}. Check last night's numbers on the dashboard, or open the map and keep building.
                </p>
              </>
            ) : (
              <>
                <h1 className="max-w-xl text-[2rem] font-bold leading-tight tracking-tight text-white sm:text-5xl lg:text-[3.25rem]">
                  Run a ski resort on a real mountain.
                </h1>
                <p className="max-w-lg text-base leading-relaxed text-white/85 sm:text-lg">
                  Build lifts and cut runs on the Park City trail map. Hire patrol and groomers, set ticket prices, and
                  get through the weather, one game day at a time. Free, in your browser.
                </p>
              </>
            )}

            <div className="flex flex-col gap-3 pt-1 sm:flex-row sm:flex-wrap sm:items-center">
              {isLoggedIn ? (
                <>
                  <a href="/dashboard" className={buttonVariants({ variant: 'primary', size: 'lg', className: 'min-h-12 w-full px-6 font-semibold sm:w-auto' })}>
                    <Icon name="fa-gauge" className="w-4" />
                    Open dashboard
                  </a>
                  <a href="/map" className={buttonVariants({ variant: 'ghost', size: 'lg', className: 'min-h-12 w-full border border-white/30 bg-white/10 px-5 text-white hover:bg-white/20 sm:w-auto' })}>
                    <Icon name="fa-map" className="w-4" />
                    Trail map
                  </a>
                </>
              ) : (
                <>
                  <a href="/register" className={buttonVariants({ variant: 'primary', size: 'lg', className: 'min-h-12 w-full px-6 font-semibold sm:w-auto' })}>
                    Play free
                  </a>
                  <DemoStartButton className="min-h-12 w-full border border-white/30 bg-white/10 px-5 text-white hover:bg-white/20 sm:w-auto">
                    Try a demo resort
                  </DemoStartButton>
                  <a href="/login" className="inline-flex min-h-11 items-center justify-center px-2 text-sm font-medium text-white/85 underline underline-offset-4 hover:text-white">
                    Log in
                  </a>
                </>
              )}
            </div>
            {!isLoggedIn && (
              <p className="text-sm text-white/70">
                The demo needs no account: you get a resort with lifts and runs already built.
              </p>
            )}

            <p className="flex flex-wrap gap-x-4 gap-y-1 border-t border-white/15 pt-4 text-sm text-white/75">
              <span>
                <strong className="font-semibold tabular-nums text-white">{playerCountFormatted}</strong> players signed up
              </span>
              <span>
                <strong className="font-semibold tabular-nums text-white">{totalSlopesFormatted}</strong> runs built
              </span>
            </p>
          </div>

          <div className="lg:col-span-5">
            <SnowBoard weather={weather} season={season} seasonLeft={seasonLeft} />
          </div>
        </div>
      </section>

      {/* What you do: the pitch, for visitors only */}
      {!isLoggedIn && (
        <section aria-labelledby="home-loop" className="border-b border-border bg-surface">
          <div className="mx-auto grid max-w-6xl grid-cols-1 gap-10 px-4 py-14 sm:px-6 sm:py-20 lg:grid-cols-12 lg:gap-14 lg:px-8">
            <div className="lg:col-span-5">
              <h2 id="home-loop" className="text-[1.75rem] font-bold leading-tight text-foreground">
                Every game day, the resort opens and the books settle
              </h2>
              <p className="mt-3 text-base leading-relaxed text-muted">
                Visitors come for the snow and the terrain you've built. What they pay, and what your staff and lifts
                cost, lands in the ledger overnight.
              </p>

              <ul className="mt-8 flex flex-col gap-5">
                <LoopItem icon="fa-cable-car" title="Build lifts">
                  Button lifts, chairlifts, gondolas and cable cars, each with its own capacity and running cost.
                </LoopItem>
                <LoopItem icon="fa-person-skiing" title="Cut runs">
                  Green <TrailSign difficulty="green" />, blue <TrailSign difficulty="blue" /> and black{' '}
                  <TrailSign difficulty="black" /> runs, drawn down the mountain's real fall lines.
                </LoopItem>
                <LoopItem icon="fa-user-group" title="Hire staff">
                  Ski patrol, lift mechanics, instructors, snowmakers and groomer drivers.
                </LoopItem>
                <LoopItem icon="fa-ticket" title="Set prices">
                  Day tickets, lodging and food. Charge too much and fewer visitors come.
                </LoopItem>
                <LoopItem icon="fa-snowflake" title="Work the weather">
                  Make snow when it's cold enough, groom overnight, and keep the base deep.
                </LoopItem>
              </ul>
            </div>

            <figure className="flex flex-col gap-2 lg:col-span-7">
              <a
                href="/register"
                aria-label="Play free on the Park City trail map"
                className="block overflow-hidden rounded-xl border border-border bg-default focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
              >
                <picture className="block">
                  {parkCityHasWebp('med') && (
                    <source type="image/webp" srcSet="/img/ParkCity_low.webp 600w, /img/ParkCity_med.webp 1200w" sizes="(min-width: 1024px) 640px, 100vw" />
                  )}
                  <img
                    src="/img/ParkCity_low.jpg"
                    srcSet="/img/ParkCity_low.jpg 600w, /img/ParkCity_med.jpg 1200w"
                    sizes="(min-width: 1024px) 640px, 100vw"
                    alt="The Park City trail map you build on"
                    width={600}
                    height={340}
                    loading="lazy"
                    decoding="async"
                    className="aspect-[600/340] h-auto w-full object-cover"
                  />
                </picture>
              </a>
              <figcaption className="text-sm text-muted">
                The real Park City trail map. Cartography licensed from{' '}
                <a href="https://skimap.com" target="_blank" rel="noopener noreferrer" className="text-link underline underline-offset-2">
                  Mapsynergy / Skimap
                </a>
                .
              </figcaption>
            </figure>
          </div>
        </section>
      )}

      {/* Season 2 news and last season's results */}
      {s2.live && (
        <section aria-labelledby="home-s2" className="border-b border-border bg-background">
          <div className="mx-auto grid max-w-6xl grid-cols-1 items-start gap-8 px-4 py-12 sm:px-6 sm:py-16 lg:grid-cols-12 lg:px-8">
            <div className="flex flex-col gap-3 lg:col-span-7">
              <p className="flex items-center gap-2 text-sm font-semibold text-foreground">
                <TrailSign difficulty="black" />
                <TrailSign difficulty="double_black" />
                Sector 2 is open
              </p>
              <h2 id="home-s2" className="text-[1.75rem] font-bold leading-tight text-foreground">
                Season 2: the upper mountain
              </h2>
              <p className="max-w-xl text-base leading-relaxed text-muted">
                The upper reaches of Park City add {plural(s2.runs, 'run', 'runs')} and {plural(s2.lifts, 'lift', 'lifts')}, {s2.steep} of
                them steep black or double-black descents. Everything built in Season 1 carries over, and so does your
                money.
              </p>
              <a
                href={isLoggedIn ? '/map' : '/register'}
                className="inline-flex min-h-11 w-fit items-center gap-2 text-sm font-semibold text-link underline-offset-4 hover:underline"
              >
                {isLoggedIn ? 'See Sector 2 on the trail map' : 'Start a resort in Season 2'}
              </a>
            </div>

            {standings.length > 0 && (
              <div className="rounded-xl border border-border bg-surface p-4 sm:p-5 lg:col-span-5">
                <div className="flex items-center justify-between gap-3">
                  <h3 className="text-base font-semibold text-foreground">Season 1 final standings</h3>
                  <a href="/leaderboard" className="inline-flex min-h-11 items-center text-sm text-link underline-offset-4 hover:underline">
                    Leaderboard
                  </a>
                </div>
                <ol className="mt-1 divide-y divide-separator">
                  {standings.slice(0, 5).map((s) => (
                    <li key={s.rank} className="flex items-center justify-between gap-3 py-2.5">
                      <div className="flex min-w-0 items-center gap-3">
                        <span className="w-6 shrink-0 text-sm font-semibold tabular-nums text-muted">{s.rank}.</span>
                        <div className="min-w-0">
                          <span className="block truncate text-sm font-semibold text-foreground">{s.username}</span>
                          <span className="block text-xs text-muted">
                            {plural(s.totalLifts, 'lift', 'lifts')} · {plural(s.totalSlopes, 'run', 'runs')}
                          </span>
                        </div>
                      </div>
                      <span className="shrink-0 text-sm font-semibold tabular-nums text-foreground">{s.cashText}</span>
                    </li>
                  ))}
                </ol>
              </div>
            )}
          </div>
        </section>
      )}

      {/* What players built lately: one row per player */}
      {recent.length > 0 && (
        <section aria-labelledby="home-recent" className="border-b border-border bg-surface">
          <div className="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
            <div className="flex items-end justify-between gap-4">
              <div>
                <h2 id="home-recent" className="text-xl font-bold text-foreground">Built this week</h2>
                <p className="mt-1 text-sm text-muted">Lifts and runs players added to their resorts, newest first.</p>
              </div>
              <a href="/activity" className="inline-flex min-h-11 shrink-0 items-center text-sm text-link underline-offset-4 hover:underline">
                All activity
              </a>
            </div>

            <ul className="mt-5 grid grid-cols-1 gap-x-8 sm:grid-cols-2 lg:grid-cols-3">
              {recent.map((r) => (
                <li key={r.username} className="flex items-center justify-between gap-3 border-b border-separator py-3">
                  <div className="min-w-0">
                    <span className="block truncate text-sm font-semibold text-foreground">{r.username}</span>
                    <BuiltSummary r={r} />
                  </div>
                  <span className="shrink-0 text-xs tabular-nums text-muted">{r.timeAgoText}</span>
                </li>
              ))}
            </ul>
          </div>
        </section>
      )}

      {/* Seasons */}
      <section aria-labelledby="home-seasons" className="border-b border-border bg-background">
        <div className="mx-auto max-w-6xl px-4 py-14 sm:px-6 sm:py-20 lg:px-8">
          <h2 id="home-seasons" className="text-[1.75rem] font-bold leading-tight text-foreground">One mountain, opened season by season</h2>
          <p className="mt-2 max-w-xl text-base text-muted">Each season opens new terrain. Your resort, money and record carry over.</p>

          <ol className="mt-8 grid grid-cols-1 gap-4 md:grid-cols-3">
            {SEASONS.map((s) => {
              const isCurrent = s.num === season.current;
              const isPast = s.num < season.current;
              return (
                <li
                  key={s.num}
                  aria-current={isCurrent ? 'step' : undefined}
                  className={`flex flex-col gap-2 rounded-xl border p-5 ${isCurrent ? 'border-accent bg-surface' : 'border-border bg-surface/60'}`}
                >
                  <div className="flex items-center justify-between gap-2">
                    <span className="text-sm font-semibold text-muted">Season {s.num}</span>
                    {isCurrent ? (
                      <Chip size="sm" variant="soft" color="accent" className="tabular-nums">
                        Day {season.day} of {season.length}
                      </Chip>
                    ) : (
                      <Chip size="sm" variant="soft" color="default">
                        {isPast ? 'Finished' : 'Coming'}
                      </Chip>
                    )}
                  </div>
                  <h3 className="flex items-center gap-2 text-lg font-semibold text-foreground">
                    {s.title}
                    {s.signs.map((d) => (
                      <TrailSign key={d} difficulty={d} />
                    ))}
                  </h3>
                  <p className="text-sm leading-relaxed text-muted">{s.desc}</p>
                </li>
              );
            })}
          </ol>
        </div>
      </section>

      {/* Closing call to action, for visitors only */}
      {!isLoggedIn && (
        <section aria-labelledby="home-cta" className="bg-surface">
          <div className="mx-auto flex max-w-6xl flex-col gap-6 px-4 py-14 sm:px-6 sm:py-16 lg:flex-row lg:items-center lg:justify-between lg:px-8">
            <div className="max-w-xl">
              <h2 id="home-cta" className="text-[1.75rem] font-bold leading-tight text-foreground">
                {seasonLeft}.
              </h2>
              <p className="mt-2 text-base text-muted">
                A new account gets its own resort and starting money right away. No download, no payment.
              </p>
            </div>
            <div className="flex flex-col gap-3 sm:flex-row">
              <a href="/register" className={buttonVariants({ variant: 'primary', size: 'lg', className: 'min-h-12 w-full px-6 font-semibold sm:w-auto' })}>
                Play free
              </a>
              <DemoStartButton variant="secondary" className="min-h-12 w-full px-5 sm:w-auto">
                Try a demo resort
              </DemoStartButton>
            </div>
          </div>
        </section>
      )}
    </div>
  );
}

/** The resort snow board: dark header strip, light panel, big numbers (DESIGN.md) */
function SnowBoard({
  weather,
  season,
  seasonLeft,
}: {
  weather?: HomeWeather | null;
  season: HomePageProps['season'];
  seasonLeft: string;
}) {
  const pct = season.length > 0 ? Math.min(100, Math.max(0, Math.round((season.day / season.length) * 100))) : 0;
  return (
    <section aria-labelledby="home-snow" className="overflow-hidden rounded-xl bg-[oklch(97%_0.006_250)] text-[oklch(26%_0.04_260)] shadow-xl">
      <div className="flex items-center justify-between gap-3 bg-[oklch(24%_0.03_260)] px-4 py-3 text-white sm:px-5">
        <h2 id="home-snow" className="flex items-center gap-2 text-sm font-bold uppercase tracking-wider">
          <Icon name="fa-snowflake" className="w-4" />
          Park City snow report
        </h2>
        <span className="text-xs text-white/75">In game, now</span>
      </div>

      {weather ? (
        <dl className="grid grid-cols-2 gap-px bg-[oklch(88%_0.01_250)]">
          <BoardStat label="Temperature" value={weather.temp} note={weather.conditionName} />
          <BoardStat label="Base depth" value={weather.snowBase} note={weather.snowfallText} />
          <BoardStat label="Wind" value={weather.windText} />
          <BoardStat label="Season" value={`Day ${season.day}`} note={`of ${season.length}`} />
        </dl>
      ) : (
        <p className="px-5 py-6 text-sm">Today's report isn't ready yet. It's written when the game day starts.</p>
      )}

      <div className="px-4 py-3 sm:px-5">
        <div className="flex items-center justify-between text-xs text-[oklch(42%_0.03_260)]">
          <span>{seasonLeft}</span>
          <span className="tabular-nums">{pct}%</span>
        </div>
        <div aria-hidden="true" className="mt-1.5 h-1.5 overflow-hidden rounded-full bg-[oklch(88%_0.01_250)]">
          <div className="h-full rounded-full bg-gradient-to-r from-[oklch(60%_0.15_245)] to-[oklch(45%_0.2_260)]" style={{ width: `${pct}%` }} />
        </div>
      </div>
    </section>
  );
}

function BoardStat({ label, value, note }: { label: string; value: string; note?: string }) {
  return (
    <div className="bg-[oklch(97%_0.006_250)] px-4 py-3 sm:px-5 sm:py-4">
      <dt className="text-xs font-medium text-[oklch(42%_0.03_260)]">{label}</dt>
      <dd className="mt-0.5 text-2xl font-bold tabular-nums sm:text-[1.75rem]">{value}</dd>
      {note && <dd className="text-xs text-[oklch(42%_0.03_260)]">{note}</dd>}
    </div>
  );
}

function LoopItem({ icon, title, children }: { icon: string; title: string; children: React.ReactNode }) {
  return (
    <li className="flex gap-3">
      <Icon name={icon} className="mt-1 h-5 w-5 self-start text-link" />
      <div className="text-sm leading-relaxed text-muted">
        <strong className="block text-base font-semibold text-foreground">{title}</strong>
        {children}
      </div>
    </li>
  );
}

/** Trail-sign shapes: green circle, blue square, black diamond, double black diamond */
function TrailSign({ difficulty }: { difficulty: Difficulty }) {
  const label = `${DIFFICULTY_LABEL[difficulty]} run`;
  if (difficulty === 'green') {
    return <span role="img" aria-label={label} className="inline-block size-3 shrink-0 align-[-1px] rounded-full bg-[var(--sign-green)]" />;
  }
  if (difficulty === 'blue') {
    return <span role="img" aria-label={label} className="inline-block size-3 shrink-0 align-[-1px] bg-[var(--sign-blue)]" />;
  }
  return (
    <span role="img" aria-label={label} className="inline-flex shrink-0 items-center gap-1 px-0.5 align-middle">
      <span className="inline-block size-2.5 rotate-45 bg-foreground" />
      {difficulty === 'double_black' && <span className="inline-block size-2.5 rotate-45 bg-foreground" />}
    </span>
  );
}

function BuiltSummary({ r }: { r: HomeRecent }) {
  const parts: React.ReactNode[] = [];
  (Object.keys(r.runs) as Difficulty[]).forEach((d) => {
    const n = r.runs[d];
    if (n > 0) {
      parts.push(
        <span key={d} className="inline-flex items-center gap-1.5">
          <TrailSign difficulty={d} />
          <span className="tabular-nums">{n}</span>
        </span>,
      );
    }
  });
  if (r.lifts > 0) {
    parts.push(
      <span key="lifts" className="inline-flex items-center gap-1.5">
        <Icon name="fa-cable-car" className="w-3.5" />
        <span className="tabular-nums">{plural(r.lifts, 'lift', 'lifts')}</span>
      </span>,
    );
  }
  return <span className="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted">{parts}</span>;
}

/** Starting a demo creates an account, so it's a form post: crawlers and prefetch following a link can't trigger it */
function DemoStartButton({
  className,
  children,
  variant = 'ghost',
}: {
  className: string;
  children: React.ReactNode;
  variant?: 'ghost' | 'secondary';
}) {
  return (
    <form action="/demo" method="post" className="contents">
      <CsrfInput />
      <Button type="submit" size="lg" variant={variant} className={className}>
        {children}
      </Button>
    </form>
  );
}
