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
        // The app shows the swap choices as text, formatted here like the web formats them
        foreach ($page['builtItems'] as $segId => $item) {
            if (empty($item['swaps'])) continue;
            $item['label'] = self::liftLabel((string) $item['subtype'], (int) $item['seats']);
            $item['capacityText'] = number_format((int) $item['capacity']) . '/hr';
            $item['runCostText'] = currency((int) $item['run_cost']) . '/day';
            $item['swaps'] = array_map(fn(array $o) => $o + [
                'label'        => self::liftLabel($o['lift_type'], $o['seats']),
                'capacityText' => number_format($o['capacity']) . '/hr',
                'runCostText'  => currency($o['run_cost']) . '/day',
                'priceText'    => $o['price'] < 0 ? currency(-$o['price']) . ' back' : currency($o['price']),
                'rebuildText'  => $o['rebuild'] < 0 ? currency(-$o['rebuild']) . ' back' : currency($o['rebuild']),
            ], $item['swaps']);
            $page['builtItems'][$segId] = $item;
        }
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

    /** Replace a built lift with another system: { lift_type, seats } */
    public function upgrade(int $id): ResponseInterface
    {
        return $this->fromAction($this->swapLift($id), fn(array $body) => $body + ['cashText' => $this->cashText()]);
    }

    private function cashText(): string
    {
        return currency((int) (db_connect()->table('player_finances')->select('cash')->where('user_id', auth()->id())->get()->getRow('cash') ?? 0));
    }
}
