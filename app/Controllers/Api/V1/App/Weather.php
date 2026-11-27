<?php

namespace App\Controllers\Api\V1\App;

class Weather extends \App\Controllers\Weather
{
    use AppPage;

    protected const ACTIONS = ['subscribe', 'renew'];
    protected const UNLOCK = null;
    protected const HIDDEN = null;
}
