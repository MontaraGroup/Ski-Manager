<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Development Roadmap<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="max-w-7xl mx-auto p-4 lg:p-8 space-y-8">
    
    <!-- Hero Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-6 border-b border-base-200">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="badge badge-primary badge-sm font-semibold tracking-wide uppercase">Community Driven</span>
                <span class="text-xs text-base-content/50"><?= $totalItems ?> items on board</span>
            </div>
            <h1 class="text-3xl font-extrabold tracking-tight">Development Roadmap</h1>
            <p class="text-sm text-base-content/70 mt-1 max-w-2xl">
                Explore upcoming seasonal features, vote on what should be built next, and follow our progress as we ship updates.
            </p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a href="/updates" class="btn btn-sm btn-outline gap-2">
                <i class="fa-solid fa-bullhorn text-primary"></i> Changelog
            </a>
            <button onclick="document.getElementById('suggest_modal').showModal()" class="btn btn-sm btn-primary gap-2 shadow-sm">
                <i class="fa-solid fa-plus"></i> Suggest Feature
            </button>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (session()->getFlashdata('success')) : ?>
        <div class="alert alert-success shadow-sm text-sm py-2.5">
            <i class="fa-solid fa-circle-check"></i>
            <span><?= session()->getFlashdata('success') ?></span>
        </div>
    <?php endif ?>
    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-error shadow-sm text-sm py-2.5">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span><?= session()->getFlashdata('error') ?></span>
        </div>
    <?php endif ?>

    <!-- Category Filter Bar -->
    <div class="flex flex-wrap items-center justify-between gap-3 bg-base-100 p-2.5 rounded-xl border border-base-200 shadow-xs">
        <div class="flex flex-wrap items-center gap-1.5" id="categoryFilterBar">
            <span class="text-xs font-semibold text-base-content/50 uppercase tracking-wider px-2">Filter:</span>
            <button type="button" onclick="filterRoadmap('all', this)" class="btn btn-xs btn-active filter-btn" data-cat="all">All</button>
            <button type="button" onclick="filterRoadmap('gameplay', this)" class="btn btn-xs btn-ghost filter-btn" data-cat="gameplay">
                <i class="fa-solid fa-person-skiing text-success text-[10px]"></i> Gameplay
            </button>
            <button type="button" onclick="filterRoadmap('quality_of_life', this)" class="btn btn-xs btn-ghost filter-btn" data-cat="quality_of_life">
                <i class="fa-solid fa-wand-magic-sparkles text-info text-[10px]"></i> Quality of Life
            </button>
            <button type="button" onclick="filterRoadmap('economy', this)" class="btn btn-xs btn-ghost filter-btn" data-cat="economy">
                <i class="fa-solid fa-coins text-warning text-[10px]"></i> Economy
            </button>
            <button type="button" onclick="filterRoadmap('mobile', this)" class="btn btn-xs btn-ghost filter-btn" data-cat="mobile">
                <i class="fa-solid fa-mobile-screen text-secondary text-[10px]"></i> Mobile
            </button>
        </div>
        <div class="text-xs text-base-content/50 pr-2">
            Click 🔺 to upvote features you want most
        </div>
    </div>

    <!-- 3-Column Kanban Board -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">
        <?php foreach ($columns as $statusKey => $col) : ?>
            <div class="bg-base-200/50 rounded-2xl border border-base-300/80 p-4 space-y-4">
                <!-- Column Header -->
                <div class="flex items-center justify-between pb-3 border-b border-base-300">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-<?= $col['color'] ?>"></span>
                        <h2 class="font-bold text-base"><?= esc($col['title']) ?></h2>
                    </div>
                    <span class="badge <?= $col['badge_color'] ?> badge-sm font-semibold count-badge" id="count-<?= $statusKey ?>">
                        <?= count($col['items']) ?>
                    </span>
                </div>
                <p class="text-xs text-base-content/60 -mt-2"><?= esc($col['description']) ?></p>

                <!-- Cards Container -->
                <div class="space-y-3 column-cards" id="col-<?= $statusKey ?>">
                    <?php if (empty($col['items'])) : ?>
                        <div class="text-center py-8 text-xs text-base-content/40 empty-state">
                            <i class="fa-solid fa-mountain-sun text-2xl mb-2 opacity-30 block"></i>
                            No items in this column
                        </div>
                    <?php else : ?>
                        <?php foreach ($col['items'] as $item) : 
                            $catBadge = match($item['category']) {
                                'gameplay' => 'badge-success',
                                'quality_of_life' => 'badge-info',
                                'economy' => 'badge-warning',
                                'mobile' => 'badge-secondary',
                                default => 'badge-ghost',
                            };
                            $catLabel = match($item['category']) {
                                'gameplay' => 'Gameplay',
                                'quality_of_life' => 'Quality of Life',
                                'economy' => 'Economy',
                                'mobile' => 'Mobile',
                                default => esc($item['category']),
                            };
                        ?>
                            <div class="card bg-base-100 shadow-sm border border-base-300 transition duration-150 roadmap-card" data-category="<?= esc($item['category']) ?>" id="card-<?= $item['id'] ?>">
                                <div class="card-body p-4 space-y-2.5">
                                    
                                    <!-- Top Meta Row -->
                                    <div class="flex items-center justify-between gap-2 flex-wrap">
                                        <span class="badge <?= $catBadge ?> badge-outline badge-xs font-semibold uppercase tracking-wider py-1.5 px-2">
                                            <?= $catLabel ?>
                                        </span>

                                        <?php if ($isAdmin) : ?>
                                            <div class="dropdown dropdown-end">
                                                <div tabindex="0" role="button" class="btn btn-ghost btn-xs px-1 text-base-content/40 hover:text-base-content">
                                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                                </div>
                                                <ul tabindex="0" class="dropdown-content z-20 menu p-2 shadow-lg bg-base-100 rounded-box w-44 text-xs border border-base-200">
                                                    <li class="menu-title text-[10px]">Change Status</li>
                                                    <li><a onclick="updateItemStatus(<?= $item['id'] ?>, 'planned')"><i class="fa-solid fa-compass text-secondary"></i> Planned</a></li>
                                                    <li><a onclick="updateItemStatus(<?= $item['id'] ?>, 'in_progress')"><i class="fa-solid fa-code text-primary"></i> In Progress</a></li>
                                                    <li><a onclick="updateItemStatus(<?= $item['id'] ?>, 'completed')"><i class="fa-solid fa-circle-check text-success"></i> Completed</a></li>
                                                    <div class="divider my-1"></div>
                                                    <li><a href="/roadmap/delete/<?= $item['id'] ?>" onclick="return confirm('Delete this card?')" class="text-error"><i class="fa-solid fa-trash"></i> Delete</a></li>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Title & Description -->
                                    <div>
                                        <h3 class="font-bold text-sm leading-snug text-base-content"><?= esc($item['title']) ?></h3>
                                        <?php if (!empty($item['description'])) : ?>
                                            <p class="text-xs text-base-content/70 mt-1 leading-relaxed"><?= esc($item['description']) ?></p>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Bottom Footer: Author & Upvote -->
                                    <div class="pt-2 flex items-center justify-between gap-2 border-t border-base-200">
                                        <div class="flex items-center gap-1.5 text-xs text-base-content/50 truncate">
                                            <i class="fa-solid fa-user-gear text-[10px] opacity-60"></i>
                                            <span class="truncate"><?= esc($item['author_name'] ?? 'Ski Manager') ?></span>
                                        </div>

                                        <button type="button" 
                                                onclick="upvoteItem(<?= $item['id'] ?>, this)" 
                                                class="btn btn-xs gap-1.5 font-semibold transition-all vote-btn <?= $item['voted'] ? 'btn-primary text-primary-content shadow-xs' : 'btn-outline border-base-300 hover:btn-primary' ?>"
                                                title="Vote for this feature">
                                            <i class="fa-solid fa-caret-up text-xs"></i>
                                            <span class="vote-count"><?= (int)$item['upvotes'] ?></span>
                                        </button>
                                    </div>

                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</div>

