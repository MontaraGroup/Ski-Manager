<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Leaderboard<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="max-w-5xl mx-auto p-4 lg:p-8">

    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle">
            <i class="fa-solid fa-chevron-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-trophy text-warning mr-2"></i>Leaderboard</h1>
            <p class="text-sm text-base-content/50">Top ski resort managers</p>
        </div>
    </div>

    <!-- Top 3 -->
    <?php if (count($players) >= 3) : ?>
    <div class="grid grid-cols-3 gap-3 mb-6">
        <!-- 2nd Place -->
        <div class="card bg-base-100 shadow-sm mt-8">
            <div class="card-body p-4 text-center">
                <div class="text-3xl mb-1">🥈</div>
                <div class="font-bold"><?= esc($players[1]['username']) ?></div>
                <div class="text-xs text-base-content/50"><?= $players[1]['staff_count'] ?> staff</div>
                <div class="text-lg font-bold text-base-content/70">#2</div>
            </div>
        </div>
        <!-- 1st Place -->
        <div class="card bg-base-100 shadow-sm border-2 border-warning">
            <div class="card-body p-4 text-center">
                <div class="text-4xl mb-1">🥇</div>
                <div class="font-bold text-lg"><?= esc($players[0]['username']) ?></div>
                <div class="text-xs text-base-content/50"><?= $players[0]['staff_count'] ?> staff</div>
                <div class="text-xl font-bold text-warning">#1</div>
            </div>
        </div>
        <!-- 3rd Place -->
        <div class="card bg-base-100 shadow-sm mt-8">
            <div class="card-body p-4 text-center">
                <div class="text-3xl mb-1">🥉</div>
                <div class="font-bold"><?= esc($players[2]['username']) ?></div>
                <div class="text-xs text-base-content/50"><?= $players[2]['staff_count'] ?> staff</div>
                <div class="text-lg font-bold text-base-content/70">#3</div>
            </div>
        </div>
    </div>
    <?php endif ?>

    <!-- Full Table -->
    <div class="card bg-base-100 shadow-sm">
        <div class="card-body p-0">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Player</th>
                            <th>Staff</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($players as $i => $player) : ?>
                        <?php
                            $rank = $i + 1;
                            $isCurrentUser = auth()->loggedIn() && auth()->id() == $player['id'];
                            $medal = $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : ($rank === 3 ? '🥉' : ''));
                        ?>
                        <tr class="<?= $isCurrentUser ? 'bg-primary/10' : '' ?>">
                            <td class="font-bold">
                                <?= $medal ?> <?= $rank ?>
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="avatar placeholder">
                                        <div class="bg-neutral text-neutral-content rounded-full w-8 h-8 flex items-center justify-center text-xs">
                                            <?= strtoupper(substr($player['username'], 0, 2)) ?>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="font-semibold <?= $isCurrentUser ? 'text-primary' : '' ?>"><?= esc($player['username']) ?> <?= $isCurrentUser ? '<span class="badge badge-primary badge-xs">You</span>' : '<a href="/tour/' . $player['id'] . '" class="badge badge-ghost badge-xs gap-1"><i class="fa-solid fa-binoculars text-[8px]"></i>Tour</a>' ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= $player['staff_count'] ?> <span class="text-xs text-base-content/50">hired</span></td>
                            <td class="text-sm text-base-content/50"><?= date('M j, Y', strtotime($player['created_at'])) ?></td>
                        </tr>
                        <?php endforeach ?>
                        <?php if (empty($players)) : ?>
                        <tr>
                            <td colspan="4" class="text-center text-base-content/40 py-8">No players yet. Be the first to register!</td>
                        </tr>
                        <?php endif ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
<?= $this->endSection() ?>
