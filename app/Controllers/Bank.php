<?php

namespace App\Controllers;

/**
 * /bank: fixed-term loans. The interest rate is the total charged over the loan's term (not a yearly
 * rate), added up front and repaid in equal daily instalments by the nightly tick. Paying off early
 * costs only what's left of the principal plus the interest earned so far.
 */
class Bank extends BaseController
{
    public const PRODUCTS = [
        'emergency' => ['name' => 'Emergency Credit', 'amount' => 25000, 'rate' => 10.0, 'days' => 14, 'icon' => 'fa-solid fa-triangle-exclamation'],
        'small' => ['name' => 'Small Loan', 'amount' => 50000, 'rate' => 4.0, 'days' => 30, 'icon' => 'fa-solid fa-coins'],
        'medium' => ['name' => 'Business Loan', 'amount' => 200000, 'rate' => 6.0, 'days' => 60, 'icon' => 'fa-solid fa-briefcase'],
        'large' => ['name' => 'Infrastructure Loan', 'amount' => 500000, 'rate' => 8.0, 'days' => 90, 'icon' => 'fa-solid fa-building-columns'],
        'mega' => ['name' => 'Mega Expansion Loan', 'amount' => 1000000, 'rate' => 10.0, 'days' => 120, 'icon' => 'fa-solid fa-city'],
    ];
    public const BASE_SLOTS = 3;

    /** Terms of a product: total interest, total to repay and the daily instalment */
    public static function terms(array $p): array
    {
        $interest = (int) round($p['amount'] * $p['rate'] / 100);
        $total = $p['amount'] + $interest;
        return $p + ['interest' => $interest, 'total_repay' => $total, 'daily_payment' => (int) ceil($total / $p['days'])];
    }

    /** What it costs to settle a loan today: the outstanding balance minus interest not yet earned */
    public static function payoff(array $loan): int
    {
        $days = max(1, (int) $loan['days_total']);
        // The loan's real total is what's left plus the instalments already taken
        $paid = ($days - (int) $loan['days_remaining']) * (int) $loan['daily_payment'];
        $interest = max(0, (int) $loan['remaining'] + $paid - (int) $loan['principal']);
        $unearned = (int) floor($interest * (int) $loan['days_remaining'] / $days);
        return max(0, (int) $loan['remaining'] - $unearned);
    }

    private function maxLoans(int $userId): int
    {
        $extra = db_connect()->table('player_boosts')->where('user_id', $userId)->where('boost_type', 'extra_loan')
            ->where('expires_at >', date('Y-m-d H:i:s'))->countAllResults() > 0;
        return self::BASE_SLOTS + ($extra ? 1 : 0);
    }

    public function index(): string
    {
        return view('bank/index', ['page' => $this->pageData()]);
    }

    /** What the page shows; the mobile API sends the same data (Api\V1\App) */
    public function pageData(): array
    {
        $userId = auth()->id();
        $db = db_connect();
        $finance = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray() ?? ['cash' => 0, 'last_tick' => null];

        $loans = $db->table('loans')->where('user_id', $userId)->where('status', 'active')->orderBy('id')->get()->getResultArray();
        foreach ($loans as &$l) {
            $l['name'] = self::PRODUCTS[$l['loan_type']]['name'] ?? ucfirst($l['loan_type']) . ' loan';
            $l['payoff'] = self::payoff($l);
            $l['saving'] = (int) $l['remaining'] - $l['payoff'];
            $l['paid_pct'] = (int) $l['days_total'] > 0 ? (int) round(((int) $l['days_total'] - (int) $l['days_remaining']) / (int) $l['days_total'] * 100) : 0;
        }
        unset($l);

        $lastTick = json_decode((string) ($finance['last_tick'] ?? ''), true) ?: [];
        $maxLoans = $this->maxLoans($userId);
        $cash = (int) $finance['cash'];
        $totalDebt = (int) array_sum(array_column($loans, 'remaining'));
        $totalPayoff = (int) array_sum(array_column($loans, 'payoff'));
        $dailyPayments = (int) array_sum(array_map(fn($l) => min((int) $l['daily_payment'], (int) $l['remaining']), $loans));
        $lastIncome = isset($lastTick['income']) ? (int) $lastTick['income'] : null;
        $loadPct = $lastIncome ? (int) round($dailyPayments / max(1, $lastIncome) * 100) : null;
        $freeSlots = max(0, $maxLoans - count($loans));

        $formattedLoans = array_map(function ($l) use ($cash) {
            $payoff = (int) $l['payoff'];
            $canPay = $cash >= $payoff;
            return [
                'id' => (int) $l['id'],
                'name' => (string) $l['name'],
                'loan_type' => (string) $l['loan_type'],
                'principal' => (int) $l['principal'],
                'principalText' => currency((int) $l['principal']),
                'interest_rate' => (float) $l['interest_rate'],
                'remaining' => (int) $l['remaining'],
                'remainingText' => currency((int) $l['remaining']),
                'daily_payment' => (int) $l['daily_payment'],
                'dailyPaymentText' => currency((int) $l['daily_payment']),
                'days_remaining' => (int) $l['days_remaining'],
                'days_total' => (int) $l['days_total'],
                'paid_pct' => (int) $l['paid_pct'],
                'payoff' => $payoff,
                'payoffText' => currency($payoff),
                'saving' => (int) $l['saving'],
                'savingText' => currency((int) $l['saving']),
                'canPay' => $canPay,
                'cashNeededText' => !$canPay ? currency($payoff - $cash) : null,
            ];
        }, $loans);

        $productTerms = array_map([self::class, 'terms'], self::PRODUCTS);
        $formattedProducts = [];
        foreach ($productTerms as $key => $p) {
            $rawIcon = $p['icon'] ?? 'fa-coins';
            $icon = preg_replace('/^fa-(solid|regular|brands)\s+/', '', (string) $rawIcon);
            $formattedProducts[] = [
                'type' => (string) $key,
                'name' => (string) $p['name'],
                'amount' => (int) $p['amount'],
                'amountText' => currency((int) $p['amount']),
                'interest' => (int) $p['interest'],
                'interestText' => currency((int) $p['interest']),
                'rate' => (float) $p['rate'],
                'days' => (int) $p['days'],
                'daily_payment' => (int) $p['daily_payment'],
                'dailyPaymentText' => currency((int) $p['daily_payment']),
                'total_repay' => (int) $p['total_repay'],
                'totalRepayText' => currency((int) $p['total_repay']),
                'icon' => $icon,
                'isEmergency' => $key === 'emergency',
            ];
        }

        $page = [
            'cash' => [
                'raw' => $cash,
                'text' => currency($cash),
                'isNegative' => $cash < 0,
            ],
            'stats' => [
                'totalDebtText' => currency($totalDebt),
                'totalDebtRaw' => $totalDebt,
                'totalPayoffText' => currency($totalPayoff),
                'dailyPaymentsText' => currency($dailyPayments),
                'dailyPaymentsRaw' => $dailyPayments,
                'loadPct' => $loadPct,
                'lastIncomeText' => $lastIncome !== null ? currency($lastIncome) : null,
                'activeCount' => count($loans),
                'maxLoans' => $maxLoans,
                'freeSlots' => $freeSlots,
                'hasExtraSlot' => $maxLoans > self::BASE_SLOTS,
            ],
            'loans' => $formattedLoans,
            'products' => $formattedProducts,
        ];

        return $page;
    }

