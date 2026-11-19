<?php

namespace App\Controllers\Api\V1\App;

use CodeIgniter\HTTP\ResponseInterface;

class ResortAnalysis extends \App\Controllers\ResortAnalysis
{
    use AppPage;

    protected const ACTIONS = ['order'];
    protected const UNLOCK = 'resort_analysis';
    protected const HIDDEN = null;

    public function report(int $id): ResponseInterface
    {
        if ($deny = $this->appGate()) {
            return $deny;
        }
        $page = $this->reportData($id);
        return $page === null ? $this->fail('Report not found.', 404, ['code' => 'not_found']) : $this->ok($page);
    }
}
