<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Alliance Leaderboard & Standings<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="max-w-7xl mx-auto p-4 lg:p-8 space-y-6">

    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="/alliances" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
            <div>
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-ranking-star text-warning text-2xl"></i>
                    <h1 class="text-2xl lg:text-3xl font-black text-base-content">Alliance Standings</h1>
                </div>
                <p class="text-sm text-base-content/60 mt-1">Ranking the world's premier alpine syndicates by combined valuation and mountain infrastructure.</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="/leaderboard" class="btn btn-sm btn-outline gap-1.5">
                <i class="fa-solid fa-person-skiing"></i> Individual Leaderboard
            </a>
            <a href="/alliances" class="btn btn-sm btn-primary font-bold gap-1.5 shadow-sm">
                <i class="fa-solid fa-handshake"></i> My Alliance
            </a>
        </div>
    </div>

    <!-- Top 3 Podium Cards -->
    <?php if (count($alliances) >= 1) : ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
            <?php 
                $podiumClasses = [
                    0 => ['border' => 'border-warning/50 bg-warning/5', 'badge' => 'badge-warning', 'label' => '1st Place &bull; World Champion', 'icon' => 'fa-crown text-warning'],
                    1 => ['border' => 'border-base-300 bg-base-100', 'badge' => 'badge-neutral', 'label' => '2nd Place', 'icon' => 'fa-medal text-base-content/60'],
                    2 => ['border' => 'border-base-300 bg-base-100', 'badge' => 'badge-neutral', 'label' => '3rd Place', 'icon' => 'fa-award text-amber-600'],
                ];
            ?>
            <?php for ($i = 0; $i < min(3, count($alliances)); $i++) : 
                $a = $alliances[$i];
                $cfg = $podiumClasses[$i];
                $tier = $passTiers[(int)$a['pass_tier']] ?? $passTiers[1];
            ?>
                <div class="card <?= $cfg['border'] ?> border shadow-sm p-5 space-y-3 relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="badge badge-sm <?= $cfg['badge'] ?> font-bold font-mono"><?= $cfg['label'] ?></span>
                        <i class="fa-solid <?= $cfg['icon'] ?> text-lg"></i>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-xl flex items-center justify-center text-sm text-white shrink-0 font-mono font-bold shadow-sm" style="background-color: <?= esc($a['crest_color']) ?>;">
                            <i class="fa-solid <?= esc($a['crest_icon']) ?>"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <a href="/alliances/view/<?= (int)$a['id'] ?>" class="font-black text-base link link-hover truncate block">
                                <?= esc($a['name']) ?>
                            </a>
                            <div class="text-xs text-base-content/50 font-mono">[<?= esc($a['tag']) ?>] &bull; Lvl <?= (int)$a['level'] ?></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 pt-2 border-t border-base-300/80 text-xs">
                        <div>
                            <div class="text-base-content/50">Combined Valuation</div>
                            <div class="font-black text-success font-mono text-sm"><?= currency((int)$a['valuation']) ?></div>
                        </div>
                        <div>
                            <div class="text-base-content/50">Infrastructure</div>
                            <div class="font-bold text-base-content font-mono text-sm"><?= (int)$a['total_slopes'] ?> Slopes &bull; <?= (int)$a['total_lifts'] ?> Lifts</div>
                        </div>
                    </div>
                </div>
            <?php endfor ?>
        </div>
    <?php endif ?>

    <!-- Full Leaderboard Table -->
    <div class="card bg-base-100 border border-base-300 shadow-sm p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-base-300 pb-3">
            <h2 class="text-lg font-bold text-base-content">Complete Syndicate Rankings</h2>
            <span class="badge badge-sm badge-ghost"><?= count($alliances) ?> Alliances</span>
        </div>

        <?php if (empty($alliances)) : ?>
            <div class="text-center py-10 text-xs text-base-content/40">No alliances ranked yet.</div>
        <?php else : ?>
            <div class="overflow-x-auto">
                <table class="table table-sm w-full">
                    <thead>
                        <tr class="text-xs text-base-content/50 border-b border-base-300">
                            <th class="w-12 text-center">Rank</th>
                            <th>Alliance</th>
                            <th>Syndicate Pass</th>
                            <th>Roster</th>
                            <th>Slopes & Lifts</th>
                            <th>Trail Network</th>
                            <th>Reputation</th>
                            <th class="text-right">Valuation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alliances as $idx => $a) : 
                            $rank = $idx + 1;
                            $isMy = ((int)$a['id'] === (int)$myAllianceId);
                            $tier = $passTiers[(int)$a['pass_tier']] ?? $passTiers[1];
                            $slopeKm = round((int)$a['total_slope_meters'] / 1000, 1);
                        ?>
                            <tr class="<?= $isMy ? 'bg-primary/5 font-semibold' : 'hover:bg-base-200/30' ?>">
                                <td class="text-center font-mono font-bold text-sm">
                                    <?php if ($rank === 1) : ?>
                                        <i class="fa-solid fa-crown text-warning"></i>
                                    <?php elseif ($rank === 2) : ?>
                                        <span class="text-base-content/70">2</span>
                                    <?php elseif ($rank === 3) : ?>
                                        <span class="text-amber-700">3</span>
                                    <?php else : ?>
                                        <span class="text-base-content/40"><?= $rank ?></span>
                                    <?php endif ?>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-md flex items-center justify-center text-[10px] text-white shrink-0 font-mono font-bold" style="background-color: <?= esc($a['crest_color']) ?>;">
                                            <i class="fa-solid <?= esc($a['crest_icon']) ?>"></i>
                                        </span>
                                        <div>
                                            <a href="/alliances/view/<?= (int)$a['id'] ?>" class="link link-hover text-sm font-bold text-base-content">
                                                <?= esc($a['name']) ?>
                                            </a>
                                            <span class="badge badge-xs badge-neutral font-mono font-bold ml-1">[<?= esc($a['tag']) ?>]</span>
                                            <?php if ($isMy) : ?>
                                                <span class="badge badge-xs badge-primary font-mono ml-1">Your Alliance</span>
                                            <?php endif ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-xs <?= esc($tier['badge']) ?> font-bold font-mono">
                                        Tier <?= (int)$a['pass_tier'] ?> (+<?= $tier['visitor_pct'] ?>%)
                                    </span>
                                </td>
                                <td class="font-mono text-xs">
                                    <?= (int)$a['member_count'] ?> / <?= (int)$a['max_members'] ?>
                                </td>
                                <td class="font-mono text-xs">
                                    <?= (int)$a['total_slopes'] ?>S &bull; <?= (int)$a['total_lifts'] ?>L
                                </td>
                                <td class="font-mono text-xs text-info">
                                    <?= $slopeKm ?> km
                                </td>
                                <td class="font-mono text-xs">
                                    <?= (int)$a['total_reputation'] ?>
                                </td>
                                <td class="font-mono text-xs text-success font-bold text-right">
                                    <?= currency((int)$a['valuation']) ?>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
    </div>

</div>
<?= $this->endSection() ?>
