<?php

namespace App\Controllers\Api\V1\App;

class Marketing extends \App\Controllers\Marketing
{
    use AppPage;

    protected const ACTIONS = ['launch', 'cancel', 'toggle'];
    protected const UNLOCK = 'marketing';
    protected const HIDDEN = null;
}
