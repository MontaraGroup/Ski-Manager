<?php

namespace App\Controllers;

use CodeIgniter\Shield\Models\UserIdentityModel;

/**
 * Activation code form. With a pending sign-up in the session, this is Shield's own check
 * (signs the player in). Without one (code opened in another browser, session expired),
 * the player gives their email too, so a code only ever works for its own account.
 * Codes expire after CODE_LIFETIME and guesses are rate-limited per IP and per account.
 */
class AuthActivation extends BaseController
{
    public const CODE_LIFETIME = 30 * MINUTE;

    /** Code form for players without a pending sign-up in this browser */
    public function form()
    {
        if (auth('session')->getAuthenticator()->getPendingUser() !== null) {
            return redirect()->to('auth/a/show');
        }
        return view(setting('Auth.views')['action_email_activate_show'], ['user' => null]);
    }

    public function submit()
    {
        $authenticator = auth('session')->getAuthenticator();
        $pending = $authenticator->getPendingUser();
        $back = $pending !== null ? 'auth/a/show' : 'auth/activate';
        $code = preg_replace('/\D/', '', (string) $this->request->getPost('code'));

        $throttler = service('throttler');
        if (!$throttler->check('activate-ip-' . md5($this->request->getIPAddress()), 20, HOUR)) {
            return redirect()->to($back)->with('error', 'Too many attempts. Try again in ' . max(1, (int) ceil($throttler->getTokenTime() / 60)) . ' minutes.')->withInput();
        }
        if (strlen($code) !== 6) {
            return redirect()->to($back)->with('error', 'Enter the 6-digit code from the email.')->withInput();
        }

        $userId = $pending?->id;
        if ($userId === null) {
            $email = strtolower(trim((string) $this->request->getPost('email')));
            $row = $email === '' ? null : db_connect()->table('auth_identities')->select('user_id')
                ->where('type', 'email_password')->where('secret', $email)->get()->getRowArray();
            $userId = $row ? (int) $row['user_id'] : null;
        }
        // Same answer for an unknown email as for a wrong code, so the form doesn't reveal accounts
        $invalid = 'That code doesn\'t match. Check the latest email, or ask for a new code.';
        if ($userId === null) {
            return redirect()->to($back)->with('error', $invalid)->withInput();
        }
        if (!$throttler->check('activate-user-' . $userId, 5, 15 * MINUTE)) {
            return redirect()->to($back)->with('error', 'Too many wrong codes for this account. Try again in 15 minutes.')->withInput();
        }

        $identity = db_connect()->table('auth_identities')->where('user_id', $userId)->where('type', 'email_activate')->get()->getRowArray();
        if (!$identity || !hash_equals((string) $identity['secret'], $code)) {
            return redirect()->to($back)->with('error', $invalid)->withInput();
        }
        if (strtotime((string) $identity['created_at']) < time() - self::CODE_LIFETIME) {
            return redirect()->to($back)->with('error', 'That code has expired. ' . ($pending !== null ? 'Ask for a new one below.' : 'Sign in again and we\'ll email a new one.'));
        }

        if ($pending !== null) {
            // Shield's own path: removes the code, ends the pending action and signs the player in
            $entity = model(UserIdentityModel::class)->getIdentityByType($pending, 'email_activate');
            if ($entity !== null && $authenticator->checkAction($entity, $code)) {
                $authenticator->getUser()->activate();
                session()->remove('activation_mail');
                if ($savedAs = session('demo_saved_as')) {
                    session()->remove('demo_saved_as');
                    return redirect()->to(config('Auth')->registerRedirect())->with('success', 'Your resort is saved. Welcome, ' . $savedAs . '.');
                }
                return redirect()->to(config('Auth')->registerRedirect())->with('message', lang('Auth.registerSuccess'));
            }
            return redirect()->to($back)->with('error', $invalid);
        }

        $db = db_connect();
        $db->table('users')->where('id', $userId)->update(['active' => 1]);
        $db->table('auth_identities')->where('id', $identity['id'])->delete();
        return redirect()->to('login')->with('message', 'Your account is verified. Sign in to open your resort.');
    }
}
