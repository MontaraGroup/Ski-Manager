<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class Throttle implements FilterInterface
{
    /** Per-account attempts allowed before the bucket has to refill. */
    private const ACCOUNT_ATTEMPTS = 5;
    private const ACCOUNT_WINDOW   = 15 * MINUTE;

    public function before(RequestInterface $request, $arguments = null)
    {
        if (strtolower($request->getMethod()) !== 'post') {
            return;
        }

        $path      = trim($request->getUri()->getPath(), '/');
        $ip        = $request->getIPAddress();
        $throttler = service('throttler');

        if (!$throttler->check(md5($ip . '_login'), 20, MINUTE * 10)) {
            // JSON API callers (the mobile auth endpoint) cannot use the HTML lockout page.
            if (str_starts_with($path, 'api/')) {
                return service('response')
                    ->setStatusCode(429)
                    ->setHeader('Retry-After', (string) (MINUTE * 10))
                    ->setHeader('Access-Control-Allow-Origin', '*')
                    ->setJSON([
                        'status'  => 'error',
                        'message' => 'Too many authentication attempts. Try again later.',
                    ]);
            }

            return service('response')
                ->setStatusCode(429)
                ->setBody(view('errors/html/lockout', ['minutes' => 10]));
        }

        // Limit the web login and magic-link forms per account as well as per IP.
        $scope = $this->accountScope($path);
        if ($scope === null) {
            return;
        }

        $key = $this->accountKey($request, $scope);
        if ($key === null || $throttler->check($key, self::ACCOUNT_ATTEMPTS, self::ACCOUNT_WINDOW)) {
            return;
        }

        $retryAfter = max(1, $throttler->getTokenTime());

        return service('response')
            ->setStatusCode(429)
            ->setHeader('Retry-After', (string) $retryAfter)
            ->setBody(view('errors/html/lockout', [
                'minutes' => max(1, (int) ceil($retryAfter / 60)),
                'message' => 'Too many attempts for this account. Try again later.',
            ]));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Clear the bucket only when this request logged into the submitted account.
        if (strtolower($request->getMethod()) !== 'post'
            || trim($request->getUri()->getPath(), '/') !== 'login'
            || ! function_exists('auth')) {
            return;
        }

        $identity = $this->accountIdentity($request);
        $user     = auth()->user();
        if ($identity === null || $user === null
            || ($identity !== strtolower((string) $user->email)
                && $identity !== strtolower((string) $user->username))) {
            return;
        }

        $key = $this->accountKey($request, 'login');
        if ($key !== null) {
            service('throttler')->remove($key);
        }
    }

    /** @return 'login'|'magic'|null */
    private function accountScope(string $path): ?string
    {
        return match ($path) {
            'login'                          => 'login',
            'login/magic-link', 'magic-link' => 'magic',
            default                          => null,
        };
    }

    private function accountKey(RequestInterface $request, string $scope): ?string
    {
        $identity = $this->accountIdentity($request);
        if ($identity === null) {
            return null;
        }

        // Password login shares the API's bucket; magic-link requests get their own.
        $prefix = $scope === 'login' ? 'auth-account-' : 'auth-magic-account-';

        return $prefix . hash('sha256', $identity);
    }

    private function accountIdentity(RequestInterface $request): ?string
    {
        if (! $request instanceof IncomingRequest) {
            return null;
        }

        $identity = $request->getPost('email');
        if (! is_string($identity) || trim($identity) === '') {
            $identity = $request->getPost('username');
        }
        if (! is_string($identity) || trim($identity) === '') {
            return null;
        }

        return strtolower(trim($identity));
    }
}
