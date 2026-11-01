<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Support<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
    $maxLength = $maxLength ?? 1000;
    $lastId = $messages ? (int) end($messages)['id'] : 0;
    $topics = [
        'bug'      => ['fa-bug', 'Something\'s broken', "What page were you on?\n\nWhat did you do?\n\nWhat happened instead?\n"],
        'account'  => ['fa-user-gear', 'Account or sign-in', "What do you need help with on your account?\n"],
        'feedback' => ['fa-lightbulb', 'Idea or feedback', ''],
    ];
?>
<style>
.sup { max-width: 44rem; margin: 0 auto; padding: 1.75rem 1rem 3rem; }
.sup h1 { font-family: var(--sign); font-size: 1.75rem; font-weight: 700; letter-spacing: -0.025em; line-height: 1.15; margin: 0; }
.sup .muted { color: color-mix(in oklab, var(--color-base-content) 70%, transparent); }
.sup .panel { background: var(--color-base-100); border: 1px solid var(--color-base-300); border-radius: 10px; box-shadow: 0 1px 2px rgba(0,0,0,0.06); overflow: hidden; }
.sup .chat { height: min(60vh, 32rem); overflow-y: auto; padding: 1rem 1.1rem; display: flex; flex-direction: column; gap: 0.6rem; }
.sup .day { align-self: center; font-size: 0.6875rem; padding: 0.15rem 0.6rem; border-radius: 999px; background: var(--color-base-200); color: color-mix(in oklab, var(--color-base-content) 70%, transparent); margin: 0.25rem 0; }
.sup .msg { max-width: 82%; padding: 0.55rem 0.85rem; border-radius: 14px; font-size: 0.9375rem; line-height: 1.5; overflow-wrap: anywhere; }
.sup .msg.me { align-self: flex-end; background: var(--color-primary); color: var(--color-primary-content); border-bottom-right-radius: 4px; }
.sup .msg.team { align-self: flex-start; background: var(--color-base-200); border-bottom-left-radius: 4px; }
.sup .msg small { display: block; font-size: 0.6875rem; margin-top: 0.2rem; opacity: 0.65; }
.sup .empty { margin: auto; text-align: center; max-width: 22rem; }
.sup .topics { display: flex; flex-wrap: wrap; gap: 0.4rem; }
.sup .topic { display: inline-flex; align-items: center; gap: 0.45rem; padding: 0.4rem 0.75rem; border-radius: 999px; border: 1px solid var(--color-base-300); font-size: 0.8125rem; font-weight: 500; background: transparent; color: inherit; cursor: pointer; }
.sup .topic:hover, .sup .topic:focus-visible { border-color: var(--color-primary); color: var(--color-primary); }
.sup .compose { border-top: 1px solid var(--color-base-300); padding: 0.75rem; }
.sup .compose textarea { width: 100%; min-height: 2.75rem; max-height: 10rem; resize: none; }
.sup .hint { display: flex; justify-content: space-between; gap: 1rem; font-size: 0.75rem; margin-top: 0.35rem; }
</style>

<div class="sup">
    <div class="flex items-start justify-between gap-4 mb-5">
        <div>
            <h1>Support</h1>
            <p class="text-sm muted mt-1">Message the Ski Manager team. We read everything, and you'll get a notification when we reply.</p>
        </div>
        <a href="/faq" class="btn btn-ghost btn-sm shrink-0">FAQ</a>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4" role="status"><i class="fa-solid fa-circle-check"></i><span><?= esc(session('success')) ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4" role="alert"><i class="fa-solid fa-circle-exclamation"></i><span><?= esc(session('error')) ?></span></div><?php endif ?>

    <div class="panel">
        <div class="chat" id="chatMessages" aria-live="polite" data-last-id="<?= $lastId ?>">
            <?php if (empty($messages)) : ?>
                <div class="empty" id="chatEmpty">
                    <p class="font-semibold">What can we help with?</p>
                    <p class="text-sm muted mt-1 mb-4">Pick a topic to get started, or just write below.</p>
                    <div class="topics justify-center">
                        <?php foreach ($topics as $__key => [$__icon, $__label, $__tpl]) : ?>
                            <button type="button" class="topic" data-template="<?= esc($__tpl, 'attr') ?>"><i class="fa-solid <?= $__icon ?>" aria-hidden="true"></i><?= esc($__label) ?></button>
                        <?php endforeach ?>
                    </div>
                </div>
            <?php else : ?>
                <?php $lastDate = null; $today = date('Y-m-d'); $yest = date('Y-m-d', strtotime('-1 day')); ?>
                <?php foreach ($messages as $msg) : ?>
                    <?php
                        $msgDate = date('Y-m-d', strtotime($msg['created_at']));
                        if ($msgDate !== $lastDate) {
                            $lastDate = $msgDate;
                            $label = $msgDate === $today ? 'Today' : ($msgDate === $yest ? 'Yesterday' : date('F j, Y', strtotime($msg['created_at'])));
                            echo '<div class="day" data-date="' . esc($msgDate) . '">' . esc($label) . '</div>';
                        }
                        $fromTeam = $msg['sender'] === 'admin';
                    ?>
                    <div class="msg <?= $fromTeam ? 'team' : 'me' ?>">
                        <?= nl2br(esc($msg['message'])) ?>
                        <small><?= $fromTeam ? 'Ski Manager team' : 'You' ?> · <?= date('g:ia', strtotime($msg['created_at'])) ?></small>
                    </div>
                <?php endforeach ?>
            <?php endif ?>
        </div>

        <form action="/support/send" method="post" id="supportForm" class="compose"><?= csrf_field() ?>
            <?php if (!empty($messages)) : ?>
            <div class="topics mb-2">
                <?php foreach ($topics as $__key => [$__icon, $__label, $__tpl]) : if ($__tpl === '') continue; ?>
                    <button type="button" class="topic" data-template="<?= esc($__tpl, 'attr') ?>"><i class="fa-solid <?= $__icon ?>" aria-hidden="true"></i><?= esc($__label) ?></button>
                <?php endforeach ?>
            </div>
            <?php endif ?>
            <label for="supportInput" class="sr-only">Your message</label>
            <div class="flex gap-2 items-end">
                <textarea name="message" id="supportInput" placeholder="Write a message…" class="textarea textarea-bordered" rows="1" maxlength="<?= $maxLength ?>" required><?= esc(old('message') ?? '') ?></textarea>
                <button type="submit" class="btn btn-primary gap-1.5" id="supportSend"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i>Send</button>
            </div>
            <div class="hint muted">
                <span id="sendHint">Shift+Enter for a new line</span>
                <span id="charCount">0/<?= $maxLength ?></span>
            </div>
        </form>
    </div>

    <p class="text-xs muted text-center mt-4">Prefer chat? Find us on <a href="https://discord.gg/u3GzGt8K6a" target="_blank" rel="noopener noreferrer" class="link link-primary">Discord</a>, or use the <a href="/contact" class="link link-primary">contact form</a>.</p>
