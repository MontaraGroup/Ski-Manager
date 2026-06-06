<?php

namespace App\Controllers;

use CodeIgniter\Shield\Entities\User;

class GoogleAuth extends BaseController
{
    private function getClientId(): string { return env('GOOGLE_CLIENT_ID', ''); }
    private function getClientSecret(): string { return env('GOOGLE_CLIENT_SECRET', ''); }
    private function getRedirectUri(): string { return env('GOOGLE_REDIRECT_URI', ''); }

    public function redirect()
    {
        $params = http_build_query([
            'client_id' => $this->getClientId(),
            'redirect_uri' => $this->getRedirectUri(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'access_type' => 'online',
            'prompt' => 'select_account',
            'state' => csrf_hash(),
        ]);

        session()->set('google_oauth_state', csrf_hash());
        return redirect()->to('https://accounts.google.com/o/oauth2/v2/auth?' . $params);
    }

    public function callback()
    {
        $code = $this->request->getGet('code');
        $error = $this->request->getGet('error');

        if ($error || !$code) {
            return redirect()->to('/login')->with('error', 'Google sign-in was cancelled.');
        }

        $tokenData = $this->exchangeCode($code);
        if (!$tokenData || !isset($tokenData['access_token'])) {
            return redirect()->to('/login')->with('error', 'Failed to authenticate with Google.');
        }

        $userInfo = $this->getUserInfo($tokenData['access_token']);
        if (!$userInfo || !isset($userInfo['email'])) {
            return redirect()->to('/login')->with('error', 'Could not retrieve Google account info.');
        }

        $email = $userInfo['email'];
        $name = $userInfo['name'] ?? explode('@', $email)[0];
        $googleId = $userInfo['sub'] ?? '';

        $db = db_connect();
        $identity = $db->table('auth_identities')->where('type', 'email_password')->where('secret', $email)->get()->getRowArray();

        if ($identity) {
            $userId = $identity['user_id'];
            $user = auth()->getProvider()->findById($userId);
            if (!$user) {
                return redirect()->to('/login')->with('error', 'Account not found.');
            }

            $banned = $db->table('users')->where('id', $userId)->get()->getRowArray();
            if (isset($banned['active']) && !$banned['active']) {
                return redirect()->to('/login')->with('error', 'This account has been banned.');
            }

            auth()->login($user);
            return redirect()->to('/dashboard');
        }

        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $name);
        $existing = $db->table('users')->where('username', $username)->countAllResults();
        if ($existing) {
            $username .= rand(100, 999);
        }

        $users = auth()->getProvider();
        $user = new User([
            'username' => $username,
            'active' => 1,
        ]);
        $users->save($user);
        $user = $users->findById($users->getInsertID());

        $randomPassword = bin2hex(random_bytes(16));
        $user->createEmailIdentity([
            'email' => $email,
            'password' => $randomPassword,
        ]);

        $db->table('player_finances')->insert(['user_id' => $user->id, 'cash' => 500000, 'total_income' => 0, 'total_expenses' => 0, 'resort_map' => session('resort_map') ?? 'Vail']);
        $db->table('genepis')->insert(['user_id' => $user->id, 'balance' => 0]);
        $db->table('daily_bonus')->insert(['user_id' => $user->id, 'last_claim_day' => 0, 'streak' => 0]);

        $gameDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime(getSeasonStartDate())) / 86400) + 1);
        log_activity($user->id, 'register', 'Joined Ski Manager via Google Sign-In');

        notify($user->id, 'welcome', 'Welcome to Ski Manager!', 'Start by hiring staff and building your first slope. Check the tutorial for a guided walkthrough!', 'fa-solid fa-mountain-sun', '/dashboard');

        auth()->login($user);
        return redirect()->to('/dashboard');
    }

    private function exchangeCode(string $code): ?array
    {
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'code' => $code,
                'client_id' => $this->getClientId(),
                'client_secret' => $this->getClientSecret(),
                'redirect_uri' => $this->getRedirectUri(),
                'grant_type' => 'authorization_code',
            ]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) return null;
        return json_decode($response, true);
    }

    private function getUserInfo(string $accessToken): ?array
    {
        $ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
            CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) return null;
        return json_decode($response, true);
    }
}
