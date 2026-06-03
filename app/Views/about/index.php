<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>About Ski Manager<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-3xl mx-auto p-4 lg:p-8">
    <h1 class="text-3xl font-bold mb-2">About Ski Manager</h1>
    <p class="text-base-content/60 mb-6">The story behind the game, the team, and the technology.</p>

    <div class="prose prose-sm max-w-none">
        <h2>What is Ski Manager?</h2>
        <p>Ski Manager is a free-to-play browser-based ski resort management simulation game. Players take on the role of a resort owner, building slopes and lifts, hiring staff, managing budgets, and competing to create the most successful ski resort. The game draws inspiration from classic tycoon games like <a href="https://en.wikipedia.org/wiki/RollerCoaster_Tycoon" target="_blank" rel="noopener noreferrer">RollerCoaster Tycoon</a> and <a href="https://en.wikipedia.org/wiki/SimCity" target="_blank" rel="noopener noreferrer">SimCity</a>, applied to the world of alpine skiing.</p>

        <h2>History</h2>
        <p>The original Ski Manager launched as a small hobby project to explore whether a skiing-themed management game could work as a browser game. After gaining a small community of players, the project evolved through several iterations. In 2026, the entire game was rebuilt from scratch as Version 2 - a complete rewrite with a modern codebase, redesigned interface, and dramatically expanded gameplay features.</p>
        <p>Today, Ski Manager includes over 30 interconnected game systems covering everything from real-time weather simulation and snowmaking operations to government compliance, insurance, financial management, and terrain park design.</p>

        <h2>The Developer</h2>
        <p>Ski Manager is developed by Marcel Saintin, a web developer and skiing enthusiast. The project combines a passion for winter sports with years of experience in full-stack web development. Marcel handles all aspects of the game - design, development, server infrastructure, and community management.</p>

        <h2>Technology</h2>
        <p>Ski Manager v2 is built on a modern, reliable technology stack:</p>
        <ul>
            <li><strong>Backend:</strong> <a href="https://codeigniter.com/" target="_blank" rel="noopener noreferrer">CodeIgniter 4</a> (PHP framework) with <a href="https://codeigniter4.github.io/shield/" target="_blank" rel="noopener noreferrer">Shield</a> authentication</li>
            <li><strong>Frontend:</strong> <a href="https://tailwindcss.com/" target="_blank" rel="noopener noreferrer">Tailwind CSS 4</a> with <a href="https://daisyui.com/" target="_blank" rel="noopener noreferrer">DaisyUI 5</a> component library</li>
            <li><strong>Trail Map:</strong> <a href="https://leafletjs.com/" target="_blank" rel="noopener noreferrer">Leaflet.js</a> with map imagery from <a href="https://www.skimap.org/" target="_blank" rel="noopener noreferrer">Mapsynergy/Skimap</a></li>
            <li><strong>Icons:</strong> <a href="https://fontawesome.com/" target="_blank" rel="noopener noreferrer">Font Awesome 6</a></li>
            <li><strong>Database:</strong> MySQL 8</li>
            <li><strong>Hosting:</strong> VPS with <a href="https://www.cloudflare.com/" target="_blank" rel="noopener noreferrer">Cloudflare</a> CDN and SSL</li>
            <li><strong>Error Tracking:</strong> <a href="https://sentry.io/" target="_blank" rel="noopener noreferrer">Sentry</a></li>
        </ul>
        <p>The game is designed with <a href="https://www.w3.org/WAI/WCAG22/quickref/" target="_blank" rel="noopener noreferrer">WCAG 2.2</a> accessibility guidelines in mind, supporting keyboard navigation, screen readers, and reduced motion preferences.</p>

        <h2>Equipment Brands</h2>
        <p>Ski Manager features real-world ski industry equipment brands to provide an authentic experience:</p>
        <ul>
            <li><a href="https://www.pistenbully.com/" target="_blank" rel="noopener noreferrer">PistenBully</a> and <a href="https://www.prinoth.com/" target="_blank" rel="noopener noreferrer">Prinoth</a> snow groomers</li>
            <li><a href="https://www.technoalpin.com/" target="_blank" rel="noopener noreferrer">TechnoAlpin</a>, Sufag, <a href="https://www.dfrgroup.it/" target="_blank" rel="noopener noreferrer">Demaclenko</a>, and SMI snowmaking systems</li>
        </ul>
        <p>All brand names and trademarks are property of their respective owners. Their inclusion in the game is for educational and simulation purposes.</p>

        <h2>Community</h2>
        <p>Ski Manager has a growing community of players who share strategies, report bugs, and suggest new features. Join us on <a href="https://discord.gg/" target="_blank" rel="noopener noreferrer">Discord</a> or follow development updates on our <a href="/updates">Updates page</a>.</p>

        <h2>Contact</h2>
        <p>Have questions, feedback, or partnership inquiries? Reach out through our <a href="/contact">contact form</a> or email <a href="mailto:contact@ski-manager.net">contact@ski-manager.net</a>.</p>
    </div>
</div>
<?= $this->endSection() ?>
