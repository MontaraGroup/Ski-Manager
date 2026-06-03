<?php
namespace App\Controllers;
class Insurance extends BaseController
{
    private function getInsuranceTypes(): array
    {
        $db = db_connect();
        $rows = $db->table('insurance_types')->orderBy('sort_order')->get()->getResultArray();
        $types = [];
        foreach ($rows as $r) {
            $types[$r['type_key']] = ['name' => $r['name'], 'premium' => (int) $r['premium'], 'coverage' => (int) $r['coverage'], 'icon' => $r['icon']];
        }
        return $types;
    }

    public function index(): string
    {
        $userId = auth()->id();
        $db = db_connect();
        $insuranceTypes = $this->getInsuranceTypes();

        $policies = $db->table('insurance')->where('user_id', $userId)->get()->getResultArray();
        if (empty($policies)) {
            foreach ($insuranceTypes as $key => $t) {
                $db->table('insurance')->insert(['user_id' => $userId, 'policy_type' => $key, 'name' => $t['name'], 'premium_per_day' => $t['premium'], 'coverage_amount' => $t['coverage'], 'active' => 0, 'created_at' => date('Y-m-d H:i:s')]);
            }
            $policies = $db->table('insurance')->where('user_id', $userId)->get()->getResultArray();
        }

        $icons = [];
        foreach ($insuranceTypes as $key => $t) { $icons[$key] = $t['icon']; }

        $activePolicies = array_filter($policies, fn($p) => $p['active']);
        $totalPremium = array_sum(array_column($activePolicies, 'premium_per_day'));
        $totalCoverage = array_sum(array_column($activePolicies, 'coverage_amount'));
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
