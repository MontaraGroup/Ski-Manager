<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Home<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="hero min-h-[60vh]">
    <div class="hero-content text-center">
        <div class="max-w-md">
            <h1 class="text-5xl font-bold">Ski Manager v2</h1>
            <p class="py-6">A fresh start. Faster, cleaner, better.</p>
            <a href="/dashboard" class="btn btn-primary">Get Started</a>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
