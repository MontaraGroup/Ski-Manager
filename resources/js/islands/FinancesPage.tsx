import { Icon } from '@/components/Icon';
import { Empty, Figure, Page, Panel } from '@/components/Page';

type Row = { label: string; amount: string; icon?: string };
type Props = {
  gameDay: number;
  cash: { text: string; negative: boolean };
  income: { source: string; total: string; rows: Row[] };
  expenses: { total: string; rows: Row[] };
  profit: { text: string; positive: boolean; note: string | null };
  lifetime: { income: string; expenses: string; net: string; positive: boolean };
  transactions: { id: number; day: number; category: string; description: string; amount: string; income: boolean }[];
};

/** /finances: cash, a day's income and costs, lifetime totals, recent transactions */
export default function FinancesPage({ gameDay, cash, income, expenses, profit, lifetime, transactions }: Props) {
  return (
    <Page title="Finances" icon="fa-coins" iconTone="text-warning" subtitle={`Day ${gameDay}: financial overview`} width="max-w-6xl">
      <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
        <Figure label="Cash" value={cash.text} tone={cash.negative ? 'text-danger' : 'text-success'} />
        <Figure label="Income per day" value={`+${income.total}`} tone="text-success" />
        <Figure label="Costs per day" value={`-${expenses.total}`} tone="text-danger" />
        <Figure label="Profit per day" value={profit.text} tone={profit.positive ? 'text-success' : 'text-danger'} note={profit.note} />
      </div>

      <div className="grid gap-4 md:grid-cols-2">
        <Panel title={<span className="flex items-center gap-2"><Icon name="fa-arrow-trend-up" className="text-success" />Income per day ({income.source})</span>}>
          <Breakdown rows={income.rows} total={income.total} sign="+" tone="text-success" />
        </Panel>
        <Panel title={<span className="flex items-center gap-2"><Icon name="fa-arrow-trend-down" className="text-danger" />Costs per day</span>}>
          <Breakdown rows={expenses.rows} total={expenses.total} sign="-" tone="text-danger" />
        </Panel>
      </div>

      <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <Figure label="Lifetime income" value={lifetime.income} tone="text-success" />
        <Figure label="Lifetime costs" value={lifetime.expenses} tone="text-danger" />
        <Figure label="Lifetime profit" value={lifetime.net} tone={lifetime.positive ? 'text-success' : 'text-danger'} />
      </div>

      <Panel title="Recent transactions">
        {transactions.length === 0 ? (
          <Empty icon="fa-receipt" title="No transactions yet">Payments and earnings appear here after your first game day.</Empty>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead className="text-left text-muted">
                <tr className="border-b border-border">
                  <th scope="col" className="py-2 pr-3 font-medium">Day</th>
                  <th scope="col" className="py-2 pr-3 font-medium">Category</th>
                  <th scope="col" className="py-2 pr-3 font-medium">Description</th>
                  <th scope="col" className="py-2 text-right font-medium">Amount</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {transactions.map((t) => (
                  <tr key={t.id}>
                    <td className="py-2 pr-3 tabular-nums text-muted">{t.day}</td>
                    <td className="py-2 pr-3"><span className="rounded-full bg-default px-2 py-0.5">{t.category}</span></td>
                    <td className="py-2 pr-3">{t.description}</td>
                    <td className={`whitespace-nowrap py-2 text-right tabular-nums ${t.income ? 'text-success' : 'text-danger'}`}>{t.amount}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </Panel>
    </Page>
  );
}

function Breakdown({ rows, total, sign, tone }: { rows: Row[]; total: string; sign: string; tone: string }) {
  return (
    <dl className="flex flex-col gap-2 text-sm">
      {rows.map((r) => (
        <div key={r.label} className="flex items-start justify-between gap-3">
          <dt className="flex items-center gap-2 text-muted">{r.icon && <Icon name={r.icon} />}{r.label}</dt>
          <dd className={`whitespace-nowrap tabular-nums ${tone}`}>{sign}{r.amount}</dd>
        </div>
      ))}
      <div className="flex justify-between gap-3 border-t border-border pt-2 font-semibold">
        <dt>Total</dt>
        <dd className={`tabular-nums ${tone}`}>{sign}{total}</dd>
      </div>
    </dl>
  );
}
