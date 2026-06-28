<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Leaderboard<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-5xl mx-auto p-4 lg:p-8">

    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-trophy text-warning mr-2"></i>Leaderboard</h1>
            <p class="text-sm text-base-content/50"><?= count($players) ?> resort managers competing</p>
        </div>
    </div>

    <!-- Top 3 Podium -->
    <?php if (count($players) >= 3) : ?>
    <div class="grid grid-cols-3 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm mt-8"><div class="card-body p-4 text-center">
            <div class="text-3xl mb-1">🥈</div>
            <div class="font-bold"><?= esc($players[1]['username']) ?></div>
            <div class="text-lg font-bold text-success"><?= currency((int)$players[1]['cash']) ?></div>
            <div class="text-xs text-base-content/50"><?= $players[1]['slope_count'] ?> slopes · <?= $players[1]['lift_count'] ?> lifts</div>
        </div></div>
        <div class="aura aura-gold rounded-2xl w-full">
            <div class="card bg-base-100 border border-base-200 shadow-sm w-full"><div class="card-body p-4 text-center">
                <div class="text-4xl mb-1">🥇</div>
                <div class="font-bold text-lg"><?= esc($players[0]['username']) ?></div>
                <div class="text-xl font-bold text-success"><?= currency((int)$players[0]['cash']) ?></div>
                <div class="text-xs text-base-content/50"><?= $players[0]['slope_count'] ?> slopes · <?= $players[0]['lift_count'] ?> lifts</div>
                <div class="badge badge-warning badge-sm mt-1 mx-auto">Champion</div>
            </div></div>
        </div>
        <div class="card bg-base-100 shadow-sm mt-8"><div class="card-body p-4 text-center">
            <div class="text-3xl mb-1">🥉</div>
            <div class="font-bold"><?= esc($players[2]['username']) ?></div>
            <div class="text-lg font-bold text-success"><?= currency((int)$players[2]['cash']) ?></div>
            <div class="text-xs text-base-content/50"><?= $players[2]['slope_count'] ?> slopes · <?= $players[2]['lift_count'] ?> lifts</div>
        </div></div>
    </div>
    <?php endif ?>

    <!-- Full Table -->
    <div class="card bg-base-100 shadow-sm"><div class="card-body p-0"><div class="overflow-x-auto">
        <table class="table">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Player</th>
                    <th>Cash</th>
                    <th>Net Profit</th>
                    <th>Resort</th>
                    <th>Score</th>
                    <th>Difficulty</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($players as $i => $player) : ?>
                <?php
                    $rank = $i + 1;
                    $isMe = $currentUserId == $player['id'];
                    $medal = match($rank) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => '' };
                    $net = (int)($player['net_profit'] ?? 0);
                    $diffBadge = match($player['difficulty'] ?? 'standard') { 'easy' => 'badge-success', 'hard' => 'badge-error', default => 'badge-ghost' };
                ?>
                <tr class="<?= $isMe ? 'bg-primary/10' : '' ?>">
                    <td class="font-bold"><?= $medal ?> <?= $rank ?></td>
                    <td>
                        <div class="flex items-center gap-2">
                            <div class="avatar placeholder"><div class="bg-neutral text-neutral-content rounded-full w-8 h-8 flex items-center justify-center text-xs"><?= strtoupper(substr($player['username'], 0, 2)) ?></div></div>
                            <div>
                                <div class="font-semibold <?= $isMe ? 'text-primary' : '' ?>">
                                    <?= esc($player['username']) ?>
                                    <?= $isMe ? '<span class="badge badge-primary badge-xs ml-1">You</span>' : '<a href="/tour/' . $player['id'] . '" class="badge badge-ghost badge-xs ml-1 gap-1"><i class="fa-solid fa-binoculars text-[8px]"></i>Tour</a>' ?>
                                </div>
                                <div class="text-xs text-base-content/40">Joined <?= date('M j', strtotime($player['created_at'])) ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="font-mono font-bold text-success"><?= currency((int)$player['cash']) ?></td>
                    <td class="font-mono <?= $net >= 0 ? 'text-success' : 'text-error' ?>"><?= $net >= 0 ? '+' : '' ?><?= currency($net) ?></td>
                    <td>
                        <div class="text-xs">
                            <span class="text-info"><?= $player['slope_count'] ?>⛷</span>
                            <span class="text-warning ml-1"><?= $player['lift_count'] ?>🚡</span>
                            <span class="text-primary ml-1"><?= $player['building_count'] ?>🏨</span>
                            <span class="ml-1"><?= $player['staff_count'] ?>👥</span>
                        </div>
                    </td>
                    <td><span class="font-bold"><?= number_format((int)($player['score'] ?? 0)) ?></span></td>
                    <td><span class="badge <?= $diffBadge ?> badge-xs"><?= ucfirst($player['difficulty'] ?? 'standard') ?></span></td>
                </tr>
            <?php endforeach ?>
            <?php if (empty($players)) : ?>
                <tr><td colspan="7" class="text-center text-base-content/40 py-8">No players yet. Be the first!</td></tr>
            <?php endif ?>
            </tbody>
        </table>
    </div></div></div>
    <!-- Vote Promo -->
    <a href="/vote" class="card bg-gradient-to-r from-primary/10 to-info/10 border border-primary/20 shadow-sm mt-6 hover:shadow-md transition-shadow">
        <div class="card-body p-4 flex-row items-center gap-3">
            <i class="fa-solid fa-check-to-slot text-primary text-2xl"></i>
            <div class="flex-1">
                <div class="font-bold">Where should we ski next?</div>
                <div class="text-sm text-base-content/50">Vote for the Season 4 resort. Deer Valley? Vail? Aspen? You decide.</div>
            </div>
            <span class="btn btn-primary btn-sm gap-1"><i class="fa-solid fa-arrow-right"></i> Vote</span>
        </div>
    </a>
</div>
<?= $this->endSection() ?>
