<?php

namespace App\Controllers\Api\V1\App;

class Support extends \App\Controllers\Support
{
    use AppPage;

    protected const ACTIONS = ['send'];
    protected const UNLOCK = null;
    protected const HIDDEN = null;
}
