<?php

namespace App\Controllers;

class Compliance extends BaseController
{
    public function index()
    {
        if (!auth()->loggedIn()) return redirect()->to('/login');
        $tab = $this->request->getGet('tab') ?? 'government';
        if (!in_array($tab, ['government', 'insurance', 'environment'], true)) {
            $tab = 'government';
        }

        $userId = (int) auth()->id();
        $db = db_connect();

        // Insurance policies
        $policies = $db->table('insurance')->where('user_id', $userId)->get()->getResultArray();
        $activePolicies = array_filter($policies, fn($p) => (int) ($p['active'] ?? 0) === 1);
        $totalPremium = array_sum(array_map(fn($p) => (int) ($p['premium_per_day'] ?? $p['premium'] ?? 0), $activePolicies));
        $totalCoverage = array_sum(array_map(fn($p) => (int) ($p['coverage_amount'] ?? $p['coverage'] ?? 0), $activePolicies));

        $formattedPolicies = array_map(function ($p) {
            $rawIcon = $p['icon'] ?? 'fa-shield';
            $icon = preg_replace('/^fa-(solid|regular|brands)\s+/', '', (string) $rawIcon);
            $premium = (int) ($p['premium_per_day'] ?? $p['premium'] ?? 0);
            $coverage = (int) ($p['coverage_amount'] ?? $p['coverage'] ?? 0);
            return [
                'id' => (int) $p['id'],
                'name' => (string) ($p['name'] ?? $p['policy_type'] ?? 'Policy'),
                'premium_per_day' => $premium,
                'premiumText' => currency($premium),
                'coverage_amount' => $coverage,
                'coverageText' => currency($coverage),
                'active' => (bool) ($p['active'] ?? 0),
                'icon' => $icon,
            ];
        }, $policies);

        // Government regulations
        $regs = $db->table('regulations')->where('user_id', $userId)->get()->getResultArray();
        $compliant = count(array_filter($regs, fn($r) => (int) ($r['compliant'] ?? 0) === 1));
        $complianceScore = count($regs) > 0 ? (int) round($compliant / count($regs) * 100) : 100;

        $formattedRegs = array_map(function ($r) {
            $rawIcon = $r['icon'] ?? 'fa-gavel';
            $icon = preg_replace('/^fa-(solid|regular|brands)\s+/', '', (string) $rawIcon);
            $cost = (int) ($r['compliance_cost'] ?? 0);
            return [
                'id' => (int) $r['id'],
                'name' => (string) ($r['name'] ?? $r['regulation_type'] ?? 'Regulation'),
                'description' => (string) ($r['description'] ?? ''),
                'compliant' => (bool) ($r['compliant'] ?? 0),
                'compliance_cost' => $cost,
                'costText' => currency($cost),
                'icon' => $icon,
            ];
        }, $regs);

        $inspections = $db->table('activity_log')->where('user_id', $userId)->like('category', 'inspection')->orderBy('created_at', 'DESC')->limit(10)->get()->getResultArray();
        $formattedInspections = array_map(function ($i) {
            return [
                'id' => (int) ($i['id'] ?? 0),
                'dateText' => isset($i['created_at']) ? date('M j', strtotime($i['created_at'])) : '',
                'message' => (string) ($i['message'] ?? ''),
                'passed' => !str_contains(strtolower($i['message'] ?? ''), 'violation') && !str_contains(strtolower($i['message'] ?? ''), 'fine'),
                'fine' => (int) ($i['fine'] ?? 0),
                'fineText' => isset($i['fine']) && (int) $i['fine'] > 0 ? currency((int) $i['fine']) : null,
            ];
        }, $inspections);

        // Environment
        $env = $db->table('environmental')->where('user_id', $userId)->get()->getRowArray();
        if (!$env) {
            $db->table('environmental')->insert([
                'user_id' => $userId,
                'eco_score' => 50,
                'carbon_output' => 0,
                'renewable_pct' => 0,
                'waste_management' => 0,
                'wildlife_impact' => 50,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $env = $db->table('environmental')->where('user_id', $userId)->get()->getRowArray();
        }

        $page = [
            'tab' => $tab,
            'summary' => [
                'activeInsCount' => count($activePolicies),
                'totalInsCount' => count($policies),
                'totalPremiumText' => currency($totalPremium),
                'complianceScore' => $complianceScore,
                'compliantCount' => $compliant,
                'totalRegCount' => count($regs),
                'ecoScore' => (int) ($env['eco_score'] ?? 50),
            ],
            'insurance' => [
                'policies' => $formattedPolicies,
                'totalPremiumText' => currency($totalPremium),
                'totalCoverageText' => currency($totalCoverage),
            ],
            'government' => [
                'regulations' => $formattedRegs,
                'inspections' => $formattedInspections,
            ],
            'environment' => [
                'eco_score' => (int) ($env['eco_score'] ?? 50),
                'renewable_pct' => (int) ($env['renewable_pct'] ?? 0),
                'waste_management' => (int) ($env['waste_management'] ?? 0),
                'wildlife_impact' => (int) ($env['wildlife_impact'] ?? 0),
            ],
        ];

        return view('compliance/index', ['page' => $page]);
    }
}
