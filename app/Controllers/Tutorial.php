<?php

namespace App\Controllers;

class Tutorial extends BaseController
{
    /** When the nightly game:tick runs (root crontab on the VPS), in the app's timezone */
    public const SETTLE_TIME = '00:05';

    /**
     * Ordered so a new player builds first and only then hires: staff hired before there's a post
     * for them are paid to do nothing and lose morale.
     *
     * Saved progress is an index into this list, so the first six steps keep their positions
     * (Demo::TUTORIAL_START_STEP points at ticket prices). Older saves past the end land on the
     * last step. {settle} is replaced in the browser with SETTLE_TIME in the player's own time.
     */
    public const STEPS = [
        [
            'title' => 'Welcome to Ski Manager',
            'text' => "You run a ski resort. The game moves one day per real day: each night at {settle}, skiers come, you're paid for tickets and charged running costs. First, build a slope and a lift so there's something to ski.",
            'action' => null,
            'page' => '/dashboard',
            'icon' => 'fa-solid fa-mountain-sun',
        ],
        [
            'title' => 'Build a slope',
            'text' => "Slopes bring skiers. On the Trail Map, press Build, choose Slopes, pick a run on the map or in the list, then press Construct. A blue run near the base is a good start.",
            'action' => 'Build a slope on the Trail Map',
            'page' => '/map',
            'icon' => 'fa-solid fa-map',
            'check' => 'has_slope',
        ],
        [
            'title' => 'Build a lift',
            'text' => "Skiers need a lift to reach your slope: without one, only about 100 skiers walk up each day. In Build, choose Lifts, pick one that starts near the bottom of your slope, choose a lift type, then press Construct. A surface lift is the cheapest.",
            'action' => 'Build a lift on the Trail Map',
            'page' => '/map',
            'icon' => 'fa-solid fa-cable-car',
            'check' => 'has_lift',
        ],
        [
            'title' => 'Set your ticket prices',
            'text' => "Skiers buy lift tickets. Prices near the suggested ones are a safe start: higher prices earn more per skier, but fewer come.",
            'action' => 'Open the Tickets page',
            'page' => '/tickets',
            'icon' => 'fa-solid fa-ticket',
            'check' => 'visit_tickets',
        ],
        [
            'title' => 'Hire your first staff',
            'text' => "Hire a Lift Mechanic for your lift: it wears more slowly and needs fewer repairs. The hire page shows what else your resort needs.",
            'action' => 'Hire a staff member',
            'page' => '/staff/hire',
            'icon' => 'fa-solid fa-users',
            'check' => 'has_staff',
        ],
        [
            'title' => 'Build a restaurant',
            'text' => "Most skiers stop for a meal: a restaurant earns from about 7 in 10 of your visitors.",
            'action' => 'Build a restaurant (or another building)',
            'page' => '/restaurants',
            'icon' => 'fa-solid fa-utensils',
            'check' => 'has_building',
        ],
        [
            'title' => 'Your first night',
            'text' => "Your resort is ready. Your first day settles tonight at {settle}. Come back tomorrow to see how you did on the dashboard.",
            'action' => null,
            'page' => '/dashboard',
            'icon' => 'fa-solid fa-moon',
            // Shown as links on this card, not as steps of their own
            'links' => [
                ['label' => 'Weather forecast', 'href' => '/weather'],
                ['label' => 'Finances', 'href' => '/finances'],
                ['label' => 'Daily bonus', 'href' => '/daily-bonus'],
                ['label' => 'Guides', 'href' => '/guides'],
            ],
        ],
    ];

    /** Next nightly settle as an ISO 8601 time, so the browser can show it in the player's own timezone */
    private static function settleAt(): string
    {
        $tz = new \DateTimeZone(config('App')->appTimezone);
        $now = new \DateTimeImmutable('now', $tz);
        $at = $now->modify('today ' . self::SETTLE_TIME);
        if ($at <= $now) $at = $at->modify('+1 day');
        return $at->format(DATE_ATOM);
    }

    /** The JSON the tutorial card renders for one step */
    private function stepPayload(int $step): array
    {
        $data = self::STEPS[$step];
        $payload = [
            'step' => $step,
            'total' => count(self::STEPS),
            'data' => $data,
            'canAdvance' => $this->canAdvance($data),
            'settleAt' => self::settleAt(),
            'done' => false,
        ];
        if ($step === count(self::STEPS) - 1) {
            $estimate = Demo::estimate((int) auth()->id());
            $payload['estimate'] = $estimate ? array_intersect_key($estimate, array_flip(['skiers', 'income', 'costs'])) : null;
        }
        return $payload;
    }

    /** Saves from before the steps were cut to seven point past the end; they land on the last step */
    private static function clampStep(int $step): int
    {
        return min(max(0, $step), count(self::STEPS) - 1);
    }

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

        return $this->response->setJSON($this->stepPayload(self::clampStep((int) $progress['current_step'])));
    }

    public function advance(): \CodeIgniter\HTTP\ResponseInterface
    {
        $userId = auth()->id();
        $db = db_connect();
        $progress = $this->getProgress();

        if (!$progress || $progress['completed'] || $progress['skipped']) {
            return $this->response->setJSON(['done' => true]);
        }

        $step = self::clampStep((int) $progress['current_step']);

        // Enforce gating server-side: a gated step can only advance once its action is done.
        if (!$this->canAdvance(self::STEPS[$step])) {
            return $this->response->setJSON($this->stepPayload($step));
        }

        $nextStep = $step + 1;

        if ($nextStep >= count(self::STEPS)) {
            $db->table('tutorial_progress')->where('user_id', $userId)->update(['current_step' => $nextStep, 'completed' => 1]);
            return $this->response->setJSON(['done' => true, 'completed' => true]);
        }

        $db->table('tutorial_progress')->where('user_id', $userId)->update(['current_step' => $nextStep]);
        return $this->response->setJSON($this->stepPayload($nextStep));
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
        return $this->response->setJSON($this->stepPayload(0));
    }
}
