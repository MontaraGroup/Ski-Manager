<?php

namespace App\Controllers\Api\V1\App;

use App\Controllers\AuthActivation;
use App\Libraries\ActivationMail;
use CodeIgniter\Events\Events;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Shield\Validation\ValidationRules;

/** Sign in, sign up and email codes. Extends the web's code check so its limits and expiry apply */
class Auth extends AuthActivation
{
    use AppResponses;

    private const ACCOUNT_ATTEMPTS = 5;
    private const ACCOUNT_WINDOW = 15 * MINUTE;

    public function login(): ResponseInterface
    {
        $identity = trim((string) $this->request->getPost('email'));
        $password = (string) $this->request->getPost('password');
        $wrong = "That email or password doesn't match.";
        if ($identity === '' || $password === '') {
            return $this->fail($wrong, 401, ['code' => 'invalid_credentials']);
        }

        // Every address shares one bucket per account, same as /api/auth
        $throttler = service('throttler');
        $key = 'auth-account-' . hash('sha256', strtolower($identity));
        if (!$throttler->check($key, self::ACCOUNT_ATTEMPTS, self::ACCOUNT_WINDOW)) {
            return $this->fail('Too many attempts. Try again in a few minutes.', 429, ['code' => 'too_many_attempts']);
        }

        $field = filter_var($identity, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $result = auth('session')->check([$field => $identity, 'password' => $password]);
        if (!$result->isOK()) {
            return $this->fail($wrong, 401, ['code' => 'invalid_credentials']);
        }
        $throttler->remove($key);

        /** @var User $user */
        $user = $result->extraInfo();
        if ($user->isBanned()) {
            return $this->fail('This account is not active.', 403, ['code' => 'account_inactive']);
        }
        if (!$user->active) {
            // Inactive with a code waiting means unconfirmed. Without one, an admin switched it off
            return $this->hasPendingCode($user)
                ? $this->fail('Confirm your email to continue.', 403, ['code' => 'activation_required', 'email' => (string) $user->email])
                : $this->fail('This account is not active.', 403, ['code' => 'account_inactive']);
        }
        return $this->ok($this->session($user));
    }

    public function register(): ResponseInterface
    {
        if (!setting('Auth.allowRegistration')) {
            return $this->fail('Sign-ups are closed right now.', 403);
        }
        $rules = (new ValidationRules())->getRegistrationRules();
        $rules['terms'] = ['label' => 'Terms', 'rules' => 'required', 'errors' => ['required' => 'Please accept the Terms of Service and Privacy Policy.']];
        $post = (array) $this->request->getPost();
        if (!$this->validateData($post, $rules, [], config('Auth')->DBGroup)) {
            $errors = $this->validator->getErrors();
            return $this->fail((string) reset($errors), 422, $errors);
        }

        $users = model(UserModel::class);
        $user = $users->createNewUser(array_intersect_key($post, array_flip(['username', 'email', 'password'])));
        $users->save($user);
        $user = $users->findById($users->getInsertID());
        $users->addToDefaultGroup($user);
        Events::trigger('register', $user);

        $sent = ActivationMail::send($user);
        return $this->ok(['email' => (string) $user->email, 'mailSent' => $sent], $sent ? null : "We couldn't send the email. Try again in a minute.", 201);
    }

    public function verify(): ResponseInterface
    {
        $email = strtolower(trim((string) $this->request->getPost('email')));
        $this->request->setGlobal('post', ['email' => $email, 'code' => (string) $this->request->getPost('code')]);

        $failed = $this->fromAction($this->submit());
        if ($failed->getStatusCode() >= 400) {
            return $failed;
        }
        $user = model(UserModel::class)->findByCredentials(['email' => $email]);
        return $user
            ? $this->ok($this->session($user))
            : $this->fail("That code doesn't match. Check the latest email, or ask for a new code.", 400);
    }

    public function resend(): ResponseInterface
    {
        $email = strtolower(trim((string) $this->request->getPost('email')));
        $user = $email === '' ? null : model(UserModel::class)->findByCredentials(['email' => $email]);

        // The same answer whether or not the account exists
        if (!$user || $user->active || !$this->hasPendingCode($user)) {
            return $this->ok();
        }
        if (!service('throttler')->check('activate-resend-' . $user->id, 1, MINUTE)) {
            return $this->fail('Wait a minute, then ask for a new code.', 429, ['code' => 'too_many_attempts']);
        }
        return ActivationMail::send($user)
            ? $this->ok()
            : $this->fail("We couldn't send the email. Try again in a minute.", 502, ['code' => 'mail_failed']);
    }

    public function logout(): ResponseInterface
    {
        $authenticator = auth('tokens')->getAuthenticator();
        auth()->user()->revokeAccessToken((string) $authenticator->getBearerToken());
        return $this->ok();
    }

    private function hasPendingCode(User $user): bool
    {
        return db_connect()->table('auth_identities')->where('user_id', $user->id)->where('type', 'email_activate')->countAllResults() > 0;
    }

    private function session(User $user): array
    {
        $finance = db_connect()->table('player_finances')->select('profile_completed')->where('user_id', $user->id)->get()->getRowArray();
        return [
            'token' => $user->generateAccessToken('android')->raw_token,
            'user' => ['id' => (int) $user->id, 'username' => (string) $user->username],
            'profileComplete' => (bool) ($finance['profile_completed'] ?? false),
        ];
    }
}
