<?php

namespace App\Libraries;

/**
 * Monthly resort vote: each calendar month players pick the real North American ski area they
 * want added next; when the month ends the admin picks the winner on /admin/resort-vote.
 *
 * Rounds follow real dates, not game days (own-pace seasons make game days per player). There is
 * no cron: rollover() runs on each vote page or API call, closes rounds from earlier months and
 * opens the current one. The admin can close the current month early and reopen it.
 *
 * Candidates come from vote_ski_areas, seeded from app/Database/Seeds/data/ski_areas_na.csv
 * (OpenSkiMap, © OpenStreetMap contributors, ODbL 1.0; scripts/build_ski_areas.py builds it).
 */
class ResortVote
{
    public const FLAG = 'resort_vote';
    public const CSV = APPPATH . 'Database/Seeds/data/ski_areas_na.csv';
    public const ATTRIBUTION = 'Resort list: OpenSkiMap, © OpenStreetMap contributors (ODbL)';
    public const SEARCH_LIMIT = 15;
    public const NOTE_MAX = 500;

    /** Already playable: never on the ballot (OpenSkiMap ids) */
    private const IN_GAME = [
        '3dad9d62b112c018cb658fd1eb49bcd41c96f257', // Park City Mountain Resort
    ];

    private const COUNTRY = ['US' => 'United States', 'CA' => 'Canada', 'MX' => 'Mexico'];

    public static function flagOn(): bool
    {
        return function_exists('featureEnabled') && featureEnabled(self::FLAG);
    }

    public static function currentMonth(): string
    {
        return date('Y-m');
    }

    public static function monthLabel(string $month): string
    {
        return date('F Y', strtotime($month . '-01'));
    }

    /** Last second of the month, when its round closes on its own */
    public static function monthEnd(string $month): string
    {
        return date('Y-m-t 23:59:59', strtotime($month . '-01'));
    }

    /**
     * Closes rounds left open from earlier months and makes sure this month has one.
     * Returns this month's round. Safe under concurrent requests (unique month, INSERT IGNORE).
     */
    public static function rollover(): array
    {
        $db = db_connect();
        $month = self::currentMonth();
        $now = date('Y-m-d H:i:s');

        $stale = $db->table('resort_vote_rounds')->where('status', 'open')->where('month <', $month)->get()->getResultArray();
        foreach ($stale as $r) {
            $db->table('resort_vote_rounds')->where('id', $r['id'])->where('status', 'open')
                ->update(['status' => 'closed', 'closed_at' => self::monthEnd($r['month']), 'updated_at' => $now]);
        }

        $db->query('INSERT IGNORE INTO resort_vote_rounds (month, status, created_at, updated_at) VALUES (?, ?, ?, ?)', [$month, 'open', $now, $now]);
        return $db->table('resort_vote_rounds')->where('month', $month)->get()->getRowArray();
    }

    public static function round(int $id): ?array
    {
        return db_connect()->table('resort_vote_rounds')->where('id', $id)->get()->getRowArray() ?: null;
    }

    public static function isOpen(array $round): bool
    {
        return $round['status'] === 'open' && $round['month'] === self::currentMonth();
    }

    public static function eligible(int $userId): bool
    {
        return SeasonVote::eligible($userId);
    }

    /** "Stowe Mountain Resort · Vermont, United States" parts for the UI */
    public static function area(array $a): array
    {
        $country = self::COUNTRY[$a['country']] ?? $a['country'];
        $place = implode(', ', array_filter([$a['region'] ?? '', $country]));
        return [
            'id' => (int) $a['id'],
            'name' => (string) $a['name'],
            'place' => $place,
            'locality' => (string) ($a['locality'] ?? ''),
            'runs' => (int) ($a['runs'] ?? 0),
            'lifts' => (int) ($a['lifts'] ?? 0),
            'verticalM' => isset($a['vertical_m']) ? (int) $a['vertical_m'] : null,
            'website' => (string) ($a['website'] ?? ''),
        ];
    }

