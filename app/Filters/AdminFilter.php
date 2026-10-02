<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!function_exists('auth') || !auth()->loggedIn()) {
            return redirect()->to('/login')->with('error', 'Please log in to access the administration sector.');
        }

        $user = auth()->user();
        $isAdmin = auth()->id() === 1 || ($user && $user->inGroup('admin', 'superadmin'));

        if (!$isAdmin) {
            return redirect()->to('/dashboard')->with('error', 'Access restricted. You do not have administrator clearance.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
