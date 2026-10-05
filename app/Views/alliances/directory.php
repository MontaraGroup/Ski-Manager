<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Resort Alliances - Discover & Form Syndicates<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="max-w-7xl mx-auto p-4 lg:p-8 space-y-6">

    <!-- Top Banner -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-handshake text-primary text-2xl"></i>
                <h1 class="text-2xl lg:text-3xl font-black text-base-content">Resort Alliances & Syndicates</h1>
            </div>
            <p class="text-sm text-base-content/60 mt-1">Band together with fellow directors to co-brand a multi-pass, pool capital, and unlock cooperative fleet discounts.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="/alliances/leaderboard" class="btn btn-sm btn-outline gap-1.5">
                <i class="fa-solid fa-ranking-star text-warning"></i> Standings
            </a>
            <button onclick="document.getElementById('foundModal').showModal()" class="btn btn-sm btn-primary font-bold gap-1.5 shadow-sm">
                <i class="fa-solid fa-plus"></i> Found an Alliance
            </button>
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

    <?php if (!empty($myApplication)) : ?>
        <div class="alert alert-warning shadow-sm border border-warning/30 flex items-center justify-between text-neutral">
            <div class="flex items-center gap-2 text-xs">
                <i class="fa-solid fa-hourglass-half text-base"></i>
                <span>You have a pending application with an alliance. Leadership will review your credentials shortly.</span>
            </div>
        </div>
    <?php endif ?>

    <!-- Why Join an Alliance Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="card bg-base-100 border border-base-300 shadow-sm p-4 space-y-2">
            <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center text-sm">
                <i class="fa-solid fa-id-badge"></i>
            </div>
            <h3 class="font-bold text-sm text-base-content">Syndicate Multi-Pass</h3>
            <p class="text-xs text-base-content/60 leading-relaxed">
                Co-brand a shared mountain pass that drives +5% to +20% cross-mountain visitors and extra price tolerance across all sister resorts.
            </p>
        </div>
        <div class="card bg-base-100 border border-base-300 shadow-sm p-4 space-y-2">
            <div class="w-8 h-8 rounded-lg bg-success/10 text-success flex items-center justify-center text-sm">
                <i class="fa-solid fa-truck-monster"></i>
            </div>
            <h3 class="font-bold text-sm text-base-content">Bulk Equipment Procurement</h3>
            <p class="text-xs text-base-content/60 leading-relaxed">
                Pool capital into the shared vault to research cooperative purchasing agreements, slashing groomer and snowmaker costs by up to 20%.
            </p>
        </div>
        <div class="card bg-base-100 border border-base-300 shadow-sm p-4 space-y-2">
            <div class="w-8 h-8 rounded-lg bg-info/10 text-info flex items-center justify-center text-sm">
                <i class="fa-solid fa-ranking-star"></i>
            </div>
            <h3 class="font-bold text-sm text-base-content">Seasonal Alliance Trophy</h3>
            <p class="text-xs text-base-content/60 leading-relaxed">
                Compete on the global Alliance Leaderboard. Carry your syndicate tag and crest on your Director Accreditation Pass and public profile.
            </p>
        </div>
    </div>

    <!-- Alliance Directory Table -->
    <div class="card bg-base-100 border border-base-300 shadow-sm p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-base-300 pb-3">
            <div>
                <h2 class="text-lg font-bold text-base-content">Active Alliances</h2>
                <p class="text-xs text-base-content/50">Browse established syndicates or apply to join their mountain collective</p>
            </div>
            <span class="badge badge-sm badge-ghost"><?= count($alliances) ?> Alliances Registered</span>
        </div>

        <?php if (empty($alliances)) : ?>
            <div class="text-center py-12 space-y-3">
                <div class="w-12 h-12 rounded-xl bg-base-200 text-base-content/30 flex items-center justify-center mx-auto text-xl">
                    <i class="fa-solid fa-mountain-sun"></i>
                </div>
                <h3 class="font-bold text-base">No alliances formed yet</h3>
                <p class="text-xs text-base-content/50 max-w-sm mx-auto">Be the first ski director to establish a mountain syndicate and recruit partner resorts!</p>
                <button onclick="document.getElementById('foundModal').showModal()" class="btn btn-sm btn-primary font-bold">
                    Found the First Alliance
                </button>
            </div>
        <?php else : ?>
            <div class="overflow-x-auto">
                <table class="table table-sm w-full">
                    <thead>
                        <tr class="text-xs text-base-content/50 border-b border-base-300">
                            <th>Alliance</th>
                            <th>Level & Pass</th>
                            <th>Members</th>
                            <th>Founder</th>
                            <th>Total Valuation</th>
                            <th>Requirements</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alliances as $a) : 
                            $tier = $passTiers[(int)$a['pass_tier']] ?? $passTiers[1];
                            $isFull = (int)$a['member_count'] >= (int)$a['max_members'];
                            $meetsRep = $userRep >= (int)$a['min_reputation'];
                        ?>
                            <tr class="hover:bg-base-200/30">
                                <td>
                                    <div class="flex items-center gap-2.5">
                                        <span class="w-7 h-7 rounded-lg flex items-center justify-center text-xs text-white shrink-0 font-mono font-bold" style="background-color: <?= esc($a['crest_color']) ?>;">
                                            <i class="fa-solid <?= esc($a['crest_icon']) ?>"></i>
                                        </span>
                                        <div>
                                            <div class="font-bold text-sm flex items-center gap-1.5">
                                                <a href="/alliances/view/<?= (int)$a['id'] ?>" class="link link-hover text-base-content">
                                                    <?= esc($a['name']) ?>
                                                </a>
                                                <span class="badge badge-xs badge-neutral font-mono font-bold">[<?= esc($a['tag']) ?>]</span>
                                            </div>
                                            <div class="text-[11px] text-base-content/50 italic line-clamp-1">"<?= esc($a['motto']) ?>"</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-xs badge-primary font-bold">Lvl <?= (int)$a['level'] ?></span>
                                    <div class="text-[11px] text-base-content/60 mt-0.5"><?= esc($tier['name']) ?> (+<?= $tier['visitor_pct'] ?>%)</div>
                                </td>
                                <td class="font-mono text-xs">
                                    <span class="<?= $isFull ? 'text-error font-bold' : 'text-base-content' ?>">
                                        <?= (int)$a['member_count'] ?> / <?= (int)$a['max_members'] ?>
                                    </span>
                                </td>
                                <td class="text-xs text-base-content/70">
                                    <?= esc($a['founder_name']) ?>
                                </td>
                                <td class="font-mono text-xs text-success font-semibold">
                                    <?= currency((int)$a['valuation']) ?>
                                </td>
                                <td>
                                    <?php if ((int)$a['min_reputation'] > 0) : ?>
                                        <span class="badge badge-xs <?= $meetsRep ? 'badge-ghost' : 'badge-error' ?> font-mono">
                                            <?= (int)$a['min_reputation'] ?>+ Rep
                                        </span>
                                    <?php else : ?>
                                        <span class="text-[11px] text-base-content/50">None</span>
                                    <?php endif ?>
                                </td>
                                <td class="text-right">
                                    <?php if ($isFull) : ?>
                                        <button class="btn btn-xs btn-disabled">Full</button>
                                    <?php elseif (!$meetsRep) : ?>
                                        <button class="btn btn-xs btn-disabled" title="Requires <?= (int)$a['min_reputation'] ?> reputation">Low Rep</button>
                                    <?php elseif ((int)$a['is_recruiting'] === 1) : ?>
                                        <form action="/alliances/join/<?= (int)$a['id'] ?>" method="post">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-xs btn-primary font-bold">Join</button>
                                        </form>
                                    <?php else : ?>
                                        <button onclick="document.getElementById('applyModal_<?= (int)$a['id'] ?>').showModal()" class="btn btn-xs btn-outline">Apply</button>

                                        <!-- Application Dialog -->
                                        <dialog id="applyModal_<?= (int)$a['id'] ?>" class="modal modal-middle" closedby="any" aria-labelledby="applyModalTitle_<?= (int)$a['id'] ?>">
                                            <div class="modal-box text-left">
                                                <h3 id="applyModalTitle_<?= (int)$a['id'] ?>" class="font-bold text-lg mb-2">Apply to <?= esc($a['name']) ?></h3>
                                                <p class="text-xs text-base-content/60 mb-4">Leadership will review your application before admitting your mountain to the syndicate.</p>
                                                <form action="/alliances/join/<?= (int)$a['id'] ?>" method="post" class="space-y-3">
                                                    <?= csrf_field() ?>
                                                    <div class="form-control">
                                                        <label class="label py-1"><span class="label-text text-xs">Introduction Note</span></label>
                                                        <textarea name="message" class="textarea textarea-bordered text-xs h-24" placeholder="Introduce your resort, slope capacity, and why you want to join..."></textarea>
                                                    </div>
                                                    <div class="modal-action">
                                                        <button type="button" onclick="this.closest('dialog').close()" class="btn btn-sm btn-ghost">Cancel</button>
                                                        <button type="submit" class="btn btn-sm btn-primary">Submit Application</button>
                                                    </div>
                                                </form>
                                            </div>
                                            <form method="dialog" class="modal-backdrop"><button>close</button></form>
                                        </dialog>
                                    <?php endif ?>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
    </div>

