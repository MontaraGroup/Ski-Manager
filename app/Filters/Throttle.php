<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class Throttle implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if ($request->getMethod() !== 'POST') {
            return;
        }

        $ip = $request->getIPAddress();
        $throttler = service('throttler');

        if (!$throttler->check(md5($ip . '_login'), 20, MINUTE * 10)) {
            return service('response')
                ->setStatusCode(429)
                ->setBody(view('errors/html/lockout', ['minutes' => 10]));
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
