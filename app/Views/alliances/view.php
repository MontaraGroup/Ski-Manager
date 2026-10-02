<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?><?= esc($alliance['name']) ?> [<?= esc($alliance['tag']) ?>] - Resort Alliance Showcase<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="max-w-6xl mx-auto p-4 lg:p-8 space-y-6">

    <!-- Top Breadcrumb -->
    <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <a href="/alliances" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
            <div>
                <div class="flex items-center gap-2">
                    <span class="badge badge-lg font-bold font-mono text-neutral" style="background-color: <?= esc($alliance['crest_color']) ?>; color: #fff;">
                        <i class="fa-solid <?= esc($alliance['crest_icon']) ?> mr-1.5"></i>[<?= esc($alliance['tag']) ?>]
                    </span>
                    <h1 class="text-2xl lg:text-3xl font-black text-base-content"><?= esc($alliance['name']) ?></h1>
                </div>
                <p class="text-sm text-base-content/60 mt-0.5 italic">"<?= esc($alliance['motto']) ?>"</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="/alliances/leaderboard" class="btn btn-sm btn-outline gap-1.5">
                <i class="fa-solid fa-ranking-star text-warning"></i> Standings
            </a>
            <?php if (!$myMembership && (int)$alliance['is_recruiting'] === 1 && count($members) < (int)$alliance['max_members']) : ?>
                <form action="/alliances/join/<?= (int)$alliance['id'] ?>" method="post">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-primary font-bold gap-1.5 shadow-sm">
                        <i class="fa-solid fa-handshake"></i> Join Alliance
                    </button>
                </form>
            <?php endif ?>
        </div>
    </div>

    <!-- Description & Pass Tier Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2 card bg-base-100 border border-base-300 shadow-sm p-6 space-y-4">
            <h2 class="text-lg font-bold text-base-content">Charter Overview</h2>
            <p class="text-sm text-base-content/70 leading-relaxed font-normal">
                <?= nl2br(esc($alliance['description'] ?: 'No charter overview written yet.')) ?>
            </p>

            <div class="pt-2 border-t border-base-300 grid grid-cols-3 gap-3 text-center">
                <div class="p-3 bg-base-200/50 rounded-xl">
                    <div class="text-xs text-base-content/50 uppercase font-semibold">Alliance Level</div>
                    <div class="text-xl font-bold text-primary mt-0.5">Level <?= (int)$alliance['level'] ?></div>
                </div>
                <div class="p-3 bg-base-200/50 rounded-xl">
                    <div class="text-xs text-base-content/50 uppercase font-semibold">Treasury Vault</div>
                    <div class="text-xl font-bold text-success mt-0.5"><?= currency((int)$alliance['treasury_cash']) ?></div>
                </div>
                <div class="p-3 bg-base-200/50 rounded-xl">
                    <div class="text-xs text-base-content/50 uppercase font-semibold">Membership</div>
                    <div class="text-xl font-bold text-base-content mt-0.5"><?= count($members) ?> / <?= (int)$alliance['max_members'] ?></div>
                </div>
            </div>
        </div>

        <?php 
            $curTier = $passTiers[(int)$alliance['pass_tier']] ?? $passTiers[1];
        ?>
        <div class="card bg-base-100 border border-base-300 shadow-sm p-6 space-y-3">
            <span class="badge badge-sm badge-warning font-mono font-bold uppercase text-neutral">Syndicate Multi-Pass</span>
            <h3 class="font-black text-lg text-base-content"><?= esc($alliance['pass_name']) ?></h3>
            <div class="text-xs font-semibold text-primary">
                Tier <?= (int)$alliance['pass_tier'] ?>: <?= esc($curTier['name']) ?> (+<?= $curTier['visitor_pct'] ?>% visitors)
            </div>
            <p class="text-xs text-base-content/60 leading-relaxed">
                <?= esc($curTier['desc']) ?>
            </p>
            <div class="text-[11px] text-base-content/50 pt-2 border-t border-base-300">
                Founder: <strong><?= esc($founder['username'] ?? 'Unknown') ?></strong>
            </div>
        </div>
    </div>

    <!-- Allied Mountains Roster -->
    <div class="card bg-base-100 border border-base-300 shadow-sm p-6 space-y-4">
        <h2 class="text-lg font-bold text-base-content"><i class="fa-solid fa-users mr-2 text-primary"></i>Allied Mountain Resorts</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
            <?php foreach ($members as $m) : ?>
                <div class="p-3 bg-base-200/40 rounded-xl border border-base-300 flex items-center justify-between">
                    <div>
                        <a href="/tour/<?= (int)$m['user_id'] ?>" class="font-bold text-sm link link-hover text-base-content">
                            <?= esc($m['username']) ?>
                        </a>
                        <div class="text-xs text-base-content/50">
                            <?= esc($m['resort_map'] ?? 'ParkCity') ?> &bull; <?= (int)$m['open_slopes'] ?> Slopes
                        </div>
                    </div>
                    <?php if ($m['role'] === 'founder') : ?>
                        <span class="badge badge-xs badge-warning font-mono font-bold text-neutral">Founder</span>
                    <?php elseif ($m['role'] === 'officer') : ?>
                        <span class="badge badge-xs badge-info font-mono font-bold">Officer</span>
                    <?php endif ?>
                </div>
            <?php endforeach ?>
        </div>
    </div>

</div>
<?= $this->endSection() ?>
