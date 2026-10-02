<?php

namespace App\Controllers;

use App\Models\LoanModel;
use App\Models\FinanceModel;

class Bank extends BaseController
{
    public function index(): string
    {
        $userId = auth()->id();
        $loanModel = new LoanModel();
        $financeModel = new FinanceModel();

        $finance = $financeModel->where('user_id', $userId)->first();
        if (!$finance) {
            $financeModel->insert(['user_id' => $userId, 'cash' => 500000]);
            $finance = $financeModel->where('user_id', $userId)->first();
        }

        $loans = $loanModel->where('user_id', $userId)->where('status', 'active')->findAll();
        $totalDebt = array_sum(array_column($loans, 'remaining'));
        $dailyPayments = array_sum(array_column($loans, 'daily_payment'));

        $loanOptions = [
            'small' => ['name' => 'Small Loan', 'amount' => 50000, 'rate' => 5.0, 'days' => 30, 'icon' => 'fa-solid fa-coins'],
            'medium' => ['name' => 'Business Loan', 'amount' => 200000, 'rate' => 4.5, 'days' => 60, 'icon' => 'fa-solid fa-briefcase'],
            'large' => ['name' => 'Infrastructure Loan', 'amount' => 500000, 'rate' => 4.0, 'days' => 90, 'icon' => 'fa-solid fa-building-columns'],
            'mega' => ['name' => 'Mega Expansion Loan', 'amount' => 1000000, 'rate' => 3.5, 'days' => 120, 'icon' => 'fa-solid fa-city'],
            'emergency' => ['name' => 'Emergency Credit', 'amount' => 25000, 'rate' => 8.0, 'days' => 14, 'icon' => 'fa-solid fa-triangle-exclamation'],
        ];

        foreach ($loanOptions as &$opt) {
            $totalInterest = round($opt['amount'] * ($opt['rate'] / 100) * ($opt['days'] / 365));
            $opt['total_repay'] = $opt['amount'] + $totalInterest;
            $opt['daily_payment'] = (int) ceil($opt['total_repay'] / $opt['days']);
        }

        $db = db_connect();
        $hasExtraSlot = $db->table('player_boosts')
            ->where('user_id', $userId)
            ->where('boost_type', 'extra_loan')
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->countAllResults() > 0;
        $maxLoans = $hasExtraSlot ? 4 : 3;

        return view('bank/index', [
            'finance'       => $finance,
            'loans'         => $loans,
            'totalDebt'     => $totalDebt,
            'dailyPayments' => $dailyPayments,
            'loanOptions'   => $loanOptions,
            'maxLoans'      => $maxLoans,
            'hasExtraSlot'  => $hasExtraSlot,
        ]);
    }

    public function borrow()
    {
        $userId = auth()->id();
        $type = $this->request->getPost('type');
        $loanModel = new LoanModel();
        $financeModel = new FinanceModel();
        $db = db_connect();

        $options = [
            'small'     => ['amount' => 50000,   'rate' => 5.0, 'days' => 30],
            'medium'    => ['amount' => 200000,  'rate' => 4.5, 'days' => 60],
            'large'     => ['amount' => 500000,  'rate' => 4.0, 'days' => 90],
            'mega'      => ['amount' => 1000000, 'rate' => 3.5, 'days' => 120],
            'emergency' => ['amount' => 25000,   'rate' => 8.0, 'days' => 14],
        ];

        if (!isset($options[$type])) {
            return redirect()->back()->with('error', 'Invalid loan type.');
        }

        $hasExtraSlot = $db->table('player_boosts')
            ->where('user_id', $userId)
            ->where('boost_type', 'extra_loan')
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->countAllResults() > 0;
        $maxLoans = $hasExtraSlot ? 4 : 3;

        $activeLoans = $loanModel->where('user_id', $userId)->where('status', 'active')->countAllResults();
        if ($activeLoans >= $maxLoans) {
            return redirect()->back()->with('error', "Maximum {$maxLoans} active loans allowed.");
        }

        $opt = $options[$type];
        $totalInterest = round($opt['amount'] * ($opt['rate'] / 100) * ($opt['days'] / 365));
        $totalRepay = $opt['amount'] + $totalInterest;
        $dailyPayment = (int) ceil($totalRepay / $opt['days']);

        $finance = $financeModel->where('user_id', $userId)->first();
        if (!$finance) {
            $financeModel->insert(['user_id' => $userId, 'cash' => 500000]);
            $finance = $financeModel->where('user_id', $userId)->first();
        }

        $db->transStart();
        $loanModel->insert([
            'user_id'        => $userId,
            'loan_type'      => $type,
            'principal'      => $opt['amount'],
            'interest_rate'  => $opt['rate'],
            'remaining'      => $totalRepay,
            'daily_payment'  => $dailyPayment,
            'days_total'     => $opt['days'],
            'days_remaining' => $opt['days'],
            'status'         => 'active',
        ]);

        $financeModel->update($finance['id'], ['cash' => (int) $finance['cash'] + $opt['amount']]);

        $startDate = getSeasonStartDate();
        $gameDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime($startDate)) / 86400) + 1);
        $db->table('financial_transactions')->insert([
            'user_id'     => $userId,
            'game_day'    => $gameDay,
            'category'    => 'Bank Loans',
            'description' => 'Borrowed ' . currency($opt['amount']) . ' (' . ucfirst($type) . ' Loan)',
            'amount'      => $opt['amount'],
            'type'        => 'income',
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();

        log_activity($userId, 'Bank', 'Borrowed ' . currency($opt['amount']), 'fa-solid fa-building-columns');
        return redirect()->to('/bank')->with('success', 'Loan of ' . currency($opt['amount']) . ' approved! Funds added to your balance.');
    }

    public function repay(int $id)
    {
        $userId = auth()->id();
        $loanModel = new LoanModel();
        $financeModel = new FinanceModel();
        $db = db_connect();

        $loan = $loanModel->where('id', $id)->where('user_id', $userId)->first();
        if (!$loan) return redirect()->back()->with('error', 'Loan not found.');

        $finance = $financeModel->where('user_id', $userId)->first();
        $userCash = (int) ($finance['cash'] ?? 0);
        $repayAmount = (int) $loan['remaining'];

        if ($userCash < $repayAmount) {
            return redirect()->back()->with('error', 'Not enough cash to repay this loan.');
        }

        $db->transStart();
        $financeModel->update($finance['id'], ['cash' => $userCash - $repayAmount]);
        $loanModel->update($id, ['status' => 'paid', 'remaining' => 0, 'days_remaining' => 0]);

        $startDate = getSeasonStartDate();
        $gameDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime($startDate)) / 86400) + 1);
        $db->table('financial_transactions')->insert([
            'user_id'     => $userId,
            'game_day'    => $gameDay,
            'category'    => 'Bank Loans',
            'description' => 'Repaid loan #' . $id . ' in full',
            'amount'      => $repayAmount,
            'type'        => 'expense',
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();

        log_activity($userId, 'Bank', 'Repaid loan of ' . currency($repayAmount), 'fa-solid fa-check');
        return redirect()->to('/bank')->with('success', 'Loan repaid in full!');
    }
}
