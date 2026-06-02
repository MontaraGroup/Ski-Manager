<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class Throttle implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $ip = $request->getIPAddress();
        $db = db_connect();

        $lockout = $db->table('login_lockouts')->where('ip_address', $ip)->where('locked_until >', date('Y-m-d H:i:s'))->get()->getRowArray();
        if ($lockout) {
            $remaining = (int) ceil((strtotime($lockout['locked_until']) - time()) / 60);
            return service('response')
                ->setStatusCode(429)
                ->setBody(view('errors/html/lockout', ['minutes' => $remaining]));
        }

        $throttler = service('throttler');
        if (!$throttler->check(md5($ip . '_login'), 5, MINUTE * 5)) {
            $lockMinutes = 30;
            $existing = $db->table('login_lockouts')->where('ip_address', $ip)->get()->getRowArray();
            if ($existing) {
                $db->table('login_lockouts')->where('ip_address', $ip)->update([
                    'attempts' => (int) $existing['attempts'] + 1,
                    'locked_until' => date('Y-m-d H:i:s', time() + ($lockMinutes * 60)),
                ]);
            } else {
                $db->table('login_lockouts')->insert([
                    'ip_address' => $ip,
                    'attempts' => 5,
                    'locked_until' => date('Y-m-d H:i:s', time() + ($lockMinutes * 60)),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }

            return service('response')
                ->setStatusCode(429)
                ->setBody(view('errors/html/lockout', ['minutes' => $lockMinutes]));
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
