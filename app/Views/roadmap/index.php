<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Development Roadmap<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="max-w-7xl mx-auto p-4 lg:p-8 space-y-6">

    <!-- Hero Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6 pb-6 border-b border-base-200">
        <div>
            <div class="flex items-center gap-2 mb-2 flex-wrap">
                <span class="badge badge-primary badge-sm font-semibold tracking-wide uppercase">Community Driven</span>
                <span class="text-xs text-base-content/50"><span id="hero_total_items"><?= $totalItems ?></span> items on board</span>
            </div>
            <h1 class="text-3xl font-extrabold tracking-tight">Development Roadmap</h1>
            <p class="text-sm text-base-content/70 mt-1 max-w-2xl leading-relaxed">
                Explore upcoming seasonal features, vote on development priorities, and track real-time progress as we ship updates.
            </p>
        </div>
        <div class="flex items-center gap-2 shrink-0 flex-wrap">
            <a href="/updates" class="btn btn-sm btn-outline gap-2">
                <i class="fa-solid fa-bullhorn text-primary"></i> Game Updates
            </a>
            <button onclick="document.getElementById('suggest_modal').showModal()" class="btn btn-sm btn-primary gap-2 shadow-sm">
                <i class="fa-solid fa-plus"></i> Suggest Feature
            </button>
        </div>
    </div>

    <!-- Live Telemetry Stats Bar -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-base-100 p-3.5 rounded-xl border border-base-200 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                <i class="fa-solid fa-thumbs-up text-base"></i>
            </div>
            <div>
                <div class="text-xs text-base-content/60 font-medium">Community Votes</div>
                <div class="text-lg font-black tracking-tight" id="hero_total_votes"><?= number_format($totalVotes) ?></div>
            </div>
        </div>

        <div class="bg-base-100 p-3.5 rounded-xl border border-base-200 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-secondary/10 text-secondary flex items-center justify-center shrink-0">
                <i class="fa-solid fa-compass text-base"></i>
            </div>
            <div>
                <div class="text-xs text-base-content/60 font-medium">Planned</div>
                <div class="text-lg font-black tracking-tight" id="hero_planned_count"><?= count($columns['planned']['items']) ?></div>
            </div>
        </div>

        <div class="bg-base-100 p-3.5 rounded-xl border border-base-200 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-warning/10 text-warning flex items-center justify-center shrink-0">
                <i class="fa-solid fa-screwdriver-wrench text-base"></i>
            </div>
            <div>
                <div class="text-xs text-base-content/60 font-medium">In Progress</div>
                <div class="text-lg font-black tracking-tight" id="hero_inprogress_count"><?= count($columns['in_progress']['items']) ?></div>
            </div>
        </div>

        <div class="bg-base-100 p-3.5 rounded-xl border border-base-200 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-success/10 text-success flex items-center justify-center shrink-0">
                <i class="fa-solid fa-circle-check text-base"></i>
            </div>
            <div>
                <div class="text-xs text-base-content/60 font-medium">Shipped</div>
                <div class="text-lg font-black tracking-tight" id="hero_completed_count"><?= count($columns['completed']['items']) ?></div>
            </div>
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

    <!-- Controls: Search & Category Filter Bar -->
    <div class="bg-base-100 p-3 rounded-xl border border-base-200 shadow-xs space-y-3">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
            
            <!-- Category Filter Pills -->
            <div class="flex flex-wrap items-center gap-1.5" id="categoryFilterBar">
                <span class="text-xs font-bold text-base-content/50 uppercase tracking-wider px-1">Category:</span>
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

            <!-- Instant Search Input -->
            <search class="flex items-center gap-2 w-full lg:w-72">
                <div class="relative w-full">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-xs text-base-content/40" aria-hidden="true"></i>
                    <input type="search" 
                           id="roadmapSearch" 
                           placeholder="Search features..." 
                           aria-label="Search features"
                           oninput="handleSearch(this.value)"
                           class="input input-sm input-bordered w-full pl-8 pr-7 text-xs rounded-lg" />
                    <button type="button" 
                            id="clearSearchBtn" 
                            onclick="clearSearch()" 
                            aria-label="Clear search"
                            class="hidden absolute right-2.5 top-2 text-xs text-base-content/40 hover:text-base-content">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
            </search>

        </div>

        <!-- Help Info Row -->
        <div class="flex items-center justify-between text-xs text-base-content/50 pt-2 border-t border-base-200">
            <div class="flex items-center gap-1.5">
                <i class="fa-solid fa-arrow-up text-primary text-[10px]"></i>
                <span>Click the arrow button on any card to upvote features you want prioritized.</span>
            </div>
            <div id="resultsCountNotice" class="font-medium">
                Showing all <?= $totalItems ?> items
            </div>
        </div>
    </div>

    <!-- Mobile Column Tabs (visible on small screens) -->
    <div class="flex md:hidden bg-base-200 p-1 rounded-xl gap-1" id="mobileTabs">
        <button type="button" onclick="switchMobileTab('planned', this)" class="flex-1 py-1.5 text-xs font-bold rounded-lg transition-all bg-base-100 shadow-xs mobile-tab" data-target="col-planned-wrapper">
            Planned (<span id="mobile-count-planned"><?= count($columns['planned']['items']) ?></span>)
        </button>
        <button type="button" onclick="switchMobileTab('in_progress', this)" class="flex-1 py-1.5 text-xs font-bold rounded-lg transition-all text-base-content/60 hover:text-base-content mobile-tab" data-target="col-in_progress-wrapper">
            In Progress (<span id="mobile-count-in_progress"><?= count($columns['in_progress']['items']) ?></span>)
        </button>
        <button type="button" onclick="switchMobileTab('completed', this)" class="flex-1 py-1.5 text-xs font-bold rounded-lg transition-all text-base-content/60 hover:text-base-content mobile-tab" data-target="col-completed-wrapper">
            Shipped (<span id="mobile-count-completed"><?= count($columns['completed']['items']) ?></span>)
        </button>
    </div>

    <!-- 3-Column Kanban Board -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">
        <?php foreach ($columns as $statusKey => $col) : 
            $dotColor = match($statusKey) {
                'planned' => 'bg-secondary',
                'in_progress' => 'bg-primary',
                'completed' => 'bg-success',
                default => 'bg-base-content/50',
            };
        ?>
            <div class="bg-base-200/50 rounded-2xl border border-base-300/80 p-4 space-y-4 kanban-column-wrapper" id="col-<?= $statusKey ?>-wrapper">
                
                <!-- Column Header -->
                <div class="flex items-center justify-between pb-3 border-b border-base-300">
                    <div class="flex items-center gap-2">
                        <?php if ($statusKey === 'in_progress'): ?>
                            <span class="relative flex h-2.5 w-2.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-primary"></span>
                            </span>
                        <?php else: ?>
                            <span class="w-2.5 h-2.5 rounded-full <?= $dotColor ?>"></span>
                        <?php endif; ?>
                        <h2 class="font-bold text-base text-base-content"><?= esc($col['title']) ?></h2>
                    </div>
                    <span class="badge <?= $col['badge_color'] ?> badge-sm font-semibold count-badge" id="count-<?= $statusKey ?>">
                        <?= count($col['items']) ?>
                    </span>
                </div>
                <p class="text-xs text-base-content/60 -mt-2"><?= esc($col['description']) ?></p>

                <!-- Cards Container -->
                <div class="space-y-3 column-cards min-h-[120px]" id="col-<?= $statusKey ?>">
                    
                    <!-- Static Empty State -->
                    <div class="text-center py-8 text-xs text-base-content/40 empty-state <?= !empty($col['items']) ? 'hidden' : '' ?>" id="empty-<?= $statusKey ?>">
                        <i class="fa-solid fa-mountain-sun text-2xl mb-2 opacity-30 block"></i>
                        <span>No items in this column</span>
                    </div>

                    <?php foreach ($col['items'] as $item) : 
                        $catBadge = match($item['category']) {
                            'gameplay' => 'badge-success',
                            'quality_of_life' => 'badge-info',
                            'economy' => 'badge-warning',
                            'mobile' => 'badge-secondary',
                            default => 'badge-ghost',
                        };
                        $catIcon = match($item['category']) {
                            'gameplay' => 'fa-person-skiing',
                            'quality_of_life' => 'fa-wand-magic-sparkles',
                            'economy' => 'fa-coins',
                            'mobile' => 'fa-mobile-screen',
                            default => 'fa-tag',
                        };
                        $catLabel = match($item['category']) {
                            'gameplay' => 'Gameplay',
                            'quality_of_life' => 'Quality of Life',
                            'economy' => 'Economy',
                            'mobile' => 'Mobile',
                            default => esc($item['category']),
                        };
                        $isTeam = ($item['author_name'] === 'Ski Manager Team');
                    ?>
                        <div class="card bg-base-100 shadow-xs border border-base-300 hover:border-primary/40 hover:shadow-md transition-all duration-150 roadmap-card cursor-pointer group" 
                             data-category="<?= esc($item['category']) ?>" 
                             data-id="<?= $item['id'] ?>"
                             id="card-<?= $item['id'] ?>"
                             onclick="openDetailModal(<?= $item['id'] ?>)">
                            
                            <div class="card-body p-4 space-y-2.5">
                                
                                <!-- Top Meta Row -->
                                <div class="flex items-center justify-between gap-2 flex-wrap">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="badge <?= $catBadge ?> badge-outline badge-xs font-semibold py-1.5 px-2 gap-1">
                                            <i class="fa-solid <?= $catIcon ?> text-[9px]"></i> <?= $catLabel ?>
                                        </span>
                                        <?php if ($isTeam): ?>
                                            <span class="badge badge-ghost badge-xs text-primary font-semibold gap-1 py-1 px-1.5">
                                                <i class="fa-solid fa-shield-halved text-[9px]"></i> Official
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($isAdmin) : ?>
                                        <div class="dropdown dropdown-end" onclick="event.stopPropagation()">
                                            <div tabindex="0" role="button" class="btn btn-ghost btn-xs px-1 text-base-content/40 hover:text-base-content">
                                                <i class="fa-solid fa-ellipsis-vertical"></i>
                                            </div>
                                            <ul tabindex="0" class="dropdown-content z-20 menu p-2 shadow-lg bg-base-100 rounded-box w-44 text-xs border border-base-200">
                                                <li class="menu-title text-[10px]">Move Status</li>
                                                <li><a onclick="updateItemStatus(<?= $item['id'] ?>, 'planned')"><i class="fa-solid fa-compass text-secondary"></i> Planned</a></li>
                                                <li><a onclick="updateItemStatus(<?= $item['id'] ?>, 'in_progress')"><i class="fa-solid fa-screwdriver-wrench text-warning"></i> In Progress</a></li>
                                                <li><a onclick="updateItemStatus(<?= $item['id'] ?>, 'completed')"><i class="fa-solid fa-circle-check text-success"></i> Shipped</a></li>
                                                <div class="divider my-1"></div>
                                                <li><a onclick="deleteItemAjax(<?= $item['id'] ?>)" class="text-error"><i class="fa-solid fa-trash"></i> Delete</a></li>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Title & Description -->
                                <div>
                                    <h3 class="font-bold text-sm leading-snug text-base-content group-hover:text-primary transition-colors card-title-text">
                                        <?= esc($item['title']) ?>
                                    </h3>
                                    <?php if (!empty($item['description'])) : ?>
                                        <p class="text-xs text-base-content/70 mt-1 leading-relaxed line-clamp-3 card-desc-text">
                                            <?= esc($item['description']) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <!-- Bottom Footer: Author & Upvote -->
                                <div class="pt-2 flex items-center justify-between gap-2 border-t border-base-200" onclick="event.stopPropagation()">
                                    <div class="flex items-center gap-1.5 text-xs text-base-content/50 truncate">
                                        <i class="fa-solid <?= $isTeam ? 'fa-user-shield text-primary' : 'fa-user' ?> text-[10px] opacity-70"></i>
                                        <span class="truncate"><?= esc($item['author_name']) ?></span>
                                    </div>

                                    <button type="button" 
                                            onclick="upvoteItem(<?= $item['id'] ?>, this, event)" 
                                            class="btn btn-xs gap-1.5 font-bold transition-all vote-btn <?= $item['voted'] ? 'btn-primary text-primary-content shadow-xs' : 'btn-outline border-base-300 hover:btn-primary' ?>"
                                            title="<?= $item['voted'] ? 'Remove your vote' : 'Upvote this feature' ?>">
                                        <i class="fa-solid fa-arrow-up text-[10px]"></i>
                                        <span class="vote-count"><?= (int)$item['upvotes'] ?></span>
                                    </button>
                                </div>

                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Global No Results Banner (when search yields zero matches) -->
    <div id="globalNoResults" class="hidden text-center py-16 bg-base-100 rounded-2xl border border-base-200 space-y-3">
        <div class="w-12 h-12 rounded-full bg-base-200 flex items-center justify-center mx-auto text-base-content/40">
            <i class="fa-solid fa-magnifying-glass text-xl"></i>
        </div>
        <h3 class="font-bold text-base text-base-content">No features match your search</h3>
        <p class="text-xs text-base-content/60 max-w-sm mx-auto">
            Try adjusting your search terms or clearing category filters to view other roadmap features.
        </p>
        <button type="button" onclick="clearSearch(); filterRoadmap('all', document.querySelector('[data-cat=all]'));" class="btn btn-xs btn-outline">
            Reset Filters
        </button>
    </div>