    /** Ids that can't be picked: playable already, or a past round's winner */
    private static function excludedSql(): string
    {
        return 'a.in_game = 0 AND a.id NOT IN (SELECT winner_area_id FROM resort_vote_rounds WHERE winner_area_id IS NOT NULL)';
    }

    public static function votable(int $areaId): ?array
    {
        $row = db_connect()->query('SELECT a.* FROM vote_ski_areas a WHERE a.id = ? AND ' . self::excludedSql(), [$areaId])->getRowArray();
        return $row ?: null;
    }

    /** Name starts-with first, then name/place contains; bigger areas first within each */
    public static function search(string $q): array
    {
        $q = trim(mb_substr($q, 0, 64));
        if (mb_strlen($q) < 2) return [];
        $db = db_connect();
        $like = '%' . $db->escapeLikeString($q) . '%';
        $starts = $db->escapeLikeString($q) . '%';
        $rows = $db->query(
            'SELECT a.* FROM vote_ski_areas a
             WHERE ' . self::excludedSql() . " AND (a.name LIKE ? ESCAPE '!' OR a.region LIKE ? ESCAPE '!' OR a.locality LIKE ? ESCAPE '!')
             ORDER BY (a.name LIKE ? ESCAPE '!') DESC, (a.name LIKE ? ESCAPE '!') DESC, a.runs DESC, a.name
             LIMIT " . self::SEARCH_LIMIT,
            [$like, $like, $like, $starts, $like]
        )->getResultArray();
        return array_map([self::class, 'area'], $rows);
    }

    public static function ballot(int $roundId, int $userId): ?array
    {
        $row = db_connect()->query(
            'SELECT a.* FROM resort_vote_ballots b JOIN vote_ski_areas a ON a.id = b.area_id WHERE b.round_id = ? AND b.user_id = ?',
            [$roundId, $userId]
        )->getRowArray();
        return $row ? self::area($row) : null;
    }

    /** One ballot per player per round: voting again replaces it */
    public static function vote(int $roundId, int $userId, int $areaId): void
    {
        $now = date('Y-m-d H:i:s');
        db_connect()->query(
            'INSERT INTO resort_vote_ballots (round_id, user_id, area_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE area_id = VALUES(area_id), updated_at = VALUES(updated_at)',
            [$roundId, $userId, $areaId, $now, $now]
        );
    }

    public static function retract(int $roundId, int $userId): void
    {
        db_connect()->table('resort_vote_ballots')->where('round_id', $roundId)->where('user_id', $userId)->delete();
    }

    /** Votes per area for one round, eligible accounts only, most votes first */
    public static function tally(int $roundId, ?int $limit = null): array
    {
        $db = db_connect();
        $sql = 'SELECT a.*, COUNT(*) AS votes FROM resort_vote_ballots b
                JOIN users u ON u.id = b.user_id
                JOIN vote_ski_areas a ON a.id = b.area_id
                WHERE b.round_id = ? AND ' . SeasonVote::ELIGIBLE_SQL . '
                GROUP BY a.id ORDER BY votes DESC, a.name';
        if ($limit) $sql .= ' LIMIT ' . (int) $limit;
        $rows = $db->query($sql, [$roundId])->getResultArray();
        $total = self::voteCount($roundId);
        return array_map(fn($r) => self::area($r) + [
            'votes' => (int) $r['votes'],
            'pct' => $total ? (int) round($r['votes'] * 100 / $total) : 0,
        ], $rows);
    }

    public static function voteCount(int $roundId): int
    {
        return (int) db_connect()->query(
            'SELECT COUNT(*) AS n FROM resort_vote_ballots b JOIN users u ON u.id = b.user_id WHERE b.round_id = ? AND ' . SeasonVote::ELIGIBLE_SQL,
            [$roundId]
        )->getRow()->n;
    }