<!-- Suggest Feature Modal -->
<dialog id="suggest_modal" class="modal">
    <div class="modal-box max-w-md">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
        </form>
        <h3 class="font-bold text-lg flex items-center gap-2">
            <i class="fa-solid fa-lightbulb text-warning"></i> Suggest a Feature
        </h3>
        <p class="text-xs text-base-content/60 mt-1">
            Have an idea to make Ski Manager better? Submit it here to share it on the community roadmap.
        </p>

        <form action="/roadmap/suggest" method="POST" class="space-y-4 mt-4">
            <?= csrf_field() ?>
            
            <div class="form-control">
                <label class="label py-1"><span class="label-text text-xs font-semibold">Feature Title</span></label>
                <input type="text" name="title" required minlength="3" maxlength="255" placeholder="e.g. Night terrain snowpark challenges" class="input input-sm input-bordered w-full" />
            </div>

            <div class="form-control">
                <label class="label py-1"><span class="label-text text-xs font-semibold">Category</span></label>
                <select name="category" class="select select-sm select-bordered w-full">
                    <option value="gameplay">Gameplay (Lifts, trails, mountain mechanics)</option>
                    <option value="quality_of_life">Quality of Life (UI/UX, shortcuts)</option>
                    <option value="economy">Economy (Pricing, shops, finances)</option>
                    <option value="mobile">Mobile (Responsive UI, notifications)</option>
                </select>
            </div>

            <div class="form-control">
                <label class="label py-1"><span class="label-text text-xs font-semibold">Description / How it works</span></label>
                <textarea name="description" rows="3" placeholder="Explain how this feature would work in the game..." class="textarea textarea-sm textarea-bordered w-full"></textarea>
            </div>

            <div class="modal-action mt-6">
                <button type="button" onclick="document.getElementById('suggest_modal').close()" class="btn btn-sm btn-ghost">Cancel</button>
                <button type="submit" class="btn btn-sm btn-primary gap-1.5">
                    <i class="fa-solid fa-paper-plane text-xs"></i> Submit Feature
                </button>
            </div>
        </form>
    </div>