</div>

<!-- Feature Detail Modal -->
<dialog id="detail_modal" class="modal" closedby="any" aria-labelledby="detail_title">
    <div class="modal-box max-w-lg p-6 space-y-4">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3" aria-label="Close dialog">✕</button>
        </form>

        <div class="flex items-center gap-2 flex-wrap pt-1">
            <span id="detail_category_badge" class="badge badge-sm font-semibold"></span>
            <span id="detail_status_badge" class="badge badge-sm font-semibold"></span>
            <span id="detail_author_badge" class="badge badge-ghost badge-sm text-base-content/60"></span>
        </div>

        <h3 id="detail_title" class="text-lg font-bold text-base-content leading-snug"></h3>
        
        <div class="p-3 bg-base-200/50 rounded-xl border border-base-300 text-xs text-base-content/80 leading-relaxed whitespace-pre-wrap max-h-60 overflow-y-auto" id="detail_description"></div>

        <div class="flex items-center justify-between pt-2 border-t border-base-200 gap-2 flex-wrap">
            <button type="button" onclick="copyCardLink(currentDetailItemId)" class="btn btn-xs btn-outline gap-1.5">
                <i class="fa-solid fa-link text-[10px]"></i> Copy Share Link
            </button>

            <button type="button" 
                    id="detail_vote_btn"
                    onclick="upvoteFromModal()" 
                    class="btn btn-xs font-bold gap-1.5">
                <i class="fa-solid fa-arrow-up text-[10px]"></i>
                <span>Vote</span> (<span id="detail_vote_count">0</span>)
            </button>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop">
        <button>close</button>
    </form>