    /** A round as the pages show it */
    public static function summary(array $round): array
    {
        $winner = null;
        if (!empty($round['winner_area_id'])) {
            $a = db_connect()->table('vote_ski_areas')->where('id', $round['winner_area_id'])->get()->getRowArray();
            $winner = $a ? self::area($a) : null;
        }
        return [
            'id' => (int) $round['id'],
            'month' => $round['month'],
            'label' => self::monthLabel($round['month']),
            'open' => self::isOpen($round),
            'closesLabel' => date('j F', strtotime(self::monthEnd($round['month']))),
            'closedLabel' => $round['closed_at'] ? date('j F Y', strtotime($round['closed_at'])) : null,
            'votes' => self::voteCount((int) $round['id']),
            'winner' => $winner,
            'winnerNote' => $round['winner_note'] ?? null,
            'roadmapItemId' => $round['roadmap_item_id'] ? (int) $round['roadmap_item_id'] : null,
            'votersNotified' => !empty($round['voters_notified_at']),
        ];
    }

    /** Rounds before this month, newest first */
    public static function pastRounds(int $limit = 12): array
    {
        $rows = db_connect()->table('resort_vote_rounds')->where('month <', self::currentMonth())
            ->orderBy('month', 'DESC')->limit($limit)->get()->getResultArray();
        return array_map([self::class, 'summary'], $rows);
    }

    /** What /vote needs */
    public static function page(int $userId): array
    {
        $round = self::rollover();
        $roundId = (int) $round['id'];
        return [
            'round' => self::summary($round),
            'canVote' => self::eligible($userId),
            'myPick' => $userId ? self::ballot($roundId, $userId) : null,
            'top' => self::tally($roundId, 10),
            'past' => array_values(array_filter(self::pastRounds(), fn($r) => $r['winner'] !== null || $r['votes'] > 0)),
            'candidateCount' => (int) db_connect()->query('SELECT COUNT(*) AS n FROM vote_ski_areas a WHERE ' . self::excludedSql())->getRow()->n,
            'attribution' => self::ATTRIBUTION,
        ];
    }

