<?php

namespace App\Controllers\Api\V1\App;

class Roadmap extends \App\Controllers\Roadmap
{
    use AppPage;

    protected const ACTIONS = ['vote', 'suggest'];
    protected const UNLOCK = null;
    protected const HIDDEN = null;
}