</dialog>

<!-- Suggest Feature Modal -->
<dialog id="suggest_modal" class="modal" closedby="any" aria-labelledby="suggest_modal_title">
    <div class="modal-box max-w-md">
        <form method="dialog">
            <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2" aria-label="Close dialog">✕</button>
        </form>
        <h3 class="font-bold text-lg flex items-center gap-2" id="suggest_modal_title">
            <i class="fa-solid fa-lightbulb text-warning" aria-hidden="true"></i> Suggest a Feature
        </h3>
        <p class="text-xs text-base-content/60 mt-1">
            Share your idea for upcoming seasons. Approved suggestions are added directly to the community roadmap.
        </p>

        <form action="/roadmap/suggest" method="POST" class="space-y-4 mt-4" onsubmit="return validateSuggestForm(this)">
            <?= csrf_field() ?>
            
            <div class="form-control">
                <div class="flex items-center justify-between py-1">
                    <label class="label-text text-xs font-semibold">Feature Title</label>
                    <span id="titleCounter" class="text-[10px] text-base-content/40">0 / 100</span>
                </div>
                <input type="text" 
                       name="title" 
                       id="suggestTitle" 
                       required 
                       minlength="5" 
                       maxlength="100" 
                       oninput="handleSuggestTitleInput(this.value)"
                       placeholder="e.g. Night terrain snowpark challenges" 
                       class="input input-sm input-bordered w-full" />
                
                <!-- Duplicate Idea Warning Hint -->
                <div id="duplicateHint" class="hidden mt-2 p-2.5 rounded-lg bg-warning/10 border border-warning/30 text-xs text-warning space-y-1">
                    <div class="font-bold flex items-center gap-1.5">
                        <i class="fa-solid fa-triangle-exclamation"></i> Similar idea already on the board:
                    </div>
                    <div id="duplicateHintTitle" class="text-[11px] font-medium text-base-content"></div>
                    <div class="text-[10px] text-base-content/60">
                        Consider upvoting the existing item instead of submitting a duplicate!
                    </div>
                </div>
            </div>

            <div class="form-control">
                <label class="label py-1"><span class="label-text text-xs font-semibold">Category</span></label>
                <select name="category" class="select select-sm select-bordered w-full text-xs">
                    <option value="gameplay">Gameplay (Lifts, trails, mountain mechanics)</option>
                    <option value="quality_of_life">Quality of Life (UI/UX, shortcuts, notifications)</option>
                    <option value="economy">Economy (Ticket pricing, shops, finances)</option>
                    <option value="mobile">Mobile (Responsive layouts, touch controls)</option>
                </select>
            </div>

            <div class="form-control">
                <div class="flex items-center justify-between py-1">
                    <label class="label-text text-xs font-semibold">Description / How it works</label>
                    <span id="descCounter" class="text-[10px] text-base-content/40">0 / 1000</span>
                </div>
                <textarea name="description" 
                          id="suggestDesc" 
                          rows="4" 
                          required
                          minlength="10" 
                          maxlength="1000" 
                          oninput="document.getElementById('descCounter').textContent = this.value.length + ' / 1000'"
                          placeholder="Describe how this feature should work in the game and why players would enjoy it..." 
                          class="textarea textarea-sm textarea-bordered w-full text-xs"></textarea>
            </div>

            <div class="modal-action mt-6">
                <button type="button" onclick="document.getElementById('suggest_modal').close()" class="btn btn-sm btn-ghost">Cancel</button>
                <button type="submit" id="submitSuggestBtn" class="btn btn-sm btn-primary gap-1.5">
                    <i class="fa-solid fa-paper-plane text-xs"></i> Submit Feature
                </button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop">
        <button>close</button>
    </form>
