<?php

namespace App\Controllers;

class Tutorial extends BaseController
{
    public const STEPS = [
        [
            'title' => 'Welcome to Ski Manager!',
            'text' => "You're the new owner of a ski resort. Let's get you set up. This quick tour covers the basics - you can skip it anytime.",
            'action' => null,
            'page' => '/dashboard',
            'icon' => 'fa-solid fa-mountain-sun',
        ],
        [
            'title' => 'Check Your Finances',
            'text' => "See what you're working with. Open the Finances page to view your starting cash and daily budget.",
            'action' => 'Visit the Finances page',
            'page' => '/finances',
            'icon' => 'fa-solid fa-coins',
            'check' => 'visit_finances',
        ],
        [
            'title' => 'Hire Your First Staff',
            'text' => "A resort needs people to run it. On the Staff page, hire at least one employee - a groomer, ski patroller, or mechanic.",
            'action' => 'Hire a staff member',
            'page' => '/staff',
            'icon' => 'fa-solid fa-users',
            'check' => 'has_staff',
        ],
        [
            'title' => 'Build a Slope',
            'text' => "Give your guests somewhere to ski. Open the Trail Map, click a segment, and build your first slope.",
            'action' => 'Build a slope on the trail map',
            'page' => '/map',
            'icon' => 'fa-solid fa-map',
            'check' => 'has_slope',
        ],
        [
            'title' => 'Set Your Ticket Prices',
            'text' => "Guests need lift tickets. On the Tickets page, set your prices - higher prices mean more revenue per guest but fewer visitors.",
            'action' => 'Visit the Tickets page',
            'page' => '/tickets',
            'icon' => 'fa-solid fa-ticket',
            'check' => 'visit_tickets',
        ],
        [
            'title' => 'Check the Weather',
            'text' => "Weather drives visitor numbers, snow conditions, and energy costs. Check today's forecast on the Weather page.",
            'action' => 'Visit the Weather page',
            'page' => '/weather',
            'icon' => 'fa-solid fa-cloud-sun',
            'check' => 'visit_weather',
        ],
        [
            'title' => 'Build a Building',
            'text' => "Guests need places to eat and stay. Build a restaurant, hotel, or shop to earn extra revenue.",
            'action' => 'Build a building (hotel, restaurant, etc.)',
            'page' => '/hotels',
            'icon' => 'fa-solid fa-building',
            'check' => 'has_building',
        ],
        [
            'title' => 'Explore Your Resort',
            'text' => "Your Resort page is the command center - an overview of everything you own. Take a look.",
            'action' => 'Visit the Resort page',
            'page' => '/resort',
            'icon' => 'fa-solid fa-mountain-sun',
            'check' => 'visit_resort',
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
            'text' => "That's the basics. From here you can add snowmaking, night skiing, terrain parks, marketing, and more. Check the Achievements page for goals to chase. Good luck!",
            'action' => null,
            'page' => '/dashboard',
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
