<?php

namespace App\Controllers;

class Tutorial extends BaseController
{
    /**
     * Ordered so a new player builds first and only then hires: staff hired before there's a post
     * for them are paid to do nothing and lose morale.
     */
    public const STEPS = [
        [
            'title' => 'Welcome to Ski Manager!',
            'text' => "You're the new owner of a ski resort. Let's get you set up: build a slope and a lift, price your tickets, then hire and build around them. You can skip this anytime.",
            'action' => null,
            'page' => '/dashboard',
            'icon' => 'fa-solid fa-mountain-sun',
        ],
        [
            'title' => 'Build a Slope',
            'text' => "Slopes are what bring skiers. Open the Trail Map, press Build, and pick a slope: a moderate one near the base is a good start.",
            'action' => 'Build a slope on the trail map',
            'page' => '/map',
            'icon' => 'fa-solid fa-map',
            'check' => 'has_slope',
        ],
        [
            'title' => 'Build a Lift',
            'text' => "Skiers need a lift to reach your slope: without one only about 100 walk-up skiers come each day. On the Trail Map, open Build and choose a lift that serves your slope.",
            'action' => 'Build a lift on the trail map',
            'page' => '/map',
            'icon' => 'fa-solid fa-cable-car',
            'check' => 'has_lift',
        ],
        [
            'title' => 'Set Your Ticket Prices',
            'text' => "Guests need lift tickets. Prices near the suggested ones are a safe start: higher prices earn more per skier but fewer come.",
            'action' => 'Visit the Tickets page',
            'page' => '/tickets',
            'icon' => 'fa-solid fa-ticket',
            'check' => 'visit_tickets',
        ],
        [
            'title' => 'Hire Your First Staff',
            'text' => "Now that you have a lift, hire a Lift Mechanic for it: it wears more slowly and needs fewer repairs. The hire page shows what else your resort needs.",
            'action' => 'Hire a staff member',
            'page' => '/staff/hire',
            'icon' => 'fa-solid fa-users',
            'check' => 'has_staff',
        ],
        [
            'title' => 'Build a Restaurant',
            'text' => "Most skiers stop for a meal: a restaurant earns from about 7 in 10 of your visitors.",
            'action' => 'Build a restaurant (or another building)',
            'page' => '/restaurants',
            'icon' => 'fa-solid fa-utensils',
            'check' => 'has_building',
        ],
        [
            'title' => 'Check the Weather',
            'text' => "Weather changes how many skiers come and how fast slopes wear. The 5-day forecast is exact, so you can plan ahead.",
            'action' => 'Visit the Weather page',
            'page' => '/weather',
            'icon' => 'fa-solid fa-cloud-sun',
            'check' => 'visit_weather',
        ],
        [
            'title' => 'Check Your Finances',
            'text' => "Each night the game adds up your income and costs. The Finances page shows where your money goes.",
            'action' => 'Visit the Finances page',
            'page' => '/finances',
            'icon' => 'fa-solid fa-coins',
            'check' => 'visit_finances',
        ],
        [
            'title' => 'Claim Your Daily Bonus',
            'text' => "Log in each day to claim a bonus reward. Keep the streak going for bigger payouts.",
            'action' => 'Claim your daily bonus',
            'page' => '/daily-bonus',
            'icon' => 'fa-solid fa-gift',
            'check' => 'claimed_bonus',
        ],
        [
            'title' => "You're Ready!",
            'text' => "That's the basics. Your first night's results appear on the dashboard tomorrow. The Guides explain grooming, snowmaking, pricing and staff in detail. Good luck!",
            'action' => null,
            'page' => '/guides',
            'icon' => 'fa-solid fa-trophy',
        ],
    ];

    public function getProgress()
    {
        $userId = auth()->id();
        if (!$userId) return null;

        $db = db_connect();
        $progress = $db->table('tutorial_progress')->where('user_id', $userId)->get()->getRowArray();

        if (!$progress) {
            $db->table('tutorial_progress')->insert(['user_id' => $userId, 'current_step' => 0]);
            $progress = $db->table('tutorial_progress')->where('user_id', $userId)->get()->getRowArray();
        }

        return $progress;
    }

