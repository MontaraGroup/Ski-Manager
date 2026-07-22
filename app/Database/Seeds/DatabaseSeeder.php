<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();
        $db->disableForeignKeyChecks();

        // 1. Seed Default Admin User
        if ($db->tableExists('users')) {
            $db->table('users')->truncate();
            $db->table('users')->insert([
                'id'         => 1,
                'username'   => 'admin',
                'status'     => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        // 2. Seed Auth Identity
        if ($db->tableExists('auth_identities')) {
            $db->table('auth_identities')->truncate();
            $db->table('auth_identities')->insert([
                'user_id'    => 1,
                'type'       => 'email_password',
                'name'       => 'Admin',
                'secret'     => 'admin@ski-manager.net',
                'secret2'    => password_hash('Admin12345!', PASSWORD_DEFAULT),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        // 3. Seed Player Finances
        if ($db->tableExists('player_finances')) {
            $db->table('player_finances')->truncate();
            $db->table('player_finances')->insert([
                'user_id'        => 1,
                'cash'           => 100000.00,
                'daily_income'   => 0.00,
                'daily_expenses' => 0.00,
                'difficulty'     => 'normal',
                'last_active'    => date('Y-m-d H:i:s'),
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);
        }

        // 4. Seed Default Weather State
        if ($db->tableExists('weather')) {
            $db->table('weather')->truncate();
            $db->table('weather')->insert([
                'user_id'     => 1,
                'condition'   => 'sunny',
                'temperature' => -2,
                'snow_depth'  => 45,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
        }

        // 5. Seed Lift Tickets Baseline
        if ($db->tableExists('lift_tickets')) {
            $db->table('lift_tickets')->truncate();
            $db->table('lift_tickets')->insert([
                'user_id'    => 1,
                'adult_price'=> 65.00,
                'child_price'=> 40.00,
                'senior_price'=> 50.00,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $db->enableForeignKeyChecks();

        // 6. Call UpdateSeeder to populate game version history
        $this->call('UpdateSeeder');
    }
}
