<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Shield\Controllers\MagicLinkController;

/**
 * Shield's magic link verify, minus the crash when the browser is already signed in.
 * Shield throws a LogicException from startLogin() in that case; the login form and
 * the magic link form already send a signed-in player on, so the link does the same.
 * The token is left unused, so it still works after a sign-out.
 */
class MagicLink extends MagicLinkController
{
    public function verify(): RedirectResponse
    {
        if (auth('session')->loggedIn()) {
            return redirect()->to(config('Auth')->loginRedirect());
        }

        if (auth('session')->getAuthenticator()->isPending()) {
            return redirect()->route('auth-action-show');
        }

        return parent::verify();
    }
}
