<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Home<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Hero -->
<div class="min-h-[80vh] flex items-center bg-gradient-to-br from-base-300 via-base-200 to-base-100">
    <div class="max-w-6xl mx-auto px-4 py-16 md:py-24">
        <div class="max-w-2xl">
            <p class="text-sm font-semibold text-primary mb-3 uppercase tracking-wider">Free browser game</p>
            <h1 class="text-4xl md:text-5xl font-black leading-[1.1] mb-5">Your mountain.<br>Your rules.</h1>
            <p class="text-lg text-base-content/60 mb-8 max-w-lg">Build a ski resort from nothing. Lay slopes, install lifts, hire staff, deal with blizzards, and try not to go bankrupt. It's harder than it sounds.</p>
            <div class="flex gap-3 flex-wrap">
                <a href="/register" class="btn btn-primary btn-lg">Start playing</a>
                <a href="/about" class="btn btn-ghost btn-lg">What is this?</a>
            </div>
        </div>
    </div>
</div>

<!-- What you actually do -->
<section class="py-20 px-4 bg-base-100">
    <div class="max-w-5xl mx-auto">
        <h2 class="text-2xl font-bold mb-8">What you'll actually spend your time doing</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex gap-4">
                <i class="fa-solid fa-map text-primary text-xl mt-1 shrink-0 w-6"></i>
                <div>
                    <h3 class="font-bold mb-1">Drawing slopes on a map</h3>
                    <p class="text-sm text-base-content/60">Click points on an interactive trail map to lay out runs and lift lines. Pick the type, difficulty, and watch it appear on your mountain.</p>
                </div>
            </div>
            <div class="flex gap-4">
                <i class="fa-solid fa-users text-warning text-xl mt-1 shrink-0 w-6"></i>
                <div>
                    <h3 class="font-bold mb-1">Managing people who don't want to work</h3>
                    <p class="text-sm text-base-content/60">Staff have morale. It drops. You throw them a party or give them a raise. They're happy for a while. Then it drops again.</p>
                </div>
            </div>
            <div class="flex gap-4">
                <i class="fa-solid fa-cloud-sun text-info text-xl mt-1 shrink-0 w-6"></i>
                <div>
                    <h3 class="font-bold mb-1">Checking the weather obsessively</h3>
                    <p class="text-sm text-base-content/60">Weather changes daily. Blizzards shut down lifts. Sunny days melt your snow. You'll learn to love cloudy skies with light snowfall.</p>
                </div>
            </div>
            <div class="flex gap-4">
                <i class="fa-solid fa-coins text-success text-xl mt-1 shrink-0 w-6"></i>
                <div>
                    <h3 class="font-bold mb-1">Watching numbers go up (or down)</h3>
                    <p class="text-sm text-base-content/60">Revenue, expenses, visitor counts, snow depth, staff morale, eco score, reputation. There are a lot of numbers. Most of them matter.</p>
                </div>
            </div>
            <div class="flex gap-4">
                <i class="fa-solid fa-snowflake text-info text-xl mt-1 shrink-0 w-6"></i>
                <div>
                    <h3 class="font-bold mb-1">Buying expensive snow machines</h3>
                    <p class="text-sm text-base-content/60">Real brands — TechnoAlpin, Sufag, Demaclenko. Real groomers — PistenBully, Prinoth. Fictional prices. Still expensive.</p>
                </div>
            </div>
            <div class="flex gap-4">
                <i class="fa-solid fa-building-columns text-primary text-xl mt-1 shrink-0 w-6"></i>
                <div>
                    <h3 class="font-bold mb-1">Dealing with the government</h3>
                    <p class="text-sm text-base-content/60">Regulations cost money to comply with. Ignoring them costs more. There's an inspection chance every day you're non-compliant.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- The boring-but-important details -->
<section class="py-16 px-4">
    <div class="max-w-5xl mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="card bg-base-100 shadow-sm">
                <div class="card-body">
                    <h3 class="font-bold">It's free</h3>
                    <p class="text-sm text-base-content/60">No paywall, no pay-to-win. There's a premium currency (Génépis) but you earn it by playing, not paying.</p>
                </div>
            </div>
            <div class="card bg-base-100 shadow-sm">
                <div class="card-body">
                    <h3 class="font-bold">It's in your browser</h3>
                    <p class="text-sm text-base-content/60">No downloads, no installs, no app store. Just open the URL and play. Works on desktop and laptop.</p>
                </div>
            </div>
            <div class="card bg-base-100 shadow-sm">
                <div class="card-body">
                    <h3 class="font-bold">It's open source</h3>
                    <p class="text-sm text-base-content/60">The entire codebase is on <a href="https://gitlab.com/contact1231/manager" target="_blank" class="link link-primary">GitLab</a>. Find a bug? Fix it. Want a feature? Build it.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-20 px-4 bg-base-100">
    <div class="max-w-xl mx-auto text-center">
        <h2 class="text-2xl font-bold mb-3">It takes 30 seconds to sign up</h2>
        <p class="text-base-content/60 mb-6">Then you can spend the next 30 hours building slopes.</p>
        <a href="/register" class="btn btn-primary btn-lg">Create an account</a>
        <p class="text-xs text-base-content/40 mt-4">Already playing? <a href="/login" class="link link-primary">Log in</a></p>
    </div>
</section>

<?= $this->endSection() ?>
