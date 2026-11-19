<?php

namespace App\Controllers\Api\V1\App;

class Resort extends \App\Controllers\Resort
{
    use AppPage;

    protected const ACTIONS = ['toggleResort', 'toggleItem', 'repairLift', 'openAll', 'closeAll'];
    protected const UNLOCK = null;
    protected const HIDDEN = null;
}
