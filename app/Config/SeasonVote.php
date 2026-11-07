<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Season format vote. The close date is stored with setting('SeasonVote.closesAt'), set from
 * /admin/season-vote; this null is the default until an admin sets one.
 */
class SeasonVote extends BaseConfig
{
    /** 'Y-m-d H:i:s' in the app timezone, or null: the vote stays closed */
    public ?string $closesAt = null;
}
