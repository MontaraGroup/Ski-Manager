<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Broadcast<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-2xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="/admin" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <h1 class="text-2xl font-bold"><i class="fa-solid fa-bullhorn mr-2 text-warning"></i>Broadcast Server Announcement</h1>
    </div>
    
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>
    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>

    <div class="card bg-base-100 shadow-sm"><div class="card-body">
        <p class="text-sm text-base-content/60 mb-4">Send a push flash notification to all players currently active in-game. This saves to their permanent log files.</p>
        
        <form action="/admin/sendBroadcast" method="post"><?= csrf_field() ?>
            <div class="grid grid-cols-2 gap-4 mb-3">
                <div>
                    <label class="label text-xs font-bold uppercase tracking-wider">Alert Type / Theme</label>
                    <select name="type" class="select select-bordered select-sm w-full">
                        <option value="info">Info (Blue)</option>
                        <option value="warning">Warning (Yellow)</option>
                        <option value="success">Success (Green)</option>
                        <option value="error">Emergency (Red)</option>
                    </select>
                </div>
                <div>
                    <label class="label text-xs font-bold uppercase tracking-wider">Display Icon</label>
                    <select name="icon" class="select select-bordered select-sm w-full">
                        <option value="fa-solid fa-bullhorn">📢 Announcement</option>
                        <option value="fa-solid fa-snowflake">❄️ Weather / Snow</option>
                        <option value="fa-solid fa-trophy">🏆 Event / Tournament</option>
                        <option value="fa-solid fa-triangle-exclamation">⚠️ Hazard / Notice</option>
                        <option value="fa-solid fa-wrench">🔧 System Maintenance</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="label text-xs font-bold uppercase tracking-wider">Announcement Content</label>
                <textarea name="message" class="textarea textarea-bordered w-full" rows="3" placeholder="Type what you want all active players to see live..." required maxlength="255"></textarea>
            </div>

            <button type="submit" class="btn btn-warning btn-sm gap-1" onclick="return confirm('Send this flash broadcast to all active resort profiles?')">
                <i class="fa-solid fa-paper-plane"></i> Dispatch Global Broadcast
            </button>
        </form>
    </div></div>
</div>
<?= $this->endSection() ?>
