<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Signed-in players only, for /api/v1. Shield's own session filter redirects to /login, which
 * a fetch() can't follow usefully; this answers 401 in the API's JSON shape instead.
 */
class ApiAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (function_exists('auth') && auth()->loggedIn()) return null;
        return service('response')->setStatusCode(401)->setJSON([
            'ok' => false, 'data' => null, 'message' => 'Sign in to continue.', 'errors' => (object) [],
        ]);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
