<?php

namespace App\Controllers\Api\V1\App;

use CodeIgniter\HTTP\ResponseInterface;

class Staff extends \App\Controllers\Staff
{
    use AppResponses;

    public function show(): ResponseInterface
    {
        return $this->ok($this->pageData());
    }

    public function candidates(): ResponseInterface
    {
        return $this->ok($this->hireData());
    }

    public function hireAction(): ResponseInterface
    {
        return $this->fromAction($this->doHire(), fn() => $this->pageData());
    }

    public function autoAssignAction(): ResponseInterface
    {
        return $this->fromAction($this->autoAssign(), fn() => $this->pageData());
    }

    public function fireAction(int $id): ResponseInterface
    {
        return $this->fromAction($this->fire($id), fn() => $this->pageData());
    }

    public function trainAction(int $id): ResponseInterface
    {
        return $this->fromAction($this->train($id), fn() => $this->pageData());
    }
}
