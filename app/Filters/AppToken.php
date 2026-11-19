<?php

namespace App\Filters;

use App\Libraries\Shell;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * For /api/v1/app: a valid access token and nothing else. Cookies are never read here, which is why
 * these routes can skip CSRF. It also does the jobs the path-based web filters do (season, maintenance,
 * feature flags, finished profile), since those don't match these paths.
 */
class AppToken implements FilterInterface
{
    /** Allowed before the player has chosen a difficulty and resort */
    private const WITHOUT_PROFILE = ['me', 'profile', 'auth/logout', 'account/delete'];

    public function before(RequestInterface $request, $arguments = null)
    {
        $auth = auth('tokens');
        $authenticator = $auth->getAuthenticator();
        $token = (string) $authenticator->getBearerToken();
        if ($token === '' || !$authenticator->attempt(['token' => $token])->isOK()) {
            return $this->deny(401, 'unauthenticated', 'Your session has ended. Sign in again.');
        }

        $user = $auth->user();
        if (!$user->active) {
            return $this->deny(403, 'account_inactive', 'This account is not active.');
        }

        $isAdmin = Shell::isAdmin();
        if (!$isAdmin && function_exists('isMaintenanceMode') && isMaintenanceMode()) {
            return $this->deny(503, 'maintenance', 'Ski Manager is down for maintenance. Try again soon.');
        }
        $db = db_connect();
        $season = $db->table('seasons')->where('active', 1)->get()->getRowArray();
        if ($user->id !== 1 && $season && !empty($season['start_date']) && strtotime($season['start_date']) > time()) {
            return $this->deny(503, 'coming_soon', "The next season hasn't started yet.");
        }

        $path = preg_replace('#^.*?api/v1/app/#', '', trim($request->getUri()->getPath(), '/'));
        $flag = FeatureGate::flagForPath($path);
        if ($flag !== null && !featureEnabled($flag) && !$isAdmin) {
            return $this->deny(403, 'feature_off', 'This feature is currently unavailable.');
        }

        if (!$isAdmin && !in_array($path, self::WITHOUT_PROFILE, true)) {
            $finance = $db->table('player_finances')->select('profile_completed')->where('user_id', $user->id)->get()->getRowArray();
            if ($finance && !$finance['profile_completed']) {
                return $this->deny(403, 'profile_incomplete', 'Choose your start first.');
            }
        }

        // Android closed-test reward: count the days this player used the Android app. Never blocks the request.
        if (self::isAndroid($request)) {
            try {
                \App\Libraries\AndroidTesting::recordAppDay((int) $user->id);
            } catch (\Throwable $e) {
                log_message('error', 'Android app day not recorded: ' . $e->getMessage());
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }

    /** The Android app sends X-Client: android/<version>; builds before that only show OkHttp's default User-Agent */
    private static function isAndroid(RequestInterface $request): bool
    {
        return str_starts_with(strtolower($request->getHeaderLine('X-Client')), 'android')
            || str_starts_with($request->getHeaderLine('User-Agent'), 'okhttp/');
    }

    private function deny(int $status, string $code, string $message): ResponseInterface
    {
        return service('response')->setStatusCode($status)->setJSON([
            'ok' => false, 'data' => null, 'message' => $message, 'errors' => ['code' => $code],
        ]);
    }
}