    public function borrow()
    {
        $userId = auth()->id();
        $type = (string) $this->request->getPost('type');
        if (!isset(self::PRODUCTS[$type])) return redirect()->back()->with('error', 'Invalid loan type.');

        $db = db_connect();
        $maxLoans = $this->maxLoans($userId);
        if ($db->table('loans')->where('user_id', $userId)->where('status', 'active')->countAllResults() >= $maxLoans) {
            return redirect()->back()->with('error', "You already have {$maxLoans} active loans. Repay one to borrow again.");
        }
        if (!$db->table('player_finances')->where('user_id', $userId)->countAllResults()) return redirect()->back()->with('error', 'No resort finances found.');

        $p = self::terms(self::PRODUCTS[$type]);
        $now = date('Y-m-d H:i:s');
        $db->transStart();
        $db->table('loans')->insert([
            'user_id' => $userId, 'loan_type' => $type, 'principal' => $p['amount'], 'interest_rate' => $p['rate'],
            'remaining' => $p['total_repay'], 'daily_payment' => $p['daily_payment'], 'days_total' => $p['days'],
            'days_remaining' => $p['days'], 'status' => 'active', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $db->table('player_finances')->where('user_id', $userId)->set('cash', 'cash + ' . $p['amount'], false)->update();
        $db->table('financial_transactions')->insert([
            'user_id' => $userId, 'game_day' => getGameDay(), 'category' => 'Bank Loans',
            'description' => 'Borrowed ' . currency($p['amount']) . ' (' . $p['name'] . ')', 'amount' => $p['amount'], 'type' => 'income', 'created_at' => $now,
        ]);
        $db->transComplete();

        log_activity($userId, 'Bank', 'Borrowed ' . currency($p['amount']) . ' (' . $p['name'] . ')', 'fa-solid fa-building-columns');
        return redirect()->to('/bank')->with('success', $p['name'] . ' of ' . currency($p['amount']) . ' approved: ' . currency($p['daily_payment']) . ' a day for ' . $p['days'] . ' days.');
    }

    public function repay(int $id)
    {
        $userId = auth()->id();
        $db = db_connect();
        $loan = $db->table('loans')->where('id', $id)->where('user_id', $userId)->where('status', 'active')->get()->getRowArray();
        if (!$loan) return redirect()->back()->with('error', 'Loan not found.');

        $payoff = self::payoff($loan);
        $name = self::PRODUCTS[$loan['loan_type']]['name'] ?? 'loan';
        $db->transStart();
        $db->table('player_finances')->where('user_id', $userId)->where('cash >=', $payoff)->set('cash', 'cash - ' . $payoff, false)->update();
        if ($payoff > 0 && $db->affectedRows() !== 1) {
            $db->transRollback();
            return redirect()->back()->with('error', 'Not enough cash: paying off this ' . $name . ' costs ' . currency($payoff) . '.');
        }
        // Only settle a loan that is still active, so a double submit can't charge twice
        $db->table('loans')->where('id', $id)->where('status', 'active')->update(['status' => 'paid', 'remaining' => 0, 'days_remaining' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
        if ($db->affectedRows() !== 1) {
            $db->transRollback();
            return redirect()->back()->with('error', 'That loan is already paid off.');
        }
        $db->table('financial_transactions')->insert([
            'user_id' => $userId, 'game_day' => getGameDay(), 'category' => 'Bank Loans',
            'description' => 'Paid off ' . $name . ' early', 'amount' => $payoff, 'type' => 'expense', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();

        $saved = (int) $loan['remaining'] - $payoff;
        log_activity($userId, 'Bank', 'Paid off ' . $name . ' for ' . currency($payoff), 'fa-solid fa-check');
        return redirect()->to('/bank')->with('success', ucfirst($name) . ' paid off for ' . currency($payoff) . ($saved > 0 ? ', saving ' . currency($saved) . ' of interest.' : '.'));
    }
}
