<?php

namespace App\Controllers\Api\V1\App;

class Genepis extends \App\Controllers\Genepis
{
    use AppPage;

    protected const ACTIONS = ['buy', 'exchange'];
    protected const UNLOCK = null;
    protected const HIDDEN = null;
}
