<?php

namespace App\Controllers\Api\V1\App;

use App\Libraries\ResortVote;

/**
 * mode "monthly": the monthly resort vote (ResortVote::page; voting goes through resort-vote/*).
 * mode "season4": the older Season 4 vote, cast and retract here.
 */
class Vote extends \App\Controllers\Vote
{
    use AppPage;

    protected const ACTIONS = ['cast', 'retract'];
    protected const UNLOCK = null;
    protected const HIDDEN = null;

    protected function appData(): array
    {
        if (ResortVote::flagOn()) {
            return ['mode' => 'monthly'] + ResortVote::page((int) auth()->id());
        }
        return ['mode' => 'season4'] + $this->season4Data();
    }
}
