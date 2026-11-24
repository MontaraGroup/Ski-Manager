<?php

namespace App\Authentication\Passwords;

use CodeIgniter\Shield\Authentication\Passwords\PwnedValidator as ShieldPwnedValidator;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Result;
use Config\Services;

/**
 * Rejects passwords found in known data breaches (Have I Been Pwned's range API: only the first
 * 5 characters of the password's SHA-1 leave the server).
 *
 * Replaces Shield's check() for two reasons:
 * - Shield asks Services::curlrequest() for the shared client with a base_uri, but a shared client
 *   created earlier keeps its own options, so the request went to a host named "range" and failed.
 *   This uses its own client and the full URL.
 * - Shield throws when the service can't be reached, which would block every sign-up and password
 *   change; here the check is skipped and logged instead.
 */
class PwnedValidator extends ShieldPwnedValidator
{
    private const RANGE_URL = 'https://api.pwnedpasswords.com/range/';

    public function check(string $password, ?User $user = null): Result
    {
        $hash   = strtoupper(sha1($password));
        $prefix = substr($hash, 0, 5);
        $suffix = substr($hash, 5);

        try {
            $client   = Services::curlrequest(['timeout' => 4, 'connect_timeout' => 3, 'http_errors' => false], null, null, false);
            $response = $client->get(self::RANGE_URL . $prefix, ['headers' => ['Accept' => 'text/plain', 'Add-Padding' => 'true']]);
        } catch (\Throwable $e) {
            log_message('error', 'Breached-password check skipped: ' . $e->getMessage());
            return new Result(['success' => true]);
        }

        if ($response->getStatusCode() !== 200) {
            log_message('error', 'Breached-password check skipped: HTTP ' . $response->getStatusCode());
            return new Result(['success' => true]);
        }

        // Lines are "SUFFIX:COUNT"; padding lines have a count of 0
        foreach (preg_split('/\r?\n/', (string) $response->getBody()) as $line) {
            [$lineSuffix, $count] = array_pad(explode(':', trim($line), 2), 2, '0');
            if ($lineSuffix === $suffix && (int) $count > 0) {
                // Our own wording: Shield's repeats the password back on the page
                return new Result([
                    'success'   => false,
                    'reason'    => 'This password has shown up in ' . number_format((int) $count) . ' data breaches, so attackers try it. Pick a different one.',
                    'extraInfo' => 'A passphrase of four or more unrelated words is easy to remember and hard to guess.',
                ]);
            }
        }

        return new Result(['success' => true]);
    }
}
