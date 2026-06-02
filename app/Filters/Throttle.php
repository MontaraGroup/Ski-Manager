<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class Throttle implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $throttler = service('throttler');
        $ip = $request->getIPAddress();

        if (!$throttler->check(md5($ip), 10, MINUTE)) {
            return service('response')
                ->setStatusCode(429)
                ->setBody('Too many requests. Please wait a moment and try again.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
