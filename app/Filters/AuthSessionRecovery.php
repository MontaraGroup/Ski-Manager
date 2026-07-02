<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthSessionRecovery implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! $request instanceof IncomingRequest) {
            return;
        }

        $session = session();
        $code = trim($request->getPost('code') ?? '');

        // If the browser dropped the identity state, rebuild it using the unique code
        if (!empty($code) && !$session->has('auth_user_id')) {
            $db = \Config\Database::connect();
            $identity = $db->table('auth_identities')
                ->where('secret', $code)
                ->where('type', 'email_activate')
                ->get()->getRowArray();

            if ($identity) {
                // Reconstruct the exact parameters Shield requires to initialize its internal actions
                $session->set('auth_user_id', $identity['user_id']);
                $session->set('auth_action', \CodeIgniter\Shield\Authentication\Actions\EmailActivator::class);
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
