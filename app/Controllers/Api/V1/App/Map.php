<?php

namespace App\Controllers\Api\V1\App;

use App\Controllers\ResortMap;
use CodeIgniter\HTTP\ResponseInterface;

class Map extends ResortMap
{
    use AppResponses;

    /** The app builds with the web's default lift, so each segment's price is what /map/build will charge */
    private const DEFAULT_LIFT = 'chair_fixed';
    private const DEFAULT_SEATS = 4;

    public function show(): ResponseInterface
    {
        $page = $this->pageData();
        unset($page['csrf'], $page['resortMaps']);
        $page['segments'] = array_map(function ($segment) {
            $segment['cost'] = self::calcCost($segment['type'], self::DEFAULT_LIFT, self::DEFAULT_SEATS, (float) $segment['length_meters']);
            $segment['costText'] = currency((int) $segment['cost']);
            return $segment;
        }, $page['segments']);
        return $this->ok($page);
    }

    public function build(): ResponseInterface
    {
        return $this->fromAction($this->buildItem(), fn(array $body) => $body + ['cashText' => $this->cashText()]);
    }

    public function demolish(int $id): ResponseInterface
    {
        return $this->fromAction($this->demolishItem($id), fn(array $body = []) => $body + ['cashText' => $this->cashText()]);
    }

    private function cashText(): string
    {
        return currency((int) (db_connect()->table('player_finances')->select('cash')->where('user_id', auth()->id())->get()->getRow('cash') ?? 0));
    }
}
