<?php

namespace App\Libraries;

/**
 * Player cash moves that the finances page should see: each one changes cash and the running totals
 * and writes a financial_transactions row in the same transaction.
 *
 * charge() is the guard against overspending: the cash check and the debit are one statement, so two
 * quick requests can't both spend the same money (reading cash first and deducting later allowed that).
 * Both methods open a transaction of their own, which nests inside the caller's when there is one; a
 * caller with more writes that depend on the payment wraps them all and rolls back when charge() fails.
 */
class Ledger
{
    /** Takes $amount from the player's cash only if they have it; false (and nothing written) when they can't pay */
    public static function charge(int $userId, int $amount, string $category, string $description): bool
    {
        if ($amount <= 0) return true;
        $db = db_connect();
        $db->transStart();
        $db->table('player_finances')->where('user_id', $userId)->where('cash >=', $amount)
            ->set('cash', 'cash - ' . $amount, false)->set('total_expenses', 'total_expenses + ' . $amount, false)->update();
        if ($db->affectedRows() !== 1) {
            $db->transComplete();
            return false;
        }
        self::record($userId, $amount, 'expense', $category, $description);
        $db->transComplete();
        return true;
    }

    /** Adds $amount to the player's cash (refunds, sales, prizes bought with Génépis) */
    public static function credit(int $userId, int $amount, string $category, string $description): void
    {
        if ($amount <= 0) return;
        $db = db_connect();
        $db->transStart();
        $db->table('player_finances')->where('user_id', $userId)
            ->set('cash', 'cash + ' . $amount, false)->set('total_income', 'total_income + ' . $amount, false)->update();
        self::record($userId, $amount, 'income', $category, $description);
        $db->transComplete();
    }

    private static function record(int $userId, int $amount, string $type, string $category, string $description): void
    {
        db_connect()->table('financial_transactions')->insert([
            'user_id' => $userId, 'game_day' => getGameDay(), 'category' => $category, 'description' => $description,
            'amount' => $amount, 'type' => $type, 'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
