<?php

namespace App\Libraries;

/**
 * Hiring candidates: each game day every role offers SHORTLIST named candidates with different
 * salaries and starting morale (and, for groomer operators, experience: level is what makes their
 * night shift restore more, see Grooming::restore). A hired candidate leaves the list; the list
 * refreshes on the next game day.
 */
class Candidates
{
    public const SHORTLIST = 3;

    /** trait => [label, salary range as % of the role's standard salary, starting morale range, level range] */
    public const TRAITS = [
        'bargain' => ['Asks for less', [80, 92], [60, 80], [1, 1]],
        'steady' => ['Steady', [95, 105], [75, 90], [1, 1]],
        'keen' => ['Keen to start', [98, 110], [90, 100], [1, 1]],
        'experienced' => ['Experienced', [110, 125], [70, 90], [2, 4]],
    ];

    private const FIRST = ['Alex', 'Marie', 'Pierre', 'Sophie', 'Lucas', 'Emma', 'Hugo', 'Lea', 'Thomas', 'Camille', 'Max', 'Clara', 'Jules', 'Alice', 'Liam', 'Chloe', 'Nina', 'Oscar', 'Ines', 'Felix', 'Mila', 'Noah', 'Elsa', 'Anton'];
    private const LAST = ['Dupont', 'Martin', 'Bernard', 'Dubois', 'Moreau', 'Laurent', 'Simon', 'Michel', 'Lefebvre', 'Roux', 'Blanc', 'Fournier', 'Morel', 'Girard', 'Huber', 'Lindqvist', 'Rossi', 'Keller', 'Berger', 'Novak'];

    /**
     * Today's candidates for every role, created on first view of the day.
     * @param array $roles role_key => ['salary' => standard daily salary, ...]
     * @return array role_key => list of candidate rows
     */
    public static function today(int $userId, array $roles): array
    {
        $db = db_connect();
        $day = getGameDay();
        $db->table('staff_candidates')->where('user_id', $userId)->where('game_day !=', $day)->delete();
        // Hired candidates stay on record (hired = 1) so a role's shortlist isn't refilled until the next day
        $rows = $db->table('staff_candidates')->where('user_id', $userId)->where('game_day', $day)->orderBy('salary')->get()->getResultArray();
        $byRole = [];
        $hasList = [];
        foreach ($rows as $r) {
            $hasList[$r['role']] = true;
            if (!(int) $r['hired']) $byRole[$r['role']][] = $r;
        }

        $new = [];
        foreach ($roles as $key => $role) {
            if (isset($hasList[$key])) continue;
            $traits = $key === 'groomer' ? ['bargain', 'steady', 'experienced'] : ['bargain', 'steady', 'keen'];
            foreach ($traits as $t) $new[] = self::make($userId, $key, (int) $role['salary'], $t, $day);
        }
        if ($new) {
            $db->table('staff_candidates')->insertBatch($new);
            return self::today($userId, $roles);
        }
        return $byRole;
    }

    private static function make(int $userId, string $role, int $standardSalary, string $trait, int $day): array
    {
        [, [$sMin, $sMax], [$mMin, $mMax], [$lMin, $lMax]] = self::TRAITS[$trait];
        $level = random_int($lMin, $lMax);
        return [
            'user_id' => $userId, 'role' => $role, 'trait' => $trait, 'game_day' => $day, 'level' => $level, 'hired' => 0,
            'name' => self::FIRST[array_rand(self::FIRST)] . ' ' . self::LAST[array_rand(self::LAST)],
            'salary' => (int) (round($standardSalary * random_int($sMin, $sMax) / 100 / 10) * 10),
            'morale' => random_int($mMin, $mMax), 'created_at' => date('Y-m-d H:i:s'),
        ];
    }

    public static function label(string $trait): string
    {
        return self::TRAITS[$trait][0] ?? ucfirst($trait);
    }
}
