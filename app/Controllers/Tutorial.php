<?php

namespace App\Controllers;

class Tutorial extends BaseController
{
    public const STEPS = [
        [
            'title' => 'Welcome to Ski Manager!',
            'text' => "You're the new owner of a ski resort. Let's get you set up. This tutorial will walk you through the basics — you can skip it anytime.",
            'action' => null,
            'page' => '/dashboard',
            'icon' => 'fa-solid fa-mountain-sun',
        ],
        [
            'title' => 'Check Your Finances',
            'text' => "First, let's see what you're working with. Head to the Finances page to see your starting cash and budget.",
            'action' => 'Visit the Finances page',
            'page' => '/finances',
            'icon' => 'fa-solid fa-coins',
            'check' => 'visit_finances',
        ],
        [
            'title' => 'Hire Your First Staff',
            'text' => "A resort needs people to run it. Go to the Staff page and hire at least one employee — a groomer, ski patrol, or mechanic.",
            'action' => 'Hire a staff member',
            'page' => '/staff',
            'icon' => 'fa-solid fa-users',
            'check' => 'has_staff',
        ],
        [
            'title' => 'Build a Slope',
            'text' => "Time to give your guests somewhere to ski! Open the Trail Map and build your first slope. Click on a segment and choose a slope type.",
            'action' => 'Build a slope on the trail map',
            'page' => '/map',
            'icon' => 'fa-solid fa-map',
            'check' => 'has_slope',
        ],
        [
            'title' => 'Set Your Ticket Prices',
            'text' => "Guests need lift tickets. Go to the Tickets page and make sure your prices are set. Higher prices mean more revenue but fewer visitors.",
            'action' => 'Visit the Tickets page',
            'page' => '/tickets',
            'icon' => 'fa-solid fa-ticket',
            'check' => 'visit_tickets',
        ],
        [
            'title' => 'Check the Weather',
            'text' => "Weather affects everything — visitor count, snow conditions, and energy costs. Check today's forecast on the Weather page.",
            'action' => 'Visit the Weather page',
            'page' => '/weather',
            'icon' => 'fa-solid fa-cloud-sun',
            'check' => 'visit_weather',
        ],
        [
            'title' => 'Build a Building',
            'text' => "Guests need places to eat and stay. Build a restaurant, hotel, or shop from the Buildings page to earn extra revenue.",
            'action' => 'Build a building (hotels, restaurants, etc.)',
            'page' => '/hotels',
            'icon' => 'fa-solid fa-building',
            'check' => 'has_building',
        ],
        [
            'title' => 'Explore Your Resort',
            'text' => "Check out your Resort page for an overview of everything you own. This is your command center.",
            'action' => 'Visit the Resort page',
            'page' => '/resort',
            'icon' => 'fa-solid fa-mountain-sun',
            'check' => 'visit_resort',
        ],
        [
            'title' => 'Claim Your Daily Bonus',
            'text' => "Every day you log in, you can claim a bonus reward. Don't miss it — streaks give bigger rewards!",
            'action' => 'Claim your daily bonus',
            'page' => '/daily-bonus',
            'icon' => 'fa-solid fa-gift',
            'check' => 'claimed_bonus',
        ],
        [
            'title' => "You're Ready!",
            'text' => "That's the basics! From here you can expand with snowmaking, night skiing, terrain parks, marketing campaigns, and more. Check the Achievements page for goals to work toward. Good luck!",
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

    public function checkStep(): \CodeIgniter\HTTP\ResponseInterface
    {
        $userId = auth()->id();
        $db = db_connect();
        $progress = $this->getProgress();

        if (!$progress || $progress['completed'] || $progress['skipped']) {
            return $this->response->setJSON(['done' => true]);
        }

        $step = (int) $progress['current_step'];
        $stepData = self::STEPS[$step] ?? null;

        if (!$stepData) {
            return $this->response->setJSON(['done' => true]);
        }

        $canAdvance = false;
        $check = $stepData['check'] ?? null;

        if ($check === null) {
            $canAdvance = true;
        } else {
            switch ($check) {
                case 'visit_finances':
                case 'visit_tickets':
                case 'visit_weather':
                case 'visit_resort':
                    $page = $stepData['page'];
                    $referer = $this->request->getHeaderLine('Referer');
                    $currentPage = $this->request->getGet('page') ?? '';
                    $canAdvance = str_contains($referer, $page) || str_contains($currentPage, $page);
                    break;
                case 'has_staff':
                    $canAdvance = $db->table('staff')->where('user_id', $userId)->where('status', 'active')->countAllResults() > 0;
                    break;
                case 'has_slope':
                    $canAdvance = $db->table('player_items')->where('user_id', $userId)->where('item_type', 'slope')->countAllResults() > 0;
                    break;
                case 'has_building':
                    $canAdvance = $db->table('buildings')->where('user_id', $userId)->countAllResults() > 0;
                    break;
                case 'claimed_bonus':
                    $gameDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime('2026-06-01')) / 86400) + 1);
                    $bonus = $db->table('daily_bonus')->where('user_id', $userId)->get()->getRowArray();
                    $canAdvance = $bonus && (int)($bonus['last_claim_day'] ?? 0) >= $gameDay;
                    break;
            }
        }

        return $this->response->setJSON([
            'step' => $step,
            'total' => count(self::STEPS),
            'data' => $stepData,
            'canAdvance' => $canAdvance,
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

        $nextStep = (int) $progress['current_step'] + 1;

        if ($nextStep >= count(self::STEPS)) {
            $db->table('tutorial_progress')->where('user_id', $userId)->update(['current_step' => $nextStep, 'completed' => 1]);
            return $this->response->setJSON(['done' => true, 'completed' => true]);
        }

        $db->table('tutorial_progress')->where('user_id', $userId)->update(['current_step' => $nextStep]);
        return $this->response->setJSON(['step' => $nextStep, 'data' => self::STEPS[$nextStep]]);
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
