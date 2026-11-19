<?php

namespace App\Controllers\Api\V1\App;

class DailyBonus extends \App\Controllers\DailyBonus
{
    use AppPage;

    protected const ACTIONS = ['claim'];
    protected const UNLOCK = null;
    protected const HIDDEN = null;
}