</dialog>

<!-- Toast Notification Container -->
<div id="toast_container" class="toast toast-bottom toast-end z-50 p-4"></div>

<!-- JSON Data & Client Scripts -->
<script>
const allItemsData = <?= json_encode(array_column($allItems, null, 'id')) ?>;
let activeCategory = 'all';
let currentSearchQuery = '';
let currentDetailItemId = null;

// Filter by category
function filterRoadmap(cat, btn) {
    activeCategory = cat;
    document.querySelectorAll('.filter-btn').forEach(b => {
        b.classList.remove('btn-active');
        b.classList.add('btn-ghost');
    });
    if (btn) {
        btn.classList.add('btn-active');
        btn.classList.remove('btn-ghost');
    }
    applyFilters();
}

// Search input handler
function handleSearch(val) {
    currentSearchQuery = val.trim().toLowerCase();
    const clearBtn = document.getElementById('clearSearchBtn');
    if (currentSearchQuery.length > 0) {
        clearBtn.classList.remove('hidden');
    } else {
        clearBtn.classList.add('hidden');
    }
    applyFilters();
}

function clearSearch() {
    const input = document.getElementById('roadmapSearch');
    input.value = '';
    currentSearchQuery = '';
    document.getElementById('clearSearchBtn').classList.add('hidden');
    applyFilters();
}

