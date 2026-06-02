<?php

namespace App\Controllers;

class Updates extends BaseController
{
    public function index(): string
    {
        $updates = [
            [
                'version' => '2.0.0',
                'date' => 'June 1, 2026',
                'title' => 'Ski Manager 2.0 Launch',
                'type' => 'major',
                'changes' => [
                    ['type' => 'new', 'text' => 'Complete rebuild on CodeIgniter 4 with DaisyUI'],
                    ['type' => 'new', 'text' => 'Interactive trail map with drawing tools'],
                    ['type' => 'new', 'text' => 'Real equipment brands — PistenBully, Prinoth, TechnoAlpin'],
                    ['type' => 'new', 'text' => 'Dynamic weather system with daily updates'],
                    ['type' => 'new', 'text' => 'Staff management with morale system'],
                    ['type' => 'new', 'text' => 'Financial system with loans and banking'],
                    ['type' => 'new', 'text' => 'Marketing campaigns'],
                    ['type' => 'new', 'text' => 'Government regulations with penalties'],
                    ['type' => 'new', 'text' => 'Environmental system with eco score'],
                    ['type' => 'new', 'text' => 'Night skiing with lighting system'],
                    ['type' => 'new', 'text' => 'Snowmaking with real snow cannon brands'],
                    ['type' => 'new', 'text' => 'Hotels, restaurants, ski rentals, real estate'],
                    ['type' => 'new', 'text' => 'Tournament hosting system'],
                    ['type' => 'new', 'text' => 'Achievement system with rewards'],
                    ['type' => 'new', 'text' => 'Daily login bonus with streak system'],
                    ['type' => 'new', 'text' => 'Génépis premium currency shop'],
                    ['type' => 'new', 'text' => 'Insurance system'],
                    ['type' => 'new', 'text' => 'Scenic lift designation for summer'],
                    ['type' => 'new', 'text' => 'Slope grooming with sector assignments'],
                    ['type' => 'new', 'text' => 'Ski school and lessons'],
                    ['type' => 'new', 'text' => 'Emergency & rescue hub'],
                    ['type' => 'new', 'text' => 'Off-season activities'],
                    ['type' => 'new', 'text' => 'Transportation system'],
                    ['type' => 'new', 'text' => 'Retail stores'],
                    ['type' => 'new', 'text' => 'Leaderboard'],
                    ['type' => 'new', 'text' => 'Activity log tracking all actions'],
                    ['type' => 'new', 'text' => 'Metric/Imperial unit switching'],
                    ['type' => 'new', 'text' => 'Dark/Light theme toggle'],
                    ['type' => 'improved', 'text' => 'Completely redesigned UI with modern dark theme'],
                    ['type' => 'improved', 'text' => 'Mobile-responsive navbar with dropdown menus'],
                    ['type' => 'improved', 'text' => 'Much better trail map building UX'],
                    ['type' => 'improved', 'text' => 'Font Awesome icon kit integration'],
                ],
            ],
        ];

        return view('updates/index', ['updates' => $updates]);
    }
}
