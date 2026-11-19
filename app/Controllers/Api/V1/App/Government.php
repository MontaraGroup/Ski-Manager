<?php

namespace App\Controllers\Api\V1\App;

class Government extends \App\Controllers\Government
{
    use AppPage;

    protected const ACTIONS = ['toggle', 'complyAll'];
    protected const UNLOCK = null;
    protected const HIDDEN = 'government';
}