</div>
<script>
(function () {
    var chat = document.getElementById('chatMessages');
    var form = document.getElementById('supportForm');
    var input = document.getElementById('supportInput');
    var send = document.getElementById('supportSend');
    var count = document.getElementById('charCount');
    var max = parseInt(input.getAttribute('maxlength'), 10);
    // Enter sends only with a real keyboard; on phones Enter adds a new line
    var fineKeyboard = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    if (!fineKeyboard) document.getElementById('sendHint').textContent = '';

    function grow() {
        count.textContent = input.value.length + '/' + max;
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 160) + 'px';
    }
    function scrollDown() { chat.scrollTop = chat.scrollHeight; }
    scrollDown();
    grow();

    input.addEventListener('input', grow);
    input.addEventListener('keydown', function (e) {
        if (fineKeyboard && e.key === 'Enter' && !e.shiftKey && !e.isComposing) {
            e.preventDefault();
            if (input.value.trim()) form.requestSubmit ? form.requestSubmit() : form.submit();
        }
    });
    form.addEventListener('submit', function (e) {
        if (!input.value.trim()) { e.preventDefault(); return; }
        send.disabled = true;
        send.innerHTML = '<span class="loading loading-spinner loading-xs"></span> Sending';
    });

    document.querySelectorAll('.topic').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var tpl = btn.getAttribute('data-template') || '';
            if (tpl && input.value.trim() === '') input.value = tpl;
            input.focus();
            var firstLineEnd = input.value.indexOf('\n');
            input.setSelectionRange(firstLineEnd > -1 ? firstLineEnd + 1 : input.value.length, firstLineEnd > -1 ? firstLineEnd + 1 : input.value.length);
            grow();
        });
    });

    // New replies appear without reloading (only while this tab is visible)
    var lastId = parseInt(chat.dataset.lastId, 10) || 0;
    var lastDay = (Array.prototype.slice.call(chat.querySelectorAll('.day')).pop() || {}).dataset;
    lastDay = lastDay ? lastDay.date : null;
    function addMessage(m) {
        var empty = document.getElementById('chatEmpty');
        if (empty) empty.remove();
        if (m.date !== lastDay) {
            lastDay = m.date;
            var d = document.createElement('div');
            d.className = 'day'; d.textContent = 'Today';
            chat.appendChild(d);
        }
        var el = document.createElement('div');
        el.className = 'msg ' + (m.sender === 'admin' ? 'team' : 'me');
        m.message.split('\n').forEach(function (line, i) {
            if (i) el.appendChild(document.createElement('br'));
            el.appendChild(document.createTextNode(line));
        });
        var meta = document.createElement('small');
        meta.textContent = (m.sender === 'admin' ? 'Ski Manager team' : 'You') + ' · ' + m.time;
        el.appendChild(meta);
        chat.appendChild(el);
    }
    function poll() {
        if (document.hidden) return;
        fetch('/support/messages?after=' + lastId, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.ok ? r.json() : []; })
            .then(function (rows) {
                if (!Array.isArray(rows) || !rows.length) return;
                rows.forEach(function (m) { if (m.id > lastId) { addMessage(m); lastId = m.id; } });
                scrollDown();
            })
            .catch(function () {});
    }
    setInterval(poll, 20000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) poll(); });
})();
</script>
<?= $this->endSection() ?>
