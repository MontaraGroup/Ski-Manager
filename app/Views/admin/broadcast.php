<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Broadcast<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-2xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="/admin" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <h1 class="text-2xl font-bold"><i class="fa-solid fa-bullhorn mr-2 text-warning"></i>Broadcast Message</h1>
    </div>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>
    <div class="card bg-base-100 shadow-sm"><div class="card-body">
        <p class="text-sm text-base-content/60 mb-4">Send a message to all players' activity logs. Use for announcements, maintenance notices, or game updates.</p>
        <form action="/admin/broadcast" method="post"><?= csrf_field() ?>
            <textarea name="message" class="textarea textarea-bordered w-full mb-3" rows="3" placeholder="Your message to all players..." required maxlength="255"></textarea>
            <button type="submit" class="btn btn-warning btn-sm gap-1" onclick="return confirm('Send to all players?')"><i class="fa-solid fa-paper-plane"></i>Send Broadcast</button>
        </form>
    </div></div>
</div>
<?= $this->endSection() ?>