// Master filter logic
function applyFilters() {
    const cards = document.querySelectorAll('.roadmap-card');
    let totalVisible = 0;
    const colCounts = { planned: 0, in_progress: 0, completed: 0 };

    cards.forEach(c => {
        const itemCat = c.dataset.category;
        const title = c.querySelector('.card-title-text')?.textContent.toLowerCase() || '';
        const desc = c.querySelector('.card-desc-text')?.textContent.toLowerCase() || '';
        
        const matchesCategory = (activeCategory === 'all' || itemCat === activeCategory);
        const matchesSearch = (!currentSearchQuery || title.includes(currentSearchQuery) || desc.includes(currentSearchQuery));

        const columnWrapper = c.closest('.column-cards');
        const colKey = columnWrapper?.id.replace('col-', '');

        if (matchesCategory && matchesSearch) {
            c.style.display = 'block';
            totalVisible++;
            if (colKey && colCounts.hasOwnProperty(colKey)) {
                colCounts[colKey]++;
            }
        } else {
            c.style.display = 'none';
        }
    });

    // Update column badge numbers
    ['planned', 'in_progress', 'completed'].forEach(key => {
        const badge = document.getElementById('count-' + key);
        const mobBadge = document.getElementById('mobile-count-' + key);
        const emptyState = document.getElementById('empty-' + key);
        const count = colCounts[key];

        if (badge) badge.textContent = count;
        if (mobBadge) mobBadge.textContent = count;

        if (emptyState) {
            if (count === 0) {
                emptyState.classList.remove('hidden');
            } else {
                emptyState.classList.add('hidden');
            }
        }
    });

    // Global no-results banner
    const globalNoRes = document.getElementById('globalNoResults');
    if (totalVisible === 0) {
        globalNoRes.classList.remove('hidden');
    } else {
        globalNoRes.classList.add('hidden');
    }

    // Notice count
    const notice = document.getElementById('resultsCountNotice');
    if (notice) {
        if (activeCategory === 'all' && !currentSearchQuery) {
            notice.textContent = `Showing all ${totalVisible} items`;
        } else {
            notice.textContent = `Showing ${totalVisible} matching items`;
        }
    }
}

