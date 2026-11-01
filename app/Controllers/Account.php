<?php

namespace App\Controllers;

use CodeIgniter\Shield\Models\UserModel;

class Account extends BaseController
{
    public function index()
    {
        return redirect()->to('/settings');
    }

    /** Purposes an emailed code can confirm, and what the email calls them */
    private const CODE_PURPOSES = ['password' => 'set a new password', 'delete' => 'delete your account'];
    private const CODE_LIFETIME = 15 * MINUTE;
    private const CODE_TRIES = 5;

    /**
     * Email a one-time code to the account address. Players who signed up with Google or Discord
     * never saw their (random) password, and others forget theirs; the code stands in for it.
     */
    /**
     * A demo account has no password anyone knows: changing its email, name or password here would
     * leave it a demo that can't be signed back into. Send the player to save the demo instead.
     */
    private function demoRedirect()
    {
        if (!\App\Controllers\Demo::isDemoUser()) return null;
        return redirect()->to('/demo/save')->with('error', 'This is a demo mountain. Save it to a permanent account first; you choose your username, email and password there.');
    }

    public function sendCode()
    {
        if ($demo = $this->demoRedirect()) return $demo;
        $purpose = (string) $this->request->getPost('purpose');
        if (!isset(self::CODE_PURPOSES[$purpose])) return redirect()->to('/settings');
        $user = auth()->user();
        if (!service('throttler')->check('account-code-' . $user->id, 3, 15 * MINUTE)) {
            return redirect()->to('/settings')->with('error', 'Too many codes requested. Try again in a few minutes.');
        }

        $code = (string) random_int(100000, 999999);
        $email = \Config\Services::email();
        $email->setFrom('contact@ski-manager.net', 'Ski Manager');
        $email->setTo($user->email);
        $email->setSubject('Your Ski Manager code: ' . $code);
        $email->setMailType('text');
        $email->setMessage("Hi " . $user->username . ",\n\nYour code to " . self::CODE_PURPOSES[$purpose] . " is: " . $code
            . "\n\nIt works for 15 minutes. If you didn't ask for it, you can ignore this email; nothing changes without the code.\n\nSki Manager");
        if (!$email->send(false)) {
            log_message('error', 'Account code email failed for user #' . $user->id);
            return redirect()->to('/settings')->with('error', 'We couldn\'t send the email right now. Please try again in a minute.');
        }

        session()->set('account_code', ['purpose' => $purpose, 'hash' => password_hash($code, PASSWORD_DEFAULT), 'expires' => time() + self::CODE_LIFETIME, 'tries' => 0]);
        [$local, $domain] = explode('@', $user->email, 2) + [1 => ''];
        return redirect()->to('/settings')->with('success', 'We emailed a code to ' . mb_substr($local, 0, 1) . '•••@' . $domain . '. Enter it below within 15 minutes.');
    }

    /** True if $code is the unexpired code emailed for $purpose; it can be used once */
    private function codeIsValid(string $purpose, string $code): bool
    {
        $c = session('account_code');
        if (!is_array($c) || $c['purpose'] !== $purpose || $c['expires'] < time() || $c['tries'] >= self::CODE_TRIES) return false;
        if (!password_verify(preg_replace('/\D/', '', $code), $c['hash'])) {
            $c['tries']++;
            session()->set('account_code', $c);
            return false;
        }
        session()->remove('account_code');
        return true;
    }

    public function changePassword()
    {
        if ($demo = $this->demoRedirect()) return $demo;
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
        $code = trim((string) $this->request->getPost('code'));
        if ($code !== '') {
            if (!$this->codeIsValid('password', $code)) {
                return redirect()->back()->with('error', 'That code is wrong or has expired. Ask for a new one.');
            }
        } elseif (!auth()->check(['email' => $user->email, 'password' => (string) $current])->isOK()) {
            return redirect()->back()->with('error', 'Current password is incorrect. No password, or forgot it? Use "Email me a code".');
        }

        $userProvider = auth()->getProvider();
        $user->password = $new;
        $userProvider->save($user);

        log_activity(auth()->id(), 'Account', 'Changed password', 'fa-solid fa-key');
        return redirect()->to('/settings')->with('success', 'Password saved. You can now also sign in with your email and this password.');
    }

    public function changeEmail()
    {
        if ($demo = $this->demoRedirect()) return $demo;
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
            ->update(['secret' => $email, 'updated_at' => date('Y-m-d H:i:s')]);

        log_activity(auth()->id(), 'Account', 'Changed email to ' . $email, 'fa-solid fa-envelope');
        return redirect()->to('/account')->with('success', 'Email updated.');
    }

    public function changeUsername()
    {
        if ($demo = $this->demoRedirect()) return $demo;
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

        $user = auth()->user();
        if (!$user) {
            return redirect()->to('/login');
        }

        // Confirmed by the account password, or by a code emailed to the account address
        $password = (string) $this->request->getPost('password');
        $code = trim((string) $this->request->getPost('code'));
        if ($code !== '') {
            if (!$this->codeIsValid('delete', $code)) {
                return redirect()->back()->with('error', 'That code is wrong or has expired. Account deletion aborted.');
            }
        } elseif ($password === '') {
            return redirect()->back()->with('error', 'Enter your password, or use "Email me a code" if you don\'t have one.');
        } elseif (!auth()->check(['email' => $user->email, 'password' => $password])->isOK()) {
            return redirect()->back()->with('error', 'Incorrect password. Account deletion aborted.');
        }

        \App\Libraries\AccountPurge::purge((int) auth()->id());

        auth()->logout();
        return redirect()->to('/')->with('message', 'Account deleted successfully.');
    }
}
