<?php

namespace App\Controllers;

use CodeIgniter\Database\BaseConnection;

class Roadmap extends BaseController
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    public static function checkAdmin(): bool
    {
        if (!function_exists('auth') || !auth()->loggedIn()) return false;
        $user = auth()->user();
        return (int) auth()->id() === 1 || ($user && $user->inGroup('admin', 'superadmin'));
    }

    public static function ensureSchema(): void
    {
        $db = db_connect();
        $forge = \Config\Database::forge();

        try {
            if (!$db->tableExists('roadmap_items')) {
                $forge->addField([
                    'id' => [
                        'type'           => 'INT',
                        'constraint'     => 11,
                        'unsigned'       => true,
                        'auto_increment' => true,
                    ],
                    'title' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 255,
                    ],
                    'description' => [
                        'type' => 'TEXT',
                        'null' => true,
                    ],
                    'status' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 32,
                        'default'    => 'planned',
                    ],
                    'category' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 64,
                        'default'    => 'gameplay',
                    ],
                    'upvotes' => [
                        'type'       => 'INT',
                        'constraint' => 11,
                        'unsigned'   => true,
                        'default'    => 0,
                    ],
                    'sort_order' => [
                        'type'       => 'INT',
                        'constraint' => 11,
                        'default'    => 0,
                    ],
                    'author_name' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 128,
                        'default'    => 'Ski Manager Team',
                    ],
                    'author_id' => [
                        'type'       => 'INT',
                        'constraint' => 11,
                        'unsigned'   => true,
                        'null'       => true,
                    ],
                    'created_at' => [
                        'type' => 'DATETIME',
                        'null' => true,
                    ],
                    'updated_at' => [
                        'type' => 'DATETIME',
                        'null' => true,
                    ],
                ]);
                $forge->addKey('id', true);
                $forge->addKey('status');
                $forge->addKey('category');
                $forge->addKey('upvotes');
                $forge->createTable('roadmap_items', true);
            }

            if (!$db->tableExists('roadmap_votes')) {
                $forge->addField([
                    'id' => [
                        'type'           => 'INT',
                        'constraint'     => 11,
                        'unsigned'       => true,
                        'auto_increment' => true,
                    ],
                    'item_id' => [
                        'type'       => 'INT',
                        'constraint' => 11,
                        'unsigned'   => true,
                    ],
                    'user_id' => [
                        'type'       => 'INT',
                        'constraint' => 11,
                        'unsigned'   => true,
                        'null'       => true,
                    ],
                    'voter_hash' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 128,
                    ],
                    'created_at' => [
                        'type' => 'DATETIME',
                        'null' => true,
                    ],
                ]);
                $forge->addKey('id', true);
                $forge->addUniqueKey(['item_id', 'voter_hash']);
                $forge->addKey('item_id');
                $forge->createTable('roadmap_votes', true);
            } else {
                try {
                    $db->query("ALTER TABLE roadmap_votes MODIFY voter_hash VARCHAR(128) NOT NULL");
                } catch (\Throwable $e) {}
            }

            // Seed default items if table is empty
            $count = $db->table('roadmap_items')->countAllResults();
            if ($count === 0) {
                $now = date('Y-m-d H:i:s');
                $defaultItems = [
                    // Completed
                    [
                        'title'       => 'Season 4 Mountain Selection & Telemetry Engine',
                        'description' => 'Introduces the community voting system for 6 candidate mountains, real-time mountain telemetry (elevation, weather, trail layouts), and Season 4 gameplay perks.',
                        'status'      => 'completed',
                        'category'    => 'gameplay',
                        'upvotes'     => 0,
                        'author_name' => 'Ski Manager Team',
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ],
                    [
                        'title'       => 'Core Performance, Database Indexing & Automated Storage Pruning',
                        'description' => 'Optimized dashboard performance with a 98% database query reduction, multi-column composite indexing, and automated log retention cleanup.',
                        'status'      => 'completed',
                        'category'    => 'quality_of_life',
                        'upvotes'     => 0,
                        'author_name' => 'Ski Manager Team',
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ],
                    [
                        'title'       => 'Markdown Content Negotiation & Bot Governance',
                        'description' => 'Native Accept: text/markdown support for AI agents, RFC 9309 crawler directives, and Content-Signals governance headers.',
                        'status'      => 'completed',
                        'category'    => 'quality_of_life',
                        'upvotes'     => 0,
                        'author_name' => 'Ski Manager Team',
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ],
                    [
                        'title'       => 'Chairlift Mechanical Degradation Curve Adjustments',
                        'description' => 'Balanced wear and tear rates during heavy blizzard weather conditions so lifts maintain realistic durability.',
                        'status'      => 'completed',
                        'category'    => 'gameplay',
                        'upvotes'     => 0,
                        'author_name' => 'Ski Manager Team',
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ],
                    // In Progress
                    [
                        'title'       => 'Interactive 3D Trail Map & Slope Fall-Line Viewer',
                        'description' => 'Real-time topographical slope fall-line rendering, grooming routes, and lift congestion visualization for resort operations.',
                        'status'      => 'in_progress',
                        'category'    => 'gameplay',
                        'upvotes'     => 0,
                        'author_name' => 'Ski Manager Team',
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ],
                    [
                        'title'       => 'Resort Alliances Cooperative Challenges',
                        'description' => 'Alliance tournaments, shared equipment leasing pools, and collaborative seasonal marketing campaigns between ski resorts.',
                        'status'      => 'in_progress',
                        'category'    => 'gameplay',
                        'upvotes'     => 0,
                        'author_name' => 'Ski Manager Team',
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ],
                    // Planned
                    [
                        'title'       => 'Mobile Companion App with Push Notifications',
                        'description' => 'Push alerts for sudden blizzards, equipment breakdown alerts, and quick daily management check-ins.',
                        'status'      => 'planned',
                        'category'    => 'mobile',
                        'upvotes'     => 0,
                        'author_name' => 'Ski Manager Team',
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ],
                    [
                        'title'       => 'Dynamic Alpine Weather & Avalanche Safety Control',
                        'description' => 'Multi-day snowstorm forecasts, snowpack accumulation dynamics, and ski patrol avalanche bombing operations.',
                        'status'      => 'planned',
                        'category'    => 'gameplay',
                        'upvotes'     => 0,
                        'author_name' => 'Ski Manager Team',
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ],
                    [
                        'title'       => 'Luxury VIP Chalets & Celebrity Guest System',
                        'description' => 'High-end luxury lodgings, fine dining Michelin-star reservations, and VIP guest reputation mechanics.',
                        'status'      => 'planned',
                        'category'    => 'economy',
                        'upvotes'     => 0,
                        'author_name' => 'Ski Manager Team',
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ],
                    [
                        'title'       => 'Night Skiing Floodlight Power Cost Balancing',
                        'description' => 'Adjust the electrical operating costs of high-powered trail floodlights during non-peak night skiing hours.',
                        'status'      => 'planned',
                        'category'    => 'economy',
                        'upvotes'     => 0,
                        'author_name' => 'Ski Manager Team',
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ],
                ];

                $db->table('roadmap_items')->insertBatch($defaultItems);
            }

            // Sync denormalized upvotes with actual count from roadmap_votes table so no fake or drifting counts exist
            $db->query("
                UPDATE roadmap_items i 
                SET upvotes = (
                    SELECT COUNT(*) 
                    FROM roadmap_votes v 
                    WHERE v.item_id = i.id
                )
            ");
        } catch (\Throwable $e) {
            log_message('error', 'Roadmap ensureSchema error: ' . $e->getMessage());
        }
    }

    private function getVoterHash(): string
    {
        if (function_exists('auth') && auth()->loggedIn()) {
            return 'u_' . auth()->id();
        }

        $ip = $this->request->getIPAddress() ?? '127.0.0.1';
        $ua = $this->request->getUserAgent() ? $this->request->getUserAgent()->getAgentString() : 'guest';
        return hash('sha256', 'guest_' . $ip . '|' . $ua);
    }

    public function index(): string
    {
        self::ensureSchema();

        $voterHash = $this->getVoterHash();
        $userId = function_exists('auth') && auth()->loggedIn() ? (int) auth()->id() : null;

        $items = $this->db->table('roadmap_items')
            ->orderBy('upvotes', 'DESC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        // Get all items this voter has upvoted
        $votedItemIds = [];
        $voteBuilder = $this->db->table('roadmap_votes');
        if ($userId !== null) {
            $voteBuilder->groupStart()
                ->where('user_id', $userId)
                ->orWhere('voter_hash', $voterHash)
            ->groupEnd();
        } else {
            $voteBuilder->where('voter_hash', $voterHash);
        }
        $votes = $voteBuilder->get()->getResultArray();
        foreach ($votes as $v) {
            $votedItemIds[(int)$v['item_id']] = true;
        }

        // Categorize by status
        $columns = [
            'planned' => [
                'title'       => 'Planned',
                'description' => 'Confirmed features queued for upcoming seasons.',
                'color'       => 'secondary',
                'badge_color' => 'badge-secondary',
                'icon'        => 'fa-solid fa-compass',
                'items'       => [],
            ],
            'in_progress' => [
                'title'       => 'In Progress',
                'description' => 'Actively under design, testing, or development.',
                'color'       => 'primary',
                'badge_color' => 'badge-primary',
                'icon'        => 'fa-solid fa-code',
                'items'       => [],
            ],
            'completed' => [
                'title'       => 'Completed / Shipped',
                'description' => 'Live in the game and available to all managers.',
                'color'       => 'success',
                'badge_color' => 'badge-success',
                'icon'        => 'fa-solid fa-circle-check',
                'items'       => [],
            ],
        ];

        foreach ($items as $item) {
            $status = $item['status'];
            if (!isset($columns[$status])) {
                $status = 'planned';
            }
            $item['voted'] = isset($votedItemIds[(int)$item['id']]);
            $columns[$status]['items'][] = $item;
        }

        $totalVotes = (int) $this->db->table('roadmap_votes')->countAllResults();

        return view('roadmap/index', [
            'columns'    => $columns,
            'isAdmin'    => self::checkAdmin(),
            'totalItems' => count($items),
            'totalVotes' => $totalVotes,
            'allItems'   => $items,
        ]);
    }

    public function vote(int $itemId)
    {
        self::ensureSchema();

        $item = $this->db->table('roadmap_items')->where('id', $itemId)->get()->getRowArray();
        if (!$item) {
            return $this->response->setJSON(['success' => false, 'message' => 'Item not found'])->setStatusCode(404);
        }

        $voterHash = $this->getVoterHash();
        $userId = function_exists('auth') && auth()->loggedIn() ? (int) auth()->id() : null;

        // Check if vote already exists for this voter
        $voteBuilder = $this->db->table('roadmap_votes')->where('item_id', $itemId);
        if ($userId !== null) {
            $voteBuilder->groupStart()
                ->where('user_id', $userId)
                ->orWhere('voter_hash', $voterHash)
            ->groupEnd();
        } else {
            $voteBuilder->where('voter_hash', $voterHash);
        }
        $existingVote = $voteBuilder->get()->getRowArray();

        if ($existingVote) {
            // Remove vote (toggle off)
            $this->db->table('roadmap_votes')
                ->where('id', (int) $existingVote['id'])
                ->delete();

            $voted = false;
        } else {
            // Add vote
            $this->db->table('roadmap_votes')->insert([
                'item_id'    => $itemId,
                'user_id'    => $userId,
                'voter_hash' => $voterHash,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $voted = true;
        }

        // Calculate REAL upvote count from roadmap_votes table (zero fake numbers)
        $newUpvotes = $this->db->table('roadmap_votes')
            ->where('item_id', $itemId)
            ->countAllResults();

        $this->db->table('roadmap_items')
            ->where('id', $itemId)
            ->update([
                'upvotes'    => $newUpvotes,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        $totalVotes = (int) $this->db->table('roadmap_votes')->countAllResults();

        return $this->response->setJSON([
            'success'    => true,
            'voted'      => $voted,
            'upvotes'    => $newUpvotes,
            'totalVotes' => $totalVotes,
        ]);
    }

    public function suggest()
    {
        self::ensureSchema();

        $title = trim($this->request->getPost('title') ?? '');
        $description = trim($this->request->getPost('description') ?? '');
        $category = trim($this->request->getPost('category') ?? 'gameplay');

        $allowedCategories = ['gameplay', 'quality_of_life', 'economy', 'mobile'];
        if (!in_array($category, $allowedCategories, true)) {
            $category = 'gameplay';
        }

        if (mb_strlen($title) < 5 || mb_strlen($title) > 100) {
            return redirect()->back()->with('error', 'Please provide a feature title between 5 and 100 characters.');
        }

        if (mb_strlen($description) < 10 || mb_strlen($description) > 1000) {
            return redirect()->back()->with('error', 'Please provide a feature description between 10 and 1000 characters.');
        }

        $authorName = 'Community Player';
        $authorId = null;
        if (function_exists('auth') && auth()->loggedIn()) {
            $user = auth()->user();
            $authorName = $user ? ($user->username ?? 'Player') : 'Player';
            $authorId = (int) auth()->id();
        }

        $voterHash = $this->getVoterHash();

        // Anti-spam check: prevent multiple submissions within 60 seconds
        $recentTime = date('Y-m-d H:i:s', time() - 60);
        $recentQuery = $this->db->table('roadmap_items')->where('created_at >', $recentTime);
        if ($authorId !== null) {
            $recentQuery->where('author_id', $authorId);
        } else {
            $recentQuery->where('author_name', $authorName);
        }
        if ($recentQuery->countAllResults() > 0) {
            return redirect()->to('/roadmap')->with('error', 'Please wait a moment before submitting another feature idea.');
        }

        $now = date('Y-m-d H:i:s');
        $this->db->transStart();

        $this->db->table('roadmap_items')->insert([
            'title'       => $title,
            'description' => $description,
            'status'      => 'planned',
            'category'    => $category,
            'upvotes'     => 1,
            'author_name' => $authorName,
            'author_id'   => $authorId,
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);

        $itemId = $this->db->insertID();

        $this->db->table('roadmap_votes')->insert([
            'item_id'    => $itemId,
            'user_id'    => $authorId,
            'voter_hash' => $voterHash,
            'created_at' => $now,
        ]);

        $this->db->transComplete();

        return redirect()->to('/roadmap')->with('success', 'Thank you! Your feature idea has been submitted and added to the community roadmap.');
    }

    public function updateStatus()
    {
        if (!self::checkAdmin()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized'])->setStatusCode(403);
        }

        $itemId = (int) $this->request->getPost('id');
        $status = trim($this->request->getPost('status') ?? '');

        $allowed = ['planned', 'in_progress', 'completed'];
        if (!in_array($status, $allowed, true)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid status'])->setStatusCode(400);
        }

        $this->db->table('roadmap_items')
            ->where('id', $itemId)
            ->update([
                'status'     => $status,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        return $this->response->setJSON(['success' => true]);
    }

    public function deleteItem(int $itemId)
    {
        if (!self::checkAdmin()) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized'])->setStatusCode(403);
            }
            return redirect()->to('/roadmap')->with('error', 'Unauthorized.');
        }

        $this->db->transStart();
        $this->db->table('roadmap_votes')->where('item_id', $itemId)->delete();
        $this->db->table('roadmap_items')->where('id', $itemId)->delete();
        $this->db->transComplete();

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => true]);
        }

        return redirect()->to('/roadmap')->with('success', 'Roadmap item removed.');
    }
}
