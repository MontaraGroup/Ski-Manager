<?php

namespace App\Controllers;

use App\Models\LiftTicketModel;
use App\Models\TicketSaleModel;

class Tickets extends BaseController
{
    public function index(): string
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

        $recentSales = $salesModel->where('user_id', $userId)->orderBy('game_day', 'DESC')->limit(7)->findAll();
        $todaySales = $salesModel->where('user_id', $userId)->where('game_day', $gameDay)->findAll();

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

        return view('tickets/index', [
            'tickets' => $tickets,
            'ticketLabels' => $ticketLabels,
            'recentSales' => $recentSales,
            'todayRevenue' => $todayRevenue,
            'todayCount' => $todayCount,
            'gameDay' => $gameDay,
        ]);
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
