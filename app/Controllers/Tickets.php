<?php

namespace App\Controllers;

use App\Models\LiftTicketModel;
use App\Models\TicketSaleModel;

class Tickets extends BaseController
{
    public function index(): string
    {
        return view('tickets/index', ['page' => $this->pageData()]);
    }

    /** What the page shows; the mobile API sends the same data (Api\V1\App) */
    protected function pageData(): array
    {
        $userId = auth()->id();
        $ticketModel = new LiftTicketModel();
        $salesModel = new TicketSaleModel();

        $tickets = $ticketModel->where('user_id', $userId)->findAll();

        if (empty($tickets)) {
            $defaults = [
                ['ticket_type' => 'half_day', 'price' => 35],
                ['ticket_type' => 'full_day', 'price' => 55],
                ['ticket_type' => 'two_day', 'price' => 95],
                ['ticket_type' => 'weekly', 'price' => 250],
                ['ticket_type' => 'season', 'price' => 800],
                ['ticket_type' => 'child', 'price' => 25],
                ['ticket_type' => 'senior', 'price' => 40],
                ['ticket_type' => 'group', 'price' => 45],
            ];
            foreach ($defaults as $d) {
                $ticketModel->insert(array_merge($d, ['user_id' => $userId, 'active' => 1]));
            }
            $tickets = $ticketModel->where('user_id', $userId)->findAll();
        }

        $startDate = getSeasonStartDate();
        $today = date('Y-m-d');
        $gameDay = max(1, (int) ((strtotime($today) - strtotime($startDate)) / 86400) + 1);

        $recentSales = $salesModel->where('user_id', $userId)->orderBy('created_at', 'DESC')->limit(7)->findAll();
        $todaySales = $salesModel->where('user_id', $userId)->where('game_day', $gameDay)->where('created_at >=', getSeasonStartDate() . ' 00:00:00')->findAll();

        $todayRevenue = array_sum(array_column($todaySales, 'revenue'));
        $todayCount = array_sum(array_column($todaySales, 'quantity'));

        $ticketLabels = [
            'half_day' => ['name' => 'Half Day', 'icon' => 'fa-solid fa-clock', 'desc' => 'Valid for morning or afternoon'],
            'full_day' => ['name' => 'Full Day', 'icon' => 'fa-solid fa-sun', 'desc' => 'Valid for the entire day'],
            'two_day' => ['name' => '2-Day Pass', 'icon' => 'fa-solid fa-calendar-days', 'desc' => 'Valid for 2 consecutive days'],
            'weekly' => ['name' => 'Weekly Pass', 'icon' => 'fa-solid fa-calendar-week', 'desc' => 'Valid for 7 consecutive days'],
            'season' => ['name' => 'Season Pass', 'icon' => 'fa-solid fa-id-card', 'desc' => 'Valid for the entire season'],
            'child' => ['name' => 'Child (under 12)', 'icon' => 'fa-solid fa-child', 'desc' => 'Discounted rate for children'],
            'senior' => ['name' => 'Senior (65+)', 'icon' => 'fa-solid fa-person-cane', 'desc' => 'Discounted rate for seniors'],
            'group' => ['name' => 'Group (10+)', 'icon' => 'fa-solid fa-people-group', 'desc' => 'Per-person rate for groups of 10+'],
        ];

        $finance = db_connect()->table('player_finances')->select('last_tick')->where('user_id', $userId)->get()->getRowArray();
        $v2 = featureEnabled('economy_v2');
        $rawLastTick = json_decode($finance['last_tick'] ?? '', true) ?: null;

        $formattedTickets = [];
        foreach ($tickets as $ticket) {
            $typeKey = $ticket['ticket_type'];
            $label = $ticketLabels[$typeKey] ?? ['name' => ucfirst($typeKey), 'icon' => 'fa-solid fa-ticket', 'desc' => ''];
            $icon = preg_match('/^[a-z0-9 -]+$/i', $label['icon']) ? $label['icon'] : 'fa-solid fa-ticket';
            $refPrice = \App\Libraries\Economy::TICKET_REFERENCE[$typeKey] ?? null;
            $mixPct = isset(\App\Libraries\Economy::TICKET_MIX[$typeKey]) ? (int) round(\App\Libraries\Economy::TICKET_MIX[$typeKey] * 100) : null;
            $priceDemandPct = $refPrice ? (int) round(\App\Libraries\Economy::priceDemand($typeKey, (float) $ticket['price']) * 100) : null;

            $formattedTickets[] = [
                'id' => (int) $ticket['id'],
                'ticket_type' => $typeKey,
                'name' => (string) $label['name'],
                'icon' => $icon,
                'desc' => (string) $label['desc'],
                'price' => (int) $ticket['price'],
                'priceText' => currency((int) $ticket['price']),
                'active' => (bool) $ticket['active'],
                'refPrice' => $refPrice,
                'refPriceText' => $refPrice ? currency($refPrice) : null,
                'mixPct' => $mixPct,
                'priceDemandPct' => $priceDemandPct,
            ];
        }

        $lastTickData = null;
        if ($rawLastTick) {
            $lastTickData = [
                'visitorsText' => number_format((int) ($rawLastTick['visitors'] ?? 0)),
                'perVisitText' => currency((int) round($rawLastTick['per_visit'] ?? 0)),
                'priceDemandPct' => (int) round(($rawLastTick['price_demand'] ?? 1) * 100),
                'turnedAwayText' => !empty($rawLastTick['turned_away']) ? number_format((int) $rawLastTick['turned_away']) : null,
            ];
        }

        $page = [
            'v2' => $v2,
            'stats' => [
                'todayRevenueText' => currency($todayRevenue),
                'todayCountText' => number_format($todayCount),
                'gameDay' => $gameDay,
            ],
            'tickets' => $formattedTickets,
            'lastTick' => $lastTickData,
            'currencySymbol' => currencySymbol(),
            'rules' => [
                'maxDemand' => \App\Libraries\Economy::MAX_DEMAND,
                'priceElasticity' => \App\Libraries\Economy::PRICE_ELASTICITY,
            ],
        ];

        return $page;
    }

    public function updatePrice()
    {
        $userId = auth()->id();
        $ticketModel = new LiftTicketModel();

        $id = (int) $this->request->getPost('id');
        $price = (int) $this->request->getPost('price');
        $active = $this->request->getPost('active') ? 1 : 0;

        $ticket = $ticketModel->where('id', $id)->where('user_id', $userId)->first();
        if (!$ticket) {
            return redirect()->back()->with('error', 'Ticket not found.');
        }

        $price = max(0, min(9999, $price));

        $ticketModel->update($id, ['price' => $price, 'active' => $active]);

        log_activity($userId, 'Tickets', 'Updated ticket pricing', 'fa-solid fa-ticket');
        return redirect()->to('/tickets')->with('success', 'Ticket pricing updated.');
    }
}
