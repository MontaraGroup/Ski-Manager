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

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>

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
                    <div class="text-center text-sm text-base-content/40 py-12">
                        <i class="fa-solid fa-comments text-3xl mb-2"></i>
                        <p>No messages yet. Send us a message below!</p>
                    </div>
                <?php else : ?>
                    <?php foreach ($messages as $msg) : ?>
                        <?php $isAdmin = $msg['sender'] === 'admin'; ?>
                        <div class="flex <?= $isAdmin ? 'justify-start' : 'justify-end' ?>">
                            <div class="max-w-[80%] <?= $isAdmin ? 'bg-base-200' : 'bg-primary text-primary-content' ?> rounded-2xl px-4 py-2 <?= $isAdmin ? 'rounded-tl-sm' : 'rounded-tr-sm' ?>">
                                <div class="text-sm"><?= nl2br(esc($msg['message'])) ?></div>
                                <div class="text-[10px] <?= $isAdmin ? 'text-base-content/40' : 'text-primary-content/60' ?> mt-1">
                                    <?= $isAdmin ? 'Support' : 'You' ?> · <?= date('M j, g:ia', strtotime($msg['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach ?>
                <?php endif ?>
            </div>

            <div class="border-t border-base-300 p-3">
                <form action="/support/send" method="post" class="flex gap-2"><?= csrf_field() ?>
                    <input type="text" name="message" placeholder="Type your message..." class="input input-bordered input-sm flex-1" maxlength="1000" required autocomplete="off">
                    <button type="submit" class="btn btn-primary btn-sm gap-1"><i class="fa-solid fa-paper-plane"></i> Send</button>
                </form>
            </div>
        </div>
    </div>

    <div class="text-center text-xs text-base-content/40">
        <p>You can also reach us on <a href="https://discord.gg/TyEnFdfd8w" target="_blank" class="link link-primary">Discord</a> or <a href="/contact" class="link link-primary">email</a>.</p>
    </div>
</div>
<script>var c=document.getElementById('chatMessages');if(c)c.scrollTop=c.scrollHeight;</script>
<?= $this->endSection() ?>
