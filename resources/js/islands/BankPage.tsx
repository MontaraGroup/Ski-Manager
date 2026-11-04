import { Meter } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Empty, Figure, Page, Panel, PostButton } from '@/components/Page';

export type BankLoan = {
  id: number;
  name: string;
  loan_type: string;
  principal: number;
  principalText: string;
  interest_rate: number;
  remaining: number;
  remainingText: string;
  daily_payment: number;
  dailyPaymentText: string;
  days_remaining: number;
  days_total: number;
  paid_pct: number;
  payoff: number;
  payoffText: string;
  saving: number;
  savingText: string;
  canPay: boolean;
  cashNeededText: string | null;
};

export type LoanProduct = {
  type: string;
  name: string;
  amount: number;
  amountText: string;
  interest: number;
  interestText: string;
  rate: number;
  days: number;
  daily_payment: number;
  dailyPaymentText: string;
  total_repay: number;
  totalRepayText: string;
  icon: string;
  isEmergency: boolean;
};

export type BankPageProps = {
  cash: {
    raw: number;
    text: string;
    isNegative: boolean;
  };
  stats: {
    totalDebtText: string;
    totalDebtRaw: number;
    totalPayoffText: string;
    dailyPaymentsText: string;
    dailyPaymentsRaw: number;
    loadPct: number | null;
    lastIncomeText: string | null;
    activeCount: number;
    maxLoans: number;
    freeSlots: number;
    hasExtraSlot: boolean;
  };
  loans: BankLoan[];
  products: LoanProduct[];
};

