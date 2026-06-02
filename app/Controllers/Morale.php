<?php

namespace App\Controllers;

use App\Models\StaffModel;

class Morale extends BaseController
{
    public function index(): string
    {
        $userId = auth()->id();
        $staffModel = new StaffModel();
        $staff = $staffModel->where('user_id', $userId)->where('status !=', 'fired')->findAll();

        $avgMorale = count($staff) > 0 ? round(array_sum(array_column($staff, 'morale')) / count($staff)) : 0;
        $lowMorale = array_filter($staff, fn($s) => (int) $s['morale'] < 50);
        $highMorale = array_filter($staff, fn($s) => (int) $s['morale'] >= 80);

        $boosts = [
            'bonus_pay' => ['name' => 'Bonus Pay', 'desc' => 'Give all staff a one-time cash bonus. +15 morale.', 'icon' => 'fa-solid fa-money-bill-wave', 'color' => 'text-success', 'morale' => 15, 'cost' => 5000],
            'team_lunch' => ['name' => 'Team Lunch', 'desc' => 'Treat everyone to a mountain restaurant meal. +10 morale.', 'icon' => 'fa-solid fa-utensils', 'color' => 'text-warning', 'morale' => 10, 'cost' => 2000],
            'day_off' => ['name' => 'Rest Day', 'desc' => 'Give all staff a day off to recover. +20 morale, but no operations for 1 day.', 'icon' => 'fa-solid fa-bed', 'color' => 'text-info', 'morale' => 20, 'cost' => 0],
            'training' => ['name' => 'Skills Training', 'desc' => 'Send staff to training workshops. +5 morale, +1 experience.', 'icon' => 'fa-solid fa-graduation-cap', 'color' => 'text-primary', 'morale' => 5, 'cost' => 3000],
            'new_uniforms' => ['name' => 'New Uniforms', 'desc' => 'Fresh uniforms for the whole team. +8 morale.', 'icon' => 'fa-solid fa-shirt', 'color' => 'text-secondary', 'morale' => 8, 'cost' => 1500],
            'party' => ['name' => 'Staff Party', 'desc' => 'End-of-week celebration. +25 morale for everyone.', 'icon' => 'fa-solid fa-champagne-glasses', 'color' => 'text-warning', 'morale' => 25, 'cost' => 8000],
            'raise' => ['name' => 'Salary Raise', 'desc' => 'Increase all salaries by 10%. Permanent +12 morale.', 'icon' => 'fa-solid fa-arrow-trend-up', 'color' => 'text-success', 'morale' => 12, 'cost' => 0, 'salary_increase' => true],
            'individual_bonus' => ['name' => 'Individual Bonus', 'desc' => 'Reward your lowest morale staff member. +30 morale for them.', 'icon' => 'fa-solid fa-user-check', 'color' => 'text-info', 'morale' => 30, 'cost' => 1000, 'individual' => true],
        ];

        return view('morale/index', [
            'staff' => $staff,
            'avgMorale' => $avgMorale,
            'lowMorale' => $lowMorale,
            'highMorale' => $highMorale,
            'boosts' => $boosts,
        ]);
    }

    public function boost()
    {
        $userId = auth()->id();
        $action = $this->request->getPost('action');
        $staffModel = new StaffModel();
        $staff = $staffModel->where('user_id', $userId)->where('status !=', 'fired')->findAll();

        if (empty($staff)) {
            return redirect()->back()->with('error', 'No staff to boost.');
        }

        $actions = [
            'bonus_pay' => ['morale' => 15, 'cost' => 5000],
            'team_lunch' => ['morale' => 10, 'cost' => 2000],
            'day_off' => ['morale' => 20, 'cost' => 0],
            'training' => ['morale' => 5, 'cost' => 3000],
            'new_uniforms' => ['morale' => 8, 'cost' => 1500],
            'party' => ['morale' => 25, 'cost' => 8000],
            'raise' => ['morale' => 12, 'cost' => 0],
            'individual_bonus' => ['morale' => 30, 'cost' => 1000],
        ];

        $names = [
            'bonus_pay' => 'Bonus Pay', 'team_lunch' => 'Team Lunch', 'day_off' => 'Rest Day',
            'training' => 'Skills Training', 'new_uniforms' => 'New Uniforms', 'party' => 'Staff Party',
            'raise' => 'Salary Raise', 'individual_bonus' => 'Individual Bonus',
        ];

        if (!isset($actions[$action])) {
            return redirect()->back()->with('error', 'Invalid action.');
        }

        $act = $actions[$action];

        if ($action === 'individual_bonus') {
            usort($staff, fn($a, $b) => (int) $a['morale'] - (int) $b['morale']);
            $lowest = $staff[0];
            $newMorale = min(100, (int) $lowest['morale'] + $act['morale']);
            $staffModel->update($lowest['id'], ['morale' => $newMorale]);
            log_activity($userId, 'Staff', 'Gave individual bonus to ' . $lowest['name'] . ' (+' . $act['morale'] . ' morale)', 'fa-solid fa-user-check');
            return redirect()->to('/morale')->with('success', $lowest['name'] . ' received a bonus! Morale: ' . $newMorale . '%');
        }

        if ($action === 'raise') {
            foreach ($staff as $s) {
                $newSalary = (int) round((int) $s['salary'] * 1.10);
                $newMorale = min(100, (int) $s['morale'] + $act['morale']);
                $staffModel->update($s['id'], ['salary' => $newSalary, 'morale' => $newMorale]);
            }
            log_activity($userId, 'Staff', 'Gave 10% salary raise to all staff (+' . $act['morale'] . ' morale)', 'fa-solid fa-arrow-trend-up');
            return redirect()->to('/morale')->with('success', 'All salaries increased by 10%! Morale boosted.');
        }

        foreach ($staff as $s) {
            $newMorale = min(100, (int) $s['morale'] + $act['morale']);
            $staffModel->update($s['id'], ['morale' => $newMorale]);
        }

        log_activity($userId, 'Staff', $names[$action] . ' — all staff morale +' . $act['morale'], 'fa-solid fa-face-smile');

        return redirect()->to('/morale')->with('success', $names[$action] . ' done! All staff morale +' . $act['morale'] . '.');
    }
}
