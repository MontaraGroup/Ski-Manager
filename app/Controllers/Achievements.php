<?php

namespace App\Controllers;

use App\Libraries\Achievements as Rules;

/**
 * /achievements: progress, rewards and the features they unlock. Progress is kept current by
 * App\Libraries\Achievements::sync() (also run by the nightly tick).
 */
class Achievements extends BaseController
{
    public function index()
    {
        if (!auth()->loggedIn()) return redirect()->to('/login');
        $userId = auth()->id();
        Rules::sync($userId, false);

        $db = db_connect();
        $rows = $db->table('achievements')->where('user_id', $userId)->get()->getResultArray();
        $unlocks = array_column($db->table('achievement_defs')->select('achievement_key, unlock_label')->where('unlocks IS NOT NULL')->get()->getResultArray(), 'unlock_label', 'achievement_key');
        $order = array_flip(array_column($db->table('achievement_defs')->select('achievement_key')->orderBy('sort_order')->get()->getResultArray(), 'achievement_key'));

        $groups = [];
        foreach ($rows as $a) {
            $a['state'] = $a['claimed'] ? 'claimed' : ($a['completed'] ? 'ready' : 'progress');
            $a['pct'] = (int) $a['target'] > 0 ? min(100, (int) floor((int) $a['progress'] / (int) $a['target'] * 100)) : 0;
            $a['unlocks'] = $unlocks[$a['achievement_key']] ?? null;
            $groups[Rules::category($a['achievement_key'])][] = $a;
        }
        // Ready to claim first, then closest to done, then finished; definition order breaks ties
        $rank = ['ready' => 0, 'progress' => 1, 'claimed' => 2];
        foreach ($groups as &$list) {
            usort($list, fn($x, $y) => [$rank[$x['state']], -$x['pct'], $order[$x['achievement_key']] ?? 999] <=> [$rank[$y['state']], -$y['pct'], $order[$y['achievement_key']] ?? 999]);
        }
        unset($list);
        $groups = array_merge(array_intersect_key(array_fill_keys(array_keys(Rules::CATEGORIES), []), $groups), $groups);

        $ready = array_filter($rows, fn($a) => $a['completed'] && !$a['claimed']);
        $lockedFeatures = [];
        foreach ($rows as $a) {
            if (isset($unlocks[$a['achievement_key']]) && !$a['claimed'] && $a['achievement_key'] !== 'season_1_pioneer') {
                $lockedFeatures[] = ['feature' => $unlocks[$a['achievement_key']], 'name' => $a['name'], 'description' => $a['description'], 'ready' => (bool) $a['completed'],
                    'pct' => (int) $a['target'] > 0 ? min(100, (int) floor((int) $a['progress'] / (int) $a['target'] * 100)) : 0];
            }
        }
        usort($lockedFeatures, fn($x, $y) => [$y['ready'], $y['pct']] <=> [$x['ready'], $x['pct']]);

        $completed = count(array_filter($rows, fn($a) => $a['completed']));
        $fmt = fn(array $a, $v) => str_starts_with($a['achievement_key'], 'cash_') ? currency((int) $v) : number_format((int) $v);
        $list = [];
        foreach ($groups as $category => $items) {
            if (!$items) continue;
            $list[] = [
                'category' => $category,
                'done' => count(array_filter($items, fn($a) => $a['state'] !== 'progress')),
                'items' => array_map(fn($a) => [
                    'id' => (int) $a['id'], 'name' => (string) $a['name'], 'description' => (string) $a['description'],
                    'icon' => preg_match('/^[a-z0-9 -]+$/i', (string) $a['icon']) ? (string) $a['icon'] : 'fa-solid fa-award',
                    'reward' => (int) $a['reward_amount'] ? currency((int) $a['reward_amount']) : null,
                    'state' => $a['state'], 'pct' => $a['pct'],
                    'progress' => $fmt($a, $a['progress']) . ' / ' . $fmt($a, $a['target']),
                    'unlocks' => $a['unlocks'],
                ], $items),
            ];
        }

        return view('achievements/index', ['page' => [
            'completed' => $completed,
            'total' => count($rows),
            'readyCount' => count($ready),
            'readyReward' => currency(array_sum(array_map(fn($a) => (int) $a['reward_amount'], $ready))),
            'earned' => currency(array_sum(array_map(fn($a) => $a['claimed'] ? (int) $a['reward_amount'] : 0, $rows))),
            'lockedFeatures' => getDifficulty() === 'easy' ? [] : $lockedFeatures,
            'groups' => $list,
        ]]);
    }

    public function claim(int $id)
    {
        if (!auth()->loggedIn()) return redirect()->to('/login');
        return $this->pay(Rules::claim(auth()->id(), [$id]));
    }

    public function claimAll()
    {
        if (!auth()->loggedIn()) return redirect()->to('/login');
        $userId = auth()->id();
        $ids = array_column(db_connect()->table('achievements')->select('id')->where('user_id', $userId)->where('completed', 1)->where('claimed', 0)->get()->getResultArray(), 'id');
        return $this->pay(Rules::claim($userId, array_map('intval', $ids)));
    }

    private function pay(array $result)
    {
        if (!$result['paid']) return redirect()->to('/achievements')->with('error', 'Nothing to claim.');
        $n = count($result['paid']);
        $label = $n === 1 ? '"' . $result['paid'][0]['name'] . '"' : $n . ' achievements';
        log_activity(auth()->id(), 'Achievement', 'Claimed ' . $label . ' for ' . currency($result['total']), 'fa-solid fa-award');
        return redirect()->to('/achievements')->with('success', 'Claimed ' . currency($result['total']) . ' for ' . $label . '.');
    }
}
