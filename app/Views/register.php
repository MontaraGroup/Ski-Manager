<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Sign Up<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="min-h-[85vh] flex items-center justify-center px-4 py-8">
    <div class="w-full max-w-5xl grid lg:grid-cols-2 rounded-2xl overflow-hidden shadow-2xl">
        <div class="hidden lg:flex flex-col justify-between p-8 bg-gradient-to-br from-primary to-secondary text-primary-content relative overflow-hidden">
            <div class="absolute inset-0 opacity-10">
                <i class="fa-solid fa-person-skiing text-[16rem] absolute -bottom-10 -right-8"></i>
            </div>
            <div class="relative">
                <div class="flex items-center gap-2 text-xl font-extrabold">
                    <i class="fa-solid fa-mountain-sun"></i> Ski Manager
                </div>
                <p class="mt-2 text-sm text-primary-content/80">Your mountain. Your rules.</p>
            </div>
            <div class="relative space-y-3 mt-8">
                <div class="flex items-center gap-3 text-sm"><i class="fa-solid fa-gift w-5"></i> Start with cash to build your first runs</div>
                <div class="flex items-center gap-3 text-sm"><i class="fa-solid fa-users w-5"></i> Hire staff, set prices, earn year-round</div>
                <div class="flex items-center gap-3 text-sm"><i class="fa-solid fa-snowflake w-5"></i> Live weather drives every season</div>
            </div>
            <p class="relative text-xs text-primary-content/60 mt-8">Free forever. Takes 30 seconds to start.</p>
        </div>
        <div class="bg-base-100 p-8 lg:p-10">
            <div class="max-w-md mx-auto">
                <div class="lg:hidden flex items-center justify-center gap-2 text-lg font-extrabold text-primary mb-4">
                    <i class="fa-solid fa-mountain-sun"></i> Ski Manager
                </div>
                <h2 class="text-2xl font-bold mb-1">Create your account</h2>
                <p class="text-sm text-base-content/50 mb-6">Build your resort in minutes.</p>
                <?php if (session('error')) : ?>
                    <div class="alert alert-error mb-4" role="alert"><span><?= session('error') ?></span></div>
                <?php endif ?>
                <?php if (session('errors')) : ?>
                    <div class="alert alert-error mb-4" role="alert">
                        <div><?php foreach (session('errors') as $error) : ?><p><?= $error ?></p><?php endforeach ?></div>
                    </div>
                <?php endif ?>
                <form action="<?= url_to('register') ?>" method="post" class="space-y-4">
                    <?= csrf_field() ?>
                    <div class="form-control">
                        <label class="label py-1" for="username"><span class="label-text font-medium">Username</span></label>
                        <input type="text" name="username" id="username" class="input input-bordered w-full" placeholder="Choose a username" autocomplete="username" value="<?= old('username') ?>" required autofocus aria-required="true">
                    </div>
                    <div class="form-control">
                        <label class="label py-1" for="email"><span class="label-text font-medium">Email</span></label>
                        <input type="email" name="email" id="email" class="input input-bordered w-full" placeholder="you@example.com" autocomplete="email" value="<?= old('email') ?>" required>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="form-control">
                            <label class="label py-1" for="password"><span class="label-text font-medium">Password</span></label>
                            <input type="password" name="password" id="password" class="input input-bordered w-full" placeholder="••••••••" autocomplete="new-password" required>
                        </div>
                        <div class="form-control">
                            <label class="label py-1" for="password_confirm"><span class="label-text font-medium">Confirm</span></label>
                            <input type="password" name="password_confirm" id="password_confirm" class="input input-bordered w-full" placeholder="••••••••" autocomplete="new-password" required>
                        </div>
                    </div>
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text font-medium">Game Difficulty</span></label>
                        <div class="grid grid-cols-3 gap-2">
                            <label class="cursor-pointer">
                                <input type="radio" name="difficulty" value="easy" class="peer hidden">
                                <div class="border-2 border-base-300 rounded-lg p-3 text-center peer-checked:border-success peer-checked:bg-success/10 transition-colors">
                                    <i class="fa-solid fa-face-smile text-success text-lg"></i>
                                    <div class="text-xs font-bold mt-1">Easy</div>
                                    <div class="text-[10px] text-base-content/50">More cash</div>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="difficulty" value="standard" class="peer hidden" checked>
                                <div class="border-2 border-base-300 rounded-lg p-3 text-center peer-checked:border-info peer-checked:bg-info/10 transition-colors">
                                    <i class="fa-solid fa-face-meh text-info text-lg"></i>
                                    <div class="text-xs font-bold mt-1">Standard</div>
                                    <div class="text-[10px] text-base-content/50">Balanced</div>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="difficulty" value="hard" class="peer hidden">
                                <div class="border-2 border-base-300 rounded-lg p-3 text-center peer-checked:border-error peer-checked:bg-error/10 transition-colors">
                                    <i class="fa-solid fa-skull text-error text-lg"></i>
                                    <div class="text-xs font-bold mt-1">Hard</div>
                                    <div class="text-[10px] text-base-content/50">Punishing</div>
                                </div>
                            </label>
                        </div>
                    </div>
                    <label class="cursor-pointer flex items-start gap-2 pt-1">
                        <input type="checkbox" name="terms" class="checkbox checkbox-primary checkbox-sm mt-0.5" required oninvalid="this.setCustomValidity('You must accept the Terms of Service to create an account')" onchange="this.setCustomValidity('')">
                        <span class="label-text text-sm">I agree to the <a href="/terms" target="_blank" rel="noopener noreferrer" class="link link-primary">Terms of Service</a> and <a href="/privacy" target="_blank" rel="noopener noreferrer" class="link link-primary">Privacy Policy</a></span>
                    </label>
                    <button type="submit" class="btn btn-primary w-full">Create Account</button>
                </form>
                <div class="divider text-xs text-base-content/40">OR</div>
                <div class="space-y-2">
                    <a href="/auth/google" class="btn btn-outline w-full gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" class="w-5 h-5"><path fill="#FFC107" d="M43.6 20.1H42V20H24v8h11.3C33.9 33.1 29.4 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 8 3l5.7-5.7C34 6 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.2-2.7-.4-3.9z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.3 15.7 18.8 13 24 13c3.1 0 5.8 1.2 8 3l5.7-5.7C34 6 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.2 26.7 36 24 36c-5.4 0-9.9-3.5-11.5-8.3l-6.5 5C9.5 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.1H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C36.7 39.5 44 34 44 24c0-1.3-.2-2.7-.4-3.9z"/></svg>
                        Sign up with Google
                    </a>
                    <a href="/auth/discord" class="btn btn-outline w-full gap-2">
                        <i class="fa-brands fa-discord text-lg text-[#5865F2]"></i> Sign up with Discord
                    </a>
                </div>
                <p class="text-center text-sm text-base-content/60 mt-6">
                    Already have an account? <a href="<?= url_to('login') ?>" class="link link-primary font-medium">Log in</a>
                </p>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
