<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * Public API discovery: RFC 9727 API catalog, OpenAPI description and human docs.
 * URLs are built from base_url() so staging advertises staging, not production.
 */
class Developers extends BaseController
{
    /** GET|HEAD /.well-known/api-catalog (RFC 9727, Linkset format per RFC 9264) */
    public function apiCatalog(): ResponseInterface
    {
        $base = rtrim(base_url(), '/');

        $catalog = [
            'linkset' => [[
                'anchor'       => $base . '/api',
                'service-desc' => [['href' => $base . '/openapi.json', 'type' => 'application/vnd.oai.openapi+json']],
                'service-doc'  => [['href' => $base . '/developers', 'type' => 'text/html']],
                'status'       => [['href' => $base . '/health', 'type' => 'application/json']],
            ]],
        ];

        return $this->response
            ->setStatusCode(200)
            ->setHeader('Content-Type', 'application/linkset+json; profile="https://www.rfc-editor.org/info/rfc9727"')
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Cache-Control', 'public, max-age=3600')
            ->setBody(json_encode($catalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /** GET /openapi.json */
    public function openapi(): ResponseInterface
    {
        return $this->response
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Cache-Control', 'public, max-age=3600')
            ->setContentType('application/json')
            ->setBody(json_encode($this->spec(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * GET|HEAD /auth.md — Auth.md for agents. Ski Manager has no OAuth server or agent
     * registration, so this is the self-contained form and says so plainly.
     */
    public function authMd(): ResponseInterface
    {
        $base = rtrim(base_url(), '/');
        $md = <<<MD
# Ski Manager auth.md

How AI agents and other automated clients can access Ski Manager ({$base}).

## Summary

- **No credentials are needed.** The public API is read-only and open to everyone.
- **There is no agent registration.** Ski Manager has no OAuth authorization server, no client registration and no API keys, so there is nothing to sign up for.
- **Accounts are for people.** A human creates an account at {$base}/register and plays in a browser.

## Who this is for

Agents, crawlers and scripts that want to read Ski Manager game data, for example the trail map, the current season, the weather or the leaderboard.

## Access without credentials

Send plain HTTPS requests. Responses are JSON and cross-origin requests are allowed.

| Method | Endpoint | What it returns |
| --- | --- | --- |
| GET | {$base}/api/resort-data | Trail map segments and sectors, current season and weather |
| GET | {$base}/api/leaderboard | Top 50 resorts |
| GET | {$base}/health | Service health |

Full description: {$base}/openapi.json (OpenAPI 3.1). Human docs: {$base}/developers. API catalog: {$base}/.well-known/api-catalog.

## What not to rely on

- `POST /api/auth` is a demo endpoint for the mobile apps. It returns a random token without checking anything, and no endpoint accepts that token. It is not a way to authenticate.
- `POST /api/sync` echoes back what you send and saves nothing.

## Acting for a person

Player-specific data (a resort's finances, lifts and buildings) is only returned to a person's own signed-in browser session. Agents must not create accounts, and must not sign in with someone's email and password on their behalf. If a person wants to share their resort data, they should sign in themselves.

## Limits and contact

Keep requests to one every few seconds. Game state only changes once a day, when the nightly update runs. Questions: {$base}/contact
MD;

        return $this->response
            ->setHeader('Content-Type', 'text/markdown; charset=UTF-8')
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Cache-Control', 'public, max-age=3600')
            ->setBody($md);
    }

    /** GET /developers */
    public function index(): string
    {
        $base = rtrim(base_url(), '/');
        $page = [
            'base' => $base,
            'endpoints' => [
                ['method' => 'GET', 'path' => '/api/resort-data', 'desc' => 'Trail map segments and sectors, the current season and weather. With a ski-manager.net session cookie it also returns your finances, lifts, slopes and buildings; without one, finances are a demo baseline and the player lists are empty.'],
                ['method' => 'GET', 'path' => '/api/leaderboard', 'desc' => 'The top 50 resorts, ranked by cash plus lifetime income.'],
                ['method' => 'POST', 'path' => '/api/auth', 'desc' => 'Demo sign-in used by the mobile apps. Returns a random token. It does not check credentials, and no other endpoint needs the token.'],
                ['method' => 'POST', 'path' => '/api/sync', 'desc' => 'Acknowledges a sync from the mobile apps and echoes back the cash you send. Nothing is saved.'],
                ['method' => 'GET', 'path' => '/health', 'desc' => 'Database and storage checks. Returns 200 when healthy and 503 when something is down.'],
            ],
            'catalogUrl' => $base . '/.well-known/api-catalog',
            'openapiUrl' => $base . '/openapi.json',
            'authMdUrl' => $base . '/auth.md',
        ];

        return view('developers', ['page' => $page]);
    }

    private function spec(): array
    {
        $json = fn(array $schema, string $description = 'OK') => [
            'description' => $description,
            'content'     => ['application/json' => ['schema' => $schema]],
        ];
        $obj = fn(array $props, array $required = []) => array_filter([
            'type' => 'object', 'properties' => $props, 'required' => $required ?: null,
        ], fn($v) => $v !== null);

        return [
            'openapi' => '3.1.0',
            'info'    => [
                'title'       => 'Ski Manager API',
                'version'     => '1.0.0',
                'description' => 'Read-only game data used by the Ski Manager mobile apps, plus a health check. '
                    . 'Endpoints return the signed-in player\'s data when called with a ski-manager.net session cookie, '
                    . 'and public or demo data otherwise.',
                'contact'     => ['name' => 'Ski Manager', 'url' => rtrim(base_url(), '/') . '/contact'],
            ],
            'servers' => [['url' => rtrim(base_url(), '/')]],
            'paths'   => [
                '/api/resort-data' => ['get' => [
                    'operationId' => 'getResortData',
                    'summary'     => 'Trail map, season, weather and (when signed in) your resort',
                    'description' => 'Returns the active map segments and sectors, the current season and weather. '
                        . 'With a session cookie it also includes your finances, lifts, slopes and buildings; '
                        . 'without one, finances are a demo baseline and the player arrays are empty.',
                    'responses'   => ['200' => $json($obj([
                        'status'        => ['type' => 'string', 'example' => 'success'],
                        'server_time'   => ['type' => 'integer', 'description' => 'Unix timestamp'],
                        'resort_map'    => ['type' => 'string', 'example' => 'ParkCity'],
                        'map_image_url' => ['type' => 'string', 'format' => 'uri'],
                        'season'        => ['type' => 'object'],
                        'game_day'      => ['type' => 'integer'],
                        'finances'      => ['type' => 'object'],
                        'segments'      => ['type' => 'array', 'items' => $obj([
                            'id'            => ['type' => 'integer'],
                            'type'          => ['type' => 'string', 'description' => 'lift, downhill, snowpark, ...'],
                            'difficulty'    => ['type' => ['string', 'null'], 'enum' => ['green', 'blue', 'black', 'double_black', null]],
                            'name'          => ['type' => 'string'],
                            'points'        => ['type' => 'array', 'items' => ['type' => 'array', 'items' => ['type' => 'number']]],
                            'length_meters' => ['type' => 'integer'],
                            'sector'        => ['type' => 'integer'],
                        ])],
                        'sectors'       => ['type' => 'array', 'items' => ['type' => 'object']],
                        'player_lifts'  => ['type' => 'array', 'items' => ['type' => 'object']],
                        'player_slopes' => ['type' => 'array', 'items' => ['type' => 'object']],
                        'buildings'     => ['type' => 'array', 'items' => ['type' => 'object']],
                        'weather'       => ['type' => 'object'],
                    ], ['status', 'segments', 'sectors']))],
                ]],
                '/api/leaderboard' => ['get' => [
                    'operationId' => 'getLeaderboard',
                    'summary'     => 'Top 50 resorts',
                    'description' => 'Ranked by cash plus lifetime income.',
                    'responses'   => ['200' => $json(['type' => 'array', 'items' => $obj([
                        'id'           => ['type' => 'integer'],
                        'username'     => ['type' => 'string'],
                        'resortName'   => ['type' => 'string'],
                        'cash'         => ['type' => 'integer'],
                        'reputation'   => ['type' => 'integer'],
                        'rankPosition' => ['type' => 'integer'],
                    ], ['id', 'username', 'cash', 'rankPosition'])])],
                ]],
                '/api/auth' => ['post' => [
                    'operationId' => 'mobileDemoAuth',
                    'summary'     => 'Demo sign-in for the mobile apps',
                    'description' => 'Returns a random token for the mobile demo. It does not check credentials, '
                        . 'and no other endpoint requires the token.',
                    'requestBody' => ['required' => false, 'content' => ['application/json' => ['schema' => $obj([
                        'username' => ['type' => 'string'],
                    ])]]],
                    'responses'   => ['200' => $json($obj([
                        'status'   => ['type' => 'string', 'example' => 'authenticated'],
                        'token'    => ['type' => 'string'],
                        'username' => ['type' => 'string'],
                        'server'   => ['type' => 'string', 'format' => 'uri'],
                    ]))],
                ]],
                '/api/sync' => ['post' => [
                    'operationId' => 'mobileSync',
                    'summary'     => 'Mobile sync acknowledgement',
                    'description' => 'Acknowledges a sync from the mobile apps and echoes back the cash value sent. Nothing is saved.',
                    'requestBody' => ['required' => false, 'content' => ['application/json' => ['schema' => $obj([
                        'cash'     => ['type' => 'integer'],
                        'game_day' => ['type' => 'integer'],
                    ])]]],
                    'responses'   => ['200' => $json($obj([
                        'status'          => ['type' => 'string', 'example' => 'success'],
                        'synced_at'       => ['type' => 'string'],
                        'server_time'     => ['type' => 'integer'],
                        'server_game_day' => ['type' => 'integer'],
                        'confirmed_cash'  => ['type' => 'integer'],
                    ]))],
                ]],
                '/health' => ['get' => [
                    'operationId' => 'getHealth',
                    'summary'     => 'Service health',
                    'description' => 'Database and storage checks. 200 when healthy, 503 when degraded.',
                    'responses'   => [
                        '200' => $json($obj([
                            'status'    => ['type' => 'string', 'enum' => ['ok', 'degraded']],
                            'app'       => ['type' => 'string'],
                            'timestamp' => ['type' => 'string', 'format' => 'date-time'],
                            'checks'    => ['type' => 'object'],
                        ], ['status'])),
                        '503' => ['description' => 'Degraded: a check failed'],
                    ],
                ]],
            ],
        ];
    }
}
