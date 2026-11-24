<?php

namespace App\Controllers;

use App\Filters\AdminFilter;

/**
 * GET/POST /admin/confirm: admins enter their password again before the admin panel opens
 * (AdminFilter sends them here when the last confirmation is older than AdminFilter::CONFIRM_IDLE).
 * The admin* throttle limits wrong guesses per IP.
 */
class AdminConfirm extends BaseController
{
    public function show()
    {
        $user = auth()->user();
        return view('admin/confirm', ['page' => [
            'email'      => (string) ($user->email ?? ''),
            'csrfField'  => csrf_token(),
            'csrfToken'  => csrf_hash(),
            'error'      => (string) (session()->getFlashdata('error') ?? ''),
            'hasPassword' => $user && $user->getEmailIdentity() !== null && !empty($user->getEmailIdentity()->secret2),
        ]]);
    }

    public function check()
    {
        $user = auth()->user();
        $password = (string) $this->request->getPost('password');
        $result = $user && $password !== '' && !empty($user->email)
            ? auth('session')->check(['email' => $user->email, 'password' => $password])
            : null;

        if ($result === null || !$result->isOK() || (int) $result->extraInfo()->id !== (int) $user->id) {
            log_message('warning', 'Admin password confirmation failed for user #' . (int) ($user->id ?? 0) . ' from ' . $this->request->getIPAddress());
            return redirect()->to('/admin/confirm')->with('error', "That password doesn't match.");
        }

        session()->regenerate();
        session()->set(AdminFilter::CONFIRMED_KEY, time());
        $next = (string) (session()->get('admin_confirm_next') ?? '/admin');
        session()->remove('admin_confirm_next');
        // Only paths inside the admin panel, never another site
        return redirect()->to(str_starts_with($next, '/admin') ? $next : '/admin');
    }
}
