<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #333; margin: 20px; }
    h1 { font-size: 22px; color: #1a1a2e; border-bottom: 3px solid #3b82f6; padding-bottom: 8px; margin-bottom: 15px; }
    h2 { font-size: 15px; color: #1a1a2e; margin-top: 20px; margin-bottom: 8px; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
    h3 { font-size: 12px; color: #555; margin-top: 12px; margin-bottom: 4px; }
    .score-box { display: inline-block; width: 80px; text-align: center; padding: 8px; margin: 4px; border: 1px solid #ddd; border-radius: 6px; }
    .score-value { font-size: 20px; font-weight: bold; }
    .score-label { font-size: 9px; color: #888; }
    .overall { font-size: 36px; font-weight: bold; text-align: center; padding: 15px; margin: 10px 0; border: 2px solid #3b82f6; border-radius: 10px; color: #3b82f6; }
    .overall-text { text-align: center; font-size: 12px; color: #666; margin-bottom: 15px; }
    table { width: 100%; border-collapse: collapse; margin: 8px 0; }
    th, td { padding: 5px 8px; text-align: left; border-bottom: 1px solid #eee; font-size: 10px; }
    th { background: #f5f5f5; font-weight: bold; color: #555; }
    .rec-critical { border-left: 3px solid #ef4444; padding-left: 8px; margin: 6px 0; background: #fef2f2; padding: 6px 8px; border-radius: 4px; }
    .rec-warning { border-left: 3px solid #f59e0b; padding-left: 8px; margin: 6px 0; background: #fffbeb; padding: 6px 8px; border-radius: 4px; }
    .rec-info { border-left: 3px solid #3b82f6; padding-left: 8px; margin: 6px 0; background: #eff6ff; padding: 6px 8px; border-radius: 4px; }
    .rec-area { font-weight: bold; font-size: 9px; color: #888; text-transform: uppercase; }
    .rec-text { font-size: 10px; margin-top: 2px; }
    .header { display: table; width: 100%; margin-bottom: 10px; }
    .header-left { display: table-cell; vertical-align: middle; }
    .header-right { display: table-cell; vertical-align: middle; text-align: right; color: #888; font-size: 9px; }
    .footer { text-align: center; font-size: 8px; color: #aaa; margin-top: 30px; border-top: 1px solid #ddd; padding-top: 8px; }
    .green { color: #22c55e; } .red { color: #ef4444; } .yellow { color: #f59e0b; } .blue { color: #3b82f6; }
</style>
</head>
<body>

<div class="header">
    <div class="header-left">
        <h1>Ski Manager - Resort Analysis</h1>
    </div>
    <div class="header-right">
        Day <?= $report['game_day'] ?><br>
        Generated: <?= date('M j, Y', strtotime($report['created_at'])) ?><br>
        skimanager.net
    </div>
</div>

<div class="overall"><?= $data['overall_score'] ?>%</div>
<div class="overall-text">
    <?= $data['overall_score'] >= 80 ? 'Excellent! Your resort is performing well.' : ($data['overall_score'] >= 60 ? 'Good foundation, but room for improvement.' : ($data['overall_score'] >= 40 ? 'Your resort needs attention in several areas.' : 'Significant improvements needed across the board.')) ?>
</div>

<h2>Category Scores</h2>
<table>
    <tr>
        <?php
        $catLabels = ['infrastructure' => 'Infrastructure', 'staffing' => 'Staffing', 'finances' => 'Finances', 'amenities' => 'Amenities', 'equipment' => 'Equipment', 'resources' => 'Resources', 'safety' => 'Safety'];
        foreach ($data['scores'] as $cat => $score) : ?>
            <th style="text-align:center"><?= $catLabels[$cat] ?? ucfirst($cat) ?></th>
        <?php endforeach ?>
    </tr>
    <tr>
        <?php foreach ($data['scores'] as $cat => $score) : ?>
            <td style="text-align:center;font-size:18px;font-weight:bold" class="<?= $score >= 70 ? 'green' : ($score >= 40 ? 'yellow' : 'red') ?>"><?= $score ?>%</td>
        <?php endforeach ?>
    </tr>
</table>

<h2>Resort Snapshot</h2>
<table>
    <tr><th>Metric</th><th>Value</th><th>Metric</th><th>Value</th></tr>
    <tr><td>Cash</td><td><?= number_format($data['stats']['cash']) ?> &euro;</td><td>Staff</td><td><?= $data['stats']['staff'] ?></td></tr>
    <tr><td>Slopes</td><td><?= $data['stats']['open_slopes'] ?>/<?= $data['stats']['slopes'] ?></td><td>Avg Morale</td><td><?= $data['stats']['avg_morale'] ?>%</td></tr>
    <tr><td>Lifts</td><td><?= $data['stats']['open_lifts'] ?>/<?= $data['stats']['lifts'] ?></td><td>Buildings</td><td><?= $data['stats']['buildings'] ?></td></tr>
    <tr><td>Equipment</td><td><?= $data['stats']['equipment'] ?></td><td>Infra Condition</td><td><?= $data['stats']['avg_infra_condition'] ?>%</td></tr>
    <tr><td>Parking</td><td><?= $data['stats']['parking'] ?></td><td>Terrain Parks</td><td><?= $data['stats']['terrain_parks'] ?></td></tr>
    <tr><td>Energy Sources</td><td><?= $data['stats']['energy_sources'] ?></td><td>Water Sources</td><td><?= $data['stats']['water_sources'] ?></td></tr>
    <tr><td>Insurance Policies</td><td><?= $data['stats']['insurance'] ?></td><td>Total Debt</td><td class="red"><?= number_format($data['stats']['total_debt']) ?> &euro;</td></tr>
    <tr><td>Daily Salary</td><td><?= number_format($data['stats']['daily_salary']) ?> &euro;</td><td></td><td></td></tr>
</table>

<h2>Recommendations (<?= count($data['recommendations']) ?>)</h2>
<?php if (empty($data['recommendations'])) : ?>
    <p class="green" style="font-size:12px;font-weight:bold">&#10003; No recommendations - your resort is in great shape!</p>
<?php else : ?>
    <?php foreach ($data['recommendations'] as $rec) : ?>
        <div class="rec-<?= $rec['type'] ?>">
            <span class="rec-area"><?= strtoupper($rec['type']) ?> - <?= $rec['area'] ?></span>
            <div class="rec-text"><?= $rec['text'] ?></div>
        </div>
    <?php endforeach ?>
<?php endif ?>

<div class="footer">
    Ski Manager Resort Analysis Report &bull; Day <?= $report['game_day'] ?> &bull; <?= date('M j, Y g:i A', strtotime($report['created_at'])) ?><br>
    Generated at skimanager.net &bull; &copy; <?= date('Y') ?> Ski Manager
</div>

</body>
</html>