</div>

<!-- Found Alliance Modal -->
<dialog id="foundModal" class="modal modal-middle" closedby="any" aria-labelledby="foundModalTitle">
    <div class="modal-box max-w-lg">
        <div class="flex items-center gap-2 border-b border-base-300 pb-3 mb-4">
            <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
            </div>
            <div>
                <h3 id="foundModalTitle" class="font-bold text-lg text-base-content">Charter a New Alliance</h3>
                <div class="text-xs text-base-content/50">Creation Fee: 50,000 € (Your Cash: <?= currency($userCash) ?>)</div>
            </div>
        </div>

        <form action="/alliances/create" method="post" class="space-y-4">
            <?= csrf_field() ?>

            <div class="grid grid-cols-3 gap-3">
                <div class="col-span-2 form-control">
                    <label class="label py-1" for="name"><span class="label-text text-xs font-semibold">Alliance Name</span></label>
                    <input type="text" name="name" id="name" class="input input-bordered input-sm w-full" placeholder="e.g. Summit Alpine Syndicate" minlength="3" maxlength="30" required>
                </div>
                <div class="form-control">
                    <label class="label py-1" for="tag"><span class="label-text text-xs font-semibold">Tag Ticker</span></label>
                    <input type="text" name="tag" id="tag" class="input input-bordered input-sm w-full font-mono uppercase" placeholder="SUMMT" minlength="3" maxlength="5" required>
                </div>
            </div>

            <div class="form-control">
                <label class="label py-1" for="motto"><span class="label-text text-xs font-semibold">Charter Motto</span></label>
                <input type="text" name="motto" id="motto" class="input input-bordered input-sm w-full" placeholder="e.g. United by fresh powder and alpine elevation." maxlength="100">
            </div>

            <div class="form-control">
                <label class="label py-1" for="description"><span class="label-text text-xs font-semibold">Description / Charter Overview</span></label>
                <textarea name="description" id="description" class="textarea textarea-bordered text-xs h-20" placeholder="Describe your cooperative goals and member standards..."></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="form-control">
                    <label class="label py-1" for="crest_icon"><span class="label-text text-xs font-semibold">Crest Icon</span></label>
                    <select name="crest_icon" id="crest_icon" class="select select-bordered select-sm w-full">
                        <option value="fa-mountain-sun">Mountain Sun</option>
                        <option value="fa-snowflake">Snowflake</option>
                        <option value="fa-shield-halved">Shield</option>
                        <option value="fa-crown">Crown</option>
                        <option value="fa-award">Medal</option>
                        <option value="fa-person-skiing">Ski Racer</option>
                        <option value="fa-bolt">Lightning Bolt</option>
                    </select>
                </div>
                <div class="form-control">
                    <label class="label py-1" for="crest_color"><span class="label-text text-xs font-semibold">Crest Color</span></label>
                    <select name="crest_color" id="crest_color" class="select select-bordered select-sm w-full">
                        <option value="#3b82f6">Alpine Blue</option>
                        <option value="#10b981">Emerald Green</option>
                        <option value="#f59e0b">Sunset Amber</option>
                        <option value="#8b5cf6">Royal Purple</option>
                        <option value="#ef4444">Summit Crimson</option>
                        <option value="#0f172a">Carbon Slate</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 items-center p-3 bg-base-200/50 rounded-xl border border-base-300">
                <div class="form-control">
                    <label class="label py-1" for="min_reputation"><span class="label-text text-xs font-semibold">Min Reputation Req</span></label>
                    <input type="number" name="min_reputation" id="min_reputation" value="0" min="0" step="5" class="input input-bordered input-sm w-full font-mono">
                </div>
                <div class="form-control pt-4">
                    <label class="cursor-pointer label justify-start gap-2 py-0">
                        <input type="checkbox" name="is_recruiting" value="1" checked class="checkbox checkbox-primary checkbox-sm">
                        <span class="label-text text-xs font-medium">Open Recruitment</span>
                    </label>
                    <div class="text-[10px] text-base-content/50 ml-6">Allow direct joining without approval</div>
                </div>
            </div>

            <div class="modal-action border-t border-base-300 pt-3">
                <button type="button" onclick="this.closest('dialog').close()" class="btn btn-sm btn-ghost">Cancel</button>
                <button type="submit" class="btn btn-sm btn-primary font-bold shadow-sm" <?= $userCash < 50000 ? 'disabled' : '' ?>>
                    Charter Alliance (50,000 €)
                </button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

<?= $this->endSection() ?>
