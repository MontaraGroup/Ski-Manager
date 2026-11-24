<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class FeatureGate implements FilterInterface
{
    /** First URL segment => feature flag. Also used to hide nav links. */
    public const ROUTE_FLAGS = [
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

    /** The flag that gates a URL path, or null if none does */
    public static function flagForPath(string $path): ?string
    {
        $segment = explode('/', trim($path, '/'))[0] ?? '';
        return self::ROUTE_FLAGS[$segment] ?? null;
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        helper('feature');
        $flag = self::flagForPath($request->getUri()->getPath());

        // featureEnabled() already lets admins through "admins only" flags; fully off is off for everyone but admins too
        if ($flag !== null && !featureEnabled($flag) && !featureViewerIsAdmin()) {
            return redirect()->to('/dashboard')->with('error', 'This feature is currently unavailable.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
