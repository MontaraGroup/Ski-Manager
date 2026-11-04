<?php

namespace App\Controllers\Api\V1;

use CodeIgniter\HTTP\ResponseInterface;

/** GET /api/v1/me: the signed-in player and the numbers the navbar stats bar shows */
class Me extends ApiController
{
    public function index(): ResponseInterface
    {
        $user = auth()->user();
        $db = db_connect();
        $finances = $db->table('player_finances')->select('cash')->where('user_id', $user->id)->get()->getRowArray();
        $genepis = $db->table('genepis')->select('balance')->where('user_id', $user->id)->get()->getRowArray();

        return $this->ok([
            'id' => (int) $user->id,
            'username' => (string) $user->username,
            'isAdmin' => $user->inGroup('admin', 'superadmin'),
            'cash' => (int) ($finances['cash'] ?? 0),
            'cashText' => currency((int) ($finances['cash'] ?? 0)),
            'genepis' => (int) ($genepis['balance'] ?? 0),
            'gameDay' => getGameDay(),
            'seasonDay' => getSeasonDay(),
        ]);
    }
}
