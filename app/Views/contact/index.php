<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Contact<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-3xl mx-auto p-4 lg:p-8">

    <div class="flex items-center gap-3 mb-6">
        <a href="/" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <h1 class="text-2xl font-bold"><i class="fa-solid fa-envelope mr-2 text-primary"></i>Contact Us</h1>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4" role="status"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4" role="alert"><span><?= session('error') ?></span></div><?php endif ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- Contact Form -->
        <div class="md:col-span-2">
            <div class="card bg-base-100 shadow-sm"><div class="card-body">
                <h2 class="font-bold text-lg mb-4">Send a Message</h2>
                <form action="/contact" method="post">
                    <?= csrf_field() ?>
                    <div class="form-control mb-4">
                        <label class="label"><span class="label-text">Name *</span></label>
                        <input type="text" name="name" class="input input-bordered w-full" value="<?= old('name') ?? (auth()->loggedIn() ? auth()->user()->username : '') ?>" required aria-required="true" autocomplete="name">
                    </div>
                    <div class="form-control mb-4">
                        <label class="label"><span class="label-text">Email *</span></label>
                        <input type="email" name="email" class="input input-bordered w-full" value="<?= old('email') ?>" required aria-required="true" autocomplete="email">
                    </div>
                    <div class="form-control mb-4">
                        <label class="label"><span class="label-text">Subject</span></label>
                        <select name="subject" class="select select-bordered w-full">
                            <option value="general">General Question</option>
                            <option value="bug">Bug Report</option>
                            <option value="feature">Feature Suggestion</option>
                            <option value="account">Account Issue</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-control mb-4">
                        <label class="label"><span class="label-text">Message *</span></label>
                        <textarea name="message" class="textarea textarea-bordered w-full" rows="5" required><?= old('message') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane mr-1"></i>Send Message</button>
                </form>
            </div></div>
        </div>

        <!-- Sidebar -->
        <div>
            <div class="card bg-base-100 shadow-sm mb-4"><div class="card-body p-4">
                <h3 class="font-bold text-sm mb-3">Other Ways to Reach Us</h3>
                <div class="space-y-3">
                    <a href="https://discord.gg/TyEnFdfd8w" target="_blank" rel="noopener noreferrer" class="flex items-center gap-3 hover:text-primary transition-colors">
                        <i class="fa-brands fa-discord text-lg w-6 text-center"></i>
                        <div><div class="text-sm font-semibold">Discord</div><div class="text-xs text-base-content/50">Fastest way to get help</div></div>
                    </a>
                    <a href="https://gitlab.com/contact1231/skimanager-v2/-/issues" target="_blank" rel="noopener noreferrer" class="flex items-center gap-3 hover:text-primary transition-colors">
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
                <h3 class="font-bold text-sm mb-3">FAQ</h3>
                <div class="space-y-2 text-xs text-base-content/60">
                    <p><strong>Response time?</strong> Usually within 48 hours.</p>
                    <p><strong>Found a bug?</strong> Please use GitLab issues for faster tracking.</p>
                    <p><strong>Feature request?</strong> We love suggestions! Use the form or GitLab.</p>
                </div>
            </div></div>
        </div>

    </div>
</div>
<?= $this->endSection() ?>
