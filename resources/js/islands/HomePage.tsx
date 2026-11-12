import React from 'react';
import { Button } from '@heroui/react/button';
import { Chip } from '@heroui/react/chip';
import { Icon } from '@/components/Icon';

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

interface HomeRecent {
  username: string;
  name: string;
  itemType: 'lift' | 'slope';
  difficulty?: 'green' | 'blue' | 'black' | 'double_black';
  timeAgoText: string;
}

interface HomePageProps {
  isLoggedIn: boolean;
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

export default function HomePage({
  isLoggedIn,
  playerCountFormatted,
  totalSlopesFormatted,
  weather,
  season,
  s2,
  standings,
  recent,
}: HomePageProps) {
  const seasonsData = [
    {
      num: 1,
      title: 'Park City Base',
      desc: 'Green and blue terrain. The first lifts, ski patrol, and balancing the initial budget.',
    },
    {
      num: 2,
      title: 'Advanced Peaks',
      desc: 'The upper mountain: steep alpine bowls, high ridgelines, black and double-black runs.',
    },
    {
      num: 3,
      title: 'Beyond Park City',
      desc: 'Backcountry terrain, custom terrain parks, and expansion onto mountains chosen by community vote.',
    },
  ];

  return (
    <div className="flex flex-col min-h-screen">
      {/* 1. Hero Section */}
      <section className="relative isolate overflow-hidden bg-[#141b24] text-white py-14 sm:py-20 lg:py-24 border-b border-border">
        {/* Background Image & Gradient */}
        <div
          className="absolute inset-0 -z-20 bg-cover bg-center opacity-75 transform scale-105 transition-transform duration-1000"
          style={{
            backgroundImage: 'url(/img/ParkCity_med.jpg), url(/img/ParkCity_low.jpg)',
          }}
        />
        <div className="absolute inset-0 -z-10 bg-gradient-to-t lg:bg-gradient-to-r from-[#0a0e14]/95 via-[#0a0e14]/80 to-[#0a0e14]/40" />

        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
          {/* Hero Left Column */}
          <div className="lg:col-span-7 space-y-6">
            <div className="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs text-white/90 backdrop-blur-md border border-white/20">
              <span className="w-2 h-2 rounded-full bg-success animate-pulse" />
              <span>
                Season {season.current} · Day {season.day} of {season.length} ({season.daysRemaining} days left)
              </span>
            </div>

            <h1 className="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight leading-tight max-w-xl text-white">
              Run a ski resort on a <span className="text-[#7fb4ff]">real mountain.</span>
            </h1>

            <p className="text-base sm:text-lg text-white/85 leading-relaxed max-w-lg">
              Ski Manager is a free, browser-native simulation game. You start with the authentic Park City trail map, starting capital for your first lift lines, and a full season to make your resort thrive.
            </p>

            <div className="flex flex-wrap items-center gap-3 pt-2">
              {!isLoggedIn ? (
                <>
                  <a
                    href="/register"
                    className="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-primary px-6 py-2.5 text-sm font-bold text-primary-foreground hover:bg-primary/90 transition-all shadow-lg shadow-primary/25"
                  >
                    <Icon name="fa-play" className="w-3.5 h-3.5" />
                    <span>Play Free</span>
                  </a>
                  <DemoStartButton className="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-white/30 bg-white/10 px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/20 transition-all backdrop-blur-sm">Try Demo Mountain</DemoStartButton>
                  <a
                    href="/login"
                    className="inline-flex min-h-11 items-center justify-center text-sm font-medium text-white/80 hover:text-white underline underline-offset-4 px-3"
                  >
                    Sign in →
                  </a>
                </>
              ) : (
                <>
                  <a
                    href="/dashboard"
                    className="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-primary px-6 py-2.5 text-sm font-bold text-primary-foreground hover:bg-primary/90 transition-all shadow-lg"
                  >
                    <Icon name="fa-mountain" className="w-4 h-4" />
                    <span>Go to Dashboard</span>
                  </a>
                  <a
                    href="/map"
                    className="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-white/30 bg-white/10 px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/20 transition-all backdrop-blur-sm"
                  >
                    <Icon name="fa-map" className="w-3.5 h-3.5" />
                    <span>Open Trail Map</span>
                  </a>
                </>
              )}
            </div>

            <p className="text-xs text-white/60">
              <strong className="text-white font-semibold">{playerCountFormatted}</strong> managers registered ·{' '}
              <strong className="text-white font-semibold">{totalSlopesFormatted}</strong> runs built across mountains
            </p>
          </div>

          {/* Hero Right Column: Live Mountain Snow Report */}
          <div className="lg:col-span-5">
            <div className="rounded-2xl border border-white/20 bg-black/40 backdrop-blur-xl p-5 sm:p-6 text-white shadow-2xl space-y-4">
              <div className="flex items-center justify-between border-b border-white/15 pb-3">
                <div className="flex items-center gap-2">
                  <Icon name="fa-snowflake" className="w-4 h-4 text-[#7fb4ff]" />
                  <h2 className="text-sm font-bold uppercase tracking-wider text-white">Live Snow Report</h2>
                </div>
                <span className="text-xs text-white/60">Park City · Today</span>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="p-3 rounded-xl bg-white/5 border border-white/10">
                  <span className="text-[11px] font-medium uppercase text-white/60">Summit Temp</span>
                  <div className="text-2xl font-bold mt-1 text-white tabular-nums">
                    {weather ? weather.temp : '–'}
                  </div>
                  <div className="text-xs text-[#7fb4ff] mt-0.5 font-medium">
                    {weather?.conditionName || 'Clear'}
                  </div>
                </div>

                <div className="p-3 rounded-xl bg-white/5 border border-white/10">
                  <span className="text-[11px] font-medium uppercase text-white/60">Base Depth</span>
                  <div className="text-2xl font-bold mt-1 text-white tabular-nums">
                    {weather ? weather.snowBase : '–'}
                  </div>
                  <div className="text-xs text-white/70 mt-0.5">
                    {weather?.snowfallText || 'No new snow'}
                  </div>
                </div>
              </div>

              <div className="pt-2 border-t border-white/15 flex items-center justify-between text-xs text-white/70">
                <span className="flex items-center gap-1.5">
                  <Icon name="fa-wind" className="w-3.5 h-3.5 text-white/50" />
                  <span>Wind: <strong>{weather?.windText || '–'}</strong></span>
                </span>
                <span className="flex items-center gap-1 text-[#7fb4ff] font-medium">
                  <Icon name="fa-clock" className="w-3 h-3" />
                  <span>Updates hourly</span>
                </span>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* 2. Season 2 Announcement (if open) */}
      {s2.live && (
        <section className="py-12 bg-card border-b border-border">
          <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
            <div className="lg:col-span-7 space-y-3">
              <Chip size="sm" variant="soft" color="accent" className="font-semibold">
                Upper Mountain Open
              </Chip>
              <h2 className="text-2xl sm:text-3xl font-extrabold text-foreground">
                Season 2: Advanced Peaks
              </h2>
              <p className="text-sm text-muted leading-relaxed">
                Sector 2, the upper reaches of Park City, is now live. It adds {s2.runs} runs and {s2.lifts} lifts, with {s2.steep} steep black and double-black descents. Everything built in Season 1 carries over with your finances intact.
              </p>
              <div className="pt-1">
                <a
                  href={isLoggedIn ? '/map' : '/register'}
                  className="text-xs font-semibold text-link hover:underline inline-flex items-center gap-1 min-h-11 sm:min-h-0"
                >
                  <span>{isLoggedIn ? 'Inspect Season 2 on the Trail Map' : 'Start your mountain today'}</span>
                  <Icon name="fa-arrow-right" className="w-3 h-3" />
                </a>
              </div>
            </div>

            {/* Standings Table */}
            {standings.length > 0 && (
              <div className="lg:col-span-5 rounded-xl border border-border bg-surface p-4 space-y-3">
                <div className="flex items-center justify-between">
                  <h3 className="text-xs font-bold uppercase tracking-wider text-muted">
                    Season 1 Hall of Fame
                  </h3>
                  <a href="/leaderboard" className="inline-flex min-h-11 items-center text-xs text-link hover:underline sm:min-h-0">
                    Full Board →
                  </a>
                </div>
                <div className="divide-y divide-border text-xs">
                  {standings.slice(0, 5).map((s) => (
                    <div key={s.rank} className="py-2 flex items-center justify-between gap-2">
                      <div className="flex items-center gap-2.5 min-w-0">
                        <span className="w-5 text-center font-bold text-muted tabular-nums">
                          #{s.rank}
                        </span>
                        <div className="min-w-0 truncate">
                          <span className="font-semibold text-foreground truncate block">
                            {s.username}
                          </span>
                          <span className="text-[10px] text-muted">
                            {s.totalLifts} lifts · {s.totalSlopes} runs
                          </span>
                        </div>
                      </div>
                      <span className="font-mono font-bold text-primary tabular-nums shrink-0">
                        {s.cashText}
                      </span>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>
        </section>
      )}

      {/* 3. How a Season Goes (5 Step Loop) */}
      <section className="py-14 sm:py-20 bg-surface border-b border-border">
        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
          <div className="lg:col-span-6 space-y-6">
            <div>
              <h2 className="text-2xl sm:text-3xl font-extrabold text-foreground">
                How a season works
              </h2>
              <p className="text-sm text-muted mt-1">
                Five straightforward operational decisions each day decide whether you profit or go bankrupt.
              </p>
            </div>

            <ol className="space-y-4 text-sm text-muted">
              <li className="flex items-start gap-3">
                <span className="w-6 h-6 rounded-full bg-primary/10 text-primary font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">
                  1
                </span>
                <div>
                  <strong className="text-foreground">Engineer lift lines.</strong> Choose from fixed-grip quads, high-speed detachables, or panoramic gondolas.
                </div>
              </li>
              <li className="flex items-start gap-3">
                <span className="w-6 h-6 rounded-full bg-primary/10 text-primary font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">
                  2
                </span>
                <div>
                  <strong className="text-foreground">Carve out trails.</strong> Lay down green cruisers, blue groomers, and black bowls along natural mountain fall lines.
                </div>
              </li>
              <li className="flex items-start gap-3">
                <span className="w-6 h-6 rounded-full bg-primary/10 text-primary font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">
                  3
                </span>
                <div>
                  <strong className="text-foreground">Hire &amp; train mountain staff.</strong> Ski patrol, mechanics, instructors, snowmakers, and groomer operators.
                </div>
              </li>
              <li className="flex items-start gap-3">
                <span className="w-6 h-6 rounded-full bg-primary/10 text-primary font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">
                  4
                </span>
                <div>
                  <strong className="text-foreground">Optimize ticket pricing.</strong> Balance day pass rates against visitor throughput, hotel lodging, and restaurant sales.
                </div>
              </li>
              <li className="flex items-start gap-3">
                <span className="w-6 h-6 rounded-full bg-primary/10 text-primary font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">
                  5
                </span>
                <div>
                  <strong className="text-foreground">Track the weather forecast.</strong> Fire snow guns in cold temperatures, groom overnight, and protect slope conditions.
                </div>
              </li>
            </ol>
          </div>

          <div className="lg:col-span-6 space-y-2">
            <a
              href={isLoggedIn ? '/map' : '/register'}
              className="block rounded-2xl overflow-hidden border border-border shadow-xl hover:border-primary/50 transition-all group"
            >
              <img
                src="/img/ParkCity_low.jpg"
                alt="Park City trail map"
                width={600}
                height={340}
                loading="lazy"
                decoding="async"
                className="w-full object-cover group-hover:scale-102 transition-transform duration-500"
              />
            </a>
            <p className="text-[11px] text-muted text-center">
              Authentic Park City topography. Cartography licensed from{' '}
              <a href="https://skimap.com" target="_blank" rel="noopener noreferrer" className="underline hover:text-foreground">
                Mapsynergy / Skimap
              </a>.
            </p>
          </div>
        </div>
      </section>

      {/* 4. Live Construction Ticker (if items exist) */}
      {recent.length > 0 && (
        <section className="py-12 bg-card border-b border-border">
          <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <div className="flex items-center justify-between">
              <div>
                <h3 className="text-lg font-bold text-foreground">Recently Built</h3>
                <p className="text-xs text-muted">Real infrastructure commissioned by players across resorts.</p>
              </div>
              <a href="/activity" className="inline-flex min-h-11 items-center text-xs text-link hover:underline sm:min-h-0">
                All Activity →
              </a>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
              {recent.map((r, i) => (
                <div
                  key={i}
                  className="p-3 rounded-lg border border-border bg-surface flex items-center justify-between gap-3"
                >
                  <div className="flex items-center gap-2.5 min-w-0">
                    {r.itemType === 'lift' ? (
                      <div className="w-7 h-7 rounded-md bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <Icon name="fa-cable-car" className="w-3.5 h-3.5" />
                      </div>
                    ) : (
                      <span
                        className={`w-3 h-3 shrink-0 ${
                          r.difficulty === 'green'
                            ? 'rounded-full bg-[#23a55a]'
                            : r.difficulty === 'blue'
                            ? 'bg-[#2f6fd6]'
                            : 'bg-foreground transform rotate-45'
                        }`}
                      />
                    )}
                    <div className="min-w-0 truncate text-xs">
                      <strong className="text-foreground font-semibold truncate block">
                        {r.username}
                      </strong>
                      <span className="text-muted truncate block">{r.name}</span>
                    </div>
                  </div>
                  <span className="text-[10px] text-muted shrink-0 tabular-nums">
                    {r.timeAgoText}
                  </span>
                </div>
              ))}
            </div>
          </div>
        </section>
      )}

      {/* 5. Seasons Roadmap */}
      <section className="py-14 sm:py-20 bg-surface border-b border-border">
        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
          <div className="max-w-md">
            <h2 className="text-2xl sm:text-3xl font-extrabold text-foreground">Mountain Seasons</h2>
            <p className="text-sm text-muted mt-1">
              Each season opens new mountain territory while preserving lifetime accomplishments.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
            {seasonsData.map((s) => {
              const isCurrent = s.num === season.current;
              const isPast = s.num < season.current;
              return (
                <div
                  key={s.num}
                  className={`rounded-xl border p-5 space-y-3 transition-all ${
                    isCurrent
                      ? 'border-primary bg-primary/5 shadow-md'
                      : 'border-border bg-card'
                  }`}
                >
                  <div className="flex items-center justify-between">
                    <span className="text-xs font-mono font-bold text-muted">
                      SEASON 0{s.num}
                    </span>
                    <Chip
                      size="sm"
                      variant="soft"
                      color={isCurrent ? 'accent' : isPast ? 'default' : 'warning'}
                      className="text-[10px] font-semibold"
                    >
                      {isCurrent ? `Live · Day ${season.day}` : isPast ? 'Completed' : 'Upcoming'}
                    </Chip>
                  </div>
                  <h3 className="text-base font-bold text-foreground">{s.title}</h3>
                  <p className="text-xs text-muted leading-relaxed">{s.desc}</p>
                </div>
              );
            })}
          </div>
        </div>
      </section>

      {/* 6. Call to Action */}
      <section className="py-16 sm:py-20 bg-card">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-6">
          <div className="w-12 h-12 rounded-2xl bg-primary/10 text-primary mx-auto flex items-center justify-center text-xl">
            <Icon name="fa-mountain-sun" className="w-6 h-6" />
          </div>
          <div className="max-w-xl mx-auto space-y-2">
            <h2 className="text-3xl font-black text-foreground tracking-tight">
              Ready to construct your resort?
            </h2>
            <p className="text-sm text-muted">
              Join thousands of alpine managers. Free to play, no downloads required, playable right now in your browser.
            </p>
          </div>
          <div className="flex flex-wrap items-center justify-center gap-3">
            {!isLoggedIn ? (
              <>
                <a
                  href="/register"
                  className="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-primary px-6 py-2.5 text-sm font-bold text-primary-foreground hover:bg-primary/90 transition-all shadow-lg"
                >
                  <Icon name="fa-play" className="w-3.5 h-3.5" />
                  <span>Create Free Account</span>
                </a>
                <DemoStartButton className="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-border bg-surface px-5 py-2.5 text-sm font-semibold hover:bg-default transition-all">Explore Demo</DemoStartButton>
              </>
            ) : (
              <a
                href="/dashboard"
                className="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-primary px-6 py-2.5 text-sm font-bold text-primary-foreground hover:bg-primary/90 transition-all shadow-lg"
              >
                <Icon name="fa-mountain" className="w-4 h-4" />
                <span>Return to Resort</span>
              </a>
            )}
          </div>
        </div>
      </section>
    </div>
  );
}

/** Starting a demo creates an account, so it's a form post: crawlers and prefetch following a link can't trigger it */
function DemoStartButton({ className, children }: { className: string; children: React.ReactNode }) {
  const meta = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]');
  return (
    <form action="/demo" method="post" className="contents">
      <input type="hidden" name={meta?.dataset.field || 'csrf_test_name'} value={meta?.content || ''} />
      <Button type="submit" className={className}>
        <span>{children}</span>
      </Button>
    </form>
  );
}
