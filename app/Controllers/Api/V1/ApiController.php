<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * JSON API for the React islands. Routes sit under /api/v1 behind the apiauth filter, so every
 * request comes from a signed-in player; writes are CSRF-checked by the global csrf filter.
 * Every answer has the shape the client expects (resources/js/lib/api.ts):
 * { ok, data, message, errors }.
 */
abstract class ApiController extends BaseController
{
    protected function ok(mixed $data = null, ?string $message = null, int $status = 200): ResponseInterface
    {
        return $this->response->setStatusCode($status)->setJSON(['ok' => true, 'data' => $data, 'message' => $message, 'errors' => (object) []]);
    }

    /** $errors: field => message, for forms */
    protected function fail(string $message, int $status = 400, array $errors = []): ResponseInterface
    {
        return $this->response->setStatusCode($status)->setJSON(['ok' => false, 'data' => null, 'message' => $message, 'errors' => (object) $errors]);
    }
}
