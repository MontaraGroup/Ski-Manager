<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Android closed test (App\Libraries\AndroidTesting). The link is stored with
 * setting('AndroidTesting.joinUrl'), set from /admin/android-testing; this is the default until then.
 */
class AndroidTesting extends BaseConfig
{
    /** Where testers enter their access code: the closed test's link from Play Console, or '' */
    public string $joinUrl = '';
}
