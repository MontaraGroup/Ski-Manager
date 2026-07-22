<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UpdateSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'version'      => 'v1.3.6',
                'title'        => 'Deployment Automation & System Resilience',
                'description'  => 'Integrated seamless GitHub webhook CI/CD deployment pipelines, automated writable cache directory recovery, and fixed session persistence boundaries.',
                'release_date' => 'July 22, 2026',
                'type'         => 'Major',
                'is_latest'    => 1,
                'content_json' => json_encode([
                    'features'     => ['<strong>GitHub Webhook Auto-Deploy:</strong> Pushing to `main` now triggers instant server-side updates, database migrations, and cache clearing automatically.'],
                    'improvements' => ['<strong>Automated Writable Safeguards:</strong> Deployment and initialization scripts now self-heal missing cache, session, and log directories with strict permission enforcement.'],
                    'bug_fixes'    => ['Resolved critical `CacheException` 500 errors caused by missing `writable/cache` directory states during cleanups.']
                ])
            ],
            [
                'version'      => 'v1.3.5',
                'title'        => 'Authentication Infrastructure Overhaul',
                'description'  => 'A massive under-the-hood refactor to isolate and stabilize user registration pipelines, resolving container session context anomalies and enhancing visual verification entry profiles.',
                'release_date' => 'July 2, 2026',
                'type'         => 'Major',
                'is_latest'    => 0,
                'content_json' => json_encode([
                    'features'     => [
                        '<strong>Tactile DaisyUI OTP Field:</strong> Replaced the old standard text box with a modern multi-segmented 6-digit split interface constraint array matching token layouts.',
                        '<strong>Session-Less Activation Bypass:</strong> Introduced a standalone transactional background recovery terminal block for heavily blocked system users.'
                    ],
                    'improvements' => [
                        '<strong>Fault-Tolerant Action Pipelines:</strong> Submissions are now dynamically insulated from failing SMTP thread exceptions, preventing layout thread terminations.',
                        '<strong>Secure Cookie Synchronizations:</strong> Shifted global cookie persistence properties to explicit HTTPS secure flags to ensure seamless validation transport across isolated host containers.'
                    ],
                    'bug_fixes'    => [
                        'Resolved a critical `PageNotFoundException` (404 Error) thrown during email validation handshakes due to tracking state cookie loss.',
                        'Fixed a native Shield `LogicException` rule boundary where lingering active workflow states blocked initial logins upon verification execution.'
                    ]
                ])
            ],
            [
                'version'      => 'v1.3.1',
                'title'        => 'Operations & Support Overhaul',
                'description'  => 'Ski Patrol deployment arrives on the mountain, completely shifting the resort vibe alongside upgraded Admin chat infrastructure and unmatched system aura.',
                'release_date' => 'June 28, 2026',
                'type'         => 'Minor',
                'is_latest'    => 0,
                'content_json' => json_encode([
                    'features'     => ['<strong>Ski Patrol:</strong> Unlockable in the hiring shop to balance and maintain slope safety parameters.'],
                    'improvements' => [
                        '<strong>Admin Chat Modifiers:</strong> Async support console elements can now edit and unsend inline messages natively.',
                        '<strong>Aura UI Components:</strong> Deployed DaisyUI v5 animated border glows, gold styles, and rainbow lighting states across prominent dashboard panels.'
                    ],
                    'bug_fixes'    => ['Resolved UI rendering boundary and layout schema mismatches for patrol staff assets.']
                ])
            ],
            [
                'version'      => 'v1.3',
                'title'        => 'Polish & Quality-of-Life',
                'description'  => 'A round of refinements across the site plus important fixes to daily bonuses and the tutorial.',
                'release_date' => 'June 14, 2026',
                'type'         => 'Minor',
                'is_latest'    => 0,
                'content_json' => json_encode([])
            ],
            [
                'version'      => 'v1.2',
                'title'        => 'UI Overhaul & New Features',
                'description'  => 'Major UI improvements across all pages, support chat, voting system, compliance hub, equipment shop redesign, and dozens of fixes.',
                'release_date' => 'June 10, 2026',
                'type'         => 'Major',
                'is_latest'    => 0,
                'content_json' => json_encode([])
            ],
            [
                'version'      => 'v1.1',
                'title'        => 'Economy Rebalance & Systems Overhaul',
                'description'  => 'Major economy rebalance, improved grooming/snowmaking/weather/finances, PWA support, and dozens of bug fixes.',
                'release_date' => 'June 9, 2026',
                'type'         => 'Major',
                'is_latest'    => 0,
                'content_json' => json_encode([])
            ],
            [
                'version'      => 'v1.0',
                'title'        => 'Season 1 Launch Update',
                'description'  => 'Major update rebuilding the trail map, admin panel, unit system, and adding feature flags.',
                'release_date' => 'June 7, 2026',
                'type'         => 'Major',
                'is_latest'    => 0,
                'content_json' => json_encode([])
            ],
            [
                'version'      => 'v0.5',
                'title'        => 'Season 1: Park City',
                'description'  => 'Season 1 officially launches June 7, 2026 at 12:00 AM Eastern Time. All players start fresh on Park City Mountain Resort.',
                'release_date' => 'June 7, 2026',
                'type'         => 'Major',
                'is_latest'    => 0,
                'content_json' => json_encode([])
            ],
            [
                'version'      => 'v0.4',
                'title'        => 'Bug Fixes & Dashboard Improvements',
                'description'  => 'Bug fixes, dashboard improvements, and developer tooling.',
                'release_date' => 'June 4, 2026',
                'type'         => 'Minor',
                'is_latest'    => 0,
                'content_json' => json_encode([])
            ],
            [
                'version'      => 'v0.3',
                'title'        => 'Polish & Compliance Update',
                'description'  => 'Cookie consent, legal pages, analytics setup, cross-system integration, and quality-of-life improvements.',
                'release_date' => 'June 3, 2026',
                'type'         => 'Minor',
                'is_latest'    => 0,
                'content_json' => json_encode([])
            ],
            [
                'version'      => 'v0.2',
                'title'        => 'Resource Management Update',
                'description'  => 'Major update bringing resource management, terrain parks, and quality-of-life improvements to Ski Manager.',
                'release_date' => 'June 1, 2026',
                'type'         => 'Major',
                'is_latest'    => 0,
                'content_json' => json_encode([])
            ],
            [
                'version'      => 'v0.1',
                'title'        => 'Complete Rebuild',
                'description'  => 'Ski Manager has been completely rebuilt from the ground up with a modern technology stack and dramatically expanded gameplay.',
                'release_date' => 'May 27, 2026',
                'type'         => 'Major',
                'is_latest'    => 0,
                'content_json' => json_encode([])
            ]
        ];

        $this->db->table('updates')->insertBatch($data);
    }
}
