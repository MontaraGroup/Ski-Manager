<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * The admin panel: admins only (user #1 or the admin/superadmin groups), and the admin must have
 * confirmed their password within the last CONFIRM_IDLE seconds of activity (AdminConfirm).
 */
class AdminFilter implements FilterInterface
{
    /** Session key holding when the admin last confirmed their password or used the panel */
    public const CONFIRMED_KEY = 'admin_confirmed_at';

    /** Idle time after which the panel asks for the password again */
    public const CONFIRM_IDLE = 2 * HOUR;

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

        $path = '/' . trim($request->getUri()->getPath(), '/');
        if ($path === '/admin/confirm') {
            return;
        }

        $session = session();
        $confirmedAt = (int) ($session->get(self::CONFIRMED_KEY) ?? 0);
        if ($confirmedAt === 0 || time() - $confirmedAt > self::CONFIRM_IDLE) {
            if (strtolower($request->getMethod()) === 'get') {
                $query = $request->getUri()->getQuery();
                $session->set('admin_confirm_next', $path . ($query !== '' ? '?' . $query : ''));
            }
            return redirect()->to('/admin/confirm');
        }

        // Activity keeps the confirmation alive
        $session->set(self::CONFIRMED_KEY, time());
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
