<?php

namespace App\Authentication\Authenticators;

use CodeIgniter\Shield\Authentication\Authenticators\Session as ShieldSession;
use CodeIgniter\Shield\Entities\User;

/**
 * Shield's session authenticator, with the login action (Email2FA in Config\Auth) only for admins:
 * an email + password login by user #1 or an admin/superadmin asks for the emailed code, players
 * log straight in. Google, Discord and magic-link logins don't run login actions (Shield only runs
 * them from attempt()), and the app's token login uses check(), so neither is affected.
 */
class Session extends ShieldSession
{
    public function startUpAction(string $type, User $user): bool
    {
        if ($type === 'login' && !self::isAdmin($user)) {
            return false;
        }

        return parent::startUpAction($type, $user);
    }

    public static function isAdmin(User $user): bool
    {
        return (int) $user->id === 1 || $user->inGroup('admin', 'superadmin');
    }
}