export default function BankPage({ cash, stats, loans, products }: BankPageProps) {
  return (
    <Page
      title="Alpine National Bank"
      icon="fa-landmark"
      iconTone="text-primary"
      subtitle="Borrow capital to invest in resort expansion, repay in daily instalments"
      width="max-w-6xl"
    >
      {/* 4 Summary stats */}
      <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <Figure
          label="Cash"
          value={cash.text}
          tone={cash.isNegative ? 'text-danger' : ''}
          note={cash.isNegative ? 'Account overdrawn' : 'Available reserves'}
        />
        <Figure
          label="Owed"
          value={stats.totalDebtText}
          tone={stats.totalDebtRaw > 0 ? 'text-danger' : 'text-success'}
          note={
            stats.totalDebtRaw > 0
              ? `${stats.totalPayoffText} to pay off today`
              : 'Debt free'
          }
        />
        <Figure
          label="Daily repayments"
          value={stats.dailyPaymentsText}
          tone={stats.dailyPaymentsRaw > 0 ? 'text-warning' : ''}
          note={
            stats.loadPct !== null
              ? `${stats.loadPct}% of yesterday's income (${stats.lastIncomeText})`
              : 'Deducted each nightly tick'
          }
        />
        <Figure
          label="Loan slots"
          value={`${stats.activeCount} / ${stats.maxLoans}`}
          note={
            stats.hasExtraSlot ? (
              'Includes extra slot perk'
            ) : (
              <a href="/genepis" className="text-accent underline hover:opacity-80">
                Extra slot with Génépis
              </a>
            )
          }
        />
      </div>

      {/* Warnings */}
      {stats.loadPct !== null && stats.loadPct > 50 ? (
        <div className="flex items-start gap-2.5 rounded-lg border border-warning/30 bg-warning/10 p-3 text-xs text-warning">
          <Icon name="fa-triangle-exclamation" className="text-sm shrink-0 mt-0.5" />
          <span>
            Repayments take more than half of what your resort earned yesterday. Repayments continue even if cash runs out, which can push your account into negative balance.
          </span>
        </div>
      ) : cash.isNegative && loans.length > 0 ? (
        <div className="flex items-start gap-2.5 rounded-lg border border-danger/30 bg-danger/10 p-3 text-xs text-danger">
          <Icon name="fa-triangle-exclamation" className="text-sm shrink-0 mt-0.5" />
          <span>
            Your cash balance is negative and scheduled repayments keep coming. Cut daily overhead or boost revenue before taking on more debt.
          </span>
        </div>
      ) : null}

      {/* Main sections */}
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-5">
        {/* Active loans (2 cols) */}
        <section className="lg:col-span-2 space-y-3">
          <h2 className="text-lg font-bold">Your loans</h2>

          {loans.length === 0 ? (
            <Panel>
              <Empty
                icon="fa-circle-check"
                title="Debt free"
              >
                No active loans. Borrowing provides quick liquidity to construct lifts and guest amenities before they generate income.
              </Empty>
            </Panel>
          ) : (
            <div className="space-y-3">
              {loans.map((loan) => (
                <Panel key={loan.id} className="space-y-3">
                  <div className="flex items-start justify-between gap-2">
                    <div>
                      <div className="font-semibold text-sm">{loan.name}</div>
                      <div className="text-xs text-muted">
                        <span className="tabular-nums font-medium text-foreground">{loan.principalText}</span> borrowed · {loan.interest_rate}% interest
                      </div>
                    </div>
                    <div className="text-right">
                      <div className="font-mono font-bold text-sm text-danger tabular-nums">
                        {loan.remainingText}
                      </div>
                      <div className="text-[11px] text-muted">left to repay</div>
                    </div>
                  </div>

                  <div>
                    <div className="flex justify-between text-xs text-muted mb-1">
                      <span>{loan.days_remaining} days left · {loan.dailyPaymentText}/day</span>
                      <span className="tabular-nums font-medium">{loan.paid_pct}% completed</span>
                    </div>
                    <Meter
                      value={loan.paid_pct}
                      maxValue={100}
                      color="accent"
                      aria-label={`${loan.name} repayment progress`}
                    />
                  </div>

                  <div className="flex flex-col gap-2 pt-1 border-t border-border sm:flex-row sm:items-center sm:justify-between">
                    <span className={`text-xs ${loan.saving > 0 ? 'text-success font-medium' : 'text-muted'}`}>
                      {loan.saving > 0
                        ? `Save ${loan.savingText} in unearned interest`
                        : 'Early settlement'}
                    </span>
                    <PostButton
                      action={`/bank/repay/${loan.id}`}
                      variant="primary"
                      size="sm"
                      disabled={!loan.canPay}
                      confirm={{
                        title: `Pay off ${loan.name}`,
                        body: `Settle this loan now for ${loan.payoffText}? This clears all remaining instalments immediately.`,
                        confirmLabel: `Pay ${loan.payoffText}`,
                      }}
                    >
                      Pay {loan.payoffText}
                    </PostButton>
                  </div>

                  {!loan.canPay && loan.cashNeededText && (
                    <div className="text-right text-[11px] text-muted">
                      Need <span className="tabular-nums font-medium text-danger">{loan.cashNeededText}</span> more cash
                    </div>
                  )}
                </Panel>
              ))}
            </div>
          )}
        </section>

        {/* Borrow products (3 cols) */}
        <section className="lg:col-span-3 space-y-3">
          <div className="flex items-center justify-between">
            <h2 className="text-lg font-bold">Borrow capital</h2>
            <span className="text-xs text-muted">
              {stats.freeSlots > 0
                ? `${stats.freeSlots} slot${stats.freeSlots > 1 ? 's' : ''} free`
                : 'No slots available'}
            </span>
          </div>

          {stats.freeSlots <= 0 && (
            <div className="flex items-center gap-2 rounded-lg border border-warning/30 bg-warning/10 p-3 text-xs text-warning">
              <Icon name="fa-lock" className="shrink-0" />
              <span>
                All {stats.maxLoans} loan slots are currently in use. Repay an active loan to free up a slot.
                {!stats.hasExtraSlot && (
                  <>
                    {' '}
                    <a href="/genepis" className="font-semibold underline">
                      Get an extra slot with Génépis.
                    </a>
                  </>
                )}
              </span>
            </div>
          )}

          <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
            {products.map((p) => (
              <Panel
                key={p.type}
                className={p.isEmergency ? 'border-danger/40' : ''}
              >
                <div className="flex items-center gap-3 mb-3">
                  <div
                    className={`flex size-10 shrink-0 items-center justify-center rounded-xl border ${
                      p.isEmergency
                        ? 'border-danger/30 bg-danger/10 text-danger'
                        : 'border-accent/30 bg-accent-soft text-accent-soft-foreground'
                    }`}
                  >
                    <Icon name={p.icon || 'fa-coins'} className="text-lg" />
                  </div>
                  <div>
                    <div className="font-bold text-sm">{p.name}</div>
                    <div className="text-lg font-bold text-success tabular-nums">
                      {p.amountText}
                    </div>
                  </div>
                </div>

                <dl className="grid grid-cols-2 gap-2 text-xs mb-3">
                  <div className="rounded-lg border border-border bg-surface-secondary/40 p-2 text-center">
                    <dt className="text-muted">Interest</dt>
                    <dd className="font-semibold tabular-nums mt-0.5">
                      {p.interestText} ({p.rate}%)
                    </dd>
                  </div>
                  <div className="rounded-lg border border-border bg-surface-secondary/40 p-2 text-center">
                    <dt className="text-muted">Term</dt>
                    <dd className="font-semibold tabular-nums mt-0.5">{p.days} days</dd>
                  </div>
                  <div className="rounded-lg border border-border bg-surface-secondary/40 p-2 text-center">
                    <dt className="text-muted">Each day</dt>
                    <dd className="font-semibold tabular-nums mt-0.5">{p.dailyPaymentText}</dd>
                  </div>
                  <div className="rounded-lg border border-border bg-surface-secondary/40 p-2 text-center">
                    <dt className="text-muted">Total to repay</dt>
                    <dd className="font-semibold tabular-nums mt-0.5">{p.totalRepayText}</dd>
                  </div>
                </dl>

                <PostButton
                  action="/bank/borrow"
                  data={{ type: p.type }}
                  variant={p.isEmergency ? 'danger' : 'primary'}
                  size="sm"
                  disabled={stats.freeSlots <= 0}
                  className="w-full"
                  confirm={{
                    title: `Confirm ${p.name}`,
                    body: `Borrow ${p.amountText} now and repay ${p.dailyPaymentText} per day over ${p.days} days (${p.totalRepayText} total)?`,
                    confirmLabel: `Borrow ${p.amountText}`,
                  }}
                >
                  <Icon name="fa-hand-holding-dollar" className="mr-1.5" />
                  Borrow {p.amountText}
                </PostButton>
              </Panel>
            ))}
          </div>

          {/* How loans work details */}
          <Panel title="How loans work">
            <ul className="space-y-1.5 text-xs text-muted list-disc pl-4">
              <li>Principal is credited to your resort cash reserves immediately upon approval.</li>
              <li>Interest is calculated for the full duration up front and repaid in equal nightly instalments.</li>
              <li>Instalments are deducted even when cash is zero or negative, so manage cash flow carefully.</li>
              <li>Early settlement saves unearned future interest: you only repay remaining principal and elapsed interest.</li>
              <li>
                You can hold up to {stats.maxLoans} active loans simultaneously. Outstanding debt reduces your resort net worth calculation at season end.
              </li>
            </ul>
          </Panel>
        </section>
      </div>
    </Page>
  );
}
