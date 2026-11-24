<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Turns away sign-up, login and magic-link posts that fill in the hidden hp_note_x7 field
 * (resources/js/components/BotTrap.tsx): people never see it, simple bots fill every field. The bot
 * gets the same "try again" a person would, so it learns nothing; the attempt is logged.
 */
class BotTrap implements FilterInterface
{
    public const FIELD = 'hp_note_x7';

    public function before(RequestInterface $request, $arguments = null)
    {
        if (strtolower($request->getMethod()) !== 'post') {
            return;
        }
        if (trim((string) $request->getPost(self::FIELD)) === '') {
            return;
        }
        log_message('notice', 'Bot trap: ' . $request->getUri()->getPath() . ' from ' . $request->getIPAddress());
        return redirect()->back()->withInput()->with('error', 'Something went wrong. Please try again.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
