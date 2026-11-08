<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class Throttle implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (strtolower($request->getMethod()) !== 'post') {
            return;
        }

        $ip = $request->getIPAddress();
        $throttler = service('throttler');

        if (!$throttler->check(md5($ip . '_login'), 20, MINUTE * 10)) {
            $path = trim($request->getUri()->getPath(), '/');
            $isJson = str_starts_with($path, 'api/')
                || (method_exists($request, 'hasHeader') && $request->hasHeader('Accept') && str_contains($request->getHeaderLine('Accept'), 'application/json'));

            if ($isJson) {
                return service('response')
                    ->setStatusCode(429)
                    ->setJSON([
                        'status'  => 'error',
                        'message' => 'Too many requests. Please try again in 10 minutes.',
                    ]);
            }

            return service('response')
                ->setStatusCode(429)
                ->setBody(view('errors/html/lockout', ['minutes' => 10]));
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
