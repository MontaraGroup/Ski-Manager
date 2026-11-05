<?php

namespace App\Controllers;

class Legal extends BaseController
{
    private function basePage(string $active, string $title, string $icon, string $lastUpdated = 'June 3, 2026'): array
    {
        return [
            'active' => $active,
            'title' => $title,
            'icon' => $icon,
            'lastUpdated' => $lastUpdated,
            'tabs' => [
                ['key' => 'terms', 'label' => 'Terms of Service', 'href' => '/terms', 'icon' => 'file-contract'],
                ['key' => 'privacy', 'label' => 'Privacy Policy', 'href' => '/privacy', 'icon' => 'user-shield'],
                ['key' => 'cookies', 'label' => 'Cookie Policy', 'href' => '/cookies', 'icon' => 'cookie-bite'],
                ['key' => 'disclaimer', 'label' => 'Disclaimer', 'href' => '/disclaimer', 'icon' => 'triangle-exclamation'],
                ['key' => 'sitemap', 'label' => 'Sitemap', 'href' => '/sitemap', 'icon' => 'sitemap'],
            ],
        ];
    }

    public function terms(): string
    {
        $page = $this->basePage('terms', 'Terms of Service', 'file-contract');
        return view('legal/terms', ['page' => $page]);
    }

    public function privacy(): string
    {
        $page = $this->basePage('privacy', 'Privacy Policy', 'user-shield');
        return view('legal/privacy', ['page' => $page]);
    }

    public function cookies(): string
    {
        $page = $this->basePage('cookies', 'Cookie Policy', 'cookie-bite');
        return view('legal/cookies', ['page' => $page]);
    }

    public function disclaimer(): string
    {
        $page = $this->basePage('disclaimer', 'Disclaimer', 'triangle-exclamation');
        return view('legal/disclaimer', ['page' => $page]);
    }

