<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Vote for Season 4<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-4xl mx-auto p-4 lg:p-8">

    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-check-to-slot mr-2 text-primary"></i>Vote for Season 4</h1>
            <p class="text-sm text-base-content/50">Choose the next resort after Park City. Your vote counts.</p>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>

    <!-- Current Vote -->
    <?php if ($userVote) : ?>
    <div class="alert alert-info mb-6">
        <i class="fa-solid fa-check-circle"></i>
        <span>You voted for <strong><?= esc($options[$userVote['resort_key']]['name'] ?? $userVote['resort_key']) ?></strong>. You can change your vote anytime.</span>
    </div>
    <?php endif ?>

    <!-- Total -->
    <div class="text-sm text-base-content/50 mb-4"><i class="fa-solid fa-chart-bar mr-1"></i> <?= $totalVotes ?> vote<?= $totalVotes !== 1 ? 's' : '' ?> cast so far</div>

    <!-- Resort Options -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <?php foreach ($options as $key => $resort) : ?>
        <?php
            $votes = $voteCounts[$key] ?? 0;
            $pct = $totalVotes > 0 ? round($votes / $totalVotes * 100) : 0;
            $isMyVote = $userVote && $userVote['resort_key'] === $key;
            $isLeading = $votes > 0 && $votes === max($voteCounts ?: [0]);
        ?>
        <div class="card bg-base-100 shadow-sm <?= $isMyVote ? 'border-2 border-primary' : '' ?> <?= $isLeading ? 'ring-1 ring-warning/50' : '' ?>">
            <div class="card-body p-4">
                <div class="flex items-start justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-10 h-10 rounded-lg bg-base-200 flex items-center justify-center">
                            <i class="<?= $resort['icon'] ?> <?= $resort['color'] ?> text-lg"></i>
                        </div>
                        <div>
                            <div class="font-bold"><?= $resort['name'] ?></div>
                            <div class="text-xs text-base-content/50"><?= $resort['location'] ?></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        <?php if ($isMyVote) : ?><span class="badge badge-primary badge-xs">Your vote</span><?php endif ?>
                        <?php if ($isLeading && $votes > 0) : ?><span class="badge badge-warning badge-xs">Leading</span><?php endif ?>
                    </div>
                </div>
                <p class="text-xs text-base-content/60 mb-3"><?= $resort['desc'] ?></p>
                <div class="flex items-center gap-2 mb-2">
                    <progress class="progress progress-primary flex-1 h-2" value="<?= $pct ?>" max="100"></progress>
                    <span class="text-xs font-mono font-bold w-12 text-right"><?= $pct ?>%</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-base-content/40"><?= $votes ?> vote<?= $votes !== 1 ? 's' : '' ?></span>
                    <form action="/vote/cast" method="post"><?= csrf_field() ?>
                        <input type="hidden" name="resort" value="<?= $key ?>">
                        <?php if ($isMyVote) : ?>
                            <button class="btn btn-sm btn-primary btn-disabled gap-1" disabled><i class="fa-solid fa-check"></i> Voted</button>
                        <?php else : ?>
                            <button class="btn btn-sm btn-outline btn-primary gap-1"><i class="fa-solid fa-check-to-slot"></i> Vote</button>
                        <?php endif ?>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach ?>
    </div>

    <!-- How it Works -->
    <div class="collapse collapse-arrow bg-base-100 shadow-sm">
        <input type="checkbox" />
        <div class="collapse-title font-semibold text-sm"><i class="fa-solid fa-circle-info mr-1"></i> How Voting Works</div>
        <div class="collapse-content text-sm text-base-content/70">
            <ul class="space-y-1 mt-2">
                <li><i class="fa-solid fa-check text-success text-xs mr-2"></i>One vote per player</li>
                <li><i class="fa-solid fa-rotate text-info text-xs mr-2"></i>Change your vote anytime before Season 4 starts</li>
                <li><i class="fa-solid fa-trophy text-warning text-xs mr-2"></i>The winning resort becomes the Season 4 map</li>
                <li><i class="fa-solid fa-mountain text-primary text-xs mr-2"></i>Seasons 1-3 are Park City (Sectors 1, 2, 3). Season 4 moves to a new mountain.</li>
            </ul>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
