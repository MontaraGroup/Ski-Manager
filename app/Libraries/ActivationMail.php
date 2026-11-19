<?php

namespace App\Libraries;

use App\Authentication\Actions\EmailActivator;
use CodeIgniter\Shield\Entities\User;

/**
 * Emails a new activation code without a pending session, for the mobile API. The web sign-up gets
 * the same code and email through EmailActivator::show().
 */
class ActivationMail
{
    /** False when the mail server refused it; the player can ask again */
    public static function send(User $user): bool
    {
        $code = (new EmailActivator())->createIdentity($user);

        helper('email');
        $request = service('request');
        $ip = $request->getIPAddress();
        $ua = (string) $request->getUserAgent();
        $date = date('Y-m-d H:i:s');

        $email = emailer(['mailType' => 'html'])->setFrom(setting('Email.fromEmail'), setting('Email.fromName') ?? '');
        $email->setTo($user->email);
        $email->setSubject(lang('Auth.emailActivateSubject'));
        $email->setMessage(view(setting('Auth.views')['action_email_activate_email'], [
            'code' => $code,
            'user' => $user,
            'ipAddress' => $ip,
            'userAgent' => $ua,
            'date' => $date,
        ], ['debug' => false]));

        // Plain text fallback so spam filters (Gmail/Outlook) do not penalize HTML-only MIME
        $altMessage = lang('Auth.emailActivateMailBody') . "\n\n"
            . "Activation Code: " . $code . "\n\n"
            . lang('Auth.emailInfo') . "\n"
            . lang('Auth.username') . ": " . esc($user->username) . "\n"
            . lang('Auth.emailIpAddress') . " " . esc($ip) . "\n"
            . lang('Auth.emailDevice') . " " . esc($ua) . "\n"
            . lang('Auth.emailDate') . " " . esc($date) . "\n";
        $email->setAltMessage($altMessage);

        $sent = $email->send(false);
        $email->clear();
        if (!$sent) {
            log_message('error', 'Activation email failed for user #' . $user->id);
            return false;
        }
        EmailActivator::markDelivered($user);
        return true;
    }
}
