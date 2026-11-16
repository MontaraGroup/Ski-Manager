<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<head>
    <meta name="x-apple-disable-message-reformatting">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title><?= lang('Auth.magicLinkSubject') ?></title>
</head>

<body>
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="border-radius: 6px; border-collapse: separate !important;">
        <tbody>
            <tr>
                <td style="line-height: 24px; font-size: 16px; border-radius: 6px; margin: 0;" align="center" bgcolor="#0d6efd">
                    <a href="<?= url_to('verify-magic-link') ?>?token=<?= $token ?>" style="color: #ffffff; font-size: 16px; font-family: Helvetica, Arial, sans-serif; text-decoration: none; border-radius: 6px; line-height: 20px; display: inline-block; font-weight: normal; white-space: nowrap; background-color: #0d6efd; padding: 8px 12px; border: 1px solid #0d6efd;"><?= lang('Auth.login') ?></a>
                </td>
            </tr>
        </tbody>
    </table>
    <p style="font-size: 13px; color: #6b7280; margin-top: 12px; word-break: break-all;">
        If the button above does not work, copy and paste this link into your browser:<br>
        <a href="<?= url_to('verify-magic-link') ?>?token=<?= $token ?>" style="color: #0d6efd; text-decoration: underline;"><?= url_to('verify-magic-link') ?>?token=<?= $token ?></a>
    </p>
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
        You received this email because a sign-in link was requested for your <a href="https://ski-manager.net" style="color: #6b7280; text-decoration: underline;">Ski Manager</a> account. If you did not request this, you can safely ignore this email.
    </div>
</body>

</html>
