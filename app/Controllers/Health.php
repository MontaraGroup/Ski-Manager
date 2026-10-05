<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

class Health extends BaseController
{
    /**
     * System health check and telemetry endpoint.
     * Used by deployment smoke tests, load balancers, and uptime monitors.
     */
    public function index(): ResponseInterface
    {
        $dbStatus = 'disconnected';
        $dbLatencyMs = null;
        $isHealthy = true;
        $errors = [];

        // 1. Verify Database Connectivity & Measure Latency
        try {
            $start = microtime(true);
            $db = db_connect();
            $query = $db->query('SELECT 1 AS ping');
            $row = $query ? $query->getRow() : null;

            if ($row && (int) $row->ping === 1) {
                $dbStatus = 'connected';
                $dbLatencyMs = round((microtime(true) - $start) * 1000, 2);
            } else {
                $isHealthy = false;
                $errors[] = 'Database ping query failed';
            }
        } catch (\Throwable $e) {
            $isHealthy = false;
            $dbStatus = 'error: ' . $e->getMessage();
            $errors[] = 'Database exception: ' . $e->getMessage();
        }

        // 2. Verify Storage & Cache Write Permissions
        $storageStatus = 'writable';
        $writePath = WRITEPATH;
        if (!is_writable($writePath)) {
            $storageStatus = 'read-only';
            $isHealthy = false;
            $errors[] = 'WRITEPATH is not writable';
        }

        // 3. Assemble Telemetry Payload
        $payload = [
            'status'      => $isHealthy ? 'ok' : 'degraded',
            'app'         => 'Ski Manager',
            'environment' => defined('ENVIRONMENT') ? ENVIRONMENT : 'production',
            'ci_version'  => \CodeIgniter\CodeIgniter::CI_VERSION,
            'timestamp'   => gmdate('Y-m-d\TH:i:s\Z'),
            'checks'      => [
                'database' => [
                    'status'     => $dbStatus,
                    'latency_ms' => $dbLatencyMs,
                ],
                'storage' => [
                    'status' => $storageStatus,
                ],
            ],
        ];

        if (!empty($errors)) {
            $payload['errors'] = $errors;
        }

        $statusCode = $isHealthy ? 200 : 503;

        return $this->response
            ->setStatusCode($statusCode)
            ->setContentType('application/json')
            ->setJSON($payload);
    }
}
