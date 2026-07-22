<?= $this->extend('layouts/main') ?>

<?= $this->section('main') ?>
<div class="container mx-auto px-4 py-8 max-w-4xl">
    <div class="text-center mb-10">
        <h1 class="text-4xl font-extrabold tracking-tight">Game Updates</h1>
        <p class="text-base-content/60 mt-2">What's new in Ski Manager. Follow our progress and see what's shipped.</p>
    </div>

    <div class="card bg-base-100 shadow-xl mb-6 border border-primary/30">
        <div class="card-body">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <h2 class="card-title text-2xl font-bold text-primary">v1.3.5 <span class="badge badge-primary">Major Release</span></h2>
                <span class="text-sm text-base-content/60">July 2026</span>
            </div>
            <p class="text-sm font-medium text-base-content/80 mt-1">Major architectural updates, strict mode compliance, and advanced optimizations across the platform.</p>
            
            <div class="divider my-2"></div>
            
            <h3 class="font-bold text-md mt-2">🚀 Major Highlights & Features</h3>
            <ul class="list-disc list-inside space-y-1 text-sm text-base-content/70 mt-1">
                <li><strong>Strict Mode Execution:</strong> Enforced rigorous strict standards and deduplication safeguards across frontend runtime scripts.</li>
                <li><strong>Performance & Tooling:</strong> Upgraded asset bundling pipelines and streamlined database routing responses.</li>
            </ul>
        </div>
    </div>

    <div class="card bg-base-100 shadow-xl mb-6 border border-base-300">
        <div class="card-body">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <h2 class="card-title text-xl font-bold">v1.3.1 <span class="badge badge-ghost">Minor</span></h2>
                <span class="text-sm text-base-content/60">June 28, 2026</span>
            </div>
            <p class="text-sm font-medium text-base-content/80 mt-1">Operations & Support Overhaul deploying Ski Patrol and admin chat enhancements.</p>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