    private function canAdvance(array $stepData): bool
    {
        $check = $stepData['check'] ?? null;
        if ($check === null) return true;

        $userId = auth()->id();
        $db = db_connect();

        switch ($check) {
            case 'visit_finances':
            case 'visit_tickets':
            case 'visit_weather':
            case 'visit_resort':
                $page = $stepData['page'];
                $referer = $this->request->getHeaderLine('Referer');
                $currentPage = $this->request->getGet('page') ?? '';
                return str_contains($referer, $page) || str_contains($currentPage, $page);
            case 'has_staff':
                return $db->table('staff')->where('user_id', $userId)->where('status', 'active')->countAllResults() > 0;
            case 'has_slope':
                return $db->table('player_items')->where('user_id', $userId)->where('item_type', 'slope')->countAllResults() > 0;
            case 'has_lift':
                return $db->table('player_items')->where('user_id', $userId)->where('item_type', 'lift')->countAllResults() > 0;
            case 'has_building':
                return $db->table('buildings')->where('user_id', $userId)->countAllResults() > 0;
            case 'claimed_bonus':
                $bonus = $db->table('daily_bonus')->where('user_id', $userId)->get()->getRowArray();
                return $bonus && ($bonus['last_claim_date'] ?? null) === date('Y-m-d');
        }
        return false;
    }

    public function checkStep(): \CodeIgniter\HTTP\ResponseInterface
    {
        $progress = $this->getProgress();

        if (!$progress || $progress['completed'] || $progress['skipped']) {
            return $this->response->setJSON(['done' => true]);
        }

        $step = (int) $progress['current_step'];
        $stepData = self::STEPS[$step] ?? null;

        if (!$stepData) {
            return $this->response->setJSON(['done' => true]);
        }

        return $this->response->setJSON([
            'step' => $step,
            'total' => count(self::STEPS),
            'data' => $stepData,
            'canAdvance' => $this->canAdvance($stepData),
            'done' => false,
        ]);
    }

    public function advance(): \CodeIgniter\HTTP\ResponseInterface
    {
        $userId = auth()->id();
        $db = db_connect();
        $progress = $this->getProgress();

        if (!$progress || $progress['completed'] || $progress['skipped']) {
            return $this->response->setJSON(['done' => true]);
        }

        $step = (int) $progress['current_step'];
        $stepData = self::STEPS[$step] ?? null;

        // Enforce gating server-side: a gated step can only advance once its action is done.
        if ($stepData && !$this->canAdvance($stepData)) {
            return $this->response->setJSON([
                'step' => $step,
                'total' => count(self::STEPS),
                'data' => $stepData,
                'canAdvance' => false,
                'done' => false,
            ]);
        }

        $nextStep = $step + 1;

        if ($nextStep >= count(self::STEPS)) {
            $db->table('tutorial_progress')->where('user_id', $userId)->update(['current_step' => $nextStep, 'completed' => 1]);
            return $this->response->setJSON(['done' => true, 'completed' => true]);
        }

        $db->table('tutorial_progress')->where('user_id', $userId)->update(['current_step' => $nextStep]);
        return $this->response->setJSON([
            'step' => $nextStep,
            'total' => count(self::STEPS),
            'data' => self::STEPS[$nextStep],
            'canAdvance' => $this->canAdvance(self::STEPS[$nextStep]),
            'done' => false,
        ]);
    }

    public function skip(): \CodeIgniter\HTTP\ResponseInterface
    {
        $userId = auth()->id();
        $db = db_connect();
        $db->table('tutorial_progress')->where('user_id', $userId)->update(['skipped' => 1]);
        return $this->response->setJSON(['done' => true]);
    }

    public function restart(): \CodeIgniter\HTTP\ResponseInterface
    {
        $userId = auth()->id();
        $db = db_connect();
        $db->table('tutorial_progress')->where('user_id', $userId)->update(['current_step' => 0, 'completed' => 0, 'skipped' => 0]);
        return $this->response->setJSON(['done' => false, 'step' => 0, 'data' => self::STEPS[0]]);
    }
}
