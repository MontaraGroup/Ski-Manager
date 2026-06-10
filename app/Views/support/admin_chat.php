<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Chat with <?= esc($player['username'] ?? '') ?><?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-2xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="/admin/support" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-headset mr-2 text-primary"></i><?= esc($player['username'] ?? 'Player') ?></h1>
            <p class="text-sm text-base-content/50">User #<?= $chatUserId ?></p>
        </div>
        <a href="/admin/user/<?= $chatUserId ?>" class="btn btn-ghost btn-sm ml-auto gap-1"><i class="fa-solid fa-user"></i> Profile</a>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>

    <div class="card bg-base-100 shadow-sm mb-4"><div class="card-body p-0">
        <div class="p-4 min-h-[400px] max-h-[600px] overflow-y-auto flex flex-col gap-3" id="chatMessages">
            <?php foreach ($messages as $msg) : ?>
                <?php $isAdmin = $msg['sender'] === 'admin'; ?>
                <div class="flex <?= $isAdmin ? 'justify-end' : 'justify-start' ?>">
                    <div class="max-w-[80%] <?= $isAdmin ? 'bg-primary text-primary-content' : 'bg-base-200' ?> rounded-2xl px-4 py-2 <?= $isAdmin ? 'rounded-tr-sm' : 'rounded-tl-sm' ?>">
                        <div class="text-sm"><?= nl2br(esc($msg['message'])) ?></div>
                        <div class="text-[10px] <?= $isAdmin ? 'text-primary-content/60' : 'text-base-content/40' ?> mt-1 flex items-center gap-1 <?= $isAdmin ? 'justify-end' : '' ?>">
                            <span><?= $isAdmin ? 'You' : esc($player['username'] ?? 'Player') ?> · <?= date('M j, g:ia', strtotime($msg['created_at'])) ?></span>
                            <?php if (!$isAdmin) : ?>
                                <?php if ($msg['is_read']) : ?>
                                    <i class="fa-solid fa-check-double text-info text-[8px]" title="Read"></i>
                                <?php else : ?>
                                    <i class="fa-solid fa-check text-base-content/30 text-[8px]" title="Delivered"></i>
                                <?php endif ?>
                            <?php endif ?>
                            <?php if ($isAdmin) : ?>
                                <?php if ($msg['is_read']) : ?>
                                    <i class="fa-solid fa-check-double text-primary-content/80 text-[8px]" title="Seen by player"></i>
                                <?php else : ?>
                                    <i class="fa-solid fa-check text-primary-content/40 text-[8px]" title="Sent"></i>
                                <?php endif ?>
                            <?php endif ?>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
        <div class="border-t border-base-300 p-3">
            <form action="/admin/support/<?= $chatUserId ?>/reply" method="post" class="flex gap-2"><?= csrf_field() ?>
                <input type="text" name="message" placeholder="Reply..." class="input input-bordered input-sm flex-1" required autocomplete="off">
                <button type="submit" class="btn btn-primary btn-sm gap-1"><i class="fa-solid fa-paper-plane"></i> Reply</button>
            </form>
        </div>
    </div></div>
</div>
<script>var c=document.getElementById('chatMessages');if(c)c.scrollTop=c.scrollHeight;</script>
<?= $this->endSection() ?>
