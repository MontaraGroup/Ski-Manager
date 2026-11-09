<?php

namespace App\Controllers\Api\V1\App;

use CodeIgniter\HTTP\ResponseInterface;

class Tickets extends \App\Controllers\Tickets
{
    use AppResponses;

    public function show(): ResponseInterface
    {
        return $this->ok($this->pageData());
    }

    public function update(int $id): ResponseInterface
    {
        $this->request->setGlobal('post', [
            'id' => $id,
            'price' => $this->request->getPost('price'),
            'active' => $this->request->getPost('active'),
        ]);
        return $this->fromAction($this->updatePrice(), fn() => $this->pageData());
    }
}
