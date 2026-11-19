<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<head>
    <meta name="x-apple-disable-message-reformatting">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title><?= lang('Auth.email2FASubject') ?></title>
</head>

<body>
    <p><?= lang('Auth.email2FAMailBody') ?></p>
    <div style="text-align: center">
        <h1><?= $code ?></h1>
    </div>
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="width: 100%;" width="100%">
        <tbody>
            <tr>
                <td style="line-height: 20px; font-size: 20px; width: 100%; height: 20px; margin: 0;" align="left" width="100%" height="20">
                    &#160;
                </td>
            </tr>
        </tbody>
    </table>
    <b><?= lang('Auth.emailInfo') ?></b>
    <p><?= lang('Auth.username') ?>: <?= esc($user->username) ?></p>
    <p><?= lang('Auth.emailIpAddress') ?> <?= esc($ipAddress) ?></p>
    <p><?= lang('Auth.emailDevice') ?> <?= esc($userAgent) ?></p>
    <p><?= lang('Auth.emailDate') ?> <?= esc($date) ?></p>
    <div style="border-top: 1px solid #e5e7eb; margin-top: 24px; padding-top: 12px; font-size: 11px; color: #9ca3af;">
        You received this email because a two-factor authentication code was requested for your <a href="https://ski-manager.net" style="color: #6b7280; text-decoration: underline;">Ski Manager</a> account. If you did not attempt to sign in, please secure your account immediately.
    </div>
</body>

</html>
