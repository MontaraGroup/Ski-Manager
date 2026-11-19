<?php

namespace App\Controllers\Api\V1\App;

class OffSeason extends \App\Controllers\OffSeason
{
    use AppPage;

    protected const ACTIONS = ['runMaintenance', 'buildActivity'];
    protected const UNLOCK = 'off_season';
    protected const HIDDEN = null;
}
