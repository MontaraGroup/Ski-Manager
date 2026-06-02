<?php
namespace App\Controllers;
class Insurance extends BaseController
{
    public function index(): string
    {
        $userId = auth()->id();
        $db = db_connect();
        $policies = $db->table('insurance')->where('user_id', $userId)->get()->getResultArray();
        if (empty($policies)) {
            $defaults = [
                ['type' => 'liability', 'name' => 'Public Liability', 'premium' => 500, 'coverage' => 100000, 'icon' => 'fa-solid fa-shield-halved'],
                ['type' => 'property', 'name' => 'Property Damage', 'premium' => 800, 'coverage' => 200000, 'icon' => 'fa-solid fa-building'],
                ['type' => 'accident', 'name' => 'Guest Accident', 'premium' => 1200, 'coverage' => 300000, 'icon' => 'fa-solid fa-user-injured'],
                ['type' => 'weather', 'name' => 'Weather Damage', 'premium' => 600, 'coverage' => 150000, 'icon' => 'fa-solid fa-cloud-bolt'],
                ['type' => 'equipment', 'name' => 'Equipment Breakdown', 'premium' => 400, 'coverage' => 80000, 'icon' => 'fa-solid fa-gear'],
                ['type' => 'business', 'name' => 'Business Interruption', 'premium' => 1500, 'coverage' => 500000, 'icon' => 'fa-solid fa-briefcase'],
            ];
            foreach ($defaults as $d) {
                $db->table('insurance')->insert(['user_id' => $userId, 'policy_type' => $d['type'], 'name' => $d['name'], 'premium_per_day' => $d['premium'], 'coverage_amount' => $d['coverage'], 'active' => 0, 'created_at' => date('Y-m-d H:i:s')]);
            }
            $policies = $db->table('insurance')->where('user_id', $userId)->get()->getResultArray();
        }
        $activePolicies = array_filter($policies, fn($p) => $p['active']);
        $totalPremium = array_sum(array_column($activePolicies, 'premium_per_day'));
        $totalCoverage = array_sum(array_column($activePolicies, 'coverage_amount'));
        $icons = ['liability' => 'fa-solid fa-shield-halved', 'property' => 'fa-solid fa-building', 'accident' => 'fa-solid fa-user-injured', 'weather' => 'fa-solid fa-cloud-bolt', 'equipment' => 'fa-solid fa-gear', 'business' => 'fa-solid fa-briefcase'];
        return view('insurance/index', ['policies' => $policies, 'totalPremium' => $totalPremium, 'totalCoverage' => $totalCoverage, 'activeCount' => count($activePolicies), 'icons' => $icons]);
    }
    public function toggle(int $id)
    {
        $userId = auth()->id();
        $db = db_connect();
        $p = $db->table('insurance')->where('id', $id)->where('user_id', $userId)->get()->getRowArray();
        if (!$p) return redirect()->back()->with('error', 'Not found.');
        $db->table('insurance')->where('id', $id)->update(['active' => $p['active'] ? 0 : 1]);
        log_activity($userId, 'Insurance', $p['name'] . ($p['active'] ? ' cancelled' : ' activated'), 'fa-solid fa-shield-halved');
        return redirect()->to('/insurance')->with('success', $p['name'] . ($p['active'] ? ' cancelled.' : ' activated.'));
    }
}
