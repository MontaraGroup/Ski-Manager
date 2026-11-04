<?php
namespace App\Controllers;
class Government extends BaseController
{
    private function getRegulationTypes(): array
    {
        $db = db_connect();
        $rows = $db->table('regulation_types')->orderBy('sort_order')->get()->getResultArray();
        $types = [];
        foreach ($rows as $r) {
            $types[$r['reg_key']] = ['name' => $r['name'], 'icon' => $r['icon'], 'cost' => (int) $r['cost'], 'penalty' => (int) $r['penalty'], 'benefit' => $r['benefit'], 'tier' => (int) $r['tier']];
        }
        return $types;
    }

    public function index(): string
    {
        if (isPageHidden('government')) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        $userId = auth()->id();
        $db = db_connect();
        $regConfig = $this->getRegulationTypes();

        $regs = $db->table('regulations')->where('user_id', $userId)->get()->getResultArray();
        if (empty($regs)) {
            foreach ($regConfig as $type => $r) {
                $db->table('regulations')->insert(['user_id' => $userId, 'regulation_type' => $type, 'name' => $r['name'], 'compliance_cost' => $r['cost'], 'penalty_risk' => $r['penalty'], 'compliant' => 0, 'created_at' => date('Y-m-d H:i:s')]);
            }
            $regs = $db->table('regulations')->where('user_id', $userId)->get()->getResultArray();
        }

        $existingTypes = array_column($regs, 'regulation_type');
        foreach ($regConfig as $type => $r) {
            if (!in_array($type, $existingTypes)) {
                $db->table('regulations')->insert(['user_id' => $userId, 'regulation_type' => $type, 'name' => $r['name'], 'compliance_cost' => $r['cost'], 'penalty_risk' => $r['penalty'], 'compliant' => 0, 'created_at' => date('Y-m-d H:i:s')]);
            }
        }
        if (count($existingTypes) < count($regConfig)) {
            $regs = $db->table('regulations')->where('user_id', $userId)->get()->getResultArray();
        }

        $compliant = count(array_filter($regs, fn($r) => $r['compliant']));
        $nonCompliant = count($regs) - $compliant;
        $totalCost = array_sum(array_column(array_filter($regs, fn($r) => $r['compliant']), 'compliance_cost'));
        $totalRisk = array_sum(array_column(array_filter($regs, fn($r) => !$r['compliant']), 'penalty_risk'));
        $totalPossibleCost = array_sum(array_column($regs, 'compliance_cost'));
        $complianceScore = count($regs) > 0 ? round($compliant / count($regs) * 100) : 0;

        $tiers = [1 => [], 2 => [], 3 => []];
        foreach ($regs as $reg) {
            $tier = $regConfig[$reg['regulation_type']]['tier'] ?? 1;
            $tiers[$tier][] = $reg;
        }

        $inspections = $db->table('activity_log')->where('user_id', $userId)->like('category', 'inspection')->orderBy('created_at', 'DESC')->limit(10)->get()->getResultArray();

        $formattedTiers = [];
        $tierMeta = [
            1 => ['Basic Regulations', 'Required for all resorts'],
            2 => ['Advanced Standards', 'Unlock additional benefits'],
            3 => ['Premium Compliance', 'Top-tier resort requirements'],
        ];

        foreach ($tiers as $tierNum => $tierRegs) {
            $formattedRegs = [];
            foreach ($tierRegs as $r) {
                $cfg = $regConfig[$r['regulation_type']] ?? [];
                $rawIcon = $cfg['icon'] ?? 'fa-gavel';
                $icon = preg_replace('/^fa-(solid|regular|brands)\s+/', '', (string) $rawIcon);
                $formattedRegs[] = [
                    'id' => (int) $r['id'],
                    'name' => (string) $r['name'],
                    'regulation_type' => (string) $r['regulation_type'],
                    'compliant' => (bool) $r['compliant'],
                    'compliance_cost' => (int) $r['compliance_cost'],
                    'costText' => currency((int) $r['compliance_cost']),
                    'penalty_risk' => (int) $r['penalty_risk'],
                    'penaltyText' => currency((int) $r['penalty_risk']),
                    'benefit' => (string) ($cfg['benefit'] ?? 'Compliant'),
                    'icon' => $icon,
                ];
            }
            $meta = $tierMeta[$tierNum] ?? ["Tier {$tierNum}", ''];
            $formattedTiers[] = [
                'tier' => (int) $tierNum,
                'title' => (string) $meta[0],
                'subtitle' => (string) $meta[1],
                'regulations' => $formattedRegs,
            ];
        }

        $formattedInspections = array_map(function ($insp) {
            $rawIcon = $insp['icon'] ?? 'fa-gavel';
            $icon = preg_replace('/^fa-(solid|regular|brands)\s+/', '', (string) $rawIcon);
            return [
                'id' => (int) ($insp['id'] ?? 0),
                'message' => (string) ($insp['message'] ?? ''),
                'game_day' => (int) ($insp['game_day'] ?? 0),
                'icon' => $icon,
                'dateText' => isset($insp['created_at']) ? date('M j', strtotime($insp['created_at'])) : '',
            ];
        }, $inspections);

        $page = [
            'stats' => [
                'compliantCount' => $compliant,
                'totalCount' => count($regs),
                'complianceScore' => (int) $complianceScore,
                'totalCostText' => currency((int) $totalCost),
                'totalRiskText' => currency((int) $totalRisk),
                'totalPossibleCostText' => currency((int) $totalPossibleCost),
                'inspectionChance' => (int) ($nonCompliant * 5),
                'reputationPenalty' => (int) ($nonCompliant * 15),
                'visitorPenalty' => (int) ($nonCompliant * 3),
            ],
            'tiers' => $formattedTiers,
            'inspections' => $formattedInspections,
        ];

        return view('government/index', ['page' => $page]);
    }

    public function toggle(int $id)
    {
        $userId = auth()->id();
        $db = db_connect();
        $reg = $db->table('regulations')->where('id', $id)->where('user_id', $userId)->get()->getRowArray();
        if (!$reg) return redirect()->back()->with('error', 'Not found.');
        $db->table('regulations')->where('id', $id)->update(['compliant' => $reg['compliant'] ? 0 : 1]);
        $action = $reg['compliant'] ? 'opted out of' : 'now compliant with';
        log_activity($userId, 'Government', $action . ' ' . $reg['name'], 'fa-solid fa-building-columns');
        return redirect()->to('/government')->with('success', 'You are ' . $action . ' ' . $reg['name']);
    }

    public function complyAll()
    {
        $userId = auth()->id();
        $db = db_connect();
        $nonCompliant = $db->table('regulations')->where('user_id', $userId)->where('compliant', 0)->get()->getResultArray();
        if (empty($nonCompliant)) return redirect()->to('/government')->with('error', 'Already fully compliant!');
        $db->table('regulations')->where('user_id', $userId)->update(['compliant' => 1]);
        log_activity($userId, 'Government', 'Opted into full compliance with all ' . count($nonCompliant) . ' regulations', 'fa-solid fa-building-columns');
        notify($userId, 'government', 'Full Compliance Achieved', 'Your resort is now fully compliant with all government regulations.', 'fa-solid fa-circle-check', '/government');
        return redirect()->to('/government')->with('success', 'Now compliant with all ' . count($nonCompliant) . ' regulations!');
    }
}
