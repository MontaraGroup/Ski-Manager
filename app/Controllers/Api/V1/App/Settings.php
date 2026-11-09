<?php

namespace App\Controllers\Api\V1\App;

use CodeIgniter\HTTP\ResponseInterface;

class Settings extends \App\Controllers\Settings
{
    use AppResponses;

    public function show(): ResponseInterface
    {
        $page = $this->pageData();
        // The web keeps the resort name in the session from sign-in; a token request has no session
        $finance = db_connect()->table('player_finances')->where('user_id', auth()->id())->get()->getRowArray();
        return $this->ok([
            'user' => $page['user'],
            'resort' => ['name' => (string) ($finance['resort_name'] ?? '') ?: $page['resort']['name']],
            'units' => $page['units'],
            'difficulty' => $page['difficulty'],
        ]);
    }

    public function units(): ResponseInterface
    {
        return $this->fromAction($this->save());
    }
}
