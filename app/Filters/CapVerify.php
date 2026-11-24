<?php

namespace App\Filters;

use App\Libraries\Cap;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Sign-up, login and magic-link posts need a solved Cap widget (App\Libraries\Cap), checked with capd
 * before Shield touches the account or sends an email. Off when Cap isn't configured in .env.
 */
class CapVerify implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (strtolower($request->getMethod()) !== 'post' || !Cap::enabled()) {
            return;
        }
        if (Cap::verify((string) $request->getPost(Cap::FIELD))) {
            return;
        }
        return redirect()->back()->withInput()->with('error', "Please tick \"I'm a human\" and try again.");
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
