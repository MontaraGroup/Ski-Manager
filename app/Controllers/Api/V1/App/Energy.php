<?php

namespace App\Controllers\Api\V1\App;

/** /energy in the app: App\Controllers\Resources serves both energy and water */
class Energy extends \App\Controllers\Resources
{
    use AppPage;

    protected const ACTIONS = ['buildEnergy', 'repairEnergy', 'toggleEnergy'];
    protected const UNLOCK = null;
    protected const HIDDEN = 'energy';

    protected function appData(): array
    {
        return $this->pageData('energy');
    }
}
