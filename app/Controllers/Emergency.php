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

        helper(['time']);
        $totalCoverage = array_sum(array_column($activeStations, 'capacity'));
        $coverageRatio = $slopes > 0 ? (int) min(100, round($totalCoverage / $slopes * 100)) : 100;
        $safetyScore = (int) min(100, round($coverageRatio * 0.4 + $patrolStaff * 8 + count($medics) * 12 + count($activeStations) * 10));

        $incidents = $db->table('activity_log')->where('user_id', $userId)
            ->groupStart()
                ->like('category', 'accident')->orLike('category', 'rescue')
                ->orLike('category', 'inspection')->orLike('category', 'emergency')
            ->groupEnd()
            ->orderBy('created_at', 'DESC')->limit(10)->get()->getResultArray();

        $formattedIncidents = array_map(function($inc) {
            $cat = $inc['category'] ?? 'incident';
            return [
                'id' => (int) $inc['id'],
                'category' => $cat,
                'message' => $inc['message'] ?? '',
                'created_at' => $inc['created_at'] ?? '',
                'timeAgo' => !empty($inc['created_at']) ? timeAgo($inc['created_at']) : 'recently',
            ];
        }, $incidents);

        $riskLevel = $safetyScore >= 80 ? 'Low' : ($safetyScore >= 50 ? 'Moderate' : 'High');

        $checklist = [
            ['id' => 'station', 'text' => 'Build a patrol station', 'done' => count($activeStations) > 0, 'link' => '/ski-patrol'],
            ['id' => 'patrol', 'text' => 'Hire ski patrol staff', 'done' => $patrolStaff > 0, 'link' => '/staff/hire'],
            ['id' => 'medic', 'text' => 'Hire a medic', 'done' => count($medics) > 0, 'link' => '/staff/hire'],
            ['id' => 'coverage', 'text' => 'Full slope coverage', 'done' => $coverageRatio >= 100, 'link' => '/ski-patrol'],
            ['id' => 'insurance', 'text' => 'Activate 3+ insurance policies', 'done' => count($insurance) >= 3, 'link' => '/insurance'],
            ['id' => 'score', 'text' => 'Reach 80+ safety score', 'done' => $safetyScore >= 80, 'link' => null],
        ];

        $page = [
            'safetyScore' => $safetyScore,
            'riskLevel' => $riskLevel,
            'coverageRatio' => $coverageRatio,
            'totalCoverage' => $totalCoverage,
            'slopes' => $slopes,
            'uncoveredSlopes' => max(0, $slopes - $totalCoverage),
            'activeStations' => count($activeStations),
            'totalStations' => count($patrolStations),
            'patrolStaff' => $patrolStaff,
            'medicsCount' => count($medics),
            'insuranceCount' => count($insurance),
            'checklist' => $checklist,
            'incidents' => $formattedIncidents,
        ];

        return view('emergency/index', ['page' => $page]);
    }
}
