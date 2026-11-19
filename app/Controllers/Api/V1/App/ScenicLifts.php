<?php

namespace App\Controllers\Api\V1\App;

class ScenicLifts extends \App\Controllers\ScenicLifts
{
    use AppPage;

    protected const ACTIONS = ['designate', 'remove', 'upgrade', 'updatePrice'];
    protected const UNLOCK = null;
    protected const HIDDEN = null;
}
