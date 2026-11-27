import { lazy, Suspense, useState } from 'react';
import { Chip, Surface } from '@heroui/react';
import { Page } from '@/components/Page';
import { Icon } from '@/components/Icon';
import { UnbuiltVenueFrame } from './winter-sports/SharedParts';
import {
  BobsleighBlueprintSvg,
  CrossCountryBlueprintSvg,
  HockeyArenaBlueprintSvg,
  IceRinkBlueprintSvg,
  SkiJumpBlueprintSvg,
} from './winter-sports/Blueprints';
import type { ActivityTab, WinterSportsPageProps } from './winter-sports/types';

const IceRinkTab = lazy(() =>
  import('./winter-sports/IceRinkTab').then((m) => ({ default: m.IceRinkTab })),
);
const CrossCountryTab = lazy(() =>
  import('./winter-sports/CrossCountryTab').then((m) => ({ default: m.CrossCountryTab })),
);
const HockeyArenaTab = lazy(() =>
  import('./winter-sports/HockeyArenaTab').then((m) => ({ default: m.HockeyArenaTab })),
);
const SkiJumpTab = lazy(() =>
  import('./winter-sports/SkiJumpTab').then((m) => ({ default: m.SkiJumpTab })),
);
const BobsleighTab = lazy(() =>
  import('./winter-sports/BobsleighTab').then((m) => ({ default: m.BobsleighTab })),
);

function renderUnbuiltTab(tab: ActivityTab) {
  switch (tab.type) {
    case 'ice_rink':
      return (
        <UnbuiltVenueFrame
          tab={tab}
          blueprintTitle="Alpine Village Plaza Oval"
          illustration={<IceRinkBlueprintSvg />}
          highlightNote={`Hosts ${tab.eventName}. Schedule 3 daily skating blocks and sell skate rentals.`}
          specs={[
            { label: 'Build Cost', value: tab.buildCostText },
            { label: 'Surface', value: tab.surface },
            { label: 'Weather', value: tab.weather },
          ]}
        />
      );
    case 'cross_country':
      return (
        <UnbuiltVenueFrame
          tab={tab}
          blueprintTitle="Topographic Loop Network"
          illustration={<CrossCountryBlueprintSvg />}
          highlightNote={`Hosts ${tab.eventName}. Links directly with Nordic trails from your Ski Map.`}
          specs={[
            { label: 'Build Cost', value: tab.buildCostText },
            { label: 'Surface', value: tab.surface },
            { label: 'Weather', value: tab.weather },
          ]}
        />
      );
    case 'hockey_arena':
      return (
        <UnbuiltVenueFrame
          tab={tab}
          blueprintTitle="Seated Rink & Jumbotron Bowl"
          illustration={<HockeyArenaBlueprintSvg />}
          highlightNote={`Hosts ${tab.eventName}. Book derby & tournament fixtures plus dasher board sponsors.`}
          specs={[
            { label: 'Build Cost', value: tab.buildCostText },
            { label: 'Surface', value: tab.surface },
            { label: 'Weather', value: tab.weather },
          ]}
        />
      );
    case 'ski_jump':
      return (
        <UnbuiltVenueFrame
          tab={tab}
          blueprintTitle="Inrun Tower & Flight Profile"
          illustration={<SkiJumpBlueprintSvg />}
          highlightNote={`Hosts ${tab.eventName}. Monitor ridge crosswinds and expand from K60 up to HS225.`}
          specs={[
            { label: 'Build Cost', value: tab.buildCostText },
            { label: 'Surface', value: tab.surface },
            { label: 'Weather', value: tab.weather },
          ]}
        />
      );
    case 'bobsleigh_track':
      return (
        <UnbuiltVenueFrame
          tab={tab}
          blueprintTitle="Refrigerated S-Curve Sliding Chute"
          illustration={<BobsleighBlueprintSvg />}
          highlightNote={`Hosts ${tab.eventName}. Manage ammonia chiller throttle and public taxi-bob rides.`}
          specs={[
            { label: 'Build Cost', value: tab.buildCostText },
            { label: 'Surface', value: tab.surface },
            { label: 'Weather', value: tab.weather },
          ]}
        />
      );
  }
}

function renderBuiltTab(tab: ActivityTab) {
  if (!tab.venue) return null;
  let content;
  switch (tab.type) {
    case 'ice_rink':
      content = <IceRinkTab venue={tab.venue} />;
      break;
    case 'cross_country':
      content = <CrossCountryTab venue={tab.venue} />;
      break;
    case 'hockey_arena':
      content = <HockeyArenaTab venue={tab.venue} />;
      break;
    case 'ski_jump':
      content = <SkiJumpTab venue={tab.venue} />;
      break;
    case 'bobsleigh_track':
      content = <BobsleighTab venue={tab.venue} />;
      break;
  }
  return (
    <Suspense
      fallback={
        <Surface variant="secondary" className="rounded-2xl border border-[var(--border)] p-8 text-center text-xs text-[var(--muted)]">
          Loading {tab.name}...
        </Surface>
      }
    >
      {content}
    </Suspense>
  );
}

