<?php

namespace App\Controllers\Api\V1\App;

use CodeIgniter\HTTP\ResponseInterface;

class Notifications extends \App\Controllers\Notifications
{
    use AppResponses;

    public function show(): ResponseInterface
    {
        return $this->ok($this->pageData());
    }

    public function markAllRead(): ResponseInterface
    {
        return $this->fromAction($this->readAll());
    }
}
