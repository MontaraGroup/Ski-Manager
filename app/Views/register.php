<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Sign Up<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="min-h-[80vh] flex items-center justify-center px-4">
    <div class="card bg-base-100 shadow-xl w-full max-w-md">
        <div class="card-body">
            <h2 class="card-title text-2xl font-bold justify-center mb-4">Create Your Account</h2>

            <?php if (session('error')) : ?>
                <div class="alert alert-error mb-4" role="alert">
                    <span><?= session('error') ?></span>
                </div>
            <?php endif ?>

            <?php if (session('errors')) : ?>
                <div class="alert alert-error mb-4" role="alert">
                    <div>
                        <?php foreach (session('errors') as $error) : ?>
                            <p><?= $error ?></p>
                        <?php endforeach ?>
                    </div>
                </div>
            <?php endif ?>

            <form action="<?= url_to('register') ?>" method="post">
                <?= csrf_field() ?>

                <div class="form-control mb-4">
                    <label class="label" for="username">
                        <span class="label-text">Username</span>
                    </label>
                    <input type="text" name="username" id="username" class="input input-bordered w-full" placeholder="Choose a username" autocomplete="username" value="<?= old('username') ?>" required autofocus aria-required="true">
                </div>

                <div class="form-control mb-4">
                    <label class="label" for="email">
                        <span class="label-text">Email</span>
                    </label>
                    <input type="email" name="email" id="email" class="input input-bordered w-full" placeholder="you@example.com" autocomplete="email" value="<?= old('email') ?>" required>
                </div>

                <div class="form-control mb-4">
                    <label class="label" for="password">
                        <span class="label-text">Password</span>
                    </label>
                    <input type="password" name="password" id="password" class="input input-bordered w-full" placeholder="••••••••" autocomplete="new-password" required>
                </div>

                <div class="form-control mb-4">
                    <label class="label" for="password_confirm">
                        <span class="label-text">Confirm Password</span>
                    </label>
                    <input type="password" name="password_confirm" id="password_confirm" class="input input-bordered w-full" placeholder="••••••••" autocomplete="new-password" required>
                </div>

                <div class="form-control mt-6">

                
                <div class="form-control mb-4">
                    <label for="difficulty" class="label"><span class="label-text">Game Difficulty</span></label>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="cursor-pointer">
                            <input type="radio" name="difficulty" id="difficulty" value="easy" class="peer hidden">
                            <div class="border-2 border-base-300 rounded-lg p-3 text-center peer-checked:border-success peer-checked:bg-success/10 transition-colors">
                                <i class="fa-solid fa-face-smile text-success text-lg"></i>
                                <div class="text-xs font-bold mt-1">Easy</div>
                                <div class="text-[10px] text-base-content/50">More cash, simpler</div>
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
                                <div class="text-[10px] text-base-content/50">Less cash, punishing</div>
                            </div>
                        </label>
                    </div>
                </div>
                <div class="form-control mb-4">
                    <label class="label"><span class="label-text">Choose Your Resort</span></label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        <?php $maps = ['ParkCity' => 'Park City, UT', 'Vail' => 'Vail, CO', 'AspenSnowmass' => 'Aspen, CO', 'DeerValley' => 'Deer Valley, UT', 'Killington' => 'Killington, VT', 'BigSkyCombo' => 'Big Sky, MT', 'PalisadesTahoe' => 'Palisades, CA']; ?>
                        <?php foreach ($maps as $key => $loc) : ?>
                        <?php $enabled = ($key === 'ParkCity'); ?>
                        <?php $enabled = ($key === 'ParkCity'); ?>
                        <label class="<?= $enabled ? 'cursor-pointer' : '' ?>">
                            <div class="border-2 rounded-lg p-2 text-center transition-colors <?= $enabled ? 'border-primary bg-primary/10' : 'border-base-300' ?>">
                                <?php $icons = ["ParkCity"=>"fa-mountain-sun","Vail"=>"fa-mountain","AspenSnowmass"=>"fa-tree","DeerValley"=>"fa-person-skiing","Killington"=>"fa-snowflake","BigSkyCombo"=>"fa-cloud-sun","PalisadesTahoe"=>"fa-water"]; ?><div class="w-full h-16 rounded mb-1 flex items-center justify-center <?= $enabled ? "bg-primary/10" : "bg-base-300" ?>"><i class="fa-solid <?= $icons[$key] ?? "fa-mountain" ?> text-xl <?= $enabled ? "text-primary" : "text-base-content/20" ?>"></i></div>
                                <div class="text-xs font-bold"><?= $key === 'BigSkyCombo' ? 'Big Sky' : ($key === 'AspenSnowmass' ? 'Aspen' : ($key === 'PalisadesTahoe' ? 'Palisades' : ($key === 'DeerValley' ? 'Deer Valley' : ($key === 'ParkCity' ? 'Park City' : $key)))) ?></div>
                                <div class="text-[10px] text-base-content/50"><?= $loc ?></div>
                                <?php if (!$enabled) : ?><div class="text-[9px] font-bold text-center mt-1"><span class="bg-neutral text-neutral-content px-1.5 py-0.5 rounded">Coming Soon</span></div><?php endif ?>
                            </div>
                        </label>
                        <?php endforeach ?>
                    </div>
                </div>
                    <label class="cursor-pointer flex items-start gap-2">
                        <input type="checkbox" name="terms" class="checkbox checkbox-primary checkbox-sm mt-0.5" required>
                        <span class="label-text text-sm">I agree to the <a href="/terms" target="_blank" rel="noopener noreferrer" class="link link-primary">Terms of Service</a> and <a href="/privacy" target="_blank" rel="noopener noreferrer" class="link link-primary">Privacy Policy</a></span>
                    </label>
                </div>
                    <button type="submit" class="btn btn-primary w-full">Create Account</button>
                </div>
            </form>

            <div class="divider text-xs" style="opacity:0.4;pointer-events:none;">OR</div>
            <a href="/auth/google" class="btn btn-outline w-full gap-2" style="opacity:0.4;pointer-events:none;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" class="w-5 h-5"><path fill="#FFC107" d="M43.6 20.1H42V20H24v8h11.3C33.9 33.1 29.4 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 8 3l5.7-5.7C34 6 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.2-2.7-.4-3.9z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.3 15.7 18.8 13 24 13c3.1 0 5.8 1.2 8 3l5.7-5.7C34 6 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.2 26.7 36 24 36c-5.4 0-9.9-3.5-11.5-8.3l-6.5 5C9.5 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.1H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C36.7 39.5 44 34 44 24c0-1.3-.2-2.7-.4-3.9z"/></svg>
                Google Sign-In temporarily unavailable
            </a>

            <div class="divider"></div>

            <p class="text-center text-sm text-base-content/60">
                By signing up, you agree to our <a href="/terms" class="link link-primary">Terms</a> and <a href="/privacy" class="link link-primary">Privacy Policy</a>.</p>
            <p class="text-center text-sm text-base-content/60">
                Already have an account? <a href="<?= url_to('login') ?>" class="link link-primary">Log in</a>
            </p>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
