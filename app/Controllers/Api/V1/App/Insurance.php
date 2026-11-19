<?php

namespace App\Controllers\Api\V1\App;

class Insurance extends \App\Controllers\Insurance
{
    use AppPage;

    protected const ACTIONS = ['toggle'];
    protected const UNLOCK = null;
    protected const HIDDEN = 'insurance';
}