    public function sitemap(): string
    {
        $page = $this->basePage('sitemap', 'Sitemap', 'sitemap');
        $page['categories'] = [
            [
                'title' => 'General & Discovery',
                'icon' => 'house',
                'links' => [
                    ['label' => 'Home', 'href' => '/', 'desc' => 'Landing page and game overview'],
                    ['label' => 'About Ski Manager', 'href' => '/about', 'desc' => 'Story, developer, and technology stack'],
                    ['label' => 'Game Updates', 'href' => '/updates', 'desc' => 'Changelog timeline and release history'],
                    ['label' => 'Player Guides', 'href' => '/guides', 'desc' => 'Beginner and advanced operational manuals'],
                    ['label' => 'Frequently Asked Questions', 'href' => '/faq', 'desc' => 'Gameplay questions and answers'],
                    ['label' => 'Contact Support', 'href' => '/contact', 'desc' => 'Get in touch with the team'],
                    ['label' => 'Leaderboard', 'href' => '/leaderboard', 'desc' => 'Top 50 mountain rankings'],
                    ['label' => 'Login', 'href' => '/login', 'desc' => 'Sign in to your resort'],
                    ['label' => 'Register', 'href' => '/register', 'desc' => 'Create a free resort account'],
                    ['label' => 'Official Wiki', 'href' => 'https://wiki.ski-manager.net', 'desc' => 'Community knowledge base', 'external' => true],
                ],
            ],
            [
                'title' => 'Mountain & Slopes',
                'icon' => 'mountain-sun',
                'links' => [
                    ['label' => 'Dashboard', 'href' => '/dashboard', 'desc' => 'Central resort telemetry and widgets'],
                    ['label' => 'Resort Overview', 'href' => '/resort', 'desc' => 'Sector runs, lifts, and repair controls'],
                    ['label' => 'Interactive Trail Map', 'href' => '/map', 'desc' => 'Full-screen canvas map view'],
                    ['label' => 'Snow Grooming', 'href' => '/grooming', 'desc' => 'Piste preparation and groomer fleet'],
                    ['label' => 'Snowmaking Operations', 'href' => '/snowmaking', 'desc' => 'Snow guns and snow production'],
                    ['label' => 'Night Skiing', 'href' => '/night-skiing', 'desc' => 'Evening slope illumination'],
                    ['label' => 'Scenic Lifts', 'href' => '/scenic-lifts', 'desc' => 'Sightseeing cable cars and gondolas'],
                    ['label' => 'Terrain Parks', 'href' => '/terrain-parks', 'desc' => 'Halfpipes, jump lines, and rails'],
                    ['label' => 'Parking & Transit', 'href' => '/parking', 'desc' => 'Parking structures and shuttle buses'],
                    ['label' => 'Resort Analysis', 'href' => '/resort-analysis', 'desc' => 'Comprehensive performance diagnostics'],
                    ['label' => 'Utilities & Resources', 'href' => '/energy', 'desc' => 'Power grids and water reserves'],
                    ['label' => 'Weather Forecast', 'href' => '/weather', 'desc' => 'Hourly weather and snow conditions'],
                ],
            ],
            [
                'title' => 'Buildings & Real Estate',
                'icon' => 'building',
                'links' => [
                    ['label' => 'Hotels & Lodging', 'href' => '/hotels', 'desc' => 'Guest rooms, pricing, and amenities'],
                    ['label' => 'Restaurants & Bars', 'href' => '/restaurants', 'desc' => 'Dining venues and alpine menus'],
                    ['label' => 'Ski Rentals', 'href' => '/rentals', 'desc' => 'Gear rental shops and gear upgrades'],
                    ['label' => 'Retail Stores', 'href' => '/retail', 'desc' => 'Apparel and souvenir boutiques'],
                    ['label' => 'Real Estate', 'href' => '/real-estate', 'desc' => 'Commercial property and land parcels'],
                    ['label' => 'Transportation Network', 'href' => '/transportation', 'desc' => 'Resort transit and bus routes'],
                    ['label' => 'Off-Season Operations', 'href' => '/off-season', 'desc' => 'Summer activities and bike parks'],
                    ['label' => 'Ski Patrol Command', 'href' => '/ski-patrol', 'desc' => 'Safety patrols and medical posts'],
                ],
            ],
            [
                'title' => 'Operations & Economy',
                'icon' => 'coins',
                'links' => [
                    ['label' => 'Finances & Ledgers', 'href' => '/finances', 'desc' => 'Revenue, expenses, and daily cashflow'],
                    ['label' => 'Bank & Loans', 'href' => '/bank', 'desc' => 'Commercial credit and debt management'],
                    ['label' => 'Lift Tickets & Pricing', 'href' => '/tickets', 'desc' => 'Day passes and season pass tiers'],
                    ['label' => 'Staff Roster', 'href' => '/staff', 'desc' => 'Employee management and recruitment'],
                    ['label' => 'Marketing Campaigns', 'href' => '/marketing', 'desc' => 'Targeted advertising and promotions'],
                    ['label' => 'Ski School', 'href' => '/ski-lessons', 'desc' => 'Instructor courses and lesson tiers'],
                    ['label' => 'Compliance & Safety', 'href' => '/compliance', 'desc' => 'Government inspections and permits'],
                    ['label' => 'Equipment Shop', 'href' => '/equipment', 'desc' => 'Fleet machinery procurement'],
                ],
            ],
            [
                'title' => 'Community & Competitions',
                'icon' => 'trophy',
                'links' => [
                    ['label' => 'Alliances HQ', 'href' => '/alliances', 'desc' => 'Player alliances and joint projects'],
                    ['label' => 'Alliance Directory', 'href' => '/alliances/directory', 'desc' => 'Browse and join alliances'],
                    ['label' => 'Alliance Standings', 'href' => '/alliances/leaderboard', 'desc' => 'Competitive alliance rankings'],
                    ['label' => 'Tournaments', 'href' => '/tournaments', 'desc' => 'Alpine ski racing championships'],
                    ['label' => 'VIP Guests', 'href' => '/vip-guests', 'desc' => 'Celebrity guests and special requests'],
                    ['label' => 'Ballot & Voting', 'href' => '/vote', 'desc' => 'Community governance votes'],
                    ['label' => 'Development Roadmap', 'href' => '/roadmap', 'desc' => 'Feature pipeline and planned releases'],
                ],
            ],
            [
                'title' => 'Account & Information',
                'icon' => 'scale-balanced',
                'links' => [
                    ['label' => 'Resort Settings', 'href' => '/settings', 'desc' => 'Account, profile, and preferences'],
                    ['label' => 'Daily Bonus', 'href' => '/daily-bonus', 'desc' => 'Consecutive sign-in rewards'],
                    ['label' => 'Génépis Store', 'href' => '/genepis', 'desc' => 'Alpine premium items and upgrades'],
                    ['label' => 'Achievements', 'href' => '/achievements', 'desc' => 'Career milestones and trophies'],
                    ['label' => 'Activity History', 'href' => '/activity', 'desc' => 'Audit log of actions and transactions'],
                    ['label' => 'Developer API', 'href' => '/developers', 'desc' => 'OpenAPI 3.1 & RFC 9727 documentation'],
                    ['label' => 'Terms of Service', 'href' => '/terms', 'desc' => 'Terms and conditions of play'],
                    ['label' => 'Privacy Policy', 'href' => '/privacy', 'desc' => 'Data protection and player privacy'],
                    ['label' => 'Cookie Policy', 'href' => '/cookies', 'desc' => 'Cookie choices and audit table'],
                    ['label' => 'Disclaimer', 'href' => '/disclaimer', 'desc' => 'Simulation notices and trademarks'],
                ],
            ],
        ];

        return view('legal/sitemap', ['page' => $page]);
    }
}
