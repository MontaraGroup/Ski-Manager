<?php

namespace App\Controllers\Api\V1\App;

use CodeIgniter\HTTP\ResponseInterface;

class Snowmaking extends \App\Controllers\Snowmaking
{
    use AppResponses;

    public function show(): ResponseInterface
    {
        return $this->ok($this->pageData());
    }

    public function toggleAction(int $id): ResponseInterface
    {
        return $this->fromAction($this->toggle($id), fn() => $this->pageData());
    }

    public function toggleAllAction(): ResponseInterface
    {
        if (empty($this->request->getPost()) && $json = $this->request->getJSON(true)) {
            $this->request->setGlobal('post', $json);
        }
        return $this->fromAction($this->toggleAll(), fn() => $this->pageData());
    }

    public function repairAction(int $id): ResponseInterface
    {
        return $this->fromAction($this->repair($id), fn() => $this->pageData());
    }

    public function assignAction(): ResponseInterface
    {
        if (empty($this->request->getPost()) && $json = $this->request->getJSON(true)) {
            $this->request->setGlobal('post', $json);
        }
        return $this->fromAction($this->assignCannon(), fn() => $this->pageData());
    }

    public function sellAction(int $id): ResponseInterface
    {
        return $this->fromAction($this->sell($id), fn() => $this->pageData());
    }
}
