<?php

namespace App\Controllers\Api\V1\App;

use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * The controllers here extend the web controllers so the game has one set of rules. This turns what a
 * web action answers (a redirect with a flash message, or the map's JSON) into { ok, data, message, errors }.
 */
trait AppResponses
{
    protected function ok(mixed $data = null, ?string $message = null, int $status = 200): ResponseInterface
    {
        return $this->response->setStatusCode($status)->setJSON(['ok' => true, 'data' => $data, 'message' => $message, 'errors' => (object) []]);
    }

    protected function fail(string $message, int $status = 400, array $errors = []): ResponseInterface
    {
        return $this->response->setStatusCode($status)->setJSON(['ok' => false, 'data' => null, 'message' => $message, 'errors' => (object) $errors]);
    }

    /** $data builds the success payload, after the action has changed things */
    protected function fromAction(ResponseInterface $result, ?callable $data = null): ResponseInterface
    {
        if ($result instanceof RedirectResponse) {
            $session = session();
            $errors = $session->getFlashdata('errors');
            if ($errors) {
                $errors = (array) $errors;
                return $this->fail((string) reset($errors), 422, $errors);
            }
            $error = $session->getFlashdata('error');
            if ($error) {
                return $this->fail((string) $error, 400);
            }
            return $this->ok($data ? $data() : null, $session->getFlashdata('success') ?: $session->getFlashdata('info') ?: null);
        }

        $body = json_decode((string) $result->getBody(), true) ?? [];
        if (!empty($body['error'])) {
            return $this->fail((string) $body['error'], max(400, $result->getStatusCode()));
        }
        unset($body['csrf_token']);
        return $this->ok($data ? $data($body) : $body, $body['message'] ?? null);
    }
}
