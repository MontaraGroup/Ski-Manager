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

            // Sync denormalized upvotes with actual count from roadmap_votes table so no fake or drifting counts exist.
            // A main idea counts each player once across itself and the ideas merged into it; merged ideas count 0.
            if ($db->fieldExists('merged_into', 'roadmap_items')) {
                $db->query("
                    UPDATE roadmap_items i
                    LEFT JOIN (
                        SELECT COALESCE(g.merged_into, g.id) AS root_id, COUNT(DISTINCT v.voter_hash) AS n
                        FROM roadmap_votes v
                        JOIN roadmap_items g ON g.id = v.item_id
                        GROUP BY root_id
                    ) c ON c.root_id = i.id
                    SET i.upvotes = COALESCE(c.n, 0)
                ");
            } else {
                $db->query("
                    UPDATE roadmap_items i
                    SET upvotes = (
                        SELECT COUNT(*)
                        FROM roadmap_votes v
                        WHERE v.item_id = i.id
                    )
                ");
            }
        } catch (\Throwable $e) {
            log_message('error', 'Roadmap ensureSchema error: ' . $e->getMessage());
        }
    }

    /** Where players join the in-game beta program, linked from "In beta" items */
    public const BETA_URL = '/beta';

    public const COMMENT_MAX = 500;
    public const COMMENTS_PER_DAY = 10;
    public const COMMENT_EDIT_MINUTES = 15;

    /** Statuses in page order, with how the page describes them */
    private const STATUSES = [
        'in_beta'     => ['In beta', 'Playable now in the beta program, before it ships to everyone.', 'fa-flask'],
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

    /** Ideas merged into this one */
    private static function mergedIdeas(int $mainId): array
    {
        $db = db_connect();
        if (!$db->fieldExists('merged_into', 'roadmap_items')) return [];
        return $db->table('roadmap_items')->where('merged_into', $mainId)->get()->getResultArray();
    }

    /** The main idea's id followed by the ids of the ideas merged into it */
    private static function groupIds(int $mainId): array
    {
        return array_merge([$mainId], array_map(fn($m) => (int) $m['id'], self::mergedIdeas($mainId)));
    }

    /**
     * Store the real count from roadmap_votes, so the number can't drift. A player who voted on
     * several ideas of a group counts once. Returns the new count.
     */
    private static function recount(int $mainId): int
    {
        $db = db_connect();
        $row = $db->table('roadmap_votes')->select('COUNT(DISTINCT voter_hash) AS n')
            ->whereIn('item_id', self::groupIds($mainId))->get()->getRowArray();
        $upvotes = (int) ($row['n'] ?? 0);
        $db->table('roadmap_items')->where('id', $mainId)->update(['upvotes' => $upvotes]);
        return $upvotes;
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
        return view('roadmap/index', ['page' => $this->pageData()]);
    }

    /** What the page shows: the web view and the app API (Api\V1\App) both use it */
    protected function pageData(): array
    {
        self::ensureSchema();
        helper('notify');

        $userId = $this->playerId();
        $loggedIn = function_exists('auth') && auth()->loggedIn();

        $all = $this->db->table('roadmap_items')->orderBy('upvotes', 'DESC')->orderBy('id', 'ASC')->get()->getResultArray();

        // Ideas merged into another one are shown inside the main idea's card, not as cards of their own
        $items = [];
        $mergedInto = [];
        foreach ($all as $row) {
            if (!empty($row['merged_into'])) $mergedInto[(int) $row['merged_into']][] = $row;
            else $items[] = $row;
        }

        $voted = [];
        if ($loggedIn) {
            $rows = $this->db->table('roadmap_votes')->select('item_id')->where('user_id', (int) auth()->id())->get()->getResultArray();
            $voted = array_fill_keys(array_map('intval', array_column($rows, 'item_id')), true);
        }

        $authorIds = array_values(array_unique(array_filter(array_map(fn($i) => (int) ($i['author_id'] ?? 0), $all))));
        $usernames = $authorIds
            ? array_column($this->db->table('users')->select('id, username')->whereIn('id', $authorIds)->get()->getResultArray(), 'username', 'id')
            : [];
        $me = $loggedIn ? (int) auth()->id() : 0;

        // Comment counts per idea; a main idea's count includes the ideas merged into it
        $commentCounts = [];
        if ($this->db->tableExists('roadmap_comments')) {
            foreach ($this->db->table('roadmap_comments')->select('item_id, COUNT(*) AS n')->groupBy('item_id')->get()->getResultArray() as $row) {
                $commentCounts[(int) $row['item_id']] = (int) $row['n'];
            }
        }

        $columns = [];
        foreach (self::STATUSES as $key => [$title, $desc, $icon]) {
            $columns[$key] = ['status' => $key, 'title' => $title, 'description' => $desc, 'icon' => $icon, 'items' => []];
            // Items in beta can be tried now: the column links to the in-game beta program
            if ($key === 'in_beta') $columns[$key]['link'] = ['url' => self::BETA_URL, 'label' => 'Try it in the beta program'];
        }
        foreach ($items as $item) {
            $status = isset($columns[$item['status']]) ? $item['status'] : 'planned';
            $id = (int) $item['id'];
            $group = $mergedInto[$id] ?? [];
            $merged = [];
            $mine = $loggedIn && (int) ($item['author_id'] ?? 0) === $me;
            $hasVoted = isset($voted[$id]);
            $comments = $commentCounts[$id] ?? 0;
            foreach ($group as $m) {
                $mMine = $loggedIn && (int) ($m['author_id'] ?? 0) === $me;
                $merged[] = [
                    'id'          => (int) $m['id'],
                    'title'       => (string) $m['title'],
                    'description' => (string) ($m['description'] ?? ''),
                    'author'      => self::authorLabel($m, $usernames),
                    'mine'        => $mMine,
                ];
                $mine = $mine || $mMine;
                $hasVoted = $hasVoted || isset($voted[(int) $m['id']]);
                $comments += $commentCounts[(int) $m['id']] ?? 0;
            }
            $columns[$status]['items'][] = [
                'id'          => $id,
                'title'       => (string) $item['title'],
                'description' => (string) ($item['description'] ?? ''),
                'status'      => $status,
                'category'    => (string) ($item['category'] ?? 'gameplay'),
                'upvotes'     => (int) ($item['upvotes'] ?? 0),
                'author'      => self::authorLabel($item, $usernames),
                // The player wrote this idea or one merged into it
                'mine'        => $mine,
                // A vote on any idea in the group counts as a vote for the main idea
                'voted'       => $hasVoted,
                'updatesUrl'  => !empty($item['updates_url']) ? (string) $item['updates_url'] : null,
                'created_at'  => (string) ($item['created_at'] ?? ''),
                'merged'      => $merged,
                'comments'    => $comments,
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
                'canComment'       => $userId !== null && !\App\Controllers\Demo::isDemoUser(),
                'isAdmin'          => self::checkAdmin(),
                'earnsRewards'     => $eligible,
                'weeklyRewardDone' => $eligible && RoadmapRewards::weeklyRewardTaken($userId),
                'suggestionsLeft'  => $userId !== null ? RoadmapRewards::suggestionsLeft($userId) : 0,
            ],
            'comments' => [
                'maxLength'   => self::COMMENT_MAX,
                'perDay'      => self::COMMENTS_PER_DAY,
                'editMinutes' => self::COMMENT_EDIT_MINUTES,
            ],
            'rewards' => [
                'voteWeekly'   => RoadmapRewards::VOTE_WEEKLY_GENEPIS,
                'ideaShipped'  => RoadmapRewards::IDEA_SHIPPED_GENEPIS,
                'perWeek'      => RoadmapRewards::SUGGESTIONS_PER_WEEK,
            ],
            'releases' => $releases,
        ];

        return $page;
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
        // A vote on a merged idea goes to the idea it was merged into
        if ($item && !empty($item['merged_into'])) {
            $item = $this->db->table('roadmap_items')->where('id', (int) $item['merged_into'])->get()->getRowArray();
        }
        if (!$item) {
            return $this->response->setJSON(['success' => false, 'message' => 'Item not found'])->setStatusCode(404);
        }
        if ($item['status'] === 'completed') {
            return $this->response->setJSON(['success' => false, 'message' => 'This idea has already shipped.'])->setStatusCode(400);
        }
        $itemId = (int) $item['id'];

        // A player has one vote per group: a vote on the main idea or any idea merged into it
        $existing = $this->db->table('roadmap_votes')->select('id')
            ->whereIn('item_id', self::groupIds($itemId))->where('user_id', $userId)->get()->getResultArray();
        $reward = 0;
        if ($existing) {
            $this->db->table('roadmap_votes')->whereIn('id', array_map('intval', array_column($existing, 'id')))->delete();
            $voted = false;
        } else {
            $this->db->table('roadmap_votes')->insert([
                'item_id' => $itemId, 'user_id' => $userId, 'voter_hash' => 'u_' . $userId, 'created_at' => date('Y-m-d H:i:s'),
            ]);
            $voted = true;
            $reward = RoadmapRewards::onVote($userId, $itemId);
        }

        $upvotes = self::recount($itemId);

        return $this->response->setJSON([
            'success'    => true,
            'id'         => $itemId,
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
        if (!empty($item['merged_into'])) {
            return $this->response->setJSON(['success' => false, 'message' => 'This idea is merged. Change the main idea instead.'])->setStatusCode(400);
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

        // Merged ideas follow the main idea's status, so their authors' achievements count it
        $merged = self::mergedIdeas($itemId);
        if ($merged) {
            $this->db->table('roadmap_items')->where('merged_into', $itemId)->update(['status' => $status, 'updated_at' => $update['updated_at']]);
        }

        $previous = (string) $item['status'];
        $item = array_merge($item, $update);
        if ($previous === 'suggested' && in_array($status, ['planned', 'in_progress', 'in_beta', 'completed'], true)) {
            RoadmapRewards::onAccepted($item);
            $told = [(int) ($item['author_id'] ?? 0) => true];
            foreach ($merged as $m) {
                $authorId = (int) ($m['author_id'] ?? 0);
                if ($authorId <= 0 || isset($told[$authorId])) continue;
                $told[$authorId] = true;
                RoadmapRewards::onAccepted(['id' => $itemId, 'title' => $item['title'], 'author_id' => $authorId]);
            }
        }
        if ($status === 'completed' && $previous !== 'completed') {
            RoadmapRewards::onShipped($item, $merged);
        }

        return $this->response->setJSON(['success' => true, 'status' => $status, 'updatesUrl' => $item['updates_url'] ?? null]);
    }

    /**
     * Admin: merge an idea into another one about the same thing. The merged idea is shown inside
     * the main idea's card, its votes count toward the main idea (once per player), it takes the
     * main idea's status, and its author gets the same credit when the main idea ships. Ideas
     * already merged into the merged idea move to the main idea, so groups stay one level deep.
     */
    public function merge()
    {
        if (!self::checkAdmin()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized'])->setStatusCode(403);
        }
        helper('notify');

        $id = (int) $this->request->getPost('id');
        $intoId = (int) $this->request->getPost('into');
        $idea = $this->db->table('roadmap_items')->where('id', $id)->get()->getRowArray();
        $main = $this->db->table('roadmap_items')->where('id', $intoId)->get()->getRowArray();
        if (!$idea || !$main) {
            return $this->response->setJSON(['success' => false, 'message' => 'Item not found'])->setStatusCode(404);
        }
        if ($id === $intoId || !empty($idea['merged_into']) || !empty($main['merged_into'])) {
            return $this->response->setJSON(['success' => false, 'message' => 'Pick two ideas that are not merged already.'])->setStatusCode(400);
        }

        $status = (string) $main['status'];
        $now = date('Y-m-d H:i:s');
        $moving = array_merge([$idea], self::mergedIdeas($id));

        $this->db->transStart();
        foreach ($moving as $m) {
            $this->db->table('roadmap_items')->where('id', (int) $m['id'])->update([
                'merged_into'     => $intoId,
                // An idea moving from another group keeps the status it had before its first merge
                'premerge_status' => $m['premerge_status'] ?? $m['status'],
                'merged_at'       => $now,
                'status'          => $status,
                'updated_at'      => $now,
            ]);
        }
        $this->db->transComplete();
        if (!$this->db->transStatus()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Not merged. Try again.'])->setStatusCode(500);
        }

        self::recount($intoId);

        $link = !empty($main['updates_url']) ? (string) $main['updates_url'] : '/roadmap#item-' . $intoId;
        $credited = [(int) ($main['author_id'] ?? 0) => true];
        foreach ($moving as $m) {
            RoadmapRewards::onMerged($m, $main);
            // Merged into an idea that already shipped: its author is credited now, once
            $authorId = (int) ($m['author_id'] ?? 0);
            if ($status === 'completed' && $authorId > 0 && !isset($credited[$authorId])) {
                $credited[$authorId] = true;
                RoadmapRewards::creditShipped($authorId, (int) $m['id'], (string) $main['title'], $link);
            }
        }

        return $this->response->setJSON(['success' => true]);
    }

    /**
     * Admin: take a merged idea out of its group again. It gets back the status it had before the
     * merge and its own votes. Rewards already paid stay paid.
     */
    public function unmerge()
    {
        if (!self::checkAdmin()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized'])->setStatusCode(403);
        }

        $id = (int) $this->request->getPost('id');
        $idea = $this->db->table('roadmap_items')->where('id', $id)->get()->getRowArray();
        if (!$idea || empty($idea['merged_into'])) {
            return $this->response->setJSON(['success' => false, 'message' => 'This idea is not merged.'])->setStatusCode(400);
        }

        $this->splitOut($idea);
        self::recount((int) $idea['merged_into']);

        return $this->response->setJSON(['success' => true]);
    }

    private function splitOut(array $idea): void
    {
        $status = (string) ($idea['premerge_status'] ?? '');
        $this->db->table('roadmap_items')->where('id', (int) $idea['id'])->update([
            'merged_into'     => null,
            'premerge_status' => null,
            'merged_at'       => null,
            'status'          => isset(self::STATUSES[$status]) ? $status : (string) $idea['status'],
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);
        self::recount((int) $idea['id']);
    }

    /** The idea a comment thread belongs to: a merged idea's thread is its main idea's */
    private function threadRoot(int $itemId): ?array
    {
        $item = $this->db->table('roadmap_items')->where('id', $itemId)->get()->getRowArray();
        if ($item && !empty($item['merged_into'])) {
            $item = $this->db->table('roadmap_items')->where('id', (int) $item['merged_into'])->get()->getRowArray();
        }
        return $item ?: null;
    }

    /** Signed-in, finished, non-demo player who may comment; null otherwise */
    private function commenterId(): ?int
    {
        $userId = $this->playerId();
        return $userId !== null && !\App\Controllers\Demo::isDemoUser() ? $userId : null;
    }

    private function commentJson(array $c, array $usernames, int $me, bool $admin): array
    {
        $mine = $me > 0 && (int) $c['user_id'] === $me;
        return [
            'id'      => (int) $c['id'],
            'author'  => $usernames[(int) $c['user_id']] ?? 'Player',
            'body'    => (string) $c['body'],
            'created' => (string) $c['created_at'],
            'edited'  => !empty($c['updated_at']),
            'mine'    => $mine,
            'canEdit' => $mine && strtotime((string) $c['created_at']) > time() - self::COMMENT_EDIT_MINUTES * 60,
            'canDelete' => $mine || $admin,
        ];
    }

    /** GET: the comment thread of an idea (and of the ideas merged into it), oldest first */
    public function comments(int $itemId)
    {
        self::ensureSchema();
        $item = $this->threadRoot($itemId);
        if (!$item) {
            return $this->response->setJSON(['success' => false, 'message' => 'Item not found'])->setStatusCode(404);
        }
        $rows = $this->db->table('roadmap_comments')->whereIn('item_id', self::groupIds((int) $item['id']))
            ->orderBy('created_at', 'ASC')->orderBy('id', 'ASC')->get()->getResultArray();
        $userIds = array_values(array_unique(array_map(fn($c) => (int) $c['user_id'], $rows)));
        $usernames = $userIds
            ? array_column($this->db->table('users')->select('id, username')->whereIn('id', $userIds)->get()->getResultArray(), 'username', 'id')
            : [];
        $me = function_exists('auth') && auth()->loggedIn() ? (int) auth()->id() : 0;
        $admin = self::checkAdmin();

        return $this->response->setJSON([
            'success'  => true,
            'comments' => array_map(fn($c) => $this->commentJson($c, $usernames, $me, $admin), $rows),
        ]);
    }

    /** POST body: add a comment to an idea (a merged idea's comment goes to its main idea) */
    public function addComment(int $itemId)
    {
        self::ensureSchema();
        helper('notify');

        $userId = $this->commenterId();
        if ($userId === null) {
            return $this->response->setJSON(['success' => false, 'message' => 'Sign in with a full account and finish setting up your resort to comment.'])->setStatusCode(401);
        }
        $item = $this->threadRoot($itemId);
        if (!$item) {
            return $this->response->setJSON(['success' => false, 'message' => 'Item not found'])->setStatusCode(404);
        }
        $body = trim((string) $this->request->getPost('body'));
        if ($body === '' || mb_strlen($body) > self::COMMENT_MAX) {
            return $this->response->setJSON(['success' => false, 'message' => 'A comment needs 1 to ' . self::COMMENT_MAX . ' characters.'])->setStatusCode(400);
        }
        $today = $this->db->table('roadmap_comments')->where('user_id', $userId)
            ->where('created_at >', date('Y-m-d H:i:s', time() - 86400))->countAllResults();
        if ($today >= self::COMMENTS_PER_DAY) {
            return $this->response->setJSON(['success' => false, 'message' => 'You can post ' . self::COMMENTS_PER_DAY . ' comments a day. Try again tomorrow.'])->setStatusCode(429);
        }

        $mainId = (int) $item['id'];
        $now = date('Y-m-d H:i:s');
        $this->db->table('roadmap_comments')->insert(['item_id' => $mainId, 'user_id' => $userId, 'body' => $body, 'created_at' => $now]);
        $commentId = (int) $this->db->insertID();

        $this->notifyAuthors($item, $userId);

        $username = (string) (auth()->user()->username ?? 'Player');
        $row = ['id' => $commentId, 'user_id' => $userId, 'body' => $body, 'created_at' => $now, 'updated_at' => null];
        return $this->response->setJSON([
            'success'  => true,
            'comment'  => $this->commentJson($row, [$userId => $username], $userId, self::checkAdmin()),
            'count'    => $this->db->table('roadmap_comments')->whereIn('item_id', self::groupIds($mainId))->countAllResults(),
        ]);
    }

    /**
     * Tell the idea's author, and the authors of ideas merged into it, about a new comment. At most
     * one notice per author per idea per hour, and never about their own comment.
     */
    private function notifyAuthors(array $item, int $commenterId): void
    {
        $mainId = (int) $item['id'];
        $link = '/roadmap#item-' . $mainId;
        $authors = [];
        foreach (array_merge([$item], self::mergedIdeas($mainId)) as $idea) {
            $authorId = (int) ($idea['author_id'] ?? 0);
            if ($authorId > 0 && $authorId !== $commenterId) $authors[$authorId] = true;
        }
        foreach (array_keys($authors) as $authorId) {
            $recent = $this->db->table('notifications')->where('user_id', $authorId)->where('type', 'roadmap_comment')
                ->where('link', $link)->where('created_at >', date('Y-m-d H:i:s', time() - 3600))->countAllResults();
            if ($recent > 0) continue;
            notify($authorId, 'roadmap_comment', 'New comment on your idea',
                'Players are discussing "' . $item['title'] . '".', 'fa-solid fa-comment', $link);
        }
    }

    /** POST body: the author edits their comment, within COMMENT_EDIT_MINUTES of posting it */
    public function editComment(int $commentId)
    {
        $userId = $this->commenterId();
        $comment = $this->db->table('roadmap_comments')->where('id', $commentId)->get()->getRowArray();
        if (!$comment || $userId === null || (int) $comment['user_id'] !== $userId) {
            return $this->response->setJSON(['success' => false, 'message' => 'You can only edit your own comments.'])->setStatusCode(403);
        }
        if (strtotime((string) $comment['created_at']) <= time() - self::COMMENT_EDIT_MINUTES * 60) {
            return $this->response->setJSON(['success' => false, 'message' => 'Comments can be edited for ' . self::COMMENT_EDIT_MINUTES . ' minutes after posting.'])->setStatusCode(400);
        }
        $body = trim((string) $this->request->getPost('body'));
        if ($body === '' || mb_strlen($body) > self::COMMENT_MAX) {
            return $this->response->setJSON(['success' => false, 'message' => 'A comment needs 1 to ' . self::COMMENT_MAX . ' characters.'])->setStatusCode(400);
        }
        $now = date('Y-m-d H:i:s');
        $this->db->table('roadmap_comments')->where('id', $commentId)->update(['body' => $body, 'updated_at' => $now]);
        $comment = array_merge($comment, ['body' => $body, 'updated_at' => $now]);
        $username = (string) (auth()->user()->username ?? 'Player');
        return $this->response->setJSON(['success' => true, 'comment' => $this->commentJson($comment, [$userId => $username], $userId, self::checkAdmin())]);
    }

    /** POST: the author or an admin deletes a comment */
    public function deleteComment(int $commentId)
    {
        $comment = $this->db->table('roadmap_comments')->where('id', $commentId)->get()->getRowArray();
        $me = function_exists('auth') && auth()->loggedIn() ? (int) auth()->id() : 0;
        if (!$comment || !($me > 0 && ((int) $comment['user_id'] === $me || self::checkAdmin()))) {
            return $this->response->setJSON(['success' => false, 'message' => 'You can only delete your own comments.'])->setStatusCode(403);
        }
        $this->db->table('roadmap_comments')->where('id', $commentId)->delete();
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

        // Ideas merged into this one come back as separate ideas instead of being deleted with it
        foreach (self::mergedIdeas($itemId) as $m) $this->splitOut($m);

        $this->db->transStart();
        if ($this->db->tableExists('roadmap_comments')) $this->db->table('roadmap_comments')->where('item_id', $itemId)->delete();
        $this->db->table('roadmap_votes')->where('item_id', $itemId)->delete();
        $this->db->table('roadmap_items')->where('id', $itemId)->delete();
        $this->db->transComplete();

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => true]);
        }

        return redirect()->to('/roadmap')->with('success', 'Roadmap item removed.');
    }
}
