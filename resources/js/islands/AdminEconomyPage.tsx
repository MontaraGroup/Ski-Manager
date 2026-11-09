import { useState } from 'react';
import { Button } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput, Figure, Page, Panel } from '@/components/Page';

export type AdminRichestPlayer = {
  user_id: number;
  username: string;
  cashFormatted: string;
  incomeFormatted: string;
  expensesFormatted: string;
};

export type AdminTransaction = {
  id: number;
  type: 'income' | 'expense';
  amountFormatted: string;
  description: string;
  game_day: number;
};

export type AdminEconomyPageProps = {
  totalCashFormatted: string;
  avgCashFormatted: string;
  totalIncomeFormatted: string;
  totalExpensesFormatted: string;
  totalDebtFormatted: string;
  playerCount: number;
  richest: AdminRichestPlayer[];
  recentTransactions: AdminTransaction[];
  demoStats: { days: number; started: number; claimed: number; rate: string; active: number };
};

export default function AdminEconomyPage({
  totalCashFormatted,
  avgCashFormatted,
  totalIncomeFormatted,
  totalExpensesFormatted,
  totalDebtFormatted,
  playerCount,
  richest,
  recentTransactions,
  demoStats,
}: AdminEconomyPageProps) {
  const [cashAmount, setCashAmount] = useState('50000');

  return (
    <Page
      title="Economy Overview"
      icon="fa-chart-line"
      iconTone="text-success"
      back="/admin"
      subtitle={`Macroeconomic telemetry across ${playerCount} active player resorts`}
    >
      {/* 4 Core Macro Indicators */}
      <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <Figure
          label="Total Game Cash"
          value={totalCashFormatted}
          tone="text-success font-bold"
          note="Combined player balances"
        />
        <Figure
          label="Average per Player"
          value={avgCashFormatted}
          tone="text-foreground font-bold"
          note="Mean liquidity"
        />
        <Figure
          label="Gross Revenue Generated"
          value={totalIncomeFormatted}
          tone="text-accent font-bold"
          note="Cumulative resort receipts"
        />
        <Figure
          label="Total Active Debt"
          value={totalDebtFormatted}
          tone="text-danger font-bold"
          note="Outstanding bank liabilities"
        />
      </div>

      <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <Figure label={`Demos started (${demoStats.days} days)`} value={demoStats.started} />
        <Figure label={`Demos saved (${demoStats.days} days)`} value={demoStats.claimed} />
        <Figure label="Save rate" value={demoStats.rate} />
        <Figure label="Demo accounts now" value={demoStats.active} />
      </div>

      <div className="grid grid-cols-1 gap-5 lg:grid-cols-2">
        {/* Richest Players */}
        <Panel
          title={
            <div className="flex items-center gap-2">
              <Icon name="fa-trophy" className="text-warning" />
              <span>Richest Players (Top 10)</span>
            </div>
          }
        >
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="border-b border-border text-[11px] font-semibold text-muted">
                <tr>
                  <th className="pb-2">#</th>
                  <th className="pb-2">Player</th>
                  <th className="pb-2 text-right">Cash</th>
                  <th className="pb-2 text-right">Income</th>
                  <th className="pb-2 text-right">Expenses</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border/60">
                {richest.map((r, i) => (
                  <tr key={r.user_id} className="hover:bg-surface-raised/40">
                    <td className="py-2.5 font-bold font-mono">
                      {i === 0 ? (
                        <span className="text-warning">★ 1</span>
                      ) : (
                        <span className="text-muted">{i + 1}</span>
                      )}
                    </td>
                    <td className="py-2.5 font-semibold">
                      <a
                        href={`/admin/user/${r.user_id}`}
                        className="text-accent hover:underline"
                      >
                        {r.username}
                      </a>
                    </td>
                    <td className="py-2.5 text-right font-mono font-bold text-foreground">
                      {r.cashFormatted}
                    </td>
                    <td className="py-2.5 text-right font-mono text-emerald-400">
                      {r.incomeFormatted}
                    </td>
                    <td className="py-2.5 text-right font-mono text-red-400">
                      {r.expensesFormatted}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </Panel>

        {/* Recent Transactions Feed */}
        <Panel
          title={
            <div className="flex items-center gap-2">
              <Icon name="fa-receipt" className="text-muted" />
              <span>Recent Financial Telemetry</span>
            </div>
          }
        >
          <div className="max-h-[360px] overflow-y-auto divide-y divide-border/60">
            {recentTransactions.map((t) => (
              <div
                key={t.id}
                className="flex items-center justify-between gap-3 p-2 text-xs hover:bg-surface-raised/40 transition-colors"
              >
                <span
                  className={`font-mono font-bold shrink-0 w-24 text-right ${
                    t.type === 'income' ? 'text-emerald-400' : 'text-red-400'
                  }`}
                >
                  {t.amountFormatted}
                </span>
                <span className="flex-1 truncate text-foreground">{t.description}</span>
                <span className="font-mono text-[11px] text-muted shrink-0">
                  Day {t.game_day}
                </span>
              </div>
            ))}
          </div>
        </Panel>
      </div>

      {/* Give Cash to All Players */}
      <Panel
        title={
          <div className="flex items-center gap-2">
            <Icon name="fa-gift" className="text-warning" />
            <span>Economic Stimulus: Grant Cash to All Players</span>
          </div>
        }
      >
        <p className="text-xs text-muted mb-3">
          Inject liquidity directly into every player's resort treasury. Useful for testing, seasonal bonuses, or server compensation events.
        </p>

        <form
          action="/admin/add-cash-all"
          method="post"
          onSubmit={(e) => {
            if (!confirm(`Inject $${Number(cashAmount).toLocaleString()} to ALL ${playerCount} active players?`)) {
              e.preventDefault();
            }
          }}
          className="flex flex-col gap-3 sm:flex-row sm:items-end"
        >
          <CsrfInput />
          <div className="flex-1 space-y-1">
            <label htmlFor="amount" className="text-xs font-semibold text-muted">
              Amount per Player
            </label>
            <input
              type="number"
              name="amount"
              id="amount"
              value={cashAmount}
              onChange={(e) => setCashAmount(e.target.value)}
              min="1"
              max="10000000"
              className="w-full rounded-xl border border-border bg-surface-raised px-3 py-2 text-xs font-mono font-bold text-foreground focus:border-accent focus:outline-hidden"
              required
            />
          </div>

          <Button
            type="submit"
            variant="primary"
            className="text-xs font-bold shrink-0"
          >
            <Icon name="fa-coins" className="mr-1.5" />
            Give to All Players
          </Button>
        </form>
      </Panel>
    </Page>
  );
}
