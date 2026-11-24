<?php

namespace App\Authentication\Actions;

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\Shield\Authentication\Actions\Email2FA as ShieldEmail2FA;
use CodeIgniter\Shield\Exceptions\RuntimeException;

/**
 * Shield's emailed login code (admins only, see App\Authentication\Authenticators\Session), without
 * the 500 when the email can't be sent: the admin is sent back to try again, and the error is logged.
 */
class Email2FA extends ShieldEmail2FA
{
    public function handle(IncomingRequest $request)
    {
        try {
            return parent::handle($request);
        } catch (RuntimeException $e) {
            log_message('error', 'Login code email failed: ' . strtok($e->getMessage(), "\n"));
            return redirect()->route('auth-action-show')->with('error', "We couldn't send the code. Try again in a minute.");
        }
    }
}
