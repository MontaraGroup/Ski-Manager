<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?><?= esc($alliance['name']) ?> [<?= esc($alliance['tag']) ?>] - Alliance Headquarters<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="max-w-7xl mx-auto p-4 lg:p-8 space-y-6">

    <!-- Top Breadcrumb & Alerts -->
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
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
                <i class="fa-solid fa-ranking-star text-warning"></i> Alliance Standings
            </a>
            <form action="/alliances/leave" method="post" onsubmit="return confirm('<?= $isFounder ? 'Are you sure you want to leave or disband this alliance?' : 'Leave this alliance?' ?>');">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-ghost text-error gap-1.5">
                    <i class="fa-solid fa-right-from-bracket"></i> Leave Alliance
                </button>
            </form>
        </div>
    </div>

    <?php if (session('success')) : ?>
        <div class="alert alert-success shadow-sm">
            <i class="fa-solid fa-circle-check"></i>
            <span><?= session('success') ?></span>
        </div>
    <?php endif ?>
    <?php if (session('error')) : ?>
        <div class="alert alert-error shadow-sm">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span><?= session('error') ?></span>
        </div>
    <?php endif ?>
    <?php if (session('info')) : ?>
        <div class="alert alert-info shadow-sm">
            <i class="fa-solid fa-circle-info"></i>
            <span><?= session('info') ?></span>
        </div>
    <?php endif ?>

    <!-- Header Stats Banner -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="card bg-base-100 border border-base-300 shadow-sm p-4 text-center">
            <div class="text-xs text-base-content/50 uppercase font-semibold">Alliance Level</div>
            <div class="text-2xl font-black text-primary mt-1">Level <?= (int)$alliance['level'] ?></div>
            <div class="text-[11px] text-base-content/50 mt-1"><?= (int)$alliance['xp'] ?> Total XP</div>
        </div>
        <div class="card bg-base-100 border border-base-300 shadow-sm p-4 text-center">
            <div class="text-xs text-base-content/50 uppercase font-semibold">Alliance Vault</div>
            <div class="text-2xl font-black text-success mt-1"><?= currency((int)$alliance['treasury_cash']) ?></div>
            <div class="text-[11px] text-base-content/50 mt-1">Shared Capital</div>
        </div>
        <div class="card bg-base-100 border border-base-300 shadow-sm p-4 text-center">
            <div class="text-xs text-base-content/50 uppercase font-semibold">Network Scale</div>
            <div class="text-2xl font-black text-base-content mt-1"><?= count($members) ?> / <?= (int)$alliance['max_members'] ?></div>
            <div class="text-[11px] text-base-content/50 mt-1"><?= $totalSlopes ?> Slopes &bull; <?= $totalLifts ?> Lifts</div>
        </div>
        <div class="card bg-base-100 border border-base-300 shadow-sm p-4 text-center">
            <div class="text-xs text-base-content/50 uppercase font-semibold">Total Trail Length</div>
            <div class="text-2xl font-black text-info mt-1"><?= $totalSlopeKm ?> km</div>
            <div class="text-[11px] text-base-content/50 mt-1">Valuation: <?= currency($combinedTreasury) ?></div>
        </div>
    </div>

    <!-- Main Grid: Syndicate Pass & Treasury & Perks -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Column 1 & 2: Syndicate Multi-Pass & Perks -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Syndicate Multi-Pass Box -->
            <?php 
                $curTierNum = (int)$alliance['pass_tier'];
                $curTier = $passTiers[$curTierNum] ?? $passTiers[1];
                $nextTierNum = $curTierNum + 1;
                $nextTier = $passTiers[$nextTierNum] ?? null;
                $sisterBonus = max(0, min(10, count($members) - 1));
                $totalPassVisitorBoost = $curTier['visitor_pct'] + $sisterBonus;
            ?>
            <div class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden">
                <div class="bg-gradient-to-r from-primary to-accent p-6 text-primary-content">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <span class="badge badge-sm badge-warning font-mono font-bold uppercase text-neutral">Syndicate Multi-Pass</span>
                            <h2 class="text-2xl font-black mt-1"><?= esc($alliance['pass_name']) ?></h2>
                            <p class="text-xs text-primary-content/80 mt-1">Cross-visitation network active across all <?= count($members) ?> allied mountains</p>
                        </div>
                        <div class="text-right">
                            <span class="badge badge-lg font-bold font-mono <?= esc($curTier['badge']) ?>">
                                <i class="fa-solid <?= esc($curTier['icon']) ?> mr-1"></i>Tier <?= $curTierNum ?>: <?= esc($curTier['name']) ?>
                            </span>
                            <div class="text-xs text-primary-content/90 font-mono mt-1.5 font-bold">
                                +<?= $totalPassVisitorBoost ?>% Total Skier Boost
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="p-3 bg-base-200/50 rounded-xl border border-base-300">
                            <div class="text-xs text-base-content/50">Base Pass Boost</div>
                            <div class="text-xl font-bold text-primary mt-0.5">+<?= $curTier['visitor_pct'] ?>%</div>
                            <div class="text-[11px] text-base-content/60"><?= esc($curTier['desc']) ?></div>
                        </div>
                        <div class="p-3 bg-base-200/50 rounded-xl border border-base-300">
                            <div class="text-xs text-base-content/50">Sister Mountain Synergy</div>
                            <div class="text-xl font-bold text-info mt-0.5">+<?= $sisterBonus ?>%</div>
                            <div class="text-[11px] text-base-content/60">+1% skier traffic per allied sister resort</div>
                        </div>
                        <div class="p-3 bg-base-200/50 rounded-xl border border-base-300">
                            <div class="text-xs text-base-content/50">Pass Upgrades</div>
                            <div class="text-xl font-bold text-success mt-0.5">Tier <?= $curTierNum ?> of 4</div>
                            <div class="text-[11px] text-base-content/60">
                                <?= $nextTier ? 'Next: ' . esc($nextTier['name']) : 'Max Tier Achieved' ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($nextTier && $isLeader) : ?>
                        <div class="flex flex-wrap items-center justify-between gap-3 p-3.5 bg-base-200 rounded-xl border border-base-300">
                            <div>
                                <div class="font-bold text-sm text-base-content">Upgrade to Tier <?= $nextTierNum ?>: <?= esc($nextTier['name']) ?></div>
                                <div class="text-xs text-base-content/60">
                                    Requires Alliance Level <?= $nextTier['req_level'] ?> &bull; Cost: <?= currency($nextTier['cost']) ?>
                                    &bull; Boosts visitors by <strong>+<?= $nextTier['visitor_pct'] ?>%</strong>
                                </div>
                            </div>
                            <form action="/alliances/upgrade-pass" method="post">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-primary font-bold shadow-sm"
                                    <?= ((int)$alliance['treasury_cash'] < $nextTier['cost'] || (int)$alliance['level'] < $nextTier['req_level']) ? 'disabled' : '' ?>>
                                    <i class="fa-solid fa-arrow-up mr-1"></i> Upgrade Multi-Pass
                                </button>
                            </form>
                        </div>
                    <?php endif ?>
                </div>
            </div>

            <!-- Cooperative Perk Tech Tree -->
            <div class="card bg-base-100 border border-base-300 shadow-sm p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-base-300 pb-3">
                    <div>
                        <h2 class="text-lg font-bold text-base-content"><i class="fa-solid fa-bolt mr-2 text-warning"></i>Syndicate Perks & Research</h2>
                        <p class="text-xs text-base-content/50">Cooperative upgrades financed by the Alliance Treasury</p>
                    </div>
                    <span class="badge badge-sm badge-outline"><?= count($unlockedPerks) ?> Active Perks</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($perkDefs as $pKey => $pDef) : 
                        $curPLevel = $unlockedPerks[$pKey] ?? 0;
                        $nextPLevel = $curPLevel + 1;
                        $hasMaxLevel = !isset($pDef['levels'][$nextPLevel]);
                        $nextPConfig = $pDef['levels'][$nextPLevel] ?? null;
                    ?>
                        <div class="p-4 rounded-xl border border-base-300 bg-base-200/40 flex flex-col justify-between space-y-3">
                            <div>
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg bg-base-300 flex items-center justify-center text-primary text-sm shrink-0">
                                            <i class="<?= esc($pDef['icon']) ?>"></i>
                                        </div>
                                        <span class="font-bold text-sm text-base-content"><?= esc($pDef['name']) ?></span>
                                    </div>
                                    <span class="badge badge-sm <?= $curPLevel > 0 ? 'badge-success font-bold' : 'badge-ghost text-base-content/40' ?>">
                                        <?= $curPLevel > 0 ? "Level {$curPLevel}" : 'Locked' ?>
                                    </span>
                                </div>
                                <p class="text-xs text-base-content/60 mt-2 leading-relaxed"><?= esc($pDef['desc']) ?></p>

                                <?php if ($curPLevel > 0) : ?>
                                    <div class="mt-2 text-xs font-semibold text-success flex items-center gap-1.5">
                                        <i class="fa-solid fa-check text-[10px]"></i> Current: <?= esc($pDef['levels'][$curPLevel]['effect']) ?>
                                    </div>
                                <?php endif ?>
                            </div>

                            <div class="pt-2 border-t border-base-300">
                                <?php if ($hasMaxLevel) : ?>
                                    <span class="text-xs font-bold text-success flex items-center gap-1">
                                        <i class="fa-solid fa-circle-check"></i> Max Rank Mastered
                                    </span>
                                <?php else : ?>
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="text-[11px] text-base-content/60">
                                            <span>Rank <?= $nextPLevel ?>: <?= currency($nextPConfig['cost']) ?></span>
                                            <span class="block text-primary font-medium"><?= esc($nextPConfig['effect']) ?></span>
                                        </div>
                                        <?php if ($isLeader) : ?>
                                            <form action="/alliances/unlock-perk" method="post">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="perk_key" value="<?= esc($pKey) ?>">
                                                <button type="submit" class="btn btn-xs btn-outline btn-primary"
                                                    <?= ((int)$alliance['treasury_cash'] < $nextPConfig['cost'] || (int)$alliance['level'] < $nextPConfig['req_level']) ? 'disabled' : '' ?>>
                                                    Upgrade
                                                </button>
                                            </form>
                                        <?php endif ?>
                                    </div>
                                <?php endif ?>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>

            <!-- Member Roster Table -->
            <div class="card bg-base-100 border border-base-300 shadow-sm p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-base-300 pb-3">
                    <div>
                        <h2 class="text-lg font-bold text-base-content"><i class="fa-solid fa-users mr-2 text-primary"></i>Allied Resort Directors</h2>
                        <p class="text-xs text-base-content/50"><?= count($members) ?> resorts in syndicate</p>
                    </div>
                    <span class="badge badge-sm badge-info font-mono"><?= count($members) ?> / <?= (int)$alliance['max_members'] ?> Cap</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="table table-sm w-full">
                        <thead>
                            <tr class="text-xs text-base-content/50 border-b border-base-300">
                                <th>Director & Resort</th>
                                <th>Role</th>
                                <th>Lifts & Slopes</th>
                                <th>Treasury</th>
                                <th>Lifetime Donated</th>
                                <?php if ($isLeader) : ?><th>Management</th><?php endif ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $m) : ?>
                                <tr class="hover:bg-base-200/30">
                                    <td>
                                        <div class="font-bold text-sm flex items-center gap-1.5">
                                            <a href="/tour/<?= (int)$m['user_id'] ?>" class="link link-hover text-base-content">
                                                <?= esc($m['username']) ?>
                                            </a>
                                            <?php if ((int)$m['user_id'] === (int)auth()->id()) : ?>
                                                <span class="badge badge-xs badge-primary font-mono">You</span>
                                            <?php endif ?>
                                        </div>
                                        <div class="text-[11px] text-base-content/50"><?= esc($m['resort_map'] ?? 'ParkCity') ?> &bull; Rep: <?= (int)$m['reputation'] ?></div>
                                    </td>
                                    <td>
                                        <?php if ($m['role'] === 'founder') : ?>
                                            <span class="badge badge-sm badge-warning font-bold text-neutral">Founder</span>
                                        <?php elseif ($m['role'] === 'officer') : ?>
                                            <span class="badge badge-sm badge-info font-bold">Officer</span>
                                        <?php else : ?>
                                            <span class="badge badge-sm badge-ghost">Member</span>
                                        <?php endif ?>
                                    </td>
                                    <td class="font-mono text-xs">
                                        <?= (int)$m['open_lifts'] ?>L / <?= (int)$m['open_slopes'] ?>S
                                    </td>
                                    <td class="font-mono text-xs text-success font-semibold">
                                        <?= currency((int)$m['cash']) ?>
                                    </td>
                                    <td class="font-mono text-xs text-primary font-semibold">
                                        <?= currency((int)$m['donated_cash']) ?>
                                    </td>
                                    <?php if ($isLeader) : ?>
                                        <td>
                                            <?php if ((int)$m['user_id'] !== (int)auth()->id() && $m['role'] !== 'founder') : ?>
                                                <div class="dropdown dropdown-end">
                                                    <div tabindex="0" role="button" class="btn btn-ghost btn-xs btn-circle"><i class="fa-solid fa-ellipsis-vertical"></i></div>
                                                    <ul tabindex="0" class="dropdown-content menu menu-xs bg-base-100 rounded-box shadow-lg z-20 w-36 p-1 border border-base-300">
                                                        <?php if ($isFounder && $m['role'] === 'member') : ?>
                                                            <li>
                                                                <form action="/alliances/manage-member" method="post">
                                                                    <?= csrf_field() ?>
                                                                    <input type="hidden" name="target_user_id" value="<?= (int)$m['user_id'] ?>">
                                                                    <input type="hidden" name="action" value="promote">
                                                                    <button type="submit" class="text-info w-full text-left">Promote to Officer</button>
                                                                </form>
                                                            </li>
                                                        <?php elseif ($isFounder && $m['role'] === 'officer') : ?>
                                                            <li>
                                                                <form action="/alliances/manage-member" method="post">
                                                                    <?= csrf_field() ?>
                                                                    <input type="hidden" name="target_user_id" value="<?= (int)$m['user_id'] ?>">
                                                                    <input type="hidden" name="action" value="demote">
                                                                    <button type="submit" class="w-full text-left">Demote to Member</button>
                                                                </form>
                                                            </li>
                                                        <?php endif ?>
                                                        <?php if ($isFounder) : ?>
                                                            <li>
                                                                <form action="/alliances/manage-member" method="post" onsubmit="return confirm('Transfer founder ownership to <?= esc($m['username']) ?>?');">
                                                                    <?= csrf_field() ?>
                                                                    <input type="hidden" name="target_user_id" value="<?= (int)$m['user_id'] ?>">
                                                                    <input type="hidden" name="action" value="transfer_founder">
                                                                    <button type="submit" class="text-warning w-full text-left">Transfer Leadership</button>
                                                                </form>
                                                            </li>
                                                        <?php endif ?>
                                                        <li>
                                                            <form action="/alliances/manage-member" method="post" onsubmit="return confirm('Remove <?= esc($m['username']) ?> from the alliance?');">
                                                                <?= csrf_field() ?>
                                                                <input type="hidden" name="target_user_id" value="<?= (int)$m['user_id'] ?>">
                                                                <input type="hidden" name="action" value="kick">
                                                                <button type="submit" class="text-error w-full text-left">Kick Member</button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                            <?php endif ?>
                                        </td>
                                    <?php endif ?>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- Column 3: Treasury Contribution, Pending Applications, Activity Log -->
        <div class="space-y-6">

            <!-- Deposit to Vault Card -->
            <div class="card bg-base-100 border border-base-300 shadow-sm p-5 space-y-4">
                <div class="flex items-center gap-2 border-b border-base-300 pb-3">
                    <i class="fa-solid fa-vault text-warning text-lg"></i>
                    <div>
                        <h3 class="font-bold text-sm text-base-content">Contribute to Vault</h3>
                        <div class="text-[11px] text-base-content/50">Your Treasury: <?= currency($userCash) ?></div>
                    </div>
                </div>

                <form action="/alliances/donate" method="post" class="space-y-3">
                    <?= csrf_field() ?>
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs font-semibold">Deposit Amount (€)</span></label>
                        <input type="number" name="amount" min="1000" step="1000" max="<?= $userCash ?>" value="10000" class="input input-bordered input-sm w-full font-mono" required>
                    </div>

                    <div class="grid grid-cols-3 gap-1.5">
                        <button type="button" onclick="document.querySelector('input[name=amount]').value=5000" class="btn btn-xs btn-outline">5,000 €</button>
                        <button type="button" onclick="document.querySelector('input[name=amount]').value=25000" class="btn btn-xs btn-outline">25,000 €</button>
                        <button type="button" onclick="document.querySelector('input[name=amount]').value=100000" class="btn btn-xs btn-outline">100,000 €</button>
                    </div>

                    <button type="submit" class="btn btn-sm btn-success w-full font-bold shadow-sm text-neutral" <?= $userCash < 1000 ? 'disabled' : '' ?>>
                        <i class="fa-solid fa-coins mr-1"></i> Deposit Capital
                    </button>
                    <div class="text-[11px] text-base-content/50 text-center">
                        Grants 1 Alliance XP per 1,000 € deposited
                    </div>
                </form>
            </div>

            <!-- Pending Applications (Leaders Only) -->
            <?php if ($isLeader && !empty($applications)) : ?>
                <div class="card bg-base-100 border border-warning/40 shadow-sm p-5 space-y-3">
                    <div class="flex items-center justify-between border-b border-base-300 pb-2">
                        <h3 class="font-bold text-sm text-base-content flex items-center gap-1.5">
                            <i class="fa-solid fa-user-clock text-warning"></i> Pending Applications
                        </h3>
                        <span class="badge badge-xs badge-warning"><?= count($applications) ?></span>
                    </div>

                    <div class="space-y-3">
                        <?php foreach ($applications as $app) : ?>
                            <div class="p-3 bg-base-200 rounded-xl space-y-2 border border-base-300">
                                <div class="flex items-center justify-between">
                                    <div class="font-bold text-sm"><?= esc($app['username']) ?></div>
                                    <span class="text-xs text-base-content/60 font-mono">Rep: <?= (int)$app['reputation'] ?></span>
                                </div>
                                <?php if (!empty($app['message'])) : ?>
                                    <p class="text-xs text-base-content/70 italic">"<?= esc($app['message']) ?>"</p>
                                <?php endif ?>
                                <div class="flex gap-2 pt-1">
                                    <form action="/alliances/review-application" method="post" class="flex-1">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="application_id" value="<?= (int)$app['id'] ?>">
                                        <input type="hidden" name="action" value="accept">
                                        <button type="submit" class="btn btn-xs btn-success w-full text-neutral font-bold">Accept</button>
                                    </form>
                                    <form action="/alliances/review-application" method="post" class="flex-1">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="application_id" value="<?= (int)$app['id'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-xs btn-ghost text-error w-full">Decline</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>
                </div>
            <?php endif ?>

            <!-- Activity Ledger -->
            <div class="card bg-base-100 border border-base-300 shadow-sm p-5 space-y-3">
                <div class="flex items-center justify-between border-b border-base-300 pb-2">
                    <h3 class="font-bold text-sm text-base-content flex items-center gap-1.5">
                        <i class="fa-solid fa-clock-rotate-left text-info"></i> Syndicate Activity
                    </h3>
                </div>

                <div class="space-y-2.5 max-h-96 overflow-y-auto pr-1">
                    <?php if (empty($activities)) : ?>
                        <div class="text-xs text-base-content/40 text-center py-6">No recent activity.</div>
                    <?php else : ?>
                        <?php foreach ($activities as $act) : ?>
                            <div class="text-xs p-2.5 bg-base-200/50 rounded-lg border border-base-300/60 space-y-1">
                                <div class="text-base-content font-medium leading-snug"><?= esc($act['message']) ?></div>
                                <div class="text-[10px] text-base-content/40 font-mono"><?= timeAgo($act['created_at']) ?></div>
                            </div>
                        <?php endforeach ?>
                    <?php endif ?>
                </div>
            </div>

        </div>

    </div>

</div>
<?= $this->endSection() ?>
