<?php

namespace App\Controllers\Api\V1\App;

class Tournaments extends \App\Controllers\Tournaments
{
    use AppPage;

    protected const ACTIONS = ['host', 'cancel'];
    protected const UNLOCK = null;
    protected const HIDDEN = null;
}
