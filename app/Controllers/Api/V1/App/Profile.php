<?php

namespace App\Controllers\Api\V1\App;

use App\Controllers\CompleteProfile;
use CodeIgniter\HTTP\ResponseInterface;

class Profile extends CompleteProfile
{
    use AppResponses;

    public function submit(): ResponseInterface
    {
        // save() sets the starting cash, so it can only run once
        $done = db_connect()->table('player_finances')->select('profile_completed')->where('user_id', auth()->id())->get()->getRow('profile_completed');
        if ($done) {
            return $this->fail('Your start is already chosen.', 409, ['code' => 'profile_complete']);
        }
        return $this->fromAction($this->save(), fn() => ['profileComplete' => true]);
    }
}
