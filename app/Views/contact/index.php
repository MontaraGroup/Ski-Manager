<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Contact<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-3xl mx-auto p-4 lg:p-8">

    <div class="flex items-center gap-3 mb-6">
        <a href="/" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-envelope mr-2 text-primary"></i>Contact Us</h1>
            <p class="text-sm text-base-content/50">Questions, bugs, ideas - we'd love to hear from you</p>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4" role="status"><i class="fa-solid fa-circle-check"></i><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4" role="alert"><i class="fa-solid fa-circle-exclamation"></i><span><?= session('error') ?></span></div><?php endif ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <div class="md:col-span-2">
            <div class="card bg-base-100 shadow-sm"><div class="card-body">
                <h2 class="font-bold text-lg mb-4">Send a Message</h2>
                <form action="/contact" method="post" class="space-y-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="form_time" value="<?= time() ?>">
                    <div aria-hidden="true" style="position:absolute;left:-9999px;top:-9999px;"><label>Website (leave blank)<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                    <div class="form-control">
                        <label class="label py-1" for="name"><span class="label-text font-medium">Name *</span></label>
                        <input type="text" name="name" id="name" class="input input-bordered w-full" value="<?= old('name') ?? (auth()->loggedIn() ? auth()->user()->username : '') ?>" required aria-required="true" autocomplete="name">
                    </div>
                    <div class="form-control">
                        <label class="label py-1" for="email"><span class="label-text font-medium">Email *</span></label>
                        <input type="email" name="email" id="email" class="input input-bordered w-full" value="<?= old('email') ?? (auth()->loggedIn() ? (auth()->user()->email ?? '') : '') ?>" required aria-required="true" autocomplete="email">
                    </div>
                    <div class="form-control">
                        <label for="subject" class="label py-1"><span class="label-text font-medium">Subject</span></label>
                        <?php $__subj = old('subject') ?? 'general'; ?>
                        <select name="subject" id="subject" class="select select-bordered w-full">
                            <option value="general" <?= $__subj === 'general' ? 'selected' : '' ?>>General Question</option>
                            <option value="bug" <?= $__subj === 'bug' ? 'selected' : '' ?>>Bug Report</option>
                            <option value="feature" <?= $__subj === 'feature' ? 'selected' : '' ?>>Feature Suggestion</option>
                            <option value="account" <?= $__subj === 'account' ? 'selected' : '' ?>>Account Issue</option>
                            <option value="other" <?= $__subj === 'other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-control">
                        <label for="message" class="label py-1"><span class="label-text font-medium">Message *</span></label>
                        <textarea name="message" id="message" class="textarea textarea-bordered w-full" rows="5" required><?= old('message') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary gap-1"><i class="fa-solid fa-paper-plane"></i> Send Message</button>
                </form>
            </div></div>
        </div>

        <div>
            <div class="card bg-base-100 shadow-sm mb-4"><div class="card-body p-4">
                <h3 class="font-bold text-sm mb-3">Other Ways to Reach Us</h3>
                <div class="space-y-3">
                    <a href="https://discord.gg/TyEnFdfd8w" target="_blank" rel="noopener noreferrer" class="flex items-center gap-3 hover:text-primary transition-colors">
                        <i class="fa-brands fa-discord text-lg w-6 text-center text-[#5865F2]"></i>
                        <div><div class="text-sm font-semibold">Discord</div><div class="text-xs text-base-content/50">Fastest way to get help</div></div>
                    </a>
                    <a href="/support" class="flex items-center gap-3 hover:text-primary transition-colors">
                        <i class="fa-solid fa-headset text-lg w-6 text-center"></i>
                        <div><div class="text-sm font-semibold">In-Game Support</div><div class="text-xs text-base-content/50">Message us from your account</div></div>
                    </a>
                    <a href="/bugs" target="_blank" rel="noopener noreferrer" class="flex items-center gap-3 hover:text-primary transition-colors">
                        <i class="fa-solid fa-bug text-lg w-6 text-center"></i>
                        <div><div class="text-sm font-semibold">Report a Bug</div><div class="text-xs text-base-content/50">GitLab issue tracker</div></div>
                    </a>
                    <a href="https://gitlab.com/contact1231/skimanager-v2" target="_blank" rel="noopener noreferrer" class="flex items-center gap-3 hover:text-primary transition-colors">
                        <i class="fa-brands fa-gitlab text-lg w-6 text-center"></i>
                        <div><div class="text-sm font-semibold">Contribute</div><div class="text-xs text-base-content/50">Open source on GitLab</div></div>
                    </a>
                </div>
            </div></div>

            <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
                <h3 class="font-bold text-sm mb-3">Good to Know</h3>
                <div class="space-y-2 text-xs text-base-content/60">
                    <p><strong>Response time?</strong> Usually within 48 hours.</p>
                    <p><strong>Found a bug?</strong> GitLab issues get tracked fastest.</p>
                    <p><strong>Feature idea?</strong> We love suggestions - use the form or GitLab.</p>
                    <p><strong>Need a quick answer?</strong> Check the <a href="/faq" class="link link-primary">FAQ</a> first.</p>
                </div>
            </div></div>
        </div>

    </div>
</div>
<?= $this->endSection() ?>
