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
    public string $mailType = 'text';
    public bool $SMTPAuth = true;

    public function __construct()
    {
        parent::__construct();
        $this->SMTPPass = getenv('SMTP_PASSWORD') ?: '';
    }
}
