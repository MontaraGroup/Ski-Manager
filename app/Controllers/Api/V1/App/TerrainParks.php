<?php

namespace App\Controllers\Api\V1\App;

class TerrainParks extends \App\Controllers\TerrainParks
{
    use AppPage;

    protected const ACTIONS = ['build', 'demolish', 'repair', 'toggle', 'hireCrew'];
    protected const UNLOCK = 'terrain_parks';
    protected const HIDDEN = null;
}
