<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class FeatureGate implements FilterInterface
{
    /**
     * First URL segment => feature flag, for pages still being tested (admins only or beta).
     * Also used to hide nav links and to add the "Beta" chip. Released pages aren't listed:
     * they are always on. Add the same URL pattern to the featuregate entry in Config\Filters.
     */
    public const ROUTE_FLAGS = [];

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
