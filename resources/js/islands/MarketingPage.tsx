import { Chip } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Empty, Figure, Page, Panel, PostButton } from '@/components/Page';

export type CampaignItem = {
  id: number;
  name: string;
  campaign_type: string;
  status: 'active' | 'paused' | string;
  dailyCost: number;
  dailyCostText: string;
  visitor_boost: number;
  reputation_boost: number;
  days_remaining: number;
};

export type CampaignTypeItem = {
  key: string;
  name: string;
  icon: string;
  cost: number;
  costText: string;
  visitors: number;
  rep: number;
  days: number;
};

export type MarketingPageProps = {
  stats: {
    activeCount: number;
    totalCampaigns: number;
    dailyCostText: string;
    totalVisitorBoost: number;
    totalRepBoost: number;
  };
  campaigns: CampaignItem[];
  campaignTypes: CampaignTypeItem[];
};

export default function MarketingPage({ stats, campaigns, campaignTypes }: MarketingPageProps) {
  return (
    <Page
      title="Marketing"
      icon="fa-bullhorn"
      iconTone="text-accent"
      subtitle="Launch campaigns to attract visitors and build reputation"
      width="max-w-6xl"
    >
      {/* 4 Summary figures */}
      <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
        <Figure label="Active campaigns" value={stats.activeCount} />
        <Figure label="Daily cost" value={stats.dailyCostText} tone="text-danger" />
        <Figure label="Visitor boost" value={`+${stats.totalVisitorBoost}%`} tone="text-success" />
        <Figure label="Reputation boost" value={`+${stats.totalRepBoost} / day`} tone="text-accent" />
      </div>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {/* Active campaigns list */}
        <div className="lg:col-span-2 space-y-3">
          <h2 className="text-lg font-bold text-foreground flex items-center gap-2">
            <Icon name="fa-signal" className="text-accent text-base" />
            Active campaigns
          </h2>

          {campaigns.length === 0 ? (
            <Empty icon="fa-bullhorn" title="No active campaigns">
              Launch a campaign from the launch panel to attract more visitors to your mountain.
            </Empty>
          ) : (
            <div className="space-y-2.5">
              {campaigns.map((c) => {
                const isActive = c.status === 'active';
                return (
                  <div
                    key={c.id}
                    className="flex flex-col justify-between rounded-xl border border-border bg-surface p-3.5 gap-2"
                  >
                    <div className="flex items-center gap-3">
                      <div
                        className={`flex size-10 shrink-0 items-center justify-center rounded-lg ${
                          isActive ? 'bg-success/15' : 'bg-default'
                        }`}
                      >
                        <Icon
                          name="fa-bullhorn"
                          className={`text-base ${isActive ? 'text-success' : 'text-muted'}`}
                        />
                      </div>

                      <div className="flex-1 min-w-0">
                        <div className="flex items-center gap-2">
                          <span className="font-semibold text-sm text-foreground">{c.name}</span>
                          <Chip
                            variant="soft"
                            size="sm"
                            color={isActive ? 'success' : 'warning'}
                            className="capitalize"
                          >
                            {c.status}
                          </Chip>
                        </div>

                        <div className="flex flex-wrap items-center gap-3 text-xs text-muted mt-1">
                          <span>
                            <Icon name="fa-coins" className="mr-1 text-xs" />
                            {c.dailyCostText} / day
                          </span>
                          <span>
                            <Icon name="fa-people-group" className="mr-1 text-xs" />
                            +{c.visitor_boost}%
                          </span>
                          <span>
                            <Icon name="fa-star" className="mr-1 text-xs" />
                            +{c.reputation_boost}
                          </span>
                          <span>
                            <Icon name="fa-clock" className="mr-1 text-xs" />
                            {c.days_remaining}d left
                          </span>
                        </div>

                        <div className="relative w-full h-1 rounded-full bg-default overflow-hidden mt-2">
                          <div
                            className={`h-full rounded-full ${isActive ? 'bg-accent' : 'bg-warning'}`}
                            style={{ width: `${Math.min(100, (c.days_remaining / 60) * 100)}%` }}
                          />
                        </div>
                      </div>

                      <div className="flex items-center gap-1 shrink-0">
                        <PostButton
                          action={`/marketing/toggle/${c.id}`}
                          variant="ghost"
                          size="sm"
                          aria-label={isActive ? 'Pause campaign' : 'Resume campaign'}
                        >
                          <Icon name={isActive ? 'fa-pause' : 'fa-play'} className="text-xs" />
                        </PostButton>

                        <PostButton
                          action={`/marketing/cancel/${c.id}`}
                          variant="ghost"
                          size="sm"
                          className="text-danger hover:bg-danger/10"
                          confirm={{
                            title: `Cancel ${c.name}?`,
                            body: 'This campaign will be terminated immediately.',
                            confirmLabel: 'Cancel campaign',
                          }}
                        >
                          <Icon name="fa-xmark" className="text-xs" />
                        </PostButton>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>

        {/* Launch campaign panel */}
        <div className="space-y-3">
          <h2 className="text-lg font-bold text-foreground flex items-center gap-2">
            <Icon name="fa-rocket" className="text-accent text-base" />
            Launch campaign
          </h2>

          <div className="space-y-2.5">
            {campaignTypes.map((t) => (
              <div
                key={t.key}
                className="flex flex-col justify-between rounded-xl border border-border bg-surface p-3.5 gap-2.5"
              >
                <div className="flex items-center justify-between gap-2">
                  <div className="flex items-center gap-2.5 min-w-0">
                    <div className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-default">
                      <Icon name={t.icon} className="text-base text-accent" />
                    </div>
                    <span className="font-semibold text-sm truncate text-foreground">{t.name}</span>
                  </div>
                  <span className="font-bold text-sm text-accent shrink-0">{t.costText} / day</span>
                </div>

                <div className="flex items-center gap-2 text-xs">
                  <Chip variant="soft" size="sm">
                    <Icon name="fa-people-group" className="mr-1 text-xs" />
                    +{t.visitors}%
                  </Chip>
                  <Chip variant="soft" size="sm">
                    <Icon name="fa-star" className="mr-1 text-xs" />
                    +{t.rep}
                  </Chip>
                  <span className="text-muted ml-auto text-xs">{t.days} days</span>
                </div>

                <PostButton
                  action="/marketing/launch"
                  data={{ type: t.key }}
                  variant="primary"
                  size="sm"
                  className="w-full mt-1"
                  confirm={{
                    title: `Launch ${t.name}?`,
                    body: `Cost will be ${t.costText} per day for ${t.days} days.`,
                    confirmLabel: 'Launch campaign',
                  }}
                >
                  <Icon name="fa-rocket" className="mr-1 text-xs" />
                  Launch
                </PostButton>
              </div>
            ))}
          </div>
        </div>
      </div>
    </Page>
  );
}
