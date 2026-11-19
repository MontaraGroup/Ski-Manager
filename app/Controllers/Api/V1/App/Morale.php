<?php

namespace App\Controllers\Api\V1\App;

class Morale extends \App\Controllers\Morale
{
    use AppPage;

    protected const ACTIONS = ['boost'];
    protected const UNLOCK = null;
    protected const HIDDEN = null;
}
