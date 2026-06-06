<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Admin Audit Log<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-5xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="/admin" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <h1 class="text-2xl font-bold"><i class="fa-solid fa-shield-halved mr-2"></i>Admin Audit Log</h1>
    </div>
    <div class="card bg-base-100 shadow-sm"><div class="card-body p-0"><div class="overflow-x-auto">
        <table class="table table-sm">
            <thead><tr><th>Time</th><th>Admin</th><th>Action</th><th>Target</th><th>Details</th></tr></thead>
            <tbody>
            <?php foreach ($logs as $l) : ?>
                <tr>
                    <td class="text-xs"><?= date('M j H:i', strtotime($l['created_at'])) ?></td>
                    <td class="text-xs font-semibold"><?= esc($l['admin_name'] ?? '') ?></td>
                    <td><span class="badge badge-sm badge-outline"><?= esc($l['action']) ?></span></td>
                    <td class="text-xs"><?= $l['target_name'] ? esc($l['target_name']) . ' (#' . $l['target_user_id'] . ')' : '-' ?></td>
                    <td class="text-xs text-base-content/60 max-w-xs truncate"><?= esc($l['details'] ?? '') ?></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div></div></div>
</div>
<?= $this->endSection() ?>
