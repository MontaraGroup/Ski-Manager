<?php

namespace App\Controllers;

use CodeIgniter\Shield\Models\UserModel;

class DiscordAuth extends BaseController
{
    private function getConfig(): array
    {
        return [
            'client_id' => env('DISCORD_CLIENT_ID'),
            'client_secret' => env('DISCORD_CLIENT_SECRET'),
            'redirect_uri' => env('DISCORD_REDIRECT_URI'),
        ];
    }

    public function redirect()
    {
        $config = $this->getConfig();
        $params = http_build_query([
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'response_type' => 'code',
            'scope' => 'identify email',
        ]);
        return redirect()->to('https://discord.com/api/oauth2/authorize?' . $params);
    }

    public function callback()
    {
        $code = $this->request->getGet('code');
        if (!$code) return redirect()->to('/login')->with('error', 'Discord login cancelled.');

        $config = $this->getConfig();

        // Exchange code for token
        $ch = curl_init('https://discord.com/api/oauth2/token');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $config['redirect_uri'],
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $tokenResponse = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (!isset($tokenResponse['access_token'])) {
            log_message("error", "Discord token error: " . json_encode($tokenResponse)); return redirect()->to("/login")->with("error", "Discord login failed. Check logs.");
        }

        // Get user info
        $ch = curl_init('https://discord.com/api/users/@me');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $tokenResponse['access_token']],
        ]);
        $userInfo = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (!isset($userInfo['id'])) {
            return redirect()->to('/login')->with('error', 'Could not get Discord user info.');
        }

        $discordId = $userInfo['id'];
        $email = $userInfo['email'] ?? null;
        $username = $userInfo['username'] ?? 'Player';

        if (!$email) {
            return redirect()->to('/login')->with('error', 'Discord account has no email. Please use email signup.');
        }

        $db = db_connect();
        $userModel = new UserModel();

        // Check if user exists by email
        $identity = $db->table('auth_identities')->where('secret', $email)->where('type', 'email_password')->get()->getRowArray();

        if ($identity) {
            $user = $userModel->find($identity['user_id']);
            if ($user) {
                auth()->login($user);
                return redirect()->to('/dashboard');
            }
        }

        // Create new user
        $existingUsername = $db->table('users')->where('username', $username)->countAllResults();
        if ($existingUsername) $username = $username . '_' . substr($discordId, -4);

        $user = new \CodeIgniter\Shield\Entities\User([
            'username' => $username,
            'active' => 1,
        ]);
        $userModel->save($user);
        $user = $userModel->find($userModel->getInsertID());

        $randomPassword = bin2hex(random_bytes(16));
        $user->createEmailIdentity([
            'email' => $email,
            'password' => $randomPassword,
        ]);

        $db->table('player_finances')->insert([
            'user_id' => $user->id,
            'cash' => 500000,
            'total_income' => 0,
            'total_expenses' => 0,
            'resort_map' => session('resort_map') ?? 'ParkCity',
            'difficulty' => session('difficulty') ?? 'standard',
        ]);
        $db->table('genepis')->insert(['user_id' => $user->id, 'balance' => 0]);
        $db->table('daily_bonus')->insert(['user_id' => $user->id, 'last_claim_day' => 0, 'streak' => 0]);

        log_activity($user->id, 'register', 'Joined Ski Manager via Discord');
        notify($user->id, 'welcome', 'Welcome to Ski Manager!', 'Start by hiring staff and building your first slope.', 'fa-solid fa-mountain-sun', '/dashboard');

        auth()->login($user);
        return redirect()->to('/dashboard');
    }
}
