<?php

namespace App\Controllers\Api\V1\App;

use CodeIgniter\HTTP\ResponseInterface;

class Buildings extends \App\Controllers\Buildings
{
    use AppResponses;

    public function show(string $type = 'hotel'): ResponseInterface
    {
        try {
            return $this->ok($this->pageData($type));
        } catch (\CodeIgniter\Exceptions\PageNotFoundException $e) {
            return $this->fail('Building type not found.', 404);
        }
    }

    public function buildAction(): ResponseInterface
    {
        if (empty($this->request->getPost()) && $json = $this->request->getJSON(true)) {
            $this->request->setGlobal('post', $json);
        }
        $type = (string) ($this->request->getPost('type') ?: 'hotel');
        return $this->fromAction($this->build(), fn() => $this->pageData($type));
    }

    public function toggleAction(int $id): ResponseInterface
    {
        $building = $this->model->where('user_id', auth()->id())->find($id);
        $type = $building ? $building['building_type'] : 'hotel';
        return $this->fromAction($this->toggle($id), fn() => $this->pageData($type));
    }

    public function upgradeAction(int $id): ResponseInterface
    {
        $building = $this->model->where('user_id', auth()->id())->find($id);
        $type = $building ? $building['building_type'] : 'hotel';
        return $this->fromAction($this->upgrade($id), fn() => $this->pageData($type));
    }

    public function sellAction(int $id): ResponseInterface
    {
        $building = $this->model->where('user_id', auth()->id())->find($id);
        $type = $building ? $building['building_type'] : 'hotel';
        return $this->fromAction($this->sell($id), fn() => $this->pageData($type));
    }
}
