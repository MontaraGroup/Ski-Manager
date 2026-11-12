<?php

namespace App\Controllers;

use App\Libraries\RoadmapRewards;
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

    /** Statuses in page order, with how the page describes them */
    private const STATUSES = [
        'in_progress' => ['In progress', 'Being designed, built or tested now.', 'fa-code'],
        'planned'     => ['Planned', 'Accepted and queued for a coming update.', 'fa-compass'],
        'suggested'   => ['Ideas', 'Suggested by players. The most-voted ones get reviewed first.', 'fa-lightbulb'],
        'completed'   => ['Shipped', 'Live in the game.', 'fa-circle-check'],
    ];

    /** Signed-in player who may vote and suggest (profile finished); null otherwise */
    private function playerId(): ?int
    {
        if (!function_exists('auth') || !auth()->loggedIn()) return null;
        $userId = (int) auth()->id();
        $finance = $this->db->table('player_finances')->select('profile_completed')->where('user_id', $userId)->get()->getRowArray();
        return !empty($finance['profile_completed']) ? $userId : null;
    }

    /** The name shown on a card: the author's current username, or who added it */
    private static function authorLabel(array $item, array $usernames): string
    {
        $id = (int) ($item['author_id'] ?? 0);
        if ($id > 0 && isset($usernames[$id])) return $usernames[$id];
        return ($item['author_name'] ?? '') === 'Ski Manager Team' ? 'Ski Manager team' : 'Community';
    }

    public function index(): string
    {
        self::ensureSchema();
        helper('notify');

        $userId = $this->playerId();
        $loggedIn = function_exists('auth') && auth()->loggedIn();

        $items = $this->db->table('roadmap_items')->orderBy('upvotes', 'DESC')->orderBy('id', 'ASC')->get()->getResultArray();

        $voted = [];
        if ($loggedIn) {
            $rows = $this->db->table('roadmap_votes')->select('item_id')->where('user_id', (int) auth()->id())->get()->getResultArray();
            $voted = array_fill_keys(array_map('intval', array_column($rows, 'item_id')), true);
        }

        $authorIds = array_values(array_unique(array_filter(array_map(fn($i) => (int) ($i['author_id'] ?? 0), $items))));
        $usernames = $authorIds
            ? array_column($this->db->table('users')->select('id, username')->whereIn('id', $authorIds)->get()->getResultArray(), 'username', 'id')
            : [];

        $columns = [];
        foreach (self::STATUSES as $key => [$title, $desc, $icon]) {
            $columns[$key] = ['status' => $key, 'title' => $title, 'description' => $desc, 'icon' => $icon, 'items' => []];
        }
        foreach ($items as $item) {
            $status = isset($columns[$item['status']]) ? $item['status'] : 'planned';
            $columns[$status]['items'][] = [
                'id'          => (int) $item['id'],
                'title'       => (string) $item['title'],
                'description' => (string) ($item['description'] ?? ''),
                'status'      => $status,
                'category'    => (string) ($item['category'] ?? 'gameplay'),
                'upvotes'     => (int) ($item['upvotes'] ?? 0),
                'author'      => self::authorLabel($item, $usernames),
                'mine'        => $loggedIn && (int) ($item['author_id'] ?? 0) === (int) auth()->id(),
                'voted'       => isset($voted[(int) $item['id']]),
                'updatesUrl'  => !empty($item['updates_url']) ? (string) $item['updates_url'] : null,
                'created_at'  => (string) ($item['created_at'] ?? ''),
            ];
        }

        // Visiting the page clears the "New" mark in the menu
        if ($loggedIn && empty(auth()->user()->roadmap_seen_at) && $this->db->fieldExists('roadmap_seen_at', 'users')) {
            $this->db->table('users')->where('id', (int) auth()->id())->update(['roadmap_seen_at' => date('Y-m-d H:i:s')]);
        }

        $eligible = $userId !== null && RoadmapRewards::eligible($userId);
        $releases = [];
        if (self::checkAdmin() && $this->db->tableExists('updates')) {
            foreach ($this->db->table('updates')->select('id, version, title')->orderBy('released_at', 'DESC')->limit(30)->get()->getResultArray() as $u) {
                $releases[] = ['url' => '/updates#update-' . (int) $u['id'], 'label' => 'v' . $u['version'] . ' ' . ($u['title'] ?? '')];
            }
        }

        $page = [
            'isAdmin' => self::checkAdmin(),
            'stats' => [
                'totalItems' => count($items),
                'totalVotes' => (int) $this->db->table('roadmap_votes')->countAllResults(),
            ],
            'columns' => array_values($columns),
            'me' => [
                'signedIn'         => $loggedIn,
                'canVote'          => $userId !== null,
                'canSuggest'       => $userId !== null && !\App\Controllers\Demo::isDemoUser(),
                'earnsRewards'     => $eligible,
                'weeklyRewardDone' => $eligible && RoadmapRewards::weeklyRewardTaken($userId),
                'suggestionsLeft'  => $userId !== null ? RoadmapRewards::suggestionsLeft($userId) : 0,
            ],
            'rewards' => [
                'voteWeekly'   => RoadmapRewards::VOTE_WEEKLY_GENEPIS,
                'ideaShipped'  => RoadmapRewards::IDEA_SHIPPED_GENEPIS,
                'perWeek'      => RoadmapRewards::SUGGESTIONS_PER_WEEK,
            ],
            'releases' => $releases,
        ];

        return view('roadmap/index', ['page' => $page]);
    }

    public function vote(int $itemId)
    {
        self::ensureSchema();
        helper('notify');

        $userId = $this->playerId();
        if ($userId === null) {
            return $this->response->setJSON(['success' => false, 'message' => 'Sign in and finish setting up your resort to vote.'])->setStatusCode(401);
        }

        $item = $this->db->table('roadmap_items')->where('id', $itemId)->get()->getRowArray();
        if (!$item) {
            return $this->response->setJSON(['success' => false, 'message' => 'Item not found'])->setStatusCode(404);
        }
        if ($item['status'] === 'completed') {
            return $this->response->setJSON(['success' => false, 'message' => 'This idea has already shipped.'])->setStatusCode(400);
        }

        $existing = $this->db->table('roadmap_votes')->where('item_id', $itemId)->where('user_id', $userId)->get()->getRowArray();
        $reward = 0;
        if ($existing) {
            $this->db->table('roadmap_votes')->where('id', (int) $existing['id'])->delete();
            $voted = false;
        } else {
            $this->db->table('roadmap_votes')->insert([
                'item_id' => $itemId, 'user_id' => $userId, 'voter_hash' => 'u_' . $userId, 'created_at' => date('Y-m-d H:i:s'),
            ]);
            $voted = true;
            $reward = RoadmapRewards::onVote($userId, $itemId);
        }

        // The real count from roadmap_votes, so the number can't drift
        $upvotes = $this->db->table('roadmap_votes')->where('item_id', $itemId)->countAllResults();
        $this->db->table('roadmap_items')->where('id', $itemId)->update(['upvotes' => $upvotes]);

        return $this->response->setJSON([
            'success'    => true,
            'voted'      => $voted,
            'upvotes'    => $upvotes,
            'totalVotes' => (int) $this->db->table('roadmap_votes')->countAllResults(),
            'reward'     => $reward,
            'message'    => $reward > 0 ? "Thanks for voting. You earned {$reward} Génépis this week." : null,
        ]);
    }

    public function suggest()
    {
        self::ensureSchema();

        $userId = $this->playerId();
        if ($userId === null || \App\Controllers\Demo::isDemoUser()) {
            return redirect()->to('/roadmap')->with('error', 'Sign in with a full account to suggest an idea.');
        }

        $title = trim($this->request->getPost('title') ?? '');
        $description = trim($this->request->getPost('description') ?? '');
        $category = trim($this->request->getPost('category') ?? 'gameplay');
        if (!in_array($category, ['gameplay', 'quality_of_life', 'economy', 'mobile'], true)) {
            $category = 'gameplay';
        }

        if (mb_strlen($title) < 5 || mb_strlen($title) > 100) {
            return redirect()->to('/roadmap')->with('error', 'The title needs 5 to 100 characters.');
        }
        if (mb_strlen($description) < 10 || mb_strlen($description) > 1000) {
            return redirect()->to('/roadmap')->with('error', 'The description needs 10 to 1000 characters.');
        }
        if (RoadmapRewards::suggestionsLeft($userId) <= 0) {
            return redirect()->to('/roadmap')->with('error', 'You can suggest ' . RoadmapRewards::SUGGESTIONS_PER_WEEK . ' ideas a week. Try again in a few days.');
        }

        $now = date('Y-m-d H:i:s');
        $this->db->transStart();
        $this->db->table('roadmap_items')->insert([
            'title'       => $title,
            'description' => $description,
            'status'      => 'suggested',
            'category'    => $category,
            'upvotes'     => 1,
            'author_name' => (string) (auth()->user()->username ?? 'Player'),
            'author_id'   => $userId,
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);
        $itemId = (int) $this->db->insertID();
        $this->db->table('roadmap_votes')->insert([
            'item_id' => $itemId, 'user_id' => $userId, 'voter_hash' => 'u_' . $userId, 'created_at' => $now,
        ]);
        $this->db->transComplete();

        // The author's own vote counts toward voting achievements but earns no Génépis
        RoadmapRewards::onVote($userId, $itemId, false);

        return redirect()->to('/roadmap#item-' . $itemId)->with('success', 'Your idea is in the Ideas list. If it ships, you earn ' . RoadmapRewards::IDEA_SHIPPED_GENEPIS . ' Génépis.');
    }

    public function updateStatus()
    {
        if (!self::checkAdmin()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized'])->setStatusCode(403);
        }
        helper('notify');

        $itemId = (int) $this->request->getPost('id');
        $status = trim($this->request->getPost('status') ?? '');
        if (!isset(self::STATUSES[$status])) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid status'])->setStatusCode(400);
        }
        $item = $this->db->table('roadmap_items')->where('id', $itemId)->get()->getRowArray();
        if (!$item) {
            return $this->response->setJSON(['success' => false, 'message' => 'Item not found'])->setStatusCode(404);
        }

        $update = ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')];
        $updatesUrl = trim((string) $this->request->getPost('updates_url'));
        if ($updatesUrl !== '') {
            if (!preg_match('~^/updates#update-\d+$~', $updatesUrl)) {
                return $this->response->setJSON(['success' => false, 'message' => 'Invalid release link'])->setStatusCode(400);
            }
            $update['updates_url'] = $updatesUrl;
        }
        $this->db->table('roadmap_items')->where('id', $itemId)->update($update);

        $previous = (string) $item['status'];
        $item = array_merge($item, $update);
        if ($previous === 'suggested' && in_array($status, ['planned', 'in_progress', 'completed'], true)) {
            RoadmapRewards::onAccepted($item);
        }
        if ($status === 'completed' && $previous !== 'completed') {
            RoadmapRewards::onShipped($item);
        }

        return $this->response->setJSON(['success' => true, 'status' => $status, 'updatesUrl' => $item['updates_url'] ?? null]);
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
