<?php

namespace App\Controllers\Api\V1\App;

class Parking extends \App\Controllers\Parking
{
    use AppPage;

    protected const ACTIONS = ['build', 'demolish', 'repair', 'toggle', 'updateFee'];
    protected const UNLOCK = 'parking';
    protected const HIDDEN = null;
}
