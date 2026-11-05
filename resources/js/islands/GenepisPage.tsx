import { useState } from 'react';
import { Button } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput, Page, Panel } from '@/components/Page';

export type ShopItem = {
  key: string;
  name: string;
  desc: string;
  cost: number;
  icon: string;
  color: string;
  duration: number | null;
  category: string;
  isActive: boolean;
  expiresAt: string | null;
};

export type EarnMethod = {
  name: string;
  desc: string;
  amount: string;
  icon: string;
  link: string;
};

export type GenepisLog = {
  amount: number;
  type: string;
  reason: string;
  timeAgo: string;
};

export type GenepisPageProps = {
  balance: number;
  balanceFormatted: string;
  totalEarned: string;
  totalSpent: string;
  rateFormatted: string;
  shopItems: ShopItem[];
  earnMethods: EarnMethod[];
  logs: GenepisLog[];
};

export default function GenepisPage({
  balance = 0,
  balanceFormatted = '0',
  totalEarned = '0',
  totalSpent = '0',
  rateFormatted = '€1,000',
  shopItems = [],
  earnMethods = [],
  logs = [],
}: GenepisPageProps) {
  const [exchangeAmount, setExchangeAmount] = useState<number | ''>('');

  const numExchange = typeof exchangeAmount === 'number' ? exchangeAmount : 0;
  const estimatedCash = numExchange > 0 ? (numExchange * 1000).toLocaleString() : '0';

  const categories = [
    { key: 'Instant', title: 'Instant Effects', icon: 'fa-bolt-lightning', tone: 'text-danger' },
    { key: 'Timed Boost', title: 'Timed Boosts', icon: 'fa-bolt', tone: 'text-warning' },
    { key: 'Permanent', title: 'Permanent Upgrades', icon: 'fa-arrow-up-right-dots', tone: 'text-primary' },
  ];

  return (
    <Page
      title="Génépis"
      icon="fa-seedling"
      iconTone="text-success"
      back="/dashboard"
      width="max-w-6xl"
      subtitle="Earn through gameplay, spend on mountain boosts and upgrades"
    >
      <div className="space-y-6">
        {/* Hero Card */}
        <div className="rounded-2xl border border-success/30 bg-gradient-to-br from-success/15 via-base-100 to-base-100 p-6 shadow-sm">
          <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
              <div className="text-xs uppercase tracking-wider font-semibold text-muted">
                Available Génépis
              </div>
              <div className="flex items-center gap-3 mt-1">
                <Icon name="fa-seedling" className="text-4xl text-success" />
                <span className="text-4xl sm:text-5xl font-extrabold tracking-tight tabular-nums text-foreground">
                  {balanceFormatted}
                </span>
              </div>
            </div>

            <div className="grid grid-cols-3 gap-4 sm:gap-8 pt-4 md:pt-0 border-t md:border-t-0 border-border/40">
              <div>
                <div className="text-xs text-muted">Total Earned</div>
                <div className="text-lg font-bold text-success tabular-nums">{totalEarned}</div>
              </div>
              <div>
                <div className="text-xs text-muted">Total Spent</div>
                <div className="text-lg font-bold text-danger tabular-nums">{totalSpent}</div>
              </div>
              <div>
                <div className="text-xs text-muted">Exchange Rate</div>
                <div className="text-lg font-bold text-foreground flex items-center gap-1">
                  1 <Icon name="fa-seedling" className="text-success text-xs" /> = {rateFormatted}
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Content Grid */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Left Column: Exchange + Earn Methods + Logs */}
          <div className="space-y-6 lg:col-span-1">
            {/* Exchange Form */}
            <Panel
              title={
                <span className="flex items-center gap-2">
                  <Icon name="fa-money-bill-transfer" className="text-success" />
                  Exchange to Cash
                </span>
              }
            >
              <p className="text-xs text-muted mb-4 leading-relaxed">
                Liquidate your Génépis into operational ski resort cash. Each Génépi converts to {rateFormatted}.
              </p>

              <form
                action="/genepis/exchange"
                method="post"
                onSubmit={(e) => {
                  if (numExchange < 1 || numExchange > balance) {
                    e.preventDefault();
                    return;
                  }
                  if (!confirm(`Convert ${numExchange} Génépi(s) to €${estimatedCash}?`)) {
                    e.preventDefault();
                  }
                }}
                className="space-y-3"
              >
                <CsrfInput />
                <input type="hidden" name="direction" value="to_cash" />

                <div>
                  <label className="text-xs font-semibold text-muted block mb-1">
                    Amount to Convert
                  </label>
                  <div className="relative">
                    <input
                      type="number"
                      name="amount"
                      aria-label="Amount to Convert"
                      min={1}
                      max={balance}
                      value={exchangeAmount}
                      onChange={(e) => {
                        const val = e.target.value === '' ? '' : parseInt(e.target.value, 10);
                        setExchangeAmount(isNaN(val as number) ? '' : val);
                      }}
                      placeholder={`Max ${balance}`}
                      disabled={balance < 1}
                      required
                      className="w-full h-10 px-3 rounded-lg border border-border bg-base-100 text-sm focus:outline-none focus:ring-2 focus:ring-success"
                    />
                    {balance > 0 && (
                      <button
                        type="button"
                        onClick={() => setExchangeAmount(balance)}
                        className="absolute right-2 top-1/2 -translate-y-1/2 px-2 py-0.5 text-[11px] font-semibold uppercase bg-muted/20 hover:bg-muted/30 rounded text-foreground transition-colors"
                      >
                        Max
                      </button>
                    )}
                  </div>
                </div>

                {numExchange > 0 && (
                  <div className="rounded-lg bg-success/10 border border-success/30 p-2.5 text-xs flex items-center justify-between">
                    <span className="text-muted">You will receive:</span>
                    <span className="font-bold text-success tabular-nums">€{estimatedCash}</span>
                  </div>
                )}

                <Button
                  type="submit"
                  variant="primary"
                  className="w-full min-h-[44px] font-semibold bg-success hover:bg-success/90 text-success-foreground border-none flex items-center justify-center gap-2"
                  isDisabled={balance < 1 || numExchange < 1 || numExchange > balance}
                >
                  <Icon name="fa-exchange-alt" />
                  Convert to Cash
                </Button>
              </form>
            </Panel>

            {/* How to Earn */}
            <Panel
              title={
                <span className="flex items-center gap-2">
                  <Icon name="fa-lightbulb" className="text-warning" />
                  How to Earn Génépis
                </span>
              }
            >
              <div className="space-y-2.5">
                {earnMethods.map((m, idx) => (
                  <a
                    key={idx}
                    href={m.link}
                    className="flex items-center justify-between p-2.5 rounded-lg border border-border/50 bg-base-200/50 hover:bg-base-200 hover:border-border transition-colors group"
                  >
                    <div className="flex items-center gap-2.5 min-w-0">
                      <div className="w-8 h-8 rounded-md bg-base-100 flex items-center justify-center text-xs text-primary shrink-0 group-hover:scale-105 transition-transform">
                        <Icon name={m.icon.startsWith('fa-') ? m.icon : `fa-${m.icon}`} />
                      </div>
                      <div className="min-w-0">
                        <div className="text-xs font-semibold text-foreground truncate">{m.name}</div>
                        <div className="text-[11px] text-muted truncate">{m.desc}</div>
                      </div>
                    </div>
                    <span className="text-xs font-bold text-success shrink-0 ml-2 tabular-nums">
                      +{m.amount}
                    </span>
                  </a>
                ))}
              </div>
            </Panel>

            {/* Transaction Logs */}
            {logs.length > 0 && (
              <Panel
                title={
                  <span className="flex items-center gap-2">
                    <Icon name="fa-clock-rotate-left" className="text-muted" />
                    Recent Activity
                  </span>
                }
              >
                <div className="space-y-2 max-h-80 overflow-y-auto pr-1">
                  {logs.map((log, idx) => {
                    const isCredit = log.amount > 0;
                    return (
                      <div
                        key={idx}
                        className="flex items-center justify-between p-2 rounded-md bg-base-200/40 border border-border/30 text-xs"
                      >
                        <div className="min-w-0 flex-1 mr-2">
                          <div className="font-medium text-foreground truncate">{log.reason}</div>
                          <div className="text-[11px] text-muted">{log.timeAgo}</div>
                        </div>
                        <span
                          className={`font-bold tabular-nums shrink-0 ${
                            isCredit ? 'text-success' : 'text-danger'
                          }`}
                        >
                          {isCredit ? `+${log.amount}` : log.amount}{' '}
                          <Icon name="fa-seedling" className="text-[10px]" />
                        </span>
                      </div>
                    );
                  })}
                </div>
              </Panel>
            )}
          </div>

          {/* Right Column: Shop Items by Category */}
          <div className="space-y-6 lg:col-span-2">
            {categories.map((cat) => {
              const items = shopItems.filter((i) => i.category === cat.key);
              if (items.length === 0) return null;

              return (
                <Panel
                  key={cat.key}
                  title={
                    <span className="flex items-center gap-2">
                      <Icon name={cat.icon} className={cat.tone} />
                      {cat.title}
                    </span>
                  }
                >
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {items.map((item) => {
                      const canAfford = balance >= item.cost;
                      const iconName = item.icon.startsWith('fa-') ? item.icon : `fa-${item.icon}`;

                      return (
                        <div
                          key={item.key}
                          className={`flex flex-col justify-between p-4 rounded-xl border transition-all ${
                            item.isActive
                              ? 'bg-primary/5 border-primary/40 ring-1 ring-primary/20'
                              : 'bg-base-200/50 border-border hover:border-border/80'
                          }`}
                        >
                          <div>
                            <div className="flex items-start justify-between gap-2 mb-2">
                              <div className="flex items-center gap-2 min-w-0">
                                <div className="w-8 h-8 rounded-lg bg-base-100 flex items-center justify-center shrink-0">
                                  <Icon name={iconName} className={item.color || cat.tone} />
                                </div>
                                <h3 className="font-bold text-sm text-foreground truncate">
                                  {item.name}
                                </h3>
                              </div>

                              {item.isActive && (
                                <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-primary/20 text-primary shrink-0">
                                  Active
                                </span>
                              )}
                            </div>

                            <p className="text-xs text-muted mb-4 leading-relaxed">{item.desc}</p>
                          </div>

                          <div className="flex items-center justify-between pt-3 border-t border-border/40 mt-auto">
                            <span className="text-sm font-bold text-success flex items-center gap-1 tabular-nums">
                              <Icon name="fa-seedling" className="text-xs" />
                              {item.cost.toLocaleString()}
                            </span>

                            <form
                              action="/genepis/buy"
                              method="post"
                              onSubmit={(e) => {
                                if (
                                  !confirm(
                                    `Spend ${item.cost} Génépi(s) to acquire "${item.name}"?`
                                  )
                                ) {
                                  e.preventDefault();
                                }
                              }}
                            >
                              <CsrfInput />
                              <input type="hidden" name="item" value={item.key} />
                              <Button
                                type="submit"
                                size="sm"
                                variant={
                                  !canAfford
                                    ? 'outline'
                                    : cat.key === 'Permanent'
                                    ? 'primary'
                                    : cat.key === 'Timed Boost'
                                    ? 'secondary'
                                    : 'danger'
                                }
                                isDisabled={!canAfford}
                                className="min-h-[36px] font-semibold"
                              >
                                {item.isActive ? 'Extend' : 'Buy'}
                              </Button>
                            </form>
                          </div>
                        </div>
                      );
                    })}
                  </div>
                </Panel>
              );
            })}
          </div>
        </div>
      </div>
    </Page>
  );
}
