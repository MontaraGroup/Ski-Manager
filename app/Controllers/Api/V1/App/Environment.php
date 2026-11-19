<?php

namespace App\Controllers\Api\V1\App;

class Environment extends \App\Controllers\Environment
{
    use AppPage;

    protected const ACTIONS = ['buyUpgrade'];
    protected const UNLOCK = null;
    protected const HIDDEN = null;
}
