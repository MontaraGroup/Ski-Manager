<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class FeatureGate implements FilterInterface
{
    private const ROUTE_FLAGS = [
        'snowmaking' => 'snowmaking',
        'bank' => 'bank_loans',
        'night-skiing' => 'night_skiing',
        'terrain-parks' => 'terrain_parks',
        'tournaments' => 'tournaments',
        'vip-guests' => 'vip_guests',
        'daily-bonus' => 'daily_bonus',
        'marketing' => 'marketing',
        'real-estate' => 'real_estate',
        'scenic-lifts' => 'scenic_lifts',
        'off-season' => 'off_season',
        'insurance' => 'insurance',
        'ski-lessons' => 'ski_school',
        'equipment' => 'equipment_shop',
        'grooming' => 'grooming',
        'hotels' => 'hotels',
        'restaurants' => 'restaurants',
        'rentals' => 'rentals',
        'parking' => 'parking',
        'energy' => 'energy',
        'water' => 'water',
        'emergency' => 'emergency',
        'government' => 'government',
        'leaderboard' => 'leaderboard',
        'tour' => 'resort_tours',
        'retail' => 'retail',
        'transportation' => 'transportation',
        'ski-patrol' => 'ski_patrol',
    ];

    public function before(RequestInterface $request, $arguments = null)
    {
        if (auth()->id() === 1) return;

        helper('feature');
        $path = trim($request->getUri()->getPath(), '/');
        $segment = explode('/', $path)[0] ?? '';

        foreach (self::ROUTE_FLAGS as $route => $flag) {
            if ($segment === $route || str_starts_with($path, $route . '/')) {
                if (!featureEnabled($flag)) {
                    return redirect()->to('/dashboard')->with('error', 'This feature is currently unavailable.');
                }
                break;
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
