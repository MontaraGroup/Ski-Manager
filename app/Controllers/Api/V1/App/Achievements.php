<?php

namespace App\Controllers\Api\V1\App;

class Achievements extends \App\Controllers\Achievements
{
    use AppPage;

    protected const ACTIONS = ['claim', 'claimAll'];
    protected const UNLOCK = null;
    protected const HIDDEN = null;
}
