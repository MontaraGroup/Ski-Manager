<?php

namespace App\Controllers\Api\V1\App;

use App\Controllers\BaseController;
use App\Libraries\AccountPurge;
use CodeIgniter\HTTP\ResponseInterface;

/** In-app account deletion, which Google Play requires. Same checks as the web's Account::deleteAccount */
class Account extends BaseController
{
    use AppResponses;

    public function delete(): ResponseInterface
    {
        if ($this->request->getPost('confirm') !== 'DELETE') {
            return $this->fail('Type DELETE to confirm.', 422, ['confirm' => 'Type DELETE to confirm.']);
        }
        $user = auth()->user();
        $userId = (int) $user->id;
        if (!service('throttler')->check('account-delete-' . $userId, 5, 15 * MINUTE)) {
            return $this->fail('Too many attempts. Try again in a few minutes.', 429, ['code' => 'too_many_attempts']);
        }
        $password = (string) $this->request->getPost('password');
        if ($password === '' || !auth('session')->check(['email' => $user->email, 'password' => $password])->isOK()) {
            return $this->fail('Incorrect password. Your account was not deleted.', 403, ['code' => 'wrong_password']);
        }
        AccountPurge::purge($userId);
        return $this->ok(null, 'Your account has been deleted.');
    }
}