export default function WinterSportsPage(props: WinterSportsPageProps) {
  const { cashText, activeTab: initialTab, weather, stats, tabs } = props;
  const [selectedType, setSelectedType] = useState<ActivityTab['type']>(
    tabs.find((t) => t.type === initialTab)?.type ?? tabs[0]?.type ?? 'ice_rink',
  );
  const currentTab = tabs.find((t) => t.type === selectedType) ?? tabs[0];

  function selectTab(type: ActivityTab['type']) {
    setSelectedType(type);
    if (typeof window !== 'undefined') {
      const url = new URL(window.location.href);
      url.searchParams.set('tab', type);
      window.history.replaceState({}, '', url.toString());
    }
  }

  return (
    <Page
      title="Winter Activities"
      icon="fa-medal"
      subtitle="Operate distinct off-slope alpine venues: ice rinks, Nordic loops, hockey matchups, jump towers, and bobsleigh chutes."
      width="max-w-6xl"
      actions={
        <div className="flex flex-wrap items-center gap-2">
          <Chip size="sm" variant="soft" color={weather.isWinter ? 'accent' : 'warning'}>
            {weather.tempC}°C | {weather.windKmh} km/h | {weather.snowBaseCm} cm
          </Chip>
          <Chip size="sm" variant="soft" color="success">
            Treasury {cashText}
          </Chip>
        </div>
      }
    >
      {/* Compact Inline Activities KPI Bar */}
      <Surface
        variant="secondary"
        className="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-[var(--border)] px-4 py-3 text-xs"
      >
        <div className="flex flex-wrap items-center gap-x-5 gap-y-1.5">
          <div>
            <span className="text-[var(--muted)]">Venues </span>
            <strong className="font-bold tabular-nums text-[var(--foreground)]">
              {stats.builtCount}/{stats.totalTypes} built
            </strong>
            <span className="text-[var(--muted)]"> ({stats.openCount} open)</span>
          </div>
          <div>
            <span className="text-[var(--muted)]">Daily Guests </span>
            <strong className="font-bold tabular-nums text-[var(--foreground)]">
              {stats.estGuests.toLocaleString()}
            </strong>
            <span className="text-[var(--muted)]"> (+{stats.visitorBoost}% resort draw)</span>
          </div>
          <div>
            <span className="text-[var(--muted)]">Projected Net </span>
            <strong className="font-bold tabular-nums text-[var(--success)]">{stats.estNetText}/d</strong>
          </div>
          <div>
            <span className="text-[var(--muted)]">Grid Load </span>
            <strong className="font-bold tabular-nums text-[var(--foreground)]">
              {stats.totalKwh.toLocaleString()} kWh
            </strong>
          </div>
        </div>
        <span className="text-[11px] font-medium text-[var(--muted)]">
          {weather.condition || (weather.isWinter ? 'Winter Operations' : 'Off-Season')}
        </span>
      </Surface>

      {/* Compact Single-Row 5-Tab Switcher */}
      <nav
        aria-label="Winter activity venues"
        className="flex gap-2 overflow-x-auto pb-1"
      >
        {tabs.map((tab) => {
          const active = tab.type === currentTab.type;
          const isOpen = tab.venue?.status === 'open';
          return (
            <button
              key={tab.type}
              type="button"
              onClick={() => selectTab(tab.type)}
              className={`flex min-h-[44px] shrink-0 cursor-pointer items-center gap-2 rounded-xl border px-3.5 py-2 text-xs font-semibold transition-colors ${
                active
                  ? 'border-[var(--link)] bg-[var(--link)]/12 text-[var(--foreground)]'
                  : 'border-[var(--border)] bg-[var(--surface-secondary)] text-[var(--muted)] hover:text-[var(--foreground)]'
              }`}
            >
              <Icon
                name={tab.icon}
                className={`text-sm ${active ? 'text-[var(--link)]' : 'text-[var(--muted)]'}`}
              />
              <span>{tab.name}</span>
              {tab.built ? (
                <span
                  className={`h-2 w-2 rounded-full ${
                    isOpen ? 'bg-[var(--success)]' : 'bg-[var(--warning)]'
                  }`}
                  title={isOpen ? 'Open' : 'Closed'}
                />
              ) : (
                <span className="text-[10px] font-normal text-[var(--muted)]">Blueprint</span>
              )}
            </button>
          );
        })}
      </nav>

      {/* Active Activity Workspace */}
      <div>{currentTab.built ? renderBuiltTab(currentTab) : renderUnbuiltTab(currentTab)}</div>
    </Page>
  );
}
