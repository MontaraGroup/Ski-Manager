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

        return view('bank/index', [
            'finance' => $finance,
            'loans' => $loans,
            'totalDebt' => $totalDebt,
            'dailyPayments' => $dailyPayments,
            'loanOptions' => $loanOptions,
        ]);
    }

    public function borrow()
    {
        $userId = auth()->id();
        $type = $this->request->getPost('type');
        $loanModel = new LoanModel();
        $financeModel = new FinanceModel();

        $options = [
            'small' => ['amount' => 50000, 'rate' => 5.0, 'days' => 30],
            'medium' => ['amount' => 200000, 'rate' => 4.5, 'days' => 60],
            'large' => ['amount' => 500000, 'rate' => 4.0, 'days' => 90],
            'mega' => ['amount' => 1000000, 'rate' => 3.5, 'days' => 120],
            'emergency' => ['amount' => 25000, 'rate' => 8.0, 'days' => 14],
        ];

        if (!isset($options[$type])) {
            return redirect()->back()->with('error', 'Invalid loan type.');
        }

        $activeLoans = $loanModel->where('user_id', $userId)->where('status', 'active')->countAllResults();
        if ($activeLoans >= 3) {
            return redirect()->back()->with('error', 'Maximum 3 active loans allowed.');
        }

        $opt = $options[$type];
        $totalInterest = round($opt['amount'] * ($opt['rate'] / 100) * ($opt['days'] / 365));
        $totalRepay = $opt['amount'] + $totalInterest;
        $dailyPayment = (int) ceil($totalRepay / $opt['days']);

        $loanModel->insert([
            'user_id' => $userId,
            'loan_type' => $type,
            'principal' => $opt['amount'],
            'interest_rate' => $opt['rate'],
            'remaining' => $totalRepay,
            'daily_payment' => $dailyPayment,
            'days_total' => $opt['days'],
            'days_remaining' => $opt['days'],
            'status' => 'active',
        ]);

        $finance = $financeModel->where('user_id', $userId)->first();
        $financeModel->update($finance['id'], ['cash' => (int) $finance['cash'] + $opt['amount']]);

        log_activity($userId, 'Bank', 'Borrowed ' . currency($opt['amount']), 'fa-solid fa-building-columns');
        return redirect()->to('/bank')->with('success', 'Loan of ' . currency($opt['amount']) . ' approved! Funds added to your balance.');
    }

    public function repay(int $id)
    {
        $userId = auth()->id();
        $loanModel = new LoanModel();
        $financeModel = new FinanceModel();

        $loan = $loanModel->where('id', $id)->where('user_id', $userId)->first();
        if (!$loan) return redirect()->back()->with('error', 'Loan not found.');

        $finance = $financeModel->where('user_id', $userId)->first();
        if ((int) $finance['cash'] < (int) $loan['remaining']) {
            return redirect()->back()->with('error', 'Not enough cash to repay this loan.');
        }

        $financeModel->update($finance['id'], ['cash' => (int) $finance['cash'] - (int) $loan['remaining']]);
        $loanModel->update($id, ['status' => 'paid', 'remaining' => 0, 'days_remaining' => 0]);

        log_activity($userId, 'Bank', 'Repaid loan of ' . currency((int)$loan['remaining']), 'fa-solid fa-check');
        return redirect()->to('/bank')->with('success', 'Loan repaid in full!');
    }
}
