<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Lift Tickets<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="max-w-5xl mx-auto p-4 lg:p-8">

    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle">
            <i class="fa-solid fa-chevron-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold">Lift Tickets & Pricing</h1>
            <p class="text-sm text-base-content/70">Set your ticket prices to attract visitors and maximize revenue</p>
        </div>
    </div>

    <?php if (session('success')) : ?>
        <div class="alert alert-success mb-4" role="status"><span><?= session('success') ?></span></div>
    <?php endif ?>

    <!-- Today's Stats -->
    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm">
            <div class="card-body p-4 text-center">
                <div class="text-xs text-base-content/70">Today's Revenue</div>
                <div class="text-2xl font-bold text-success"><?= currency($todayRevenue) ?></div>
            </div>
        </div>
        <div class="card bg-base-100 shadow-sm">
            <div class="card-body p-4 text-center">
                <div class="text-xs text-base-content/70">Tickets Sold Today</div>
                <div class="text-2xl font-bold"><?= number_format($todayCount) ?></div>
            </div>
        </div>
        <div class="card bg-base-100 shadow-sm">
            <div class="card-body p-4 text-center">
                <div class="text-xs text-base-content/70">Game Day</div>
                <div class="text-2xl font-bold"><?= $gameDay ?></div>
            </div>
        </div>
    </div>

    <!-- Pricing -->
    <div class="card bg-base-100 shadow-sm mb-6">
        <div class="card-body">
            <h2 class="card-title text-base mb-4"><i class="fa-solid fa-ticket mr-2"></i>Ticket Pricing</h2>
            <p class="text-xs text-base-content/70 mb-4">Set prices too high and visitors stay away. Set them too low and you lose profit. Watch your visitor count to find the sweet spot.</p>

            <div class="space-y-3">
                <?php foreach ($tickets as $ticket) : ?>
                <?php $label = $ticketLabels[$ticket['ticket_type']] ?? ['name' => $ticket['ticket_type'], 'icon' => 'fa-solid fa-ticket', 'desc' => '']; ?>
                <form action="/tickets/update" method="post" class="flex flex-col md:flex-row md:items-center gap-3 bg-base-200 rounded-lg p-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $ticket['id'] ?>">

                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <i class="<?= $label['icon'] ?> text-lg text-primary w-6 text-center"></i>
                        <div class="min-w-0">
                            <div class="font-semibold text-sm"><?= $label['name'] ?></div>
                            <div class="text-xs text-base-content/70"><?= $label['desc'] ?></div>
                            <?php if ($v2 && isset(\App\Libraries\Economy::TICKET_REFERENCE[$ticket['ticket_type']])) : ?>
                            <div class="text-xs mt-0.5 text-base-content/70">Normal price <?= currency(\App\Libraries\Economy::TICKET_REFERENCE[$ticket['ticket_type']]) ?> · <?= round(\App\Libraries\Economy::TICKET_MIX[$ticket['ticket_type']] * 100) ?>% of skiers want this ticket · demand at your price <b class="ticket-demand"><?= round(\App\Libraries\Economy::priceDemand($ticket['ticket_type'], (float) $ticket['price']) * 100) ?>%</b></div>
                            <?php endif ?>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-1">
                            <input type="number" name="price" value="<?= $ticket['price'] ?>" min="0" max="9999" class="input input-bordered input-sm w-24 text-right font-mono" <?= $v2 && isset(\App\Libraries\Economy::TICKET_REFERENCE[$ticket['ticket_type']]) ? 'data-ref="' . \App\Libraries\Economy::TICKET_REFERENCE[$ticket['ticket_type']] . '"' : '' ?>>
                            <span class="text-sm"><?= currencySymbol() ?></span>
                        </div>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="active" class="toggle toggle-success toggle-sm" <?= $ticket['active'] ? 'checked' : '' ?>>
                            <span class="text-xs"><?= $ticket['active'] ? 'On Sale' : 'Off' ?></span>
                        </label>

                        <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk"></i></button>
                    </div>
                </form>
                <?php endforeach ?>
            </div>
        </div>
    </div>

    <?php if ($v2) : ?>
    <div class="card bg-base-100 shadow-sm">
        <div class="card-body">
            <h2 class="card-title text-base mb-2"><i class="fa-solid fa-lightbulb mr-2 text-warning"></i>How pricing works</h2>
            <ul class="text-sm space-y-1.5 list-disc pl-5">
                <li>Each skier buys one ticket. Charging more than the normal price scares some of them away: at double the price, about 4 in 10 still come. Charging less brings more, up to 1.5×.</li>
                <li>Revenue usually peaks close to the normal price. Watch the demand figure next to each ticket.</li>
                <li>Multi-day passes earn per day used: a weekly pass covers 6 ski days, a season pass about 25.</li>
                <li>Taking a ticket off sale loses skiers: only half of those who wanted it buy a different one.</li>
                <li>Skiers can't exceed what your open lifts carry each day. When they do, extra demand is lost: build more or bigger lifts.</li>
                <?php if ($lastTick) : ?>
                <li>Last tick: <?= number_format($lastTick['visitors'] ?? 0) ?> skiers, <?= currency((int) round($lastTick['per_visit'] ?? 0)) ?> per skier, prices kept <?= round(($lastTick['price_demand'] ?? 1) * 100) ?>% of demand<?= !empty($lastTick['turned_away']) ? ', ' . number_format($lastTick['turned_away']) . ' turned away by lift capacity' : '' ?>.</li>
                <?php endif ?>
            </ul>
        </div>
    </div>
    <script>
    document.querySelectorAll('input[name="price"][data-ref]').forEach(function (el) {
        el.addEventListener('input', function () {
            var p = parseFloat(el.value) || 0, ref = parseFloat(el.dataset.ref);
            var d = Math.min(<?= \App\Libraries\Economy::MAX_DEMAND ?>, Math.exp(-<?= \App\Libraries\Economy::PRICE_ELASTICITY ?> * (p / ref - 1)));
            var out = el.closest('form').querySelector('.ticket-demand');
            if (out) out.textContent = Math.round(d * 100) + '%';
        });
    });
    </script>
    <?php else : ?>
    <!-- Pricing Tips -->
    <div class="card bg-base-100 shadow-sm">
        <div class="card-body">
            <h2 class="card-title text-base mb-3"><i class="fa-solid fa-lightbulb mr-2 text-warning"></i>Pricing Guide</h2>
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Price Range</th>
                            <th>Visitor Impact</th>
                            <th>Revenue per Visitor</th>
                            <th>Best For</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-success font-semibold">Low (< 30€)</td>
                            <td class="text-success">+++ visitors</td>
                            <td class="text-error">Low</td>
                            <td>New resorts, building reputation</td>
                        </tr>
                        <tr>
                            <td class="text-info font-semibold">Medium (30-60€)</td>
                            <td class="text-info">++ visitors</td>
                            <td class="text-info">Balanced</td>
                            <td>Growing resorts, steady income</td>
                        </tr>
                        <tr>
                            <td class="text-warning font-semibold">High (60-100€)</td>
                            <td class="text-warning">+ visitors</td>
                            <td class="text-success">High</td>
                            <td>Popular resorts with many slopes</td>
                        </tr>
                        <tr>
                            <td class="text-error font-semibold">Premium (100€+)</td>
                            <td class="text-error">Fewer visitors</td>
                            <td class="text-success">Very High</td>
                            <td>Luxury 5-star resorts only</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif ?>

</div>
<?= $this->endSection() ?>
