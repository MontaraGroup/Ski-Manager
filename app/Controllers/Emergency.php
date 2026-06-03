<?php
namespace App\Controllers;
class Emergency extends BaseController
{
    public function index(): string
    {
        $userId = auth()->id();
        $db = db_connect();

        $patrolStations = $db->table('buildings')->where('user_id', $userId)->where('building_type', 'ski_patrol')->get()->getResultArray();
        $activeStations = array_filter($patrolStations, fn($b) => $b['status'] === 'open');
        $patrolStaff = $db->table('staff')->where('user_id', $userId)->where('role', 'ski_patrol')->where('status', 'active')->countAllResults();
        $medics = $db->table('staff')->where('user_id', $userId)->where('role', 'medic')->where('status', 'active')->get()->getResultArray();
        $insurance = $db->table('insurance')->where('user_id', $userId)->where('active', 1)->get()->getResultArray();
        $slopes = $db->table('player_items')->where('user_id', $userId)->where('item_type', 'slope')->countAllResults();

        $totalCoverage = array_sum(array_column($activeStations, 'capacity'));
        $coverageRatio = $slopes > 0 ? min(100, round($totalCoverage / $slopes * 100)) : 100;
        $safetyScore = min(100, round($coverageRatio * 0.4 + $patrolStaff * 8 + count($medics) * 12 + count($activeStations) * 10));

        $incidents = $db->table('activity_log')->where('user_id', $userId)
            ->groupStart()
                ->like('category', 'accident')->orLike('category', 'rescue')
                ->orLike('category', 'inspection')->orLike('category', 'emergency')
            ->groupEnd()
            ->orderBy('created_at', 'DESC')->limit(10)->get()->getResultArray();

        return view('emergency/index', [
            'patrolStations' => $patrolStations,
            'activeStations' => count($activeStations),
            'patrolStaff' => $patrolStaff,
            'medics' => $medics,
            'insurance' => $insurance,
            'slopes' => $slopes,
            'totalCoverage' => $totalCoverage,
            'coverageRatio' => $coverageRatio,
            'safetyScore' => $safetyScore,
            'incidents' => $incidents,
        ]);
    }
}
