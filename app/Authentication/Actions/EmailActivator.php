<?php

namespace App\Authentication\Actions;

use CodeIgniter\Shield\Authentication\Actions\EmailActivator as ShieldEmailActivator;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Exceptions\RuntimeException;
use CodeIgniter\Shield\Models\UserIdentityModel;

/**
 * Shield's activator emails a new code on every visit to /auth/a/show and throws (a 500)
 * when the mail can't be sent, which left players stuck in an error loop. This one emails
 * a code once, lets the player ask for another after RESEND_AFTER seconds, and says so
 * plainly when sending fails. A code whose email was delivered survives new logins for
 * REUSE_FOR, because Shield would otherwise replace it (and void the emailed one) every time.
 */
class EmailActivator extends ShieldEmailActivator
{
    public const RESEND_AFTER = 60;
    /** A code whose email was delivered is reused for this long, so logging in again doesn't void it */
    public const REUSE_FOR = 72 * 3600;
    /** identity name marking a code whose email went out (Shield names new codes 'register') */
    public const DELIVERED = 'register-sent';

    /** The player's activation code if its email was delivered recently enough to still be in their inbox */
    public static function deliveredCode(User $user): ?object
    {
        $identity = model(UserIdentityModel::class)->getIdentityByType($user, 'email_activate');
        if ($identity === null || $identity->name !== self::DELIVERED || empty($identity->created_at)) return null;
        // created_at may be a Time object or a plain string depending on how the entity was loaded
        return strtotime((string) $identity->created_at) > time() - self::REUSE_FOR ? $identity : null;
    }

    public static function markDelivered(User $user): void
    {
        db_connect()->table('auth_identities')->where('user_id', $user->id)->where('type', 'email_activate')->update(['name' => self::DELIVERED]);
    }

    public function show(): string
    {
        $user = auth('session')->getAuthenticator()->getPendingUser();
        $view = setting('Auth.views')['action_email_activate_show'];
        if ($user === null) {
            return $this->view($view, ['user' => null]);
        }

        $session = session();
        $sent = $session->get('activation_mail');
        $sent = is_array($sent) && ($sent['user'] ?? null) === $user->id ? $sent : null;
        $wantsResend = service('request')->getGet('resend') !== null;

        // A code already went out: reloading the page shouldn't email another
        if ($sent !== null && $sent['ok'] && !$wantsResend) {
            return $this->view($view, ['user' => $user, 'mailNotice' => 'sent']);
        }
        // A new login with a delivered code still pending: making a new one would void the code in their inbox
        if ($sent === null && !$wantsResend && self::deliveredCode($user) !== null) {
            $session->set('activation_mail', ['user' => $user->id, 'at' => time(), 'ok' => true]);
            return $this->view($view, ['user' => $user, 'mailNotice' => 'sent']);
        }
        if ($sent !== null && $sent['at'] > time() - self::RESEND_AFTER) {
            return $this->view($view, ['user' => $user, 'mailNotice' => $sent['ok'] ? 'sent' : 'failed',
                'retryIn' => $sent['at'] + self::RESEND_AFTER - time()]);
        }

        try {
            parent::show(); // creates a fresh code and emails it
            self::markDelivered($user);
            $session->set('activation_mail', ['user' => $user->id, 'at' => time(), 'ok' => true]);
            return $this->view($view, ['user' => $user, 'mailNotice' => $wantsResend ? 'resent' : 'sent']);
        } catch (RuntimeException $e) {
            // The exception text carries the address and SMTP transcript; log just the server's reply
            preg_match_all('/\b[45]\d\d[ -][^\n<]*/', $e->getMessage(), $m);
            log_message('error', 'Activation email failed for user #' . $user->id . ': ' . (end($m[0]) ?: 'no SMTP reply'));
            $session->set('activation_mail', ['user' => $user->id, 'at' => time(), 'ok' => false]);
            return $this->view($view, ['user' => $user, 'mailNotice' => 'failed', 'retryIn' => self::RESEND_AFTER]);
        }
    }
}
