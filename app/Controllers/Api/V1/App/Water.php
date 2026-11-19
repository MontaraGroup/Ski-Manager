<?php

namespace App\Controllers\Api\V1\App;

/** /water in the app: App\Controllers\Resources serves both energy and water */
class Water extends \App\Controllers\Resources
{
    use AppPage;

    protected const ACTIONS = ['buildWater', 'repairWater', 'toggleWater'];
    protected const UNLOCK = null;
    protected const HIDDEN = 'water';

    protected function appData(): array
    {
        return $this->pageData('water');
    }
}
