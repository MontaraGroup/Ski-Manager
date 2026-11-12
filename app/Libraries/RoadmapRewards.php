<?php

namespace App\Libraries;

/**
 * Rewards for taking part in the /roadmap.
 *
 * - Voting: 2 Génépis once per ISO week, paid on a vote for an item the player has never voted on
 *   before. roadmap_vote_history keeps every item a player has ever voted on, even after unvoting,
 *   so vote/unvote/vote can't pay twice.
 * - Shipped idea: 25 Génépis to the author when an admin marks their suggestion completed, once per item.
 * - Achievements (roadmap_*) are measured by Achievements::metrics() from the same tables.
 *
 * Every payout inserts a roadmap_rewards row first; its unique key (user_id, kind, ref_key) means a
 * second attempt inserts nothing and pays nothing. Demo accounts and players who haven't finished
 * their profile get nothing.
 */
class RoadmapRewards
{
    public const VOTE_WEEKLY_GENEPIS = 2;
    public const IDEA_SHIPPED_GENEPIS = 25;
    public const SUGGESTIONS_PER_WEEK = 3;

    /** A finished, non-demo account: the only kind that earns rewards */
    public static function eligible(int $userId): bool
    {
        $db = db_connect();
        $user = $db->table('users')->select('status')->where('id', $userId)->get()->getRowArray();
        if (!$user || (string) ($user['status'] ?? '') === 'demo') return false;
        if (session()->get('is_demo') && auth()->id() === $userId) return false;
        $finance = $db->table('player_finances')->select('profile_completed')->where('user_id', $userId)->get()->getRowArray();
        return (bool) ($finance['profile_completed'] ?? false);
    }

    /** "2026-W46": the period the weekly voting reward is counted in */
    public static function weekKey(?int $time = null): string
    {
        return date('o-\WW', $time ?? time());
    }

    public static function weeklyRewardTaken(int $userId): bool
    {
        return db_connect()->table('roadmap_rewards')
            ->where(['user_id' => $userId, 'kind' => 'vote_week', 'ref_key' => self::weekKey()])
            ->countAllResults() > 0;
    }

    /** Suggestions the player may still make in the last 7 days */
    public static function suggestionsLeft(int $userId): int
    {
        $used = db_connect()->table('roadmap_items')->where('author_id', $userId)
            ->where('created_at >', date('Y-m-d H:i:s', strtotime('-7 days')))->countAllResults();
        return max(0, self::SUGGESTIONS_PER_WEEK - $used);
    }

    /**
     * A vote was just added. Records it in the history and pays the weekly reward if this is the
     * player's first-ever vote on the item and they haven't had this week's reward yet.
     * @param bool $payable false for the author's automatic vote on their own suggestion
     * @return int Génépis paid (0 or VOTE_WEEKLY_GENEPIS)
     */
    public static function onVote(int $userId, int $itemId, bool $payable = true): int
    {
        if (!self::eligible($userId)) return 0;
        $db = db_connect();

        $db->table('roadmap_vote_history')->ignore(true)->insert([
            'user_id' => $userId, 'item_id' => $itemId, 'first_voted_at' => date('Y-m-d H:i:s'),
        ]);
        $firstVoteOnItem = $db->affectedRows() > 0;
        $paid = 0;

        if ($firstVoteOnItem && $payable) {
            $paid = self::pay($userId, 'vote_week', self::weekKey(), $itemId, self::VOTE_WEEKLY_GENEPIS, 'Roadmap vote (weekly)');
        }
        if ($firstVoteOnItem) Achievements::sync($userId);
        return $paid;
    }

    /** An admin moved a player's suggestion onto the roadmap */
    public static function onAccepted(array $item): void
    {
        $authorId = (int) ($item['author_id'] ?? 0);
        if ($authorId <= 0) return;
        notify($authorId, 'roadmap', 'Your idea is on the roadmap',
            '"' . $item['title'] . '" was accepted. Players can now vote on it.',
            'fa-solid fa-lightbulb', '/roadmap#item-' . (int) $item['id']);
        if (self::eligible($authorId)) Achievements::sync($authorId);
    }

    /**
     * An admin marked an item completed: pay the author once and tell everyone who voted for it.
     * Both happen at most once per item, however often the status is changed back and forth.
     */
    public static function onShipped(array $item): void
    {
        $db = db_connect();
        $itemId = (int) $item['id'];
        $link = !empty($item['updates_url']) ? (string) $item['updates_url'] : '/roadmap#item-' . $itemId;
        $authorId = (int) ($item['author_id'] ?? 0);

        if ($authorId > 0 && self::eligible($authorId)) {
            $paid = self::pay($authorId, 'idea_shipped', 'item:' . $itemId, $itemId, self::IDEA_SHIPPED_GENEPIS, 'Your roadmap idea shipped');
            if ($paid > 0) {
                notify($authorId, 'roadmap', 'Your idea shipped',
                    '"' . $item['title'] . '" is now in the game. You earned ' . $paid . ' Génépis.',
                    'fa-solid fa-rocket', $link);
            }
            Achievements::sync($authorId);
        }

        // Voters are told once per item: a user_id 0 row marks the item as announced
        $db->table('roadmap_rewards')->ignore(true)->insert([
            'user_id' => 0, 'kind' => 'ship_notice', 'ref_key' => 'item:' . $itemId, 'item_id' => $itemId, 'amount' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        if ($db->affectedRows() === 0) return;

        $voters = $db->table('roadmap_votes')->select('user_id')->distinct()
            ->where('item_id', $itemId)->where('user_id IS NOT NULL')->where('user_id !=', $authorId)
            ->get()->getResultArray();
        foreach ($voters as $v) {
            notify((int) $v['user_id'], 'roadmap', 'An idea you voted for shipped',
                '"' . $item['title'] . '" is now in the game.', 'fa-solid fa-circle-check', $link);
        }
    }

    /**
     * Credit Génépis once for (user, kind, ref). The reward row and the balance change commit together.
     * @return int amount paid, 0 if this reward was already paid
     */
    private static function pay(int $userId, string $kind, string $ref, ?int $itemId, int $amount, string $reason): int
    {
        $db = db_connect();
        $now = date('Y-m-d H:i:s');
        $db->transStart();
        $db->table('roadmap_rewards')->ignore(true)->insert([
            'user_id' => $userId, 'kind' => $kind, 'ref_key' => $ref, 'item_id' => $itemId, 'amount' => $amount, 'created_at' => $now,
        ]);
        if ($db->affectedRows() === 0) {
            $db->transComplete();
            return 0;
        }
        if (!$db->table('genepis')->where('user_id', $userId)->countAllResults()) {
            $db->table('genepis')->insert(['user_id' => $userId, 'balance' => 0, 'total_earned' => 0, 'total_spent' => 0, 'updated_at' => $now]);
        }
        $db->table('genepis')->where('user_id', $userId)
            ->set('balance', 'balance + ' . $amount, false)->set('total_earned', 'total_earned + ' . $amount, false)
            ->set('updated_at', $now)->update();
        $db->table('genepis_log')->insert(['user_id' => $userId, 'amount' => $amount, 'type' => 'earned', 'reason' => $reason, 'created_at' => $now]);
        $db->transComplete();
        return $db->transStatus() ? $amount : 0;
    }
}
