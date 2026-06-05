<?php

namespace App\Controllers;

use CodeIgniter\Shield\Models\UserModel;

class Account extends BaseController
{
    public function index(): string
    {
        return redirect()->to('/settings');
    }

    public function changePassword()
    {
        $current = $this->request->getPost('current_password');
        $new = $this->request->getPost('new_password');
        $confirm = $this->request->getPost('confirm_password');

        if ($new !== $confirm) {
            return redirect()->back()->with('error', 'New passwords do not match.');
        }

        if (strlen($new) < 8) {
            return redirect()->back()->with('error', 'Password must be at least 8 characters.');
        }

        $user = auth()->user();
        $credentials = ['email' => $user->email, 'password' => $current];
        $result = auth()->check($credentials);

        if (!$result->isOK()) {
            return redirect()->back()->with('error', 'Current password is incorrect.');
        }

        $userProvider = auth()->getProvider();
        $user->password = $new;
        $userProvider->save($user);

        log_activity(auth()->id(), 'Account', 'Changed password', 'fa-solid fa-key');
        return redirect()->to('/account')->with('success', 'Password changed successfully.');
    }

    public function changeEmail()
    {
        $email = strip_tags(trim($this->request->getPost('email')));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->with('error', 'Invalid email address.');
        }

        $db = db_connect();
        $existing = $db->table('auth_identities')->where('secret', $email)->where('user_id !=', auth()->id())->countAllResults();
        if ($existing > 0) {
            return redirect()->back()->with('error', 'Email already in use.');
        }

        $db->table('auth_identities')
            ->where('user_id', auth()->id())
            ->where('type', 'email_password')
            ->update(['secret' => $email]);

        log_activity(auth()->id(), 'Account', 'Changed email to ' . $email, 'fa-solid fa-envelope');
        return redirect()->to('/account')->with('success', 'Email updated.');
    }

    public function changeUsername()
    {
        $username = strip_tags(trim($this->request->getPost('username')));

        if (strlen($username) < 3 || strlen($username) > 30) {
            return redirect()->back()->with('error', 'Username must be 3-30 characters.');
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return redirect()->back()->with('error', 'Username can only contain letters, numbers, and underscores.');
        }

        $db = db_connect();
        $existing = $db->table('users')->where('username', $username)->where('id !=', auth()->id())->countAllResults();
        if ($existing > 0) {
            return redirect()->back()->with('error', 'Username already taken.');
        }

        $db->table('users')->where('id', auth()->id())->update(['username' => $username]);

        log_activity(auth()->id(), 'Account', 'Changed username to ' . $username, 'fa-solid fa-user');
        return redirect()->to('/account')->with('success', 'Username updated.');
    }

    public function deleteAccount()
    {
        $confirm = $this->request->getPost('confirm_delete');
        if ($confirm !== 'DELETE') {
            return redirect()->back()->with('error', 'Type DELETE to confirm.');
        }

        $userId = auth()->id();
        $db = db_connect();

        $tables = ['staff', 'buildings', 'snow_cannons', 'night_skiing', 'equipment', 'marketing_campaigns', 'loans', 'regulations', 'insurance', 'achievements', 'daily_bonus', 'player_finances', 'financial_transactions', 'activity_log', 'lift_tickets', 'ticket_sales', 'player_items', 'genepis', 'genepis_log', 'environmental'];

        foreach ($tables as $table) {
            try { $db->table($table)->where('user_id', $userId)->delete(); } catch (\Exception $e) {}
        }

        $db->table('auth_identities')->where('user_id', $userId)->delete();
        $db->table('auth_logins')->where('user_id', $userId)->delete();
        $db->table('auth_remember_tokens')->where('user_id', $userId)->delete();
        $db->table('users')->where('id', $userId)->delete();

        auth()->logout();
        return redirect()->to('/')->with('message', 'Account deleted.');
    }
}
