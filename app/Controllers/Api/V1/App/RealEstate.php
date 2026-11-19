<?php

namespace App\Controllers\Api\V1\App;

class RealEstate extends \App\Controllers\RealEstate
{
    use AppPage;

    protected const ACTIONS = ['develop', 'sell', 'toggle', 'repair'];
    protected const UNLOCK = null;
    protected const HIDDEN = null;
}
