<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Sitemap<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-5xl mx-auto p-4 lg:p-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold mb-1">Sitemap</h1>
        <p class="text-sm text-base-content/50">Every page in Ski Manager, organized by area.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

        <div class="card bg-base-100 shadow-sm"><div class="card-body p-5">
            <h2 class="font-bold text-base mb-3"><i class="fa-solid fa-house mr-2 text-primary"></i>General</h2>
            <ul class="space-y-2 text-sm">
                <li><a href="/" class="link link-hover">Home</a></li>
                <li><a href="/about" class="link link-hover">About</a></li>
                <li><a href="/updates" class="link link-hover">Game Updates</a></li>
                <li><a href="/faq" class="link link-hover">FAQ</a></li>
                <li><a href="/contact" class="link link-hover">Contact</a></li>
                <li><a href="/leaderboard" class="link link-hover">Leaderboard</a></li>
                <li><a href="/login" class="link link-hover">Login</a></li>
                <li><a href="/register" class="link link-hover">Register</a></li>
                <li><a href="https://wiki.ski-manager.net" target="_blank" rel="noopener noreferrer" class="link link-hover">Wiki</a></li>
            </ul>
        </div></div>

        <div class="card bg-base-100 shadow-sm"><div class="card-body p-5">
            <h2 class="font-bold text-base mb-3"><i class="fa-solid fa-mountain-sun mr-2 text-primary"></i>Resort</h2>
            <ul class="space-y-2 text-sm">
                <li><a href="/dashboard" class="link link-hover">Dashboard</a></li>
                <li><a href="/resort" class="link link-hover">Resort Overview</a></li>
                <li><a href="/map" class="link link-hover">Trail Map</a></li>
                <li><a href="/grooming" class="link link-hover">Grooming</a></li>
                <li><a href="/snowmaking" class="link link-hover">Snowmaking</a></li>
                <li><a href="/night-skiing" class="link link-hover">Night Skiing</a></li>
                <li><a href="/scenic-lifts" class="link link-hover">Scenic Lifts</a></li>
                <li><a href="/terrain-parks" class="link link-hover">Terrain Parks</a></li>
                <li><a href="/parking" class="link link-hover">Parking</a></li>
                <li><a href="/resort-analysis" class="link link-hover">Resort Analysis</a></li>
                <li><a href="/energy" class="link link-hover">Energy</a></li>
                <li><a href="/water" class="link link-hover">Water</a></li>
                <li><a href="/weather" class="link link-hover">Weather</a></li>
            </ul>
        </div></div>

        <div class="card bg-base-100 shadow-sm"><div class="card-body p-5">
            <h2 class="font-bold text-base mb-3"><i class="fa-solid fa-building mr-2 text-primary"></i>Buildings</h2>
            <ul class="space-y-2 text-sm">
                <li><a href="/hotels" class="link link-hover">Hotels</a></li>
                <li><a href="/restaurants" class="link link-hover">Restaurants</a></li>
                <li><a href="/rentals" class="link link-hover">Ski Rentals</a></li>
                <li><a href="/retail" class="link link-hover">Retail</a></li>
                <li><a href="/real-estate" class="link link-hover">Real Estate</a></li>
                <li><a href="/transportation" class="link link-hover">Transportation</a></li>
                <li><a href="/off-season" class="link link-hover">Off-Season</a></li>
                <li><a href="/ski-patrol" class="link link-hover">Ski Patrol</a></li>
            </ul>
        </div></div>

        <div class="card bg-base-100 shadow-sm"><div class="card-body p-5">
            <h2 class="font-bold text-base mb-3"><i class="fa-solid fa-coins mr-2 text-primary"></i>Operations</h2>
            <ul class="space-y-2 text-sm">
                <li><a href="/finances" class="link link-hover">Finances</a></li>
                <li><a href="/bank" class="link link-hover">Bank & Loans</a></li>
                <li><a href="/tickets" class="link link-hover">Lift Tickets</a></li>
                <li><a href="/staff" class="link link-hover">Staff</a></li>
                <li><a href="/marketing" class="link link-hover">Marketing</a></li>
                <li><a href="/ski-lessons" class="link link-hover">Ski School</a></li>
                <li><a href="/compliance" class="link link-hover">Compliance</a></li>
                <li><a href="/equipment" class="link link-hover">Equipment Shop</a></li>
            </ul>
        </div></div>

        <div class="card bg-base-100 shadow-sm"><div class="card-body p-5">
            <h2 class="font-bold text-base mb-3"><i class="fa-solid fa-star mr-2 text-primary"></i>More</h2>
            <ul class="space-y-2 text-sm">
                <li><a href="/emergency" class="link link-hover">Emergency</a></li>
                <li><a href="/tournaments" class="link link-hover">Events & Tournaments</a></li>
                <li><a href="/vip-guests" class="link link-hover">VIP Guests</a></li>
                <li><a href="/achievements" class="link link-hover">Achievements</a></li>
                <li><a href="/daily-bonus" class="link link-hover">Daily Bonus</a></li>
                <li><a href="/genepis" class="link link-hover">Génépis</a></li>
                <li><a href="/activity" class="link link-hover">Activity Log</a></li>
                <li><a href="/vote" class="link link-hover">Vote Season 4</a></li>
                <li><a href="/support" class="link link-hover">Support</a></li>
                <li><a href="/settings" class="link link-hover">Settings</a></li>
            </ul>
        </div></div>

        <div class="card bg-base-100 shadow-sm"><div class="card-body p-5">
            <h2 class="font-bold text-base mb-3"><i class="fa-solid fa-scale-balanced mr-2 text-primary"></i>Legal</h2>
            <ul class="space-y-2 text-sm">
                <li><a href="/terms" class="link link-hover">Terms of Service</a></li>
                <li><a href="/privacy" class="link link-hover">Privacy Policy</a></li>
                <li><a href="/cookies" class="link link-hover">Cookie Policy</a></li>
                <li><a href="/disclaimer" class="link link-hover">Disclaimer</a></li>
                <li><a href="/sitemap" class="link link-hover">Sitemap</a></li>
            </ul>
        </div></div>

    </div>
</div>
<?= $this->endSection() ?>
