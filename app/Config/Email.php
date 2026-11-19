<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Email extends BaseConfig
{
    public string $fromEmail = 'contact@ski-manager.net';
    public string $fromName = 'Ski Manager';
    public string $protocol = 'smtp';
    public string $SMTPHost = 'smtp.hostinger.com';
    public int $SMTPPort = 465;
    public string $SMTPUser = 'contact@ski-manager.net';
    public string $SMTPPass = '';
    public string $SMTPCrypto = 'ssl';
    public string $mailType = 'html';
    public bool $sendMultipart = true;
    public bool $SMTPAuth = true;

    public function __construct()
    {
        parent::__construct();
        // env() reads this request's .env values first. getenv() reads the PHP-FPM worker's process
        // environment, which keeps whatever value the worker first saw: after a password change,
        // long-lived workers kept sending the old one.
        $this->SMTPPass = (string) (env('SMTP_PASSWORD') ?: '');
    }
}
