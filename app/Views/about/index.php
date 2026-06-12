<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>About Ski Manager<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-3xl mx-auto p-4 lg:p-8">

    <div class="mb-8">
        <h1 class="text-3xl font-bold mb-2"><i class="fa-solid fa-mountain-sun mr-2 text-primary"></i>About Ski Manager</h1>
        <p class="text-base-content/60">A free, browser-based ski resort tycoon - built by one developer who loves the mountains.</p>
    </div>

    <div class="card bg-base-100 shadow-sm mb-4"><div class="card-body">
        <h2 class="font-bold text-lg mb-2"><i class="fa-solid fa-circle-info mr-2 text-info"></i>What is Ski Manager?</h2>
        <p class="text-sm text-base-content/70 leading-relaxed">Ski Manager is a free-to-play browser-based ski resort management sim. You take on the role of a resort owner - building slopes and lifts, hiring staff, managing budgets, and competing to run the most successful resort.</p>
    </div></div>

    <div class="card bg-base-100 shadow-sm mb-4"><div class="card-body">
        <h2 class="font-bold text-lg mb-2"><i class="fa-solid fa-clock-rotate-left mr-2 text-primary"></i>The Story</h2>
        <p class="text-sm text-base-content/70 leading-relaxed mb-2">Ski Manager began as a small hobby project to find out whether a skiing-themed management game could work in the browser. It picked up a community, evolved through several iterations, and in 2026 was rebuilt from scratch as Version 2 - a complete rewrite with a modern codebase, redesigned interface, and far deeper gameplay.</p>
        <p class="text-sm text-base-content/70 leading-relaxed">Today it runs dozens of interconnected systems, from hourly weather and snowmaking to compliance, insurance, finance, and terrain park design.</p>
    </div></div>

    <div class="card bg-base-100 shadow-sm mb-4"><div class="card-body">
        <h2 class="font-bold text-lg mb-2"><i class="fa-solid fa-user mr-2 text-primary"></i>The Developer</h2>
        <p class="text-sm text-base-content/70 leading-relaxed">Ski Manager is built by Marcel, a web developer and skiing enthusiast. It's a one-person project combining a love of winter sports with full-stack development - Marcel handles design, code, server infrastructure, and the community. The game is free, ad-supported, and never pay-to-win.</p>
    </div></div>

    <div class="card bg-base-100 shadow-sm mb-4"><div class="card-body">
        <h2 class="font-bold text-lg mb-3"><i class="fa-solid fa-microchip mr-2 text-primary"></i>Technology</h2>
        <ul class="space-y-2 text-sm text-base-content/70">
            <li><i class="fa-solid fa-server fa-fw mr-1 text-base-content/40"></i><strong>Backend:</strong> <a href="https://codeigniter.com/" target="_blank" rel="noopener noreferrer" class="link link-primary">CodeIgniter 4</a> (PHP) with <a href="https://codeigniter4.github.io/shield/" target="_blank" rel="noopener noreferrer" class="link link-primary">Shield</a> auth</li>
            <li><i class="fa-solid fa-palette fa-fw mr-1 text-base-content/40"></i><strong>Frontend:</strong> <a href="https://tailwindcss.com/" target="_blank" rel="noopener noreferrer" class="link link-primary">Tailwind CSS 4</a> + <a href="https://daisyui.com/" target="_blank" rel="noopener noreferrer" class="link link-primary">DaisyUI 5</a></li>
            <li><i class="fa-solid fa-map fa-fw mr-1 text-base-content/40"></i><strong>Trail Map:</strong> <a href="https://leafletjs.com/" target="_blank" rel="noopener noreferrer" class="link link-primary">Leaflet.js</a>, imagery from <a href="https://skimap.com/" target="_blank" rel="noopener noreferrer" class="link link-primary">Mapsynergy/Skimap</a></li>
            <li><i class="fa-solid fa-icons fa-fw mr-1 text-base-content/40"></i><strong>Icons:</strong> <a href="https://fontawesome.com/" target="_blank" rel="noopener noreferrer" class="link link-primary">Font Awesome 6</a></li>
            <li><i class="fa-solid fa-database fa-fw mr-1 text-base-content/40"></i><strong>Database:</strong> MySQL 9</li>
            <li><i class="fa-solid fa-cloud fa-fw mr-1 text-base-content/40"></i><strong>Hosting:</strong> VPS with <a href="https://www.cloudflare.com/" target="_blank" rel="noopener noreferrer" class="link link-primary">Cloudflare</a> CDN and SSL</li>
            <li><i class="fa-solid fa-bug fa-fw mr-1 text-base-content/40"></i><strong>Error Tracking:</strong> <a href="https://sentry.io/" target="_blank" rel="noopener noreferrer" class="link link-primary">Sentry</a></li>
        </ul>
        <p class="text-sm text-base-content/70 leading-relaxed mt-3">Built with <a href="https://www.w3.org/WAI/WCAG22/quickref/" target="_blank" rel="noopener noreferrer" class="link link-primary">WCAG 2.2</a> accessibility in mind - keyboard navigation, screen readers, and reduced-motion support.</p>
    </div></div>

    <div class="card bg-base-100 shadow-sm mb-4"><div class="card-body">
        <h2 class="font-bold text-lg mb-2"><i class="fa-solid fa-snowflake mr-2 text-primary"></i>Real Equipment Brands</h2>
        <p class="text-sm text-base-content/70 leading-relaxed mb-2">For authenticity, the game features real ski-industry brands:</p>
        <ul class="space-y-1 text-sm text-base-content/70">
            <li><i class="fa-solid fa-tractor fa-fw mr-1 text-base-content/40"></i><a href="https://www.pistenbully.com/" target="_blank" rel="noopener noreferrer" class="link link-primary">PistenBully</a> and <a href="https://www.prinoth.com/" target="_blank" rel="noopener noreferrer" class="link link-primary">Prinoth</a> snow groomers</li>
            <li><i class="fa-solid fa-spray-can fa-fw mr-1 text-base-content/40"></i><a href="https://www.technoalpin.com/" target="_blank" rel="noopener noreferrer" class="link link-primary">TechnoAlpin</a>, <a href="https://www.dfrgroup.it/" target="_blank" rel="noopener noreferrer" class="link link-primary">Demaclenko</a>, HKD, and SMI snowmaking systems</li>
        </ul>
        <p class="text-xs text-base-content/50 mt-3">All brand names and trademarks are property of their respective owners. Their inclusion is for simulation and educational purposes only.</p>
    </div></div>

    <div class="card bg-gradient-to-br from-primary to-secondary text-primary-content shadow-sm"><div class="card-body text-center">
        <h2 class="font-bold text-xl mb-1">Join the community</h2>
        <p class="text-sm opacity-80 mb-4">Share strategies, report bugs, suggest features, and follow development.</p>
        <div class="flex gap-2 justify-center flex-wrap">
            <a href="https://discord.gg/TyEnFdfd8w" target="_blank" rel="noopener noreferrer" class="btn btn-sm bg-base-100 text-primary border-0 hover:bg-base-200 gap-1"><i class="fa-brands fa-discord"></i> Discord</a>
            <a href="/updates" class="btn btn-sm btn-outline border-white text-white hover:bg-white hover:text-primary gap-1"><i class="fa-solid fa-newspaper"></i> Updates</a>
            <a href="/contact" class="btn btn-sm btn-outline border-white text-white hover:bg-white hover:text-primary gap-1"><i class="fa-solid fa-envelope"></i> Contact</a>
        </div>
    </div></div>

    <?php if (!auth()->loggedIn()) : ?>
    <div class="text-center mt-6">
        <a href="/register" class="btn btn-primary btn-lg gap-2"><i class="fa-solid fa-play"></i> Play Free</a>
    </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
