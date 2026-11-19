<?php

namespace App\Controllers\Api\V1\App;

class NightSkiing extends \App\Controllers\NightSkiing
{
    use AppPage;

    protected const ACTIONS = ['buy', 'repair', 'sell', 'toggle', 'toggleAll'];
    protected const UNLOCK = 'night_skiing';
    protected const HIDDEN = null;
}
