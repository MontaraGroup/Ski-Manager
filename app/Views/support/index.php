<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Support<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-2xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-headset mr-2 text-primary"></i>Support</h1>
            <p class="text-sm text-base-content/50">Chat with the Ski Manager team</p>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4" role="status"><i class="fa-solid fa-circle-check"></i><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4" role="alert"><i class="fa-solid fa-circle-exclamation"></i><span><?= session('error') ?></span></div><?php endif ?>

    <div class="card bg-base-100 shadow-sm mb-4">
        <div class="card-body p-0">
            <div class="bg-base-200 p-3 rounded-t-2xl border-b border-base-300">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center">
                        <i class="fa-solid fa-headset text-primary text-sm"></i>
                    </div>
                    <div>
                        <div class="text-sm font-semibold">Ski Manager Support</div>
                        <div class="text-xs text-base-content/50">Usually replies within a few hours</div>
                    </div>
                </div>
            </div>

            <div class="p-4 min-h-[300px] max-h-[500px] overflow-y-auto flex flex-col gap-3" id="chatMessages">
                <?php if (empty($messages)) : ?>
                    <div class="text-center text-sm text-base-content/40 py-12 m-auto">
                        <i class="fa-solid fa-comments text-4xl mb-3"></i>
                        <p class="font-semibold text-base-content/60">No messages yet</p>
                        <p class="mt-1">Send us a message below and we'll get back to you.</p>
                    </div>
                <?php else : ?>
                    <?php $lastDate = null; ?>
                    <?php foreach ($messages as $msg) : ?>
                        <?php
                            $msgDate = date('Y-m-d', strtotime($msg['created_at']));
                            if ($msgDate !== $lastDate) {
                                $lastDate = $msgDate;
                                $today = date('Y-m-d');
                                $yest = date('Y-m-d', strtotime('-1 day'));
                                $label = $msgDate === $today ? 'Today' : ($msgDate === $yest ? 'Yesterday' : date('F j, Y', strtotime($msg['created_at'])));
                                echo '<div class="text-center my-1"><span class="text-[10px] uppercase tracking-wide text-base-content/40 bg-base-200 rounded-full px-3 py-0.5">' . esc($label) . '</span></div>';
                            }
                            $isAdmin = $msg['sender'] === 'admin';
                        ?>
                        <div class="flex <?= $isAdmin ? 'justify-start' : 'justify-end' ?>">
                            <div class="max-w-[80%] <?= $isAdmin ? 'bg-base-200' : 'bg-primary text-primary-content' ?> rounded-2xl px-4 py-2 <?= $isAdmin ? 'rounded-tl-sm' : 'rounded-tr-sm' ?>">
                                <div class="text-sm break-words"><?= nl2br(esc($msg['message'])) ?></div>
                                <div class="text-[10px] <?= $isAdmin ? 'text-base-content/40' : 'text-primary-content/60' ?> mt-1">
                                    <?= $isAdmin ? 'Support' : 'You' ?> · <?= date('g:ia', strtotime($msg['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach ?>
                <?php endif ?>
            </div>

            <div class="border-t border-base-300 p-3">
                <form action="/support/send" method="post" id="supportForm"><?= csrf_field() ?>
                    <div class="flex gap-2 items-end">
                        <textarea name="message" id="supportInput" placeholder="Type your message..." class="textarea textarea-bordered textarea-sm flex-1 resize-none" rows="1" maxlength="1000" required autocomplete="off"></textarea>
                        <button type="submit" class="btn btn-primary btn-sm gap-1"><i class="fa-solid fa-paper-plane"></i> Send</button>
                    </div>
                    <div class="text-[10px] text-base-content/40 mt-1 flex justify-between">
                        <span>Enter to send · Shift+Enter for new line</span>
                        <span id="charCount">0/1000</span>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="text-center text-xs text-base-content/40">
        <p>You can also reach us on <a href="https://discord.gg/TyEnFdfd8w" target="_blank" rel="noopener noreferrer" class="link link-primary">Discord</a> or <a href="/contact" class="link link-primary">email</a>.</p>
    </div>
</div>
<script>
(function(){
    var c = document.getElementById('chatMessages');
    if (c) c.scrollTop = c.scrollHeight;
    var input = document.getElementById('supportInput');
    var form = document.getElementById('supportForm');
    var count = document.getElementById('charCount');
    if (input) {
        input.addEventListener('input', function(){
            count.textContent = this.value.length + '/1000';
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });
        input.addEventListener('keydown', function(e){
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                if (this.value.trim().length > 0) form.submit();
            }
        });
    }
})();
</script>
<?= $this->endSection() ?>
