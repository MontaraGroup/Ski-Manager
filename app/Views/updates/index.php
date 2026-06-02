<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Game Updates<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-3xl mx-auto p-4 lg:p-8">
    <h1 class="text-3xl font-bold mb-2">Game Updates</h1>
    <p class="text-base-content/60 mb-6">What's new in Ski Manager. Follow our development progress and see what's coming next.</p>

    <div class="space-y-6">
        <div class="card bg-base-100 shadow-sm">
            <div class="card-body">
                <div class="flex items-center gap-2 mb-2">
                    <span class="badge badge-primary">v2.5</span>
                    <span class="text-sm text-base-content/50">June 1, 2026</span>
                </div>
                <h2 class="card-title text-lg">Resource Management Update</h2>
                <div class="prose prose-sm max-w-none mt-2">
                    <p>Major update bringing resource management, terrain parks, and quality-of-life improvements to Ski Manager.</p>
                    <h3>New Features</h3>
                    <ul>
                        <li><strong>Energy Management</strong> — Build power grid connections, solar panels, wind turbines, and diesel generators to power your resort. Monitor supply vs. demand across all your operations.</li>
                        <li><strong>Water Management</strong> — Construct reservoirs, wells, river pumps, and water recycling plants to supply your snowmaking operations.</li>
                        <li><strong>Terrain Parks</strong> — Build halfpipes, jump lines, rail gardens, and slopestyle courses. Hire dedicated park crew to maintain features. Attracts younger visitors.</li>
                        <li><strong>Parking & Transit</strong> — Build surface lots, parking garages, shuttle stops, and a village gondola. Set dynamic parking fees. Visitors are turned away when parking is full.</li>
                        <li><strong>VIP Guests</strong> — Random celebrity visitors, film crews, influencers, and ski teams arrive based on your resort quality. Meet their requirements for cash and reputation bonuses.</li>
                        <li><strong>Resort Analysis</strong> — Order a detailed report analyzing your resort across seven categories. Costs 20 Génépis and provides actionable recommendations.</li>
                        <li><strong>Notification System</strong> — Bell icon in the navbar with real-time notifications for construction completions, equipment breakdowns, VIP arrivals, and more.</li>
                        <li><strong>Tutorial System</strong> — New player tutorial that guides you through hiring staff, building slopes, and setting up your resort.</li>
                        <li><strong>Google Sign-In</strong> — Sign up or log in with your Google account for faster access.</li>
                    </ul>
                    <h3>Dashboard Improvements</h3>
                    <ul>
                        <li>iOS-style widget system with small, medium, and large sizes</li>
                        <li>Drag-and-drop widget reordering</li>
                        <li>Show/hide widgets with edit mode</li>
                        <li>New widgets: Parking, Terrain Parks, Finances, Staff, Equipment, Insurance, Loans, Marketing</li>
                        <li>All stats now pull real data from the database</li>
                        <li>Resort star rating displayed in the stats bar</li>
                    </ul>
                    <h3>Quality of Life</h3>
                    <ul>
                        <li>Equipment durability system — equipment degrades with use and needs repair</li>
                        <li>Resort open/close toggle — close your resort for maintenance days</li>
                        <li>Expanded settings page with resort naming, tutorial restart, and data management</li>
                        <li>Improved mobile navigation with categorized hamburger menu</li>
                        <li>Faster Font Awesome icon loading</li>
                        <li>Admin panel improvements: economy overview, activity log viewer, manual game tick</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm">
            <div class="card-body">
                <div class="flex items-center gap-2 mb-2">
                    <span class="badge badge-ghost">v2.0</span>
                    <span class="text-sm text-base-content/50">May 27, 2026</span>
                </div>
                <h2 class="card-title text-lg">Complete Rebuild</h2>
                <div class="prose prose-sm max-w-none mt-2">
                    <p>Ski Manager has been completely rebuilt from the ground up. The original game (v1) was built on CodeIgniter 3 with a basic interface. Version 2 is a modern rewrite with an entirely new technology stack and dramatically expanded gameplay.</p>
                    <h3>Core Systems</h3>
                    <ul>
                        <li>Modern UI built with Tailwind CSS and DaisyUI with light/dark theme support</li>
                        <li>Interactive trail map powered by Leaflet.js with map data from Mapsynergy</li>
                        <li>Comprehensive staff management with 10 different roles</li>
                        <li>Dynamic weather system that affects visitor count and snow conditions</li>
                        <li>Full financial system with income tracking, expenses, loans, and insurance</li>
                        <li>8 categories of buildings including hotels, restaurants, and retail shops</li>
                        <li>Snowmaking and night skiing operations</li>
                        <li>Government regulations and environmental compliance</li>
                        <li>Equipment shop with real-world brands (PistenBully, Prinoth, TechnoAlpin)</li>
                        <li>Achievement system with 12 trackable goals</li>
                        <li>Daily bonus with streak rewards</li>
                        <li>Leaderboard and tournament system</li>
                        <li>Génépis premium currency</li>
                        <li>Automated game engine with daily processing via cron</li>
                        <li>Admin panel for game management</li>
                        <li>WCAG 2.2 accessibility compliance</li>
                        <li>Mobile responsive design</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
