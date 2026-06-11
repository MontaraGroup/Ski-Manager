<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Hire Staff<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php $__cash = db_connect()->table('player_finances')->where('user_id', auth()->id())->get()->getRowArray()['cash'] ?? 0; ?>
<div class="max-w-4xl mx-auto p-4 lg:p-8">

    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="/staff" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold"><i class="fa-solid fa-user-plus mr-2 text-primary"></i>Hire Staff</h1>
                <p class="text-sm text-base-content/50">Choose a role. Each hire starts at Lv.1.</p>
            </div>
        </div>
        <div class="text-right">
            <div class="text-xs text-base-content/50">Your Balance</div>
            <div class="font-bold text-success"><?= currency($__cash) ?></div>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>

    <!-- Role Categories -->
    <?php
        $categories = [
            'Operations' => ['patrol', 'mechanic', 'groomer', 'snowmaker'],
            'Guest Services' => ['instructor', 'receptionist', 'chef'],
            'Specialized' => ['medic', 'park_crew', 'manager'],
        ];
    ?>

    <?php foreach ($categories as $catName => $catRoles) : ?>
    <h2 class="text-sm font-semibold text-base-content/40 uppercase tracking-wider mb-3 <?= $catName !== 'Operations' ? 'mt-6' : '' ?>"><?= $catName ?></h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <?php foreach ($catRoles as $key) : ?>
        <?php if (!isset($roles[$key])) continue; $role = $roles[$key]; $canAfford = $__cash >= ($role['salary'] ?? 0); ?>
        <div class="card bg-base-100 shadow-sm hover:shadow-lg transition-all group">
            <div class="card-body p-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-base-200 group-hover:bg-primary/10 flex items-center justify-center shrink-0 transition-colors">
                        <i class="<?= $role['icon'] ?> text-xl <?= $role['color'] ?> group-hover:scale-110 transition-transform"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-bold"><?= $role['name'] ?></div>
                        <div class="text-xs text-base-content/50 mb-1"><?= $role['desc'] ?></div>
                        <div class="flex items-center gap-2 text-xs">
                            <span class="badge badge-ghost badge-xs gap-1"><i class="fa-solid fa-coins"></i><?= currency($role['salary']) ?>/day</span>
                            <?php if ($key === 'manager') : ?><span class="badge badge-warning badge-xs">+5% efficiency</span><?php endif ?>
                            <?php if ($key === 'medic') : ?><span class="badge badge-error badge-xs">Emergency</span><?php endif ?>
                            <?php if ($key === 'groomer') : ?><span class="badge badge-success badge-xs">Slopes</span><?php endif ?>
                            <?php if ($key === 'snowmaker') : ?><span class="badge badge-info badge-xs">Snow</span><?php endif ?>
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <div class="text-lg font-bold text-primary"><?= currency($role['salary']) ?></div>
                        <form action="/staff/hire" method="post" data-confirm="Hire a <?= $role['name'] ?> for <?= currency($role['salary']) ?>/day?"><?= csrf_field() ?>
                            <input type="hidden" name="role" value="<?= $key ?>">
                            <button class="btn btn-primary btn-sm gap-1 mt-1" <?= !$canAfford ? 'disabled' : '' ?>>
                                <i class="fa-solid fa-user-plus"></i> Hire
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach ?>
    </div>
    <?php endforeach ?>

    <!-- How It Works -->
    <div class="collapse collapse-arrow bg-base-100 shadow-sm mt-6">
        <input type="checkbox" />
        <div class="collapse-title font-semibold text-sm"><i class="fa-solid fa-circle-info mr-1"></i>How Staff Works</div>
        <div class="collapse-content text-sm text-base-content/70">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                <div>
                    <h4 class="font-semibold text-xs uppercase tracking-wide mb-2 text-base-content/40">Basics</h4>
                    <ul class="space-y-1">
                        <li><i class="fa-solid fa-coins text-warning text-xs mr-2"></i>Salary is deducted daily by the game tick</li>
                        <li><i class="fa-solid fa-circle-check text-success text-xs mr-2"></i>Staff must be assigned to be effective</li>
                        <li><i class="fa-solid fa-face-smile text-info text-xs mr-2"></i>Higher morale = better performance</li>
                        <li><i class="fa-solid fa-star text-warning text-xs mr-2"></i>Level up through training for +effectiveness</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold text-xs uppercase tracking-wide mb-2 text-base-content/40">Roles</h4>
                    <ul class="space-y-1">
                        <li><i class="fa-solid fa-shield text-xs mr-2"></i>Patrol keeps slopes safe, prevents accidents</li>
                        <li><i class="fa-solid fa-wrench text-xs mr-2"></i>Mechanics maintain lifts and reduce breakdowns</li>
                        <li><i class="fa-solid fa-tractor text-xs mr-2"></i>Groomers improve slope conditions</li>
                        <li><i class="fa-solid fa-user-tie text-xs mr-2"></i>Managers boost overall resort efficiency by 5%</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