// Mobile Tab Switcher
function switchMobileTab(statusKey, btn) {
    document.querySelectorAll('.mobile-tab').forEach(b => {
        b.classList.remove('bg-base-100', 'shadow-xs', 'text-base-content');
        b.classList.add('text-base-content/60');
    });
    btn.classList.add('bg-base-100', 'shadow-xs');
    btn.classList.remove('text-base-content/60');

    ['planned', 'in_progress', 'completed'].forEach(k => {
        const el = document.getElementById('col-' + k + '-wrapper');
        if (el) {
            if (k === statusKey) {
                el.classList.remove('hidden');
            } else {
                el.classList.add('hidden');
            }
        }
    });
}

// Initial mobile check
function checkMobileLayout() {
    if (window.innerWidth < 768) {
        const activeTab = document.querySelector('.mobile-tab.bg-base-100');
        if (activeTab) {
            const target = activeTab.getAttribute('onclick');
            if (target && target.includes('planned')) {
                switchMobileTab('planned', activeTab);
            }
        }
    } else {
        ['planned', 'in_progress', 'completed'].forEach(k => {
            const el = document.getElementById('col-' + k + '-wrapper');
            if (el) el.classList.remove('hidden');
        });
    }
}
window.addEventListener('resize', checkMobileLayout);
window.addEventListener('DOMContentLoaded', checkMobileLayout);

