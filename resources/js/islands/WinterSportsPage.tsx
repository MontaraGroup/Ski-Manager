import { useState } from 'react';
import { Icon } from '@/components/Icon';
import { Figure, Page } from '@/components/Page';
import { BobsleighTab } from './winter-sports/BobsleighTab';
import { CrossCountryTab } from './winter-sports/CrossCountryTab';
import { HockeyArenaTab } from './winter-sports/HockeyArenaTab';
import { IceRinkTab } from './winter-sports/IceRinkTab';
import { UnbuiltBlueprint } from './winter-sports/SharedParts';
import { SkiJumpTab } from './winter-sports/SkiJumpTab';
import type { ActivityTab, WinterSportsPageProps } from './winter-sports/types';

function renderTabBody(tab: ActivityTab, cash: number) {
  if (!tab.built || !tab.venue) {
    return <UnbuiltBlueprint tab={tab} />;
  }
  switch (tab.type) {
    case 'ice_rink':
      return <IceRinkTab tab={tab} v={tab.venue} cash={cash} />;
    case 'cross_country':
      return <CrossCountryTab tab={tab} v={tab.venue} cash={cash} />;
    case 'hockey_arena':
      return <HockeyArenaTab tab={tab} v={tab.venue} cash={cash} />;
    case 'ski_jump':
      return <SkiJumpTab tab={tab} v={tab.venue} cash={cash} />;
    case 'bobsleigh_track':
      return <BobsleighTab tab={tab} v={tab.venue} cash={cash} />;
  }
}

export default function WinterSportsPage({ cash, cashText, activeTab, stats, tabs }: WinterSportsPageProps) {
  const [selected, setSelected] = useState<string>(activeTab || tabs[0]?.type || 'ice_rink');
  const currentTab = tabs.find((t) => t.type === selected) ?? tabs[0];

  function chooseTab(type: string) {
    setSelected(type);
    const url = new URL(window.location.href);
    url.searchParams.set('tab', type);
    window.history.replaceState({}, '', url.toString());
  }

  return (
    <Page
      title="Winter Activities"
      icon="fa-hockey-puck"
      iconTone="text-link"
      back="/resort"
      width="max-w-6xl"
      subtitle="Five distinct non-alpine venues with their own schedules, trails, matchdays, hills, weather limits and tournaments."
      actions={
        <span className="rounded-full border border-border px-3 py-1 text-xs font-semibold tabular-nums text-success">
          Cash {cashText}
        </span>
      }
    >
      {/* Top Resort Summary Bar */}
      <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <Figure
          label="Flagship venues"
          value={`${stats.builtCount} / ${stats.totalTypes}`}
          tone="text-foreground"
          note={`${stats.openCount} open · +${stats.visitorBoost.toLocaleString('en-US')} visitors/day`}
        />
        <Figure
          label="Est. daily income"
          value={stats.estRevenueText}
          tone="text-success"
          note={`${stats.estTicketText} entry + ${stats.estAddonText} extras`}
        />
        <Figure
          label="Est. guests/day"
          value={stats.estGuests.toLocaleString('en-US')}
          tone="text-link"
          note="Across open activities"
        />
        <Figure
          label="Est. daily net"
          value={stats.estNetText}
          tone="text-foreground"
          note={`Upkeep ${stats.totalUpkeepText} · ${stats.totalKwh.toLocaleString('en-US')} kWh`}
        />
      </div>

      {/* 5 Activity Tabs */}
      <div
        role="tablist"
        aria-label="Winter activities"
        className="grid grid-cols-2 gap-2 sm:grid-cols-5"
      >
        {tabs.map((t) => {
          const isSelected = currentTab?.type === t.type;
          const dotColor = !t.built
            ? 'bg-border'
            : t.venue?.status === 'open'
              ? 'bg-success'
              : t.venue?.status === 'maintenance'
                ? 'bg-warning'
                : 'bg-muted';
          return (
            <button
              key={t.type}
              type="button"
              role="tab"
              aria-selected={isSelected}
              onClick={() => chooseTab(t.type)}
              className={`flex min-h-14 flex-col justify-between gap-1 rounded-xl border p-3 text-left transition-colors ${
                isSelected
                  ? 'border-link bg-link/10 text-foreground'
                  : 'border-border bg-surface text-muted hover:bg-default/60 hover:text-foreground'
              }`}
            >
              <div className="flex items-center justify-between gap-2">
                <Icon name={t.icon} className={isSelected ? 'text-link' : 'text-muted'} />
                <span className={`inline-block size-2 rounded-full ${dotColor}`} title={t.built ? t.venue?.status : 'Unbuilt'} />
              </div>
              <div>
                <div className="text-sm font-semibold leading-tight text-foreground">{t.name}</div>
                <div className="mt-0.5 truncate text-xs text-muted">
                  {t.built ? (t.venue?.status === 'open' ? `${t.venue.est.revenueText}/day` : t.venue?.status) : t.buildCostText}
                </div>
              </div>
            </button>
          );
        })}
      </div>

      {/* Active Workspace */}
      {currentTab && (
        <section role="tabpanel" aria-label={currentTab.name}>
          {renderTabBody(currentTab, cash)}
        </section>
      )}
    </Page>
  );
}
