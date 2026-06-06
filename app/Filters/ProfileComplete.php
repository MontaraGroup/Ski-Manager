<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ProfileComplete implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!auth()->loggedIn()) return;

        $db = db_connect();
        $finance = $db->table('player_finances')->where('user_id', auth()->id())->get()->getRowArray();

        if ($finance && !$finance['profile_completed']) {
            return redirect()->to('/complete-profile');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