// Feature Detail Modal
function openDetailModal(itemId) {
    const item = allItemsData[itemId];
    if (!item) return;

    currentDetailItemId = itemId;
    document.getElementById('detail_title').textContent = item.title;
    document.getElementById('detail_description').textContent = item.description || 'No description provided.';

    // Category badge
    const catBadge = document.getElementById('detail_category_badge');
    const catLabels = { gameplay: 'Gameplay', quality_of_life: 'Quality of Life', economy: 'Economy', mobile: 'Mobile' };
    catBadge.textContent = catLabels[item.category] || item.category;
    catBadge.className = 'badge badge-sm font-semibold ' + (
        item.category === 'gameplay' ? 'badge-success' :
        item.category === 'quality_of_life' ? 'badge-info' :
        item.category === 'economy' ? 'badge-warning' : 'badge-secondary'
    );

    // Status badge
    const statusBadge = document.getElementById('detail_status_badge');
    const statusLabels = { planned: 'Planned', in_progress: 'In Progress', completed: 'Shipped' };
    statusBadge.textContent = statusLabels[item.status] || item.status;
    statusBadge.className = 'badge badge-sm font-semibold ' + (
        item.status === 'completed' ? 'badge-success' :
        item.status === 'in_progress' ? 'badge-primary' : 'badge-secondary'
    );

    // Author
    document.getElementById('detail_author_badge').textContent = 'By ' + item.author_name;

    // Vote button
    const cardBtn = document.querySelector(`#card-${itemId} .vote-btn`);
    const isVoted = cardBtn?.classList.contains('btn-primary');
    const voteBtn = document.getElementById('detail_vote_btn');
    const voteCountSpan = document.getElementById('detail_vote_count');
    
    voteCountSpan.textContent = item.upvotes || 0;
    if (isVoted) {
        voteBtn.className = 'btn btn-xs btn-primary text-primary-content font-bold gap-1.5';
    } else {
        voteBtn.className = 'btn btn-xs btn-outline font-bold gap-1.5';
    }

    document.getElementById('detail_modal').showModal();
    history.replaceState(null, null, '#card-' + itemId);
}

function upvoteFromModal() {
    if (!currentDetailItemId) return;
    const cardBtn = document.querySelector(`#card-${currentDetailItemId} .vote-btn`);
    if (cardBtn) {
        upvoteItem(currentDetailItemId, cardBtn, null);
        setTimeout(() => {
            const isVoted = cardBtn.classList.contains('btn-primary');
            const count = cardBtn.querySelector('.vote-count')?.textContent || '0';
            const modalBtn = document.getElementById('detail_vote_btn');
            document.getElementById('detail_vote_count').textContent = count;
            if (isVoted) {
                modalBtn.className = 'btn btn-xs btn-primary text-primary-content font-bold gap-1.5';
            } else {
                modalBtn.className = 'btn btn-xs btn-outline font-bold gap-1.5';
            }
        }, 50);
    }
}

// Copy link to clipboard
function copyCardLink(itemId) {
    const url = window.location.origin + '/roadmap#card-' + itemId;
    navigator.clipboard.writeText(url).then(() => {
        showToast('Link copied to clipboard!');
    }).catch(() => {
        showToast('Direct URL: ' + url);
    });
}

function showToast(msg) {
    const container = document.getElementById('toast_container');
    const toast = document.createElement('div');
    toast.className = 'alert alert-info text-xs py-2 px-3 shadow-lg flex items-center gap-2';
    toast.innerHTML = `<i class="fa-solid fa-circle-check"></i> <span>${msg}</span>`;
    container.appendChild(toast);
    setTimeout(() => { toast.remove(); }, 3000);
}

