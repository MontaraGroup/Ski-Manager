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
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>

    <div class="card bg-base-100 shadow-sm mb-4"><div class="card-body p-0">
        <div class="p-4 min-h-[400px] max-h-[600px] overflow-y-auto flex flex-col gap-3" id="chatMessages">
            <?php if (empty($messages)) : ?>
                <div class="text-center text-sm text-base-content/40 py-12 m-auto">
                    <i class="fa-solid fa-comments text-3xl mb-2"></i><p>No messages in this conversation.</p>
                </div>
            <?php else : ?>
                <?php $lastDate = null; ?>
                <?php foreach ($messages as $msg) : ?>
                    <?php
                        $msgDate = date('Y-m-d', strtotime($msg['created_at']));
                        if ($msgDate !== $lastDate) {
                            $lastDate = $msgDate;
                            $today = date('Y-m-d'); $yest = date('Y-m-d', strtotime('-1 day'));
                            $label = $msgDate === $today ? 'Today' : ($msgDate === $yest ? 'Yesterday' : date('F j, Y', strtotime($msg['created_at'])));
                            echo '<div class="text-center my-1"><span class="text-[10px] uppercase tracking-wide text-base-content/40 bg-base-200 rounded-full px-3 py-0.5">' . esc($label) . '</span></div>';
                        }
                        $isAdmin = $msg['sender'] === 'admin';
                    ?>
                    <div class="flex <?= $isAdmin ? 'justify-end' : 'justify-start' ?>">
                        <div class="max-w-[80%] <?= $isAdmin ? 'bg-primary text-primary-content' : 'bg-base-200' ?> rounded-2xl px-4 py-2 <?= $isAdmin ? 'rounded-tr-sm' : 'rounded-tl-sm' ?>">
                            <div class="text-sm break-words"><?= nl2br(esc($msg['message'])) ?></div>
                            <div class="text-[10px] <?= $isAdmin ? 'text-primary-content/60' : 'text-base-content/40' ?> mt-1 flex items-center gap-1 <?= $isAdmin ? 'justify-end' : '' ?>">
                                <span><?= $isAdmin ? 'You' : esc($player['username'] ?? 'Player') ?> · <?= date('g:ia', strtotime($msg['created_at'])) ?></span>
                                <?php if (!$isAdmin) : ?>
                                    <?php if ($msg['is_read']) : ?><i class="fa-solid fa-check-double text-info text-[8px]" title="Read"></i><?php else : ?><i class="fa-solid fa-check text-base-content/30 text-[8px]" title="Delivered"></i><?php endif ?>
                                <?php else : ?>
                                    <?php if ($msg['is_read']) : ?><i class="fa-solid fa-check-double text-primary-content/80 text-[8px]" title="Seen by player"></i><?php else : ?><i class="fa-solid fa-check text-primary-content/40 text-[8px]" title="Sent"></i><?php endif ?>
                                <?php endif ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach ?>
            <?php endif ?>
        </div>
        <div class="border-t border-base-300 p-3">
            <form action="/admin/support/<?= $chatUserId ?>/reply" method="post" id="replyForm"><?= csrf_field() ?>
                <div class="flex gap-2 items-end">
                    <textarea name="message" id="replyInput" placeholder="Reply..." class="textarea textarea-bordered textarea-sm flex-1 resize-none" rows="1" maxlength="2000" required autocomplete="off"></textarea>
                    <button type="submit" class="btn btn-primary btn-sm gap-1"><i class="fa-solid fa-paper-plane"></i> Reply</button>
                </div>
                <div class="text-[10px] text-base-content/40 mt-1">Enter to send · Shift+Enter for new line</div>
            </form>
        </div>
    </div></div>
</div>
<script>
(function(){
    var c = document.getElementById('chatMessages'); if (c) c.scrollTop = c.scrollHeight;
    var input = document.getElementById('replyInput'); var form = document.getElementById('replyForm');
    if (input) {
        input.addEventListener('input', function(){ this.style.height='auto'; this.style.height=Math.min(this.scrollHeight,120)+'px'; });
        input.addEventListener('keydown', function(e){ if(e.key==='Enter'&&!e.shiftKey){e.preventDefault(); if(this.value.trim().length>0) form.submit();} });
    }
})();
</script>
<?= $this->endSection() ?>
