<!DOCTYPE html>
<html lang="en">
<head>
<title>Resort Analysis Report</title>
<meta charset="UTF-8">
<style>
    @page { margin: 25px 30px; }
    body { font-family: Helvetica, Arial, sans-serif; font-size: 10px; color: #2d3748; margin: 0; padding: 0; }

    /* Header banner */
    .banner { background: #1e3a5f; color: white; padding: 20px 25px; margin: -25px -30px 20px -30px; }
    .banner h1 { font-size: 24px; margin: 0 0 3px 0; font-weight: 800; letter-spacing: -0.5px; }
    .banner .subtitle { font-size: 10px; opacity: 0.7; }
    .banner .meta { float: right; text-align: right; font-size: 9px; opacity: 0.8; line-height: 1.6; }

    /* Overall score card */
    .score-hero { text-align: center; margin: 15px 0 20px 0; padding: 20px; border: 2px solid #e2e8f0; border-radius: 12px; background: #f7fafc; }
    .score-hero .number { font-size: 48px; font-weight: 900; letter-spacing: -2px; }
    .score-hero .label { font-size: 13px; font-weight: 600; color: #4a5568; margin-top: 2px; }
    .score-hero .desc { font-size: 10px; color: #718096; margin-top: 6px; }
    .score-green { color: #059669; }
    .score-yellow { color: #d97706; }
    .score-red { color: #dc2626; }

    /* Section headers */
    h2 { font-size: 13px; color: #1e3a5f; margin: 22px 0 10px 0; padding-bottom: 5px; border-bottom: 2px solid #1e3a5f; text-transform: uppercase; letter-spacing: 1px; }

    /* Category score cards */
    .cat-grid { width: 100%; margin: 0 0 5px 0; }
    .cat-grid td { text-align: center; padding: 10px 5px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; width: 14.2%; }
    .cat-score { font-size: 22px; font-weight: 800; }
    .cat-label { font-size: 8px; color: #718096; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 3px; }
    .cat-bar { height: 4px; border-radius: 2px; background: #e2e8f0; margin: 5px auto 0; width: 80%; }
    .cat-bar-fill { height: 100%; border-radius: 2px; }

    /* Snapshot table */
    .snap-table { width: 100%; border-collapse: separate; border-spacing: 0; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
    .snap-table th { background: #f1f5f9; padding: 7px 10px; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; border-bottom: 1px solid #e2e8f0; }
    .snap-table td { padding: 6px 10px; border-bottom: 1px solid #f1f5f9; }
    .snap-table tr:last-child td { border-bottom: none; }
    .snap-label { color: #64748b; }
    .snap-value { font-weight: 700; text-align: right; }

    /* Recommendations */
    .rec { padding: 10px 12px; margin: 6px 0; border-radius: 6px; page-break-inside: avoid; }
    .rec-critical { background: #fef2f2; border: 1px solid #fecaca; }
    .rec-warning { background: #fffbeb; border: 1px solid #fde68a; }
    .rec-info { background: #eff6ff; border: 1px solid #bfdbfe; }
    .rec-badge { display: inline-block; font-size: 7px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; padding: 2px 6px; border-radius: 3px; color: white; }
    .rec-badge-critical { background: #dc2626; }
    .rec-badge-warning { background: #d97706; }
    .rec-badge-info { background: #2563eb; }
    .rec-area { font-size: 8px; color: #64748b; margin-left: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
    .rec-text { font-size: 10px; margin-top: 5px; line-height: 1.5; color: #374151; }

    .all-good { text-align: center; padding: 20px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; }
    .all-good-icon { font-size: 24px; color: #059669; }
    .all-good-text { font-size: 12px; font-weight: 600; color: #059669; margin-top: 5px; }

    /* Footer */
    .footer { text-align: center; font-size: 7px; color: #94a3b8; margin-top: 25px; padding-top: 10px; border-top: 1px solid #e2e8f0; }

    /* Utilities */
    .green { color: #059669; } .red { color: #dc2626; } .yellow { color: #d97706; } .blue { color: #2563eb; }
    .clearfix::after { content: ""; display: table; clear: both; }
</style>
</head>
<body>

<!-- Header Banner -->
<div class="banner clearfix">
    <div class="meta">
        Day <?= $report['game_day'] ?> - Season <?= (int) ceil($report['game_day'] / 135) ?><br>
        <?= date('F j, Y', strtotime($report['created_at'])) ?><br>
        <?= date('g:i A', strtotime($report['created_at'])) ?>
    </div>
    <h1>Resort Analysis Report</h1>
    <div class="subtitle">skimanager.net - Comprehensive Resort Performance Review</div>
</div>

<!-- Overall Score -->
<div class="score-hero">
    <div class="number <?= $data['overall_score'] >= 70 ? 'score-green' : ($data['overall_score'] >= 40 ? 'score-yellow' : 'score-red') ?>"><?= $data['overall_score'] ?>%</div>
    <div class="label">Overall Resort Score</div>
    <div class="desc"><?= $data['overall_score'] >= 80 ? 'Excellent! Your resort is performing at a high level across all categories.' : ($data['overall_score'] >= 60 ? 'Solid foundation with room for strategic improvements in key areas.' : ($data['overall_score'] >= 40 ? 'Several areas require attention to improve resort performance.' : 'Critical improvements needed. Focus on the recommendations below.')) ?></div>
</div>

<!-- Category Scores -->
<h2>Performance by Category</h2>
<table class="cat-grid" cellspacing="6">
    <tr>
        <?php
        $catLabels = ['infrastructure' => 'Infra', 'staffing' => 'Staff', 'finances' => 'Finance', 'amenities' => 'Amenity', 'equipment' => 'Equip', 'resources' => 'Resource', 'safety' => 'Safety'];
        $catIcons = ['infrastructure' => 'Mountain', 'staffing' => 'Team', 'finances' => 'Money', 'amenities' => 'Facility', 'equipment' => 'Tools', 'resources' => 'Power', 'safety' => 'Shield'];
        foreach ($data['scores'] as $cat => $score) :
            $color = $score >= 70 ? '#059669' : ($score >= 40 ? '#d97706' : '#dc2626');
            $bgColor = $score >= 70 ? '#f0fdf4' : ($score >= 40 ? '#fffbeb' : '#fef2f2');
        ?>
        <td style="background:<?= $bgColor ?>">
            <div class="cat-score" style="color:<?= $color ?>"><?= $score ?>%</div>
            <div class="cat-label"><?= $catLabels[$cat] ?? ucfirst($cat) ?></div>
            <div class="cat-bar"><div class="cat-bar-fill" style="width:<?= $score ?>%;background:<?= $color ?>"></div></div>
        </td>
        <?php endforeach ?>
    </tr>
</table>

<!-- Resort Snapshot -->
<h2>Resort Snapshot</h2>
<table class="snap-table">
    <tr>
        <th>Metric</th><th style="text-align:right">Value</th>
        <th>Metric</th><th style="text-align:right">Value</th>
        <th>Metric</th><th style="text-align:right">Value</th>
    </tr>
    <tr>
        <td class="snap-label">Cash on Hand</td><td class="snap-value"><?= number_format($data['stats']['cash']) ?> &euro;</td>
        <td class="snap-label">Open Slopes</td><td class="snap-value"><?= $data['stats']['open_slopes'] ?> / <?= $data['stats']['slopes'] ?></td>
        <td class="snap-label">Open Lifts</td><td class="snap-value"><?= $data['stats']['open_lifts'] ?> / <?= $data['stats']['lifts'] ?></td>
    </tr>
    <tr>
        <td class="snap-label">Active Staff</td><td class="snap-value"><?= $data['stats']['staff'] ?></td>
        <td class="snap-label">Avg Morale</td><td class="snap-value"><?= $data['stats']['avg_morale'] ?>%</td>
        <td class="snap-label">Buildings</td><td class="snap-value"><?= $data['stats']['buildings'] ?></td>
    </tr>
    <tr>
        <td class="snap-label">Equipment</td><td class="snap-value"><?= $data['stats']['equipment'] ?></td>
        <td class="snap-label">Infra Condition</td><td class="snap-value"><?= $data['stats']['avg_infra_condition'] ?>%</td>
        <td class="snap-label">Equip Condition</td><td class="snap-value"><?= $data['stats']['avg_equip_condition'] ?>%</td>
    </tr>
    <tr>
        <td class="snap-label">Parking Lots</td><td class="snap-value"><?= $data['stats']['parking'] ?></td>
        <td class="snap-label">Terrain Parks</td><td class="snap-value"><?= $data['stats']['terrain_parks'] ?></td>
        <td class="snap-label">Energy Sources</td><td class="snap-value"><?= $data['stats']['energy_sources'] ?></td>
    </tr>
    <tr>
        <td class="snap-label">Water Sources</td><td class="snap-value"><?= $data['stats']['water_sources'] ?></td>
        <td class="snap-label">Insurance</td><td class="snap-value"><?= $data['stats']['insurance'] ?> policies</td>
        <td class="snap-label">Total Debt</td><td class="snap-value red"><?= number_format($data['stats']['total_debt']) ?> &euro;</td>
    </tr>
    <tr>
        <td class="snap-label">Daily Salary</td><td class="snap-value"><?= number_format($data['stats']['daily_salary']) ?> &euro;</td>
        <td class="snap-label"></td><td></td>
        <td class="snap-label"></td><td></td>
    </tr>
</table>

<!-- Recommendations -->
<h2>Expert Recommendations (<?= count($data['recommendations']) ?>)</h2>
<?php if (empty($data['recommendations'])) : ?>
    <div class="all-good">
        <div class="all-good-icon">&#10003;</div>
        <div class="all-good-text">No recommendations - your resort is in excellent shape!</div>
    </div>
<?php else : ?>
    <?php foreach ($data['recommendations'] as $i => $rec) : ?>
        <div class="rec rec-<?= $rec['type'] ?>">
            <span class="rec-badge rec-badge-<?= $rec['type'] ?>"><?= strtoupper($rec['type']) ?></span>
            <span class="rec-area"><?= $rec['area'] ?></span>
            <div class="rec-text"><?= $rec['text'] ?></div>
        </div>
    <?php endforeach ?>
<?php endif ?>

<!-- Footer -->
<div class="footer">
    CONFIDENTIAL - Ski Manager Resort Analysis Report &bull; Day <?= $report['game_day'] ?> &bull; Generated <?= date('F j, Y \a\t g:i A', strtotime($report['created_at'])) ?><br>
    skimanager.net &bull; &copy; <?= date('Y') ?> Ski Manager. All rights reserved. This report was generated by the Ski Manager analysis engine.
</div>

</body>
</html>