// Interactive Upvoting
async function upvoteItem(itemId, btn, event) {
    if (event) event.stopPropagation();

    const countSpan = btn.querySelector('.vote-count');
    const prevCount = parseInt(countSpan.textContent) || 0;
    const isVoted = btn.classList.contains('btn-primary');
    const heroVotes = document.getElementById('hero_total_votes');
    const prevTotalVotes = parseInt(heroVotes.textContent.replace(/,/g, '')) || 0;

    // Optimistic UI update
    btn.disabled = true;
    if (isVoted) {
        btn.classList.remove('btn-primary', 'text-primary-content');
        btn.classList.add('btn-outline');
        btn.title = 'Upvote this feature';
        countSpan.textContent = Math.max(0, prevCount - 1);
        if (heroVotes) heroVotes.textContent = Math.max(0, prevTotalVotes - 1).toLocaleString();
    } else {
        btn.classList.add('btn-primary', 'text-primary-content');
        btn.classList.remove('btn-outline');
        btn.title = 'Remove your vote';
        countSpan.textContent = prevCount + 1;
        if (heroVotes) heroVotes.textContent = (prevTotalVotes + 1).toLocaleString();
    }

    try {
        const res = await fetch('/roadmap/vote/' + itemId, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        });
        const data = await res.json();
        if (data.success) {
            countSpan.textContent = data.upvotes;
            if (allItemsData[itemId]) allItemsData[itemId].upvotes = data.upvotes;
            if (heroVotes && data.totalVotes !== undefined) {
                heroVotes.textContent = Number(data.totalVotes).toLocaleString();
            }
            if (data.voted) {
                btn.classList.add('btn-primary', 'text-primary-content');
                btn.classList.remove('btn-outline');
                btn.title = 'Remove your vote';
            } else {
                btn.classList.remove('btn-primary', 'text-primary-content');
                btn.classList.add('btn-outline');
                btn.title = 'Upvote this feature';
            }
        } else {
            // Revert
            countSpan.textContent = prevCount;
            if (heroVotes) heroVotes.textContent = prevTotalVotes.toLocaleString();
        }
    } catch (e) {
        countSpan.textContent = prevCount;
        if (heroVotes) heroVotes.textContent = prevTotalVotes.toLocaleString();
    } finally {
        btn.disabled = false;
    }
}

// Duplicate Suggestion Detector
function handleSuggestTitleInput(val) {
    const trimmed = val.trim().toLowerCase();
    document.getElementById('titleCounter').textContent = val.length + ' / 100';

    const hint = document.getElementById('duplicateHint');
    const hintTitle = document.getElementById('duplicateHintTitle');

    if (trimmed.length < 4) {
        hint.classList.add('hidden');
        return;
    }

    // Check against allItemsData
    let foundMatch = null;
    for (const id in allItemsData) {
        const item = allItemsData[id];
        const existing = item.title.toLowerCase();
        if (existing.includes(trimmed) || trimmed.includes(existing) || (trimmed.length > 7 && existing.includes(trimmed.slice(0, 8)))) {
            foundMatch = item;
            break;
        }
    }

    if (foundMatch) {
        hintTitle.textContent = `"${foundMatch.title}" (${foundMatch.status.replace('_', ' ')})`;
        hint.classList.remove('hidden');
    } else {
        hint.classList.add('hidden');
    }
}

function validateSuggestForm(form) {
    const title = form.title.value.trim();
    const desc = form.description.value.trim();
    if (title.length < 5) {
        alert('Please enter a feature title with at least 5 characters.');
        return false;
    }
    if (desc.length < 10) {
        alert('Please enter a feature description with at least 10 characters.');
        return false;
    }
    document.getElementById('submitSuggestBtn').classList.add('loading');
    return true;
}

// Admin Status Update via AJAX
async function updateItemStatus(itemId, newStatus) {
    try {
        const formData = new FormData();
        formData.append('id', itemId);
        formData.append('status', newStatus);

        const res = await fetch('/roadmap/admin/status', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
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

// Admin Item Deletion via AJAX
async function deleteItemAjax(itemId) {
    if (!confirm('Are you sure you want to permanently delete this roadmap item?')) return;
    try {
        const res = await fetch('/roadmap/delete/' + itemId, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const data = await res.json();
        if (data.success) {
            const card = document.getElementById('card-' + itemId);
            if (card) card.remove();
            applyFilters();
            showToast('Roadmap item deleted.');
        } else {
            alert(data.message || 'Error deleting item');
        }
    } catch (e) {
        location.href = '/roadmap/delete/' + itemId;
    }
}

// Deep linking on page load
window.addEventListener('DOMContentLoaded', () => {
    const hash = window.location.hash;
    if (hash && hash.startsWith('#card-')) {
        const itemId = hash.replace('#card-', '');
        const card = document.getElementById('card-' + itemId);
        if (card) {
            setTimeout(() => {
                card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                openDetailModal(parseInt(itemId));
            }, 300);
        }
    }
});
</script>
<?= $this->endSection() ?>
