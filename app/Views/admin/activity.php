<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-clock-rotate-left mr-2 text-primary"></i>Activity Log</h1>
            <p class="text-sm text-base-content/50">Global real-time tracking for all player modules and backend cron automation.</p>
        </div>
    </div>

    <div class="overflow-x-auto bg-base-100 rounded-xl shadow-sm border border-base-300">
        <table class="table table-zebra w-full text-sm">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Player</th>
                    <th>Day</th>
                    <th>Category</th>
                    <th>Message</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($logs)): ?>
                    <?php foreach ($logs as $log): 
                        // Safely support both object and array return types
                        $row = (object) $log; 
                    ?>
                        <tr>
                            <td class="whitespace-nowrap opacity-60"><?= date('M d H:i', strtotime($row->created_at)) ?></td>
                            <td class="font-bold text-primary"><?= esc($row->username) ?></td>
                            <td><span class="badge badge-ghost badge-sm font-mono">D<?= esc($row->game_day) ?></span></td>
                            <td><span class="badge badge-outline badge-sm"><?= esc($row->category) ?></span></td>
                            <td class="max-w-md truncate" title="<?= esc($row->message) ?>"><?= esc($row->message) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center py-8 text-base-content/40">No activities recorded yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
