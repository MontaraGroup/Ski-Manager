<?php

namespace App\Controllers;

class ResortAnalysis extends BaseController
{
    private const COST_GENEPIS = 20;

    public function index(): string
    {
        $locked = checkFeatureUnlock('resort_analysis'); if ($locked) return $locked;
        $userId = auth()->id();
        $db = db_connect();
        $reports = $db->table('resort_reports')->where('user_id', $userId)->orderBy('game_day', 'DESC')->get()->getResultArray();
        $genepis = $db->table('genepis')->where('user_id', $userId)->get()->getRowArray();
        $gameDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime('2026-06-01')) / 86400) + 1);
        $todayReport = $db->table('resort_reports')->where('user_id', $userId)->where('game_day', $gameDay)->get()->getRowArray();

        return view('resort_analysis/index', [
            'reports' => $reports,
            'genepis' => $genepis,
            'gameDay' => $gameDay,
            'todayReport' => $todayReport,
            'cost' => self::COST_GENEPIS,
        ]);
    }

    public function order()
    {
        $userId = auth()->id();
        $db = db_connect();
        $gameDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime('2026-06-01')) / 86400) + 1);

        $existing = $db->table('resort_reports')->where('user_id', $userId)->where('game_day', $gameDay)->get()->getRowArray();
        if ($existing) return redirect()->to('/resort-analysis')->with('error', 'You already ordered a report for today.');

        $genepis = $db->table('genepis')->where('user_id', $userId)->get()->getRowArray();
        if (($genepis['balance'] ?? 0) < self::COST_GENEPIS) {
            return redirect()->to('/resort-analysis')->with('error', 'Not enough Génépis. You need ' . self::COST_GENEPIS . '.');
        }

        $db->table('genepis')->where('user_id', $userId)->set('balance', 'balance - ' . self::COST_GENEPIS, false)->update();
        $db->table('genepis_log')->insert([
            'user_id' => $userId, 'amount' => -self::COST_GENEPIS,
            'reason' => 'Resort Analysis Report', 'created_at' => date('Y-m-d H:i:s'),
        ]);

        $reportData = $this->generateReport($userId, $db);

        $db->table('resort_reports')->insert([
            'user_id' => $userId,
            'game_day' => $gameDay,
            'status' => 'ready',
            'report_data' => json_encode($reportData),
        ]);

        log_activity($userId, 'resort_analysis', 'Ordered resort analysis report for ' . self::COST_GENEPIS . ' Génépis');

        return redirect()->to('/resort-analysis')->with('success', 'Report generated! Check the results below.');
    }

    public function view(int $id): string
    {
        $userId = auth()->id();
        $db = db_connect();
        $report = $db->table('resort_reports')->where('id', $id)->where('user_id', $userId)->get()->getRowArray();
        if (!$report) return redirect()->to('/resort-analysis')->with('error', 'Report not found.');

        $data = json_decode($report['report_data'], true);
        return view('resort_analysis/view', ['report' => $report, 'data' => $data]);
    }

    private function generateReport(int $userId, $db): array
    {
        $finance = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
        $items = $db->table('player_items')->where('user_id', $userId)->get()->getResultArray();
        $staff = $db->table('staff')->where('user_id', $userId)->where('status', 'active')->get()->getResultArray();
        $buildings = $db->table('buildings')->where('user_id', $userId)->get()->getResultArray();
        $equipment = $db->table('equipment')->where('user_id', $userId)->get()->getResultArray();
        $parking = $db->table('parking')->where('user_id', $userId)->get()->getResultArray();
        $terrainParks = $db->table('terrain_parks')->where('user_id', $userId)->get()->getResultArray();
        $energy = $db->table('energy_management')->where('user_id', $userId)->get()->getResultArray();
        $water = $db->table('water_management')->where('user_id', $userId)->get()->getResultArray();
        $insurance = $db->table('insurance')->where('user_id', $userId)->get()->getResultArray();
        $loans = $db->table('loans')->where('user_id', $userId)->where('status', 'active')->get()->getResultArray();

        $slopes = array_filter($items, fn($i) => $i['item_type'] === 'slope');
        $lifts = array_filter($items, fn($i) => $i['item_type'] === 'lift');
        $openSlopes = array_filter($slopes, fn($i) => $i['status'] === 'open');
        $openLifts = array_filter($lifts, fn($i) => $i['status'] === 'open');

        $avgCondition = count($items) > 0 ? round(array_sum(array_column($items, 'condition_pct')) / count($items)) : 0;
        $avgMorale = count($staff) > 0 ? round(array_sum(array_column($staff, 'morale')) / count($staff)) : 0;
        $avgEquipCond = count($equipment) > 0 ? round(array_sum(array_column($equipment, 'condition_pct')) / count($equipment)) : 0;

        $totalSalary = array_sum(array_column($staff, 'salary'));
        $activeInsurance = count(array_filter($insurance, fn($i) => ($i['active'] ?? 0) == 1));
        $totalDebt = array_sum(array_column($loans, 'remaining'));

        $recommendations = [];
        $scores = [];

        // Infrastructure
        $infraScore = min(100, count($openSlopes) * 15 + count($openLifts) * 20);
        $scores['infrastructure'] = $infraScore;
        if (count($slopes) === 0) $recommendations[] = ['type' => 'critical', 'area' => 'Infrastructure', 'text' => 'You have no slopes! Build at least one slope on the trail map to start attracting visitors.'];
        if (count($lifts) === 0) $recommendations[] = ['type' => 'critical', 'area' => 'Infrastructure', 'text' => 'No lifts built. Visitors need lifts to access your slopes.'];
        if ($avgCondition < 50) $recommendations[] = ['type' => 'warning', 'area' => 'Infrastructure', 'text' => 'Average infrastructure condition is ' . $avgCondition . '%. Repair slopes and lifts to avoid closures.'];
        if (count($slopes) > 0 && count($lifts) === 0) $recommendations[] = ['type' => 'warning', 'area' => 'Infrastructure', 'text' => 'You have slopes but no lifts. Build a lift to increase visitor throughput.'];

        // Staffing
        $staffScore = min(100, count($staff) * 12);
        $scores['staffing'] = $staffScore;
        if (count($staff) === 0) $recommendations[] = ['type' => 'critical', 'area' => 'Staffing', 'text' => 'No active staff. Hire ski patrol, groomers, and mechanics to keep your resort running.'];
        if ($avgMorale < 50 && count($staff) > 0) $recommendations[] = ['type' => 'warning', 'area' => 'Staffing', 'text' => 'Staff morale is low (' . $avgMorale . '%). Boost morale to prevent staff quitting.'];
        $roles = array_unique(array_column($staff, 'role'));
        if (count($staff) > 0 && !in_array('groomer', $roles)) $recommendations[] = ['type' => 'info', 'area' => 'Staffing', 'text' => 'Consider hiring a Groomer to maintain slope conditions.'];
        if (count($staff) > 0 && !in_array('ski_patrol', $roles)) $recommendations[] = ['type' => 'info', 'area' => 'Staffing', 'text' => 'Ski Patrol staff improve safety ratings and visitor confidence.'];

        // Finances
        $finScore = 50;
        $cash = (int) ($finance['cash'] ?? 0);
        if ($cash > 200000) $finScore = 90;
        elseif ($cash > 100000) $finScore = 70;
        elseif ($cash > 50000) $finScore = 50;
        elseif ($cash > 10000) $finScore = 30;
        else $finScore = 10;
        $scores['finances'] = $finScore;
        if ($cash < 10000) $recommendations[] = ['type' => 'critical', 'area' => 'Finances', 'text' => 'Cash reserves critically low (' . currency($cash) . '). Consider taking a loan or reducing expenses.'];
        if ($totalDebt > $cash * 2) $recommendations[] = ['type' => 'warning', 'area' => 'Finances', 'text' => 'Total debt (' . currency($totalDebt) . ') exceeds twice your cash. Focus on paying down loans.'];
        if ($totalSalary > $cash * 0.1 && $cash > 0) $recommendations[] = ['type' => 'info', 'area' => 'Finances', 'text' => 'Daily salary costs are ' . currency($totalSalary) . '. Make sure revenue covers staffing expenses.'];

        // Amenities
        $amenityScore = min(100, count($buildings) * 15 + count($parking) * 10 + count($terrainParks) * 12);
        $scores['amenities'] = $amenityScore;
        if (count($buildings) === 0) $recommendations[] = ['type' => 'warning', 'area' => 'Amenities', 'text' => 'No buildings. Restaurants and hotels generate passive income and improve visitor satisfaction.'];
        if (count($parking) === 0) $recommendations[] = ['type' => 'info', 'area' => 'Amenities', 'text' => 'No parking facilities. Visitors may be turned away during busy days.'];
        if (count($terrainParks) === 0) $recommendations[] = ['type' => 'info', 'area' => 'Amenities', 'text' => 'Terrain parks attract younger visitors and boost reputation.'];

        // Equipment
        $equipScore = count($equipment) > 0 ? min(100, $avgEquipCond) : 0;
        $scores['equipment'] = $equipScore;
        if (count($equipment) === 0) $recommendations[] = ['type' => 'info', 'area' => 'Equipment', 'text' => 'No equipment owned. Groomers and snowmakers improve slope quality.'];
        if ($avgEquipCond < 40 && count($equipment) > 0) $recommendations[] = ['type' => 'warning', 'area' => 'Equipment', 'text' => 'Equipment condition is low (' . $avgEquipCond . '%). Repair or replace aging equipment.'];

        // Resources
        $activeEnergy = array_filter($energy, fn($e) => $e['status'] === 'active');
        $activeWater = array_filter($water, fn($w) => $w['status'] === 'active');
        $resScore = min(100, count($activeEnergy) * 25 + count($activeWater) * 25);
        $scores['resources'] = $resScore;
        if (count($energy) === 0) $recommendations[] = ['type' => 'warning', 'area' => 'Resources', 'text' => 'No energy sources. Build a power grid connection or generator to power your operations.'];
        if (count($water) === 0) $recommendations[] = ['type' => 'info', 'area' => 'Resources', 'text' => 'No water sources. Required for snowmaking operations.'];

        // Safety
        $safetyScore = min(100, $activeInsurance * 15 + (in_array('ski_patrol', $roles) ? 30 : 0));
        $scores['safety'] = $safetyScore;
        if ($activeInsurance === 0) $recommendations[] = ['type' => 'warning', 'area' => 'Safety', 'text' => 'No active insurance policies. You are exposed to liability and accident costs.'];

        $overallScore = count($scores) > 0 ? round(array_sum($scores) / count($scores)) : 0;

        return [
            'overall_score' => $overallScore,
            'scores' => $scores,
            'recommendations' => $recommendations,
            'stats' => [
                'cash' => $cash,
                'slopes' => count($slopes),
                'open_slopes' => count($openSlopes),
                'lifts' => count($lifts),
                'open_lifts' => count($openLifts),
                'staff' => count($staff),
                'avg_morale' => $avgMorale,
                'buildings' => count($buildings),
                'equipment' => count($equipment),
                'avg_equip_condition' => $avgEquipCond,
                'avg_infra_condition' => $avgCondition,
                'parking' => count($parking),
                'terrain_parks' => count($terrainParks),
                'energy_sources' => count($activeEnergy),
                'water_sources' => count($activeWater),
                'insurance' => $activeInsurance,
                'total_debt' => $totalDebt,
                'daily_salary' => $totalSalary,
            ],
        ];
    }
    public function pdf(int $id)
    {
        $userId = auth()->id();
        $db = db_connect();
        $report = $db->table("resort_reports")->where("id", $id)->where("user_id", $userId)->get()->getRowArray();
        if (!$report) return redirect()->to("/resort-analysis")->with("error", "Report not found.");

        $data = json_decode($report["report_data"], true);

        $html = view("resort_analysis/pdf", ["report" => $report, "data" => $data]);

        $dompdf = new \Dompdf\Dompdf(["defaultFont" => "Helvetica"]);
        $dompdf->loadHtml($html);
        $dompdf->addInfo("Title", "Ski Manager Resort Analysis - Day " . $report["game_day"]);
        $dompdf->render();
        $dompdf->addInfo("Title", "Ski Manager Resort Analysis - Day " . $report["game_day"]);
        $dompdf->addInfo("Author", "Ski Manager - skimanager.net");
        $dompdf->addInfo("Subject", "Resort Analysis Report");
        $dompdf->addInfo("Keywords", "ski resort, analysis, management, report");
        $dompdf->addInfo("Creator", "Ski Manager v2");
        $dompdf->addInfo("Author", "Ski Manager - skimanager.net");
        $dompdf->addInfo("Subject", "Resort Analysis Report");
        $dompdf->addInfo("Keywords", "ski resort, analysis, management, report");
        $dompdf->addInfo("Creator", "Ski Manager v2");
        $dompdf->render();

        $filename = "Resort_Analysis_Day_" . $report["game_day"] . ".pdf";
        $tmpFile = WRITEPATH . "tmp_" . uniqid() . ".pdf";
        $outFile = WRITEPATH . "tmp_" . uniqid() . "_v2.pdf";
        file_put_contents($tmpFile, $dompdf->output());
        exec("qpdf --force-version=2.0 --linearize --object-streams=generate --linearize --object-streams=generate " . escapeshellarg($tmpFile) . " " . escapeshellarg($outFile) . " 2>&1", $qpdfOut, $qpdfCode);
        if ($qpdfCode === 0 && file_exists($outFile)) {
            header("Content-Type: application/pdf");
            header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
            readfile($outFile);
            unlink($tmpFile);
            unlink($outFile);
        } else {
            header("Content-Type: application/pdf");
            header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
            readfile($tmpFile);
            unlink($tmpFile);
        }
        exit;
    }
}
