<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Sitemap<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-3xl mx-auto p-4 lg:p-8">
    <h1 class="text-3xl font-bold mb-6">Sitemap</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
            <h2 class="font-bold text-lg mb-3"><i class="fa-solid fa-house mr-1"></i> General</h2>
            <ul class="space-y-2 text-sm">
                <li><a href="/" class="link link-hover">Home</a></li>
                <li><a href="/about" class="link link-hover">About</a></li>
                <li><a href="/updates" class="link link-hover">Game Updates</a></li>
                <li><a href="/faq" class="link link-hover">FAQ</a></li>
                <li><a href="/contact" class="link link-hover">Contact</a></li>
                <li><a href="/leaderboard" class="link link-hover">Leaderboard</a></li>
                <li><a href="/login" class="link link-hover">Login</a></li>
                <li><a href="/register" class="link link-hover">Register</a></li>
            </ul>
        </div>

        <div>
            <h2 class="font-bold text-lg mb-3"><i class="fa-solid fa-gamepad mr-1"></i> Game</h2>
            <ul class="space-y-2 text-sm">
                <li><a href="/dashboard" class="link link-hover">Dashboard</a></li>
                <li><a href="/resort" class="link link-hover">Resort</a></li>
                <li><a href="/map" class="link link-hover">Trail Map</a></li>
                <li><a href="/weather" class="link link-hover">Weather</a></li>
                <li><a href="/staff" class="link link-hover">Staff</a></li>
                <li><a href="/finances" class="link link-hover">Finances</a></li>
                <li><a href="/tickets" class="link link-hover">Tickets</a></li>
                <li><a href="/equipment" class="link link-hover">Equipment</a></li>
                <li><a href="/achievements" class="link link-hover">Achievements</a></li>
                <li><a href="/daily-bonus" class="link link-hover">Daily Bonus</a></li>
                <li><a href="/genepis" class="link link-hover">Génépis</a></li>
            </ul>
        </div>

        <div>
            <h2 class="font-bold text-lg mb-3"><i class="fa-solid fa-cogs mr-1"></i> Operations</h2>
            <ul class="space-y-2 text-sm">
                <li><a href="/grooming" class="link link-hover">Grooming</a></li>
                <li><a href="/snowmaking" class="link link-hover">Snowmaking</a></li>
                <li><a href="/night-skiing" class="link link-hover">Night Skiing</a></li>
                <li><a href="/terrain-parks" class="link link-hover">Terrain Parks</a></li>
                <li><a href="/parking" class="link link-hover">Parking & Transit</a></li>
                <li><a href="/energy" class="link link-hover">Energy</a></li>
                <li><a href="/water" class="link link-hover">Water</a></li>
                <li><a href="/hotels" class="link link-hover">Hotels</a></li>
                <li><a href="/restaurants" class="link link-hover">Restaurants</a></li>
                <li><a href="/bank" class="link link-hover">Bank & Loans</a></li>
                <li><a href="/insurance" class="link link-hover">Insurance</a></li>
                <li><a href="/marketing" class="link link-hover">Marketing</a></li>
                <li><a href="/resort-analysis" class="link link-hover">Resort Analysis</a></li>
            </ul>

            <h2 class="font-bold text-lg mt-6 mb-3"><i class="fa-solid fa-scale-balanced mr-1"></i> Legal</h2>
            <ul class="space-y-2 text-sm">
                <li><a href="/terms" class="link link-hover">Terms of Service</a></li>
                <li><a href="/privacy" class="link link-hover">Privacy Policy</a></li>
                <li><a href="/cookies" class="link link-hover">Cookie Policy</a></li>
                <li><a href="/disclaimer" class="link link-hover">Disclaimer</a></li>
            </ul>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
