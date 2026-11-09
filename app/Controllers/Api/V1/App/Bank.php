<?php

namespace App\Controllers\Api\V1\App;

use CodeIgniter\HTTP\ResponseInterface;

class Bank extends \App\Controllers\Bank
{
    use AppResponses;

    public function show(): ResponseInterface
    {
        return $this->ok($this->pageData());
    }

    public function borrowAction(): ResponseInterface
    {
        if (empty($this->request->getPost()) && $json = $this->request->getJSON(true)) {
            $this->request->setGlobal('post', $json);
        }
        return $this->fromAction($this->borrow(), fn() => $this->pageData());
    }

    public function repayAction(int $id): ResponseInterface
    {
        return $this->fromAction($this->repay($id), fn() => $this->pageData());
    }
}
