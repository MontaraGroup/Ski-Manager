<?php

namespace App\Libraries;

/** Spending Génépis (premium currency): balance in `genepis`, history in `genepis_log` */
class GenepisWallet
{
    /**
     * Takes $amount Génépis in one statement, only if the balance covers it, and logs it. Reading the
     * balance and writing back balance - cost let two quick requests both spend the same Génépis.
     */
    public static function spend(int $userId, int $amount, string $reason): bool
    {
        $db = db_connect();
        $db->table('genepis')->where('user_id', $userId)->where('balance >=', $amount)
            ->set('balance', 'balance - ' . $amount, false)->set('total_spent', 'total_spent + ' . $amount, false)
            ->set('updated_at', date('Y-m-d H:i:s'))->update();
        if ($db->affectedRows() !== 1) return false;
        $db->table('genepis_log')->insert(['user_id' => $userId, 'amount' => $amount, 'type' => 'spent', 'reason' => $reason, 'created_at' => date('Y-m-d H:i:s')]);
        return true;
    }

    public static function balance(int $userId): int
    {
        return (int) (db_connect()->table('genepis')->select('balance')->where('user_id', $userId)->get()->getRow()->balance ?? 0);
    }
}
