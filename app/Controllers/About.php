<?php

namespace App\Controllers;

class About extends BaseController
{
    public function index(): string
    {
        $page = [
            'isLoggedIn' => (bool) (function_exists('auth') && auth()->loggedIn()),
            'developer' => [
                'name' => 'Marcel',
                'title' => 'Creator & Full-Stack Developer',
                'description' => 'Ski Manager is built and maintained by Marcel, an independent web developer and lifelong alpine skier. It is a passion project combining authentic mountain operations with full-stack software engineering. The game is free to play, supported by unobtrusive advertisements, and never pay-to-win.',
            ],
            'story' => [
                'origin' => 'Ski Manager started as an experiment to explore whether a full-fledged ski resort simulation could be experienced natively in the browser without plugins or downloads. Driven by a dedicated player community, it rapidly expanded from basic lift operations to an interconnected mountain economy.',
                'rewrite' => 'In 2026, the entire application was rebuilt from the ground up as Version 2 with a high-performance modern tech stack, redesigned interfaces, dynamic weather systems, real equipment licensing, and deep financial telemetry.',
            ],
            'stack' => [
                ['category' => 'Backend Framework', 'name' => 'CodeIgniter 4 (PHP 8.2+)', 'url' => 'https://codeigniter.com/', 'desc' => 'Modern PHP MVC backend with Shield auth, rate-limiting throttlers, database migrations, and MySQL 9 connection pooling.'],
                ['category' => 'Frontend Architecture', 'name' => 'React 19 + HeroUI 3.2.7', 'url' => 'https://heroui.com/', 'desc' => 'Island-mounted UI architecture built on React Aria primitives for high accessibility and keyboard control.'],
                ['category' => 'Styling & Design Tokens', 'name' => 'Tailwind CSS 4 + Bespoke Alpine Tokens', 'url' => 'https://tailwindcss.com/', 'desc' => 'Carbon Cloud dark theme and Winter light theme with strict WCAG 2.2 AA contrast compliance.'],
                ['category' => 'Build Tooling', 'name' => 'Vite 6 + TypeScript', 'url' => 'https://vite.dev/', 'desc' => 'Zero-runtime overhead build pipeline producing deterministic, code-split island chunks.'],
                ['category' => 'Interactive Trail Map', 'name' => 'Leaflet.js + Mapsynergy/Skimap', 'url' => 'https://leafletjs.com/', 'desc' => 'High-resolution pan/zoom alpine canvas engine rendering real mountain topography and sector runs.'],
                ['category' => 'Iconography', 'name' => 'Font Awesome 6 Pro', 'url' => 'https://fontawesome.com/', 'desc' => 'Consistent iconography mapped via SVG React adapters.'],
                ['category' => 'Infrastructure & Edge', 'name' => 'VPS + Cloudflare CDN', 'url' => 'https://www.cloudflare.com/', 'desc' => 'Global edge asset distribution, HTTP/3 transport, and DDoS mitigation.'],
                ['category' => 'Observability', 'name' => 'Sentry Error & Performance Tracing', 'url' => 'https://sentry.io/', 'desc' => 'Real-time telemetry, session replays, and client anomaly alerts.'],
            ],
            'brands' => [
                ['name' => 'PistenBully', 'category' => 'Snow Groomers', 'url' => 'https://www.pistenbully.com/', 'desc' => 'Precision slope preparation and alpine grooming simulation.'],
                ['name' => 'Prinoth', 'category' => 'Snow Groomers', 'url' => 'https://www.prinoth.com/', 'desc' => 'High-efficiency fleet machinery and terrain park sculpting.'],
                ['name' => 'TechnoAlpin', 'category' => 'Snowmaking Systems', 'url' => 'https://www.technoalpin.com/', 'desc' => 'Automated snow guns, lances, and fan generators.'],
                ['name' => 'Demaclenko', 'category' => 'Snowmaking Systems', 'url' => 'https://www.dfrgroup.it/', 'desc' => 'Advanced pumping infrastructure and water resource optimization.'],
            ],
            'community' => [
                'discord' => 'https://discord.gg/u3GzGt8K6a',
                'updates' => '/updates',
                'contact' => '/contact',
                'roadmap' => '/roadmap',
            ],
        ];

        return view('about/index', ['page' => $page]);
    }
}
