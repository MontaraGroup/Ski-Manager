<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Leaderboard<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
// Gold, silver and bronze medal for the podium; the rank number is always printed next to it
$medal = fn(int $rank) => match ($rank) {
    1 => '<i class="fa-solid fa-medal text-warning" aria-hidden="true"></i>',
    2 => '<i class="fa-solid fa-medal text-base-content/70" aria-hidden="true"></i>',
    3 => '<i class="fa-solid fa-medal text-orange-700" aria-hidden="true"></i>',
    default => '',
};
$money = fn(int $v) => ($v > 0 ? '+' : '') . currency($v);
$initials = fn(string $name) => mb_strtoupper(mb_substr($name, 0, 2));
$metric = fn(array $p) => $v2 ? (int) $p['net_profit'] : (int) $p['score'];
$row = function (array $p, bool $isMe) use ($medal, $money, $initials, $v2) { ?>
    <tr class="<?= $isMe ? 'bg-primary/10' : '' ?>">
        <td class="font-bold whitespace-nowrap"><?= $medal((int) $p['rank']) ?> <?= (int) $p['rank'] ?></td>
        <td>
            <div class="flex items-center gap-2 min-w-0">
                <div class="w-8 h-8 shrink-0 rounded-full bg-base-300 flex items-center justify-center text-xs font-semibold" aria-hidden="true"><?= esc($initials($p['username'])) ?></div>
                <div class="min-w-0">
                    <div class="font-semibold truncate <?= $isMe ? 'text-primary' : '' ?>">
                        <?= esc($p['username']) ?>
                        <?php if ($isMe) : ?><span class="badge badge-primary badge-xs ml-1">You</span>
                        <?php else : ?><a href="/tour/<?= (int) $p['id'] ?>" class="badge badge-ghost badge-xs ml-1 gap-1" aria-label="Tour <?= esc($p['username'], 'attr') ?>'s resort"><i class="fa-solid fa-binoculars text-[8px]" aria-hidden="true"></i>Tour</a><?php endif ?>
                    </div>
                    <?php if (!empty($p['resort_name'])) : ?><div class="text-xs text-base-content/70 truncate"><?= esc($p['resort_name']) ?></div><?php endif ?>
                </div>
            </div>
        </td>
        <td class="font-mono font-bold whitespace-nowrap <?= (int) $p['net_profit'] >= 0 ? 'text-success' : 'text-error' ?>"><?= $money((int) $p['net_profit']) ?></td>
        <td class="font-mono text-sm whitespace-nowrap hidden sm:table-cell"><?= number_format((int) $p['daily_visitors']) ?></td>
        <td class="text-xs whitespace-nowrap hidden md:table-cell"><?= (int) $p['slope_count'] ?> slopes · <?= (int) $p['lift_count'] ?> lifts · <?= (int) $p['building_count'] ?> buildings</td>
        <td class="font-mono text-sm whitespace-nowrap hidden lg:table-cell"><?= currency((int) $p['cash']) ?></td>
        <?php if (!$v2) : ?><td class="font-bold"><?= number_format((int) $p['score']) ?></td><?php endif ?>
    </tr>
<?php }; ?>
<div class="max-w-5xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle" aria-label="Back to dashboard"><i class="fa-solid fa-chevron-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-trophy text-warning mr-2" aria-hidden="true"></i>Leaderboard</h1>
            <p class="text-sm text-base-content/70"><?= esc($seasonName) ?> · day <?= (int) $seasonDay ?> · <?= $v2 ? 'ranked by profit this season' : 'ranked by score' ?></p>
        </div>
    </div>

    <?php if ($v2) : ?>
    <div role="tablist" class="tabs tabs-boxed mb-4 w-fit">
        <?php foreach (['easy' => 'Easy', 'standard' => 'Standard', 'hard' => 'Hard', 'all' => 'All'] as $key => $label) : ?>
        <a role="tab" href="/leaderboard?difficulty=<?= $key ?>" class="tab <?= $difficulty === $key ? 'tab-active' : '' ?>" <?= $difficulty === $key ? 'aria-selected="true"' : '' ?>><?= $label ?></a>
        <?php endforeach ?>
    </div>
    <?php endif ?>

    <?php if ($me) : ?>
    <div class="card bg-primary/10 border border-primary/30 shadow-sm mb-4"><div class="card-body p-4 flex-row flex-wrap items-center gap-x-6 gap-y-1">
        <div class="text-sm">Your position: <b class="text-lg"><?= $medal((int) $me['rank']) ?> #<?= (int) $me['rank'] ?></b> of <?= $rankedCount ?></div>
        <div class="text-sm">Season profit: <b class="<?= (int) $me['net_profit'] >= 0 ? 'text-success' : 'text-error' ?>"><?= $money((int) $me['net_profit']) ?></b></div>
        <?php if ((int) $me['rank'] > 1 && isset($players[0])) : ?>
        <div class="text-sm text-base-content/70"><?= currency(max(0, $metric($players[0]) - $metric($me))) ?> behind the leader</div>
        <?php endif ?>
    </div></div>
    <?php elseif ($meInBoard) : ?>
    <div role="status" class="alert alert-info mb-4 text-sm"><i class="fa-solid fa-person-skiing" aria-hidden="true"></i><span>You're not ranked yet. <a href="/map" class="link font-semibold">Build your first slope or lift</a> to join the leaderboard.</span></div>
    <?php endif ?>

    <?php if (count($players) >= 3) : ?>
    <!-- Podium -->
    <div class="grid grid-cols-3 gap-3 mb-6 items-end">
        <?php foreach ([1, 0, 2] as $i) : $p = $players[$i]; ?>
        <div class="card bg-base-100 shadow-sm <?= $i === 0 ? 'border border-warning/50' : '' ?>"><div class="card-body p-3 sm:p-4 text-center <?= $i === 0 ? 'sm:py-6' : '' ?>">
            <div class="text-3xl" aria-hidden="true"><?= $medal((int) $p['rank']) ?: '#' . (int) $p['rank'] ?></div>
            <div class="font-bold truncate"><?= esc($p['username']) ?></div>
            <div class="font-bold text-success text-sm sm:text-lg"><?= $v2 ? $money((int) $p['net_profit']) : number_format((int) $p['score']) ?></div>
            <div class="text-xs text-base-content/70"><?= (int) $p['slope_count'] ?> slopes · <?= (int) $p['lift_count'] ?> lifts</div>
        </div></div>
        <?php endforeach ?>
    </div>
    <?php endif ?>

    <!-- Table -->
    <div class="card bg-base-100 shadow-sm"><div class="overflow-x-auto">
        <table class="table">
            <thead><tr>
                <th>Rank</th><th>Resort manager</th><th><?= $v2 ? 'Season profit' : 'Net profit' ?></th>
                <th class="hidden sm:table-cell">Skiers today</th><th class="hidden md:table-cell">Resort</th><th class="hidden lg:table-cell">Cash</th>
                <?php if (!$v2) : ?><th>Score</th><?php endif ?>
            </tr></thead>
            <tbody>
            <?php foreach ($players as $p) $row($p, (int) $p['id'] === $currentUserId); ?>
            <?php if ($meOutsideTop) : ?>
                <tr aria-hidden="true"><td colspan="6" class="text-center text-base-content/70 py-1">⋯</td></tr>
                <?php $row($me, true); ?>
            <?php endif ?>
            <?php if (!$players) : ?>
                <tr><td colspan="6" class="text-center text-base-content/70 py-8">No resorts on this board yet. Be the first to build one.</td></tr>
            <?php endif ?>
            </tbody>
        </table>
    </div></div>
    <p class="text-xs text-base-content/70 mt-2">
        Showing the top <?= min(\App\Controllers\Leaderboard::SHOWN, $rankedCount) ?> of <?= $rankedCount ?> ranked resort<?= $rankedCount === 1 ? '' : 's' ?>.
        <?php if ($unranked) : ?><?= $unranked ?> manager<?= $unranked === 1 ? ' hasn\'t' : 's haven\'t' ?> built anything yet this season.<?php endif ?>
        Profit is income minus running costs since the season started; tied resorts share a rank.
    </p>

    <?php if ($pastSeasons) : ?>
    <!-- Hall of fame -->
    <h2 class="text-lg font-bold mt-8 mb-3"><i class="fa-solid fa-landmark mr-1 text-warning" aria-hidden="true"></i> Hall of fame</h2>
    <?php foreach ($pastSeasons as $num => $season) : ?>
    <details class="card bg-base-100 shadow-sm mb-3" <?= $num === array_key_first($pastSeasons) ? 'open' : '' ?>>
        <summary class="card-body p-4 cursor-pointer font-semibold"><?= esc($season['name'] ?: 'Season ' . $num) ?> · final top 10</summary>
        <div class="overflow-x-auto px-2 pb-3">
            <table class="table table-sm">
                <thead><tr><th>Rank</th><th>Manager</th><th>Final cash</th><th class="hidden sm:table-cell">Resort</th></tr></thead>
                <tbody>
                <?php foreach ($season['rows'] as $r) : ?>
                <tr>
                    <td class="font-bold"><?= $medal((int) $r['rank_position']) ?> <?= (int) $r['rank_position'] ?></td>
                    <td><div class="font-semibold"><?= esc($r['username']) ?></div><?php if (!empty($r['resort_name'])) : ?><div class="text-xs text-base-content/70"><?= esc($r['resort_name']) ?></div><?php endif ?></td>
                    <td class="font-mono"><?= currency((int) $r['cash']) ?></td>
                    <td class="text-xs hidden sm:table-cell"><?= (int) $r['total_slopes'] ?> slopes · <?= (int) $r['total_lifts'] ?> lifts</td>
                </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </details>
    <?php endforeach ?>
    <?php endif ?>

    <a href="/vote" class="card bg-base-100 border border-primary/20 shadow-sm mt-6 hover:shadow-md transition-shadow">
        <div class="card-body p-4 flex-row items-center gap-3">
            <i class="fa-solid fa-check-to-slot text-primary text-2xl" aria-hidden="true"></i>
            <div class="flex-1">
                <div class="font-bold">Where should we ski next?</div>
                <div class="text-sm text-base-content/70">Vote for the next season's resort.</div>
            </div>
            <span class="btn btn-primary btn-sm gap-1"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i> Vote</span>
        </div>
    </a>
</div>
<?= $this->endSection() ?>