</dialog>

<!-- Interactive Scripts -->
<script>
// Filter by category
function filterRoadmap(cat, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => {
        b.classList.remove('btn-active', 'btn-primary');
        b.classList.add('btn-ghost');
    });
    btn.classList.add('btn-active');
    btn.classList.remove('btn-ghost');

    const cards = document.querySelectorAll('.roadmap-card');
    cards.forEach(c => {
        if (cat === 'all' || c.dataset.category === cat) {
            c.style.display = 'block';
        } else {
            c.style.display = 'none';
        }
    });
}

// Interactive Upvoting
async function upvoteItem(itemId, btn) {
    const countSpan = btn.querySelector('.vote-count');
    const prevCount = parseInt(countSpan.textContent) || 0;
    const isVoted = btn.classList.contains('btn-primary');

    // Optimistic UI update
    btn.disabled = true;
    if (isVoted) {
        btn.classList.remove('btn-primary', 'text-primary-content');
        btn.classList.add('btn-outline');
        countSpan.textContent = Math.max(0, prevCount - 1);
    } else {
        btn.classList.add('btn-primary', 'text-primary-content');
        btn.classList.remove('btn-outline');
        countSpan.textContent = prevCount + 1;
    }

    try {
        const res = await fetch('/roadmap/vote/' + itemId, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '<?= csrf_hash() ?>'
            }
        });
        const data = await res.json();
        if (data.success) {
            countSpan.textContent = data.upvotes ?? data.votes ?? prevCount;
            if (data.voted) {
                btn.classList.add('btn-primary', 'text-primary-content');
                btn.classList.remove('btn-outline');
            } else {
                btn.classList.remove('btn-primary', 'text-primary-content');
                btn.classList.add('btn-outline');
            }
        } else {
            // Revert
            countSpan.textContent = prevCount;
        }
    } catch (e) {
        countSpan.textContent = prevCount;
    } finally {
        btn.disabled = false;
    }
}

// Admin status change
async function updateItemStatus(itemId, newStatus) {
    try {
        const formData = new FormData();
        formData.append('id', itemId);
        formData.append('status', newStatus);
        formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

        const res = await fetch('/roadmap/admin/status', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '<?= csrf_hash() ?>'
            }
        });
        const data = await res.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error updating status');
        }
    } catch (e) {
        alert('Failed to update status');
    }
}
</script>
<?= $this->endSection() ?>
