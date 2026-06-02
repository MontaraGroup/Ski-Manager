<?php

namespace App\Controllers;

class Government extends BaseController
{
    private const REGULATIONS = [
        'safety' => ['name' => 'Slope Safety Standards', 'icon' => 'fa-solid fa-helmet-safety', 'cost' => 2000, 'penalty' => 10000, 'benefit' => 'Prevents forced slope closures after accidents', 'tier' => 1],
        'environmental' => ['name' => 'Environmental Protection Act', 'icon' => 'fa-solid fa-leaf', 'cost' => 3000, 'penalty' => 15000, 'benefit' => '+5 eco score, avoids environmental fines', 'tier' => 1],
        'labor' => ['name' => 'Labor Laws Compliance', 'icon' => 'fa-solid fa-users', 'cost' => 1500, 'penalty' => 8000, 'benefit' => '+10 staff morale, prevents strikes', 'tier' => 1],
        'building' => ['name' => 'Building Code Standards', 'icon' => 'fa-solid fa-building', 'cost' => 2500, 'penalty' => 12000, 'benefit' => 'Buildings last 25% longer', 'tier' => 1],
        'noise' => ['name' => 'Noise Pollution Limits', 'icon' => 'fa-solid fa-volume-high', 'cost' => 1000, 'penalty' => 5000, 'benefit' => '+3% visitor satisfaction', 'tier' => 1],
        'waste' => ['name' => 'Waste Management Regulation', 'icon' => 'fa-solid fa-recycle', 'cost' => 1800, 'penalty' => 7000, 'benefit' => '+5 eco score, recycling revenue', 'tier' => 1],
        'accessibility' => ['name' => 'Accessibility Requirements', 'icon' => 'fa-solid fa-wheelchair', 'cost' => 2200, 'penalty' => 9000, 'benefit' => '+8% visitor capacity', 'tier' => 2],
        'wildlife' => ['name' => 'Wildlife Protection Zone', 'icon' => 'fa-solid fa-paw', 'cost' => 3500, 'penalty' => 20000, 'benefit' => '+10 eco score, nature tours', 'tier' => 2],
        'water_quality' => ['name' => 'Water Quality Standards', 'icon' => 'fa-solid fa-droplet', 'cost' => 2800, 'penalty' => 18000, 'benefit' => 'Snowmaking efficiency +15%', 'tier' => 2],
        'fire_safety' => ['name' => 'Fire Safety Regulations', 'icon' => 'fa-solid fa-fire-extinguisher', 'cost' => 2000, 'penalty' => 25000, 'benefit' => 'Prevents building fires, -50% insurance cost', 'tier' => 2],
        'energy_efficiency' => ['name' => 'Energy Efficiency Standards', 'icon' => 'fa-solid fa-bolt', 'cost' => 3000, 'penalty' => 12000, 'benefit' => '-20% energy costs', 'tier' => 3],
        'tourism_license' => ['name' => 'Tourism Operating License', 'icon' => 'fa-solid fa-passport', 'cost' => 5000, 'penalty' => 50000, 'benefit' => '+15% visitor capacity, VIP guest chance +10%', 'tier' => 3],
    ];

    public function index(): string
    {
        $userId = auth()->id();
        $db = db_connect();

        $regs = $db->table('regulations')->where('user_id', $userId)->get()->getResultArray();
        if (empty($regs)) {
            foreach (self::REGULATIONS as $type => $r) {
                $db->table('regulations')->insert([
                    'user_id' => $userId, 'regulation_type' => $type,
                    'name' => $r['name'], 'compliance_cost' => $r['cost'],
                    'penalty_risk' => $r['penalty'], 'compliant' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
            $regs = $db->table('regulations')->where('user_id', $userId)->get()->getResultArray();
        }

        // Add any new regulations that don't exist yet
        $existingTypes = array_column($regs, 'regulation_type');
        foreach (self::REGULATIONS as $type => $r) {
            if (!in_array($type, $existingTypes)) {
                $db->table('regulations')->insert([
                    'user_id' => $userId, 'regulation_type' => $type,
                    'name' => $r['name'], 'compliance_cost' => $r['cost'],
                    'penalty_risk' => $r['penalty'], 'compliant' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
        if (count($existingTypes) < count(self::REGULATIONS)) {
            $regs = $db->table('regulations')->where('user_id', $userId)->get()->getResultArray();
        }

        $compliant = count(array_filter($regs, fn($r) => $r['compliant']));
        $nonCompliant = count($regs) - $compliant;
        $totalCost = array_sum(array_column(array_filter($regs, fn($r) => $r['compliant']), 'compliance_cost'));
        $totalRisk = array_sum(array_column(array_filter($regs, fn($r) => !$r['compliant']), 'penalty_risk'));
        $totalPossibleCost = array_sum(array_column($regs, 'compliance_cost'));

        $inspectionChance = $nonCompliant * 5;
        $reputationPenalty = $nonCompliant * 15;
        $visitorPenalty = $nonCompliant * 3;
        $complianceScore = count($regs) > 0 ? round($compliant / count($regs) * 100) : 0;

        // Inspection history from activity log
        $inspections = $db->table('activity_log')->where('user_id', $userId)->like('category', 'inspection')->orderBy('created_at', 'DESC')->limit(10)->get()->getResultArray();

        // Group by tier
        $tiers = [1 => [], 2 => [], 3 => []];
        foreach ($regs as $reg) {
            $tier = self::REGULATIONS[$reg['regulation_type']]['tier'] ?? 1;
            $tiers[$tier][] = $reg;
        }

        return view('government/index', [
            'regs' => $regs, 'tiers' => $tiers,
            'compliant' => $compliant, 'total' => count($regs),
            'totalCost' => $totalCost, 'totalRisk' => $totalRisk,
            'totalPossibleCost' => $totalPossibleCost,
            'inspectionChance' => $inspectionChance,
            'reputationPenalty' => $reputationPenalty,
            'visitorPenalty' => $visitorPenalty,
            'complianceScore' => $complianceScore,
            'inspections' => $inspections,
            'regConfig' => self::REGULATIONS,
        ]);
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

        if (empty($nonCompliant)) {
            return redirect()->to('/government')->with('error', 'Already fully compliant!');
        }

        $db->table('regulations')->where('user_id', $userId)->update(['compliant' => 1]);
        log_activity($userId, 'Government', 'Opted into full compliance with all ' . count($nonCompliant) . ' regulations', 'fa-solid fa-building-columns');
        notify($userId, 'government', 'Full Compliance Achieved', 'Your resort is now fully compliant with all government regulations.', 'fa-solid fa-circle-check', '/government');

        return redirect()->to('/government')->with('success', 'Now compliant with all ' . (count($nonCompliant)) . ' regulations!');
    }
}
