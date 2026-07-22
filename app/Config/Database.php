<?php

namespace Config;

use CodeIgniter\Database\Config as BaseConfig;

class Database extends BaseConfig
{
    /**
     * The directory that holds the Migrations
     * and Seeds directories.
     */
    public string $filesPath = APPPATH . 'Database' . DIRECTORY_SEPARATOR;

    /**
     * Default connection group.
     */
    public string $defaultGroup = 'default';

    /**
     * The default database connection.
     */
    public array $default = [
        'DSN'          => '',
        'hostname'     => '1Panel-mysql-Uibp',
        'username'     => 'root',
        'password'     => '',
        'database'     => 'skimanager_v2',
        'DBDriver'     => 'MySQLi',
        'DBPrefix'     => '',
        'pConnect'     => false,
        'DBDebug'      => true,
        'charset'      => 'utf8mb4',
        'DBCollation'  => 'utf8mb4_general_ci',
        'swapPre'      => '',
        'encrypt'      => false,
        'compress'     => false,
        'strictOn'     => false,
        'failover'     => [],
        'port'         => 3306,
        'numberAsFloat'=> false,
    ];

    /**
     * This database connection is used when running automated PHPUnit tests.
     */
    public array $tests = [
        'DSN'         => '',
        'hostname'    => '127.0.0.1',
        'username'    => 'root',
        'password'    => '',
        'database'    => 'skimanager_v2_test',
        'DBDriver'    => 'MySQLi',
        'DBPrefix'    => '',
        'pConnect'    => false,
        'DBDebug'     => true,
        'charset'     => 'utf8mb4',
        'DBCollation' => 'utf8mb4_general_ci',
        'swapPre'     => '',
        'encrypt'     => false,
        'compress'    => false,
        'strictOn'    => false,
        'failover'    => [],
        'port'        => 3306,
        'numberAsFloat' => false,
    ];

    public function __construct()
    {
        parent::__construct();

        // Automatically route CLI/terminal commands (php spark) to 127.0.0.1
        // while leaving web browser requests on the internal Docker container name.
        if (is_cli()) {
            $this->default['hostname'] = '127.0.0.1';
        }
    }
}
