<?php

namespace App\Controllers;

class Compliance extends BaseController
{
    public function index()
    {
        if (!auth()->loggedIn()) return redirect()->to('/login');
        $tab = $this->request->getGet('tab') ?? 'government';

        $insurance = new Insurance();
        $government = new Government();
        $environment = new Environment();

        // Get data from each controller's index method via their view data
        // We'll call them directly and merge
        $userId = (int) auth()->id();
        $db = db_connect();

        // Insurance data
        $insCtrl = new Insurance();
        $insData = $this->getInsuranceData($userId, $db);

        // Government data
        $govData = $this->getGovernmentData($userId, $db);

        // Environment data
        $envData = $this->getEnvironmentData($userId, $db);

        return view('compliance/index', array_merge(
            ['tab' => $tab],
            $insData,
            $govData,
            $envData
        ));
    }

    private function getInsuranceData(int $userId, $db): array
    {
        $policies = $db->table('insurance')->where('user_id', $userId)->get()->getResultArray();
        $activePolicies = array_filter($policies, fn($p) => ($p['active'] ?? 0) == 1);
        $totalPremium = array_sum(array_column($activePolicies, 'premium'));
        $totalCoverage = array_sum(array_column($activePolicies, 'coverage'));
        return ['policies' => $policies, 'totalPremium' => $totalPremium, 'totalCoverage' => $totalCoverage, 'activeInsCount' => count($activePolicies)];
    }

    private function getGovernmentData(int $userId, $db): array
    {
        $regs = $db->table('regulations')->where('user_id', $userId)->get()->getResultArray();
        $compliant = count(array_filter($regs, fn($r) => ($r['compliant'] ?? 0) == 1));
        $nonCompliant = count($regs) - $compliant;
        $totalCost = array_sum(array_map(fn($r) => ($r['compliant'] ?? 0) ? (int)($r['compliance_cost'] ?? 0) : 0, $regs));
        $complianceScore = count($regs) > 0 ? round($compliant / count($regs) * 100) : 100;
        $inspections = $db->table('activity_log')->where('user_id', $userId)->like('category', 'inspection')->orderBy('created_at', 'DESC')->limit(5)->get()->getResultArray();
        return ['regs' => $regs, 'compliant' => $compliant, 'totalRegCost' => $totalCost, 'complianceScore' => $complianceScore, 'nonCompliant' => $nonCompliant, 'inspections' => $inspections];
    }

    private function getEnvironmentData(int $userId, $db): array
    {
        $env = $db->table('environmental')->where('user_id', $userId)->get()->getRowArray();
        if (!$env) {
            $db->table('environmental')->insert(['user_id' => $userId, 'eco_score' => 50, 'carbon_offset' => 0]);
            $env = $db->table('environmental')->where('user_id', $userId)->get()->getRowArray();
        }
        $upgrades = [];
        return ['env' => $env, 'ecoUpgrades' => $upgrades];
    }
}
