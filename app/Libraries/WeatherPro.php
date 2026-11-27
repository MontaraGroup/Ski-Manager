<?php

namespace App\Libraries;

/**
 * Weather Pro: a 7-day pass bought with Génépis. It only adds information, never money or skiers:
 * a 14-day outlook with narrower ranges (Weather::outlook), tonight's settle hour by hour with wind
 * and snow gun hours, a 14-day snow base projection and snowmaking plan, and storm, wind and thaw
 * alerts two days ahead.
 *
 * Stored in player_boosts: 'weather_pro' with its expiry, and 'weather_pro_renew' (a 2099 row, like
 * permanent upgrades) while auto-renew is on. Auto-renew is opt-in; the tick renews a pass that runs
 * out before the next tick, if the balance covers it. Behind the PostHog flag FLAG.
 */
class WeatherPro
{
    public const FLAG = 'weather-pro';
    public const PRICE = 15;
    public const DAYS = 7;
    public const BOOST = 'weather_pro';
    public const RENEW = 'weather_pro_renew';
    private const FOREVER = '2099-12-31 23:59:59';

    /** Whether this player can buy and use Weather Pro (the flag) */
    public static function enabledFor(int $userId): bool
    {
        return function_exists('featureEnabledFor') && featureEnabledFor(self::FLAG, $userId);
    }

    private static function row(int $userId): ?array
    {
        return db_connect()->table('player_boosts')->where('user_id', $userId)->where('boost_type', self::BOOST)
            ->where('expires_at >', date('Y-m-d H:i:s'))->orderBy('expires_at', 'DESC')->limit(1)->get()->getRowArray();
    }

    /** True while the pass runs and the flag is on for the player */
    public static function active(int $userId): bool
    {
        return $userId > 0 && self::enabledFor($userId) && self::row($userId) !== null;
    }

    public static function autoRenew(int $userId): bool
    {
        return db_connect()->table('player_boosts')->where('user_id', $userId)->where('boost_type', self::RENEW)->countAllResults() > 0;
    }

    public static function setAutoRenew(int $userId, bool $on): void
    {
        $db = db_connect();
        $db->table('player_boosts')->where('user_id', $userId)->where('boost_type', self::RENEW)->delete();
        if ($on) $db->table('player_boosts')->insert(['user_id' => $userId, 'boost_type' => self::RENEW, 'expires_at' => self::FOREVER]);
    }

    /**
     * Charges PRICE and adds DAYS to the pass (from now, or from its end if it still runs).
     * The caller wraps it in a transaction. @return string|null new expiry, or null if the balance is short
     */
    public static function subscribe(int $userId, string $reason = 'Weather Pro, 7 days'): ?string
    {
        if (!GenepisWallet::spend($userId, self::PRICE, $reason)) return null;
        $db = db_connect();
        $row = self::row($userId);
        $until = date('Y-m-d H:i:s', ($row ? strtotime($row['expires_at']) : time()) + self::DAYS * 86400);
        if ($row) {
            $db->table('player_boosts')->where('id', $row['id'])->update(['expires_at' => $until]);
        } else {
            $db->table('player_boosts')->insert(['user_id' => $userId, 'boost_type' => self::BOOST, 'expires_at' => $until]);
        }
        return $until;
    }

    /** What the pages and the app show about the pass */
    public static function status(int $userId): array
    {
        $available = $userId > 0 && self::enabledFor($userId);
        $row = $available ? self::row($userId) : null;
        $balance = $available ? GenepisWallet::balance($userId) : 0;
        return [
            'available' => $available,
            'active' => $row !== null,
            'expiresAt' => $row ? date('c', strtotime($row['expires_at'])) : null,
            'autoRenew' => $available && self::autoRenew($userId),
            'price' => self::PRICE,
            'days' => self::DAYS,
            'balance' => $balance,
            'canAfford' => $balance >= self::PRICE,
        ];
    }

    /**
     * Nightly, for each player: renew a pass that ends before the next tick (auto-renew), warn the day
     * before it ends, and send alerts for the day after tomorrow's settle.
     */
    public static function tick(int $userId, int $gameDay): void
    {
        if (!self::enabledFor($userId)) return;
        $row = self::row($userId);
        if (!$row) return;

        $endsSoon = strtotime($row['expires_at']) <= time() + 86400 + 600;
        if ($endsSoon) {
            if (!self::autoRenew($userId)) {
                notify($userId, 'weather_pro', 'Weather Pro ends tomorrow', 'Renew it for ' . self::PRICE . ' Génépis on the Weather page, or switch on auto-renew.', 'fa-solid fa-cloud-sun', '/weather');
            } elseif ($until = self::subscribe($userId, 'Weather Pro, 7 days (auto-renew)')) {
                notify($userId, 'weather_pro', 'Weather Pro renewed', self::PRICE . ' Génépis for 7 more days, until ' . date('M j', strtotime($until)) . '.', 'fa-solid fa-cloud-sun', '/weather');
            } else {
                notify($userId, 'weather_pro', 'Weather Pro could not renew', 'You need ' . self::PRICE . ' Génépis. It ends tomorrow.', 'fa-solid fa-cloud-sun', '/genepis');
            }
        }

        foreach (self::alerts($gameDay) as $a) {
            notify($userId, 'weather_alert', $a['title'], $a['text'], 'fa-solid fa-triangle-exclamation', '/weather');
        }
    }

    /** Alerts for the day after the next settle (exact for Pro), worked out once per tick */
    private static function alerts(int $gameDay): array
    {
        static $cache = [];
        if (isset($cache[$gameDay])) return $cache[$gameDay];
        $outlook = array_slice(Weather::outlook(true, $gameDay + 2), 0, 1);
        $titles = ['storm' => 'Storm', 'wind' => 'Wind hold', 'thaw' => 'Thaw'];
        $out = [];
        foreach (Weather::callouts($outlook) as $c) {
            $out[] = ['title' => $titles[$c['kind']] . ' on day ' . Weather::seasonDayOf($c['gameDay']), 'text' => $c['text']];
        }
        return $cache[$gameDay] = $out;
    }
}