    /** Admin: close the current month early, or reopen it */
    public static function setOpen(int $roundId, bool $open): bool
    {
        $round = self::round($roundId);
        if (!$round || $round['month'] !== self::currentMonth()) return false;
        db_connect()->table('resort_vote_rounds')->where('id', $roundId)->update([
            'status' => $open ? 'open' : 'closed',
            'closed_at' => $open ? null : date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return true;
    }

    /**
     * Admin: record the winner of a closed round. Optionally adds a planned roadmap item (once;
     * later picks rename it) and notifies that round's voters (once).
     * Returns null on success, or the reason it was refused.
     */
    public static function pickWinner(int $roundId, int $areaId, ?string $note, bool $roadmap, bool $notifyVoters): ?string
    {
        $db = db_connect();
        $round = self::round($roundId);
        if (!$round) return 'That round does not exist.';
        if (self::isOpen($round)) return 'Close the vote before picking a winner.';
        $areaRow = $db->table('vote_ski_areas')->where('id', $areaId)->get()->getRowArray();
        if (!$areaRow) return 'That resort is not on the list.';
        $taken = $db->table('resort_vote_rounds')->where('winner_area_id', $areaId)->where('id !=', $roundId)->countAllResults();
        if ($taken) return $areaRow['name'] . ' already won an earlier month.';

        $area = self::area($areaRow);
        $now = date('Y-m-d H:i:s');
        $month = self::monthLabel($round['month']);
        $update = ['winner_area_id' => $areaId, 'winner_note' => $note, 'winner_at' => $now, 'updated_at' => $now];

        $title = 'Add ' . $area['name'] . ' (' . $area['place'] . ')';
        $description = 'Winner of the ' . $month . ' resort vote. The trail map and lifts get built in Map Studio.';
        if ($roadmap && empty($round['roadmap_item_id'])) {
            $db->table('roadmap_items')->insert([
                'title' => $title, 'description' => $description, 'status' => 'planned', 'category' => 'gameplay',
                'upvotes' => 0, 'author_name' => 'Ski Manager Team', 'created_at' => $now, 'updated_at' => $now,
            ]);
            $update['roadmap_item_id'] = (int) $db->insertID();
        } elseif (!empty($round['roadmap_item_id'])) {
            $db->table('roadmap_items')->where('id', $round['roadmap_item_id'])->update(['title' => $title, 'description' => $description, 'updated_at' => $now]);
        }

        $db->table('resort_vote_rounds')->where('id', $roundId)->update($update);

        if ($notifyVoters && empty($round['voters_notified_at'])) {
            helper('notify');
            $voters = $db->query('SELECT b.user_id FROM resort_vote_ballots b JOIN users u ON u.id = b.user_id WHERE b.round_id = ? AND ' . SeasonVote::ELIGIBLE_SQL, [$roundId])->getResultArray();
            foreach ($voters as $v) {
                notify((int) $v['user_id'], 'resort_vote_winner', $area['name'] . ' won the ' . $month . ' vote',
                    $area['name'] . ' is the next resort coming to Ski Manager. This month\'s vote is open now.', 'fa-solid fa-check-to-slot', '/vote');
            }
            $db->table('resort_vote_rounds')->where('id', $roundId)->update(['voters_notified_at' => $now]);
        }
        return null;
    }

    /** Admin: mark an area playable (or not), which takes it off the ballot */
    public static function setInGame(int $areaId, bool $inGame): void
    {
        db_connect()->table('vote_ski_areas')->where('id', $areaId)->update(['in_game' => $inGame ? 1 : 0, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Loads the CSV into vote_ski_areas. New areas are added, known ones (same source_id) get the
     * fresh figures; in_game is never touched by a refresh, except IN_GAME entries set to 1.
     * Returns how many rows were read.
     */
    public static function importAreas(string $path = self::CSV): int
    {
        $fh = fopen($path, 'r');
        if (!$fh) throw new \RuntimeException('Cannot read ' . $path);
        $db = db_connect();
        $now = date('Y-m-d H:i:s');
        $header = null;
        $count = 0;
        $batch = [];
        $flush = function () use (&$batch, $db) {
            if (!$batch) return;
            $cols = array_keys($batch[0]);
            $place = '(' . implode(',', array_fill(0, count($cols), '?')) . ')';
            $values = [];
            foreach ($batch as $row) array_push($values, ...array_values($row));
            $set = implode(', ', array_map(fn($c) => "$c = VALUES($c)", array_diff($cols, ['source_id', 'created_at', 'in_game'])));
            $db->query('INSERT INTO vote_ski_areas (' . implode(',', $cols) . ') VALUES ' . implode(',', array_fill(0, count($batch), $place))
                . ' ON DUPLICATE KEY UPDATE ' . $set, $values);
            $batch = [];
        };
        while (($line = fgetcsv($fh, 0, ',', '"', '')) !== false) {
            if ($line === [null] || str_starts_with((string) $line[0], '#')) continue;
            if ($header === null) { $header = $line; continue; }
            $r = array_combine($header, $line);
            $num = fn($k) => $r[$k] === '' ? null : $r[$k];
            $batch[] = [
                'source_id' => $r['source_id'], 'name' => mb_substr($r['name'], 0, 128), 'country' => $r['country'],
                'region_code' => $num('region_code'), 'region' => $num('region'), 'locality' => $num('locality') !== null ? mb_substr($r['locality'], 0, 96) : null,
                'runs' => min(65535, (int) $r['runs']), 'lifts' => min(65535, (int) $r['lifts']),
                'vertical_m' => $num('vertical_m'), 'top_m' => $num('top_m'), 'lat' => $num('lat'), 'lng' => $num('lng'),
                'website' => $num('website'), 'in_game' => in_array($r['source_id'], self::IN_GAME, true) ? 1 : 0,
                'created_at' => $now, 'updated_at' => $now,
            ];
            $count++;
            if (count($batch) >= 200) $flush();
        }
        $flush();
        fclose($fh);
        if (self::IN_GAME) $db->table('vote_ski_areas')->whereIn('source_id', self::IN_GAME)->update(['in_game' => 1]);
        return $count;
    }
}
