<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class SeasonGate implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (auth()->id() === 1) {
            return;
        }

        $db = db_connect();
        $season = $db->table('seasons')->where('active', 1)->get()->getRowArray();

        if ($season && strtotime($season['start_date']) > time()) {
            return redirect()->to('/coming-soon');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
