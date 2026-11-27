<?php

namespace App\Libraries;

use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\ScopeInterface;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Common\Time\ClockFactory;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Trace\Sampler\ParentBased;
use OpenTelemetry\SDK\Trace\Sampler\TraceIdRatioBasedSampler;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;

/**
 * Request tracing to PostHog (Tracing), over OpenTelemetry OTLP through the POSTHOG_HOST proxy.
 *
 * One server span per web request (route, method, status, player id) with a child span per
 * database query slower than 2 ms. POSTHOG_TRACE_SAMPLE (default 0.2) sets the share of requests
 * traced. Spans are exported from the shutdown function in Config\Events, after the response
 * has gone out, and any failure only logs.
 */
class Tracing
{
    private const SLOW_QUERY_MS = 2.0;

    private static ?TracerProvider $provider = null;
    private static ?SpanInterface $root = null;
    private static ?ScopeInterface $scope = null;

    public static function start(): void
    {
        if (self::$provider || is_cli()) return;
        $key = (string) env('POSTHOG_KEY', '');
        if ($key === '' || !class_exists(TracerProvider::class)) return;
        try {
            $host = rtrim((string) env('POSTHOG_HOST', 'https://us.i.posthog.com'), '/');
            $transport = (new OtlpHttpTransportFactory())->create(
                $host . '/i/v1/traces',
                'application/x-protobuf',
                ['Authorization' => 'Bearer ' . $key],
            );
            $resource = ResourceInfoFactory::defaultResource()->merge(ResourceInfo::create(Attributes::create([
                'service.name' => 'ski-manager-web',
                'deployment.environment' => ENVIRONMENT,
            ])));
            $ratio = max(0.0, min(1.0, (float) env('POSTHOG_TRACE_SAMPLE', 0.2)));
            self::$provider = TracerProvider::builder()
                ->addSpanProcessor(new BatchSpanProcessor(new SpanExporter($transport), ClockFactory::getDefault()))
                ->setResource($resource)
                ->setSampler(new ParentBased(new TraceIdRatioBasedSampler($ratio)))
                ->build();

            $request = service('request');
            $method = method_exists($request, 'getMethod') ? strtoupper($request->getMethod()) : 'GET';
            $path = '/' . ltrim((string) (method_exists($request, 'getPath') ? $request->getPath() : ''), '/');
            self::$root = self::$provider->getTracer('ski-manager')
                ->spanBuilder($method . ' ' . $path)
                ->setSpanKind(SpanKind::KIND_SERVER)
                ->setAttribute('http.request.method', $method)
                ->setAttribute('url.path', $path)
                ->startSpan();
            // Links the trace to the browser's session replay in PostHog
            $sessionId = Analytics::sessionId();
            if ($sessionId !== null) self::$root->setAttribute('sessionId', $sessionId);
            self::$scope = self::$root->activate();
        } catch (\Throwable $e) {
            log_message('warning', 'Tracing start failed: ' . $e->getMessage());
            self::$provider = null;
            self::$root = null;
        }
    }

    /** Events::on('DBQuery'): a child span for each slow query, timed from the query itself */
    public static function query($query): void
    {
        if (!self::$root || !self::$root->isRecording() || !is_object($query)) return;
        try {
            $ms = (float) $query->getDuration(4) * 1000;
            if ($ms < self::SLOW_QUERY_MS) return;
            $endNs = (int) (microtime(true) * 1e9);
            $sql = (string) $query->getOriginalQuery();
            self::$provider->getTracer('ski-manager')
                ->spanBuilder('db ' . strtoupper(strtok(ltrim($sql), " \n") ?: 'query'))
                ->setSpanKind(SpanKind::KIND_CLIENT)
                ->setStartTimestamp($endNs - (int) ($ms * 1e6))
                ->setAttribute('db.system.name', 'mysql')
                // Query shape only: values are bound placeholders in the original query
                ->setAttribute('db.query.text', mb_substr($sql, 0, 1000))
                ->startSpan()
                ->end($endNs);
        } catch (\Throwable) {
        }
    }

    public static function finish(): void
    {
        if (!self::$provider) return;
        try {
            if (self::$root) {
                $status = (int) service('response')->getStatusCode();
                $matched = service('router')?->getMatchedRoute();
                $route = is_array($matched) ? (string) ($matched[0] ?? '') : '';
                self::$root->setAttribute('http.response.status_code', $status);
                if ($route !== '') {
                    self::$root->setAttribute('http.route', $route);
                    self::$root->updateName(strtoupper(service('request')->getMethod()) . ' /' . ltrim($route, '/'));
                }
                // Runs after the response was sent: read the player only from a session the request
                // already opened, since starting one now raises "headers already sent"
                if (session_status() === PHP_SESSION_ACTIVE && function_exists('auth') && auth()->loggedIn()) {
                    self::$root->setAttribute('user.id', (string) auth()->id());
                    self::$root->setAttribute('posthogDistinctId', (string) auth()->id());
                }
                if ($status >= 500) self::$root->setStatus(StatusCode::STATUS_ERROR);
                self::$root->end();
            }
            self::$scope?->detach();
            self::$provider->shutdown();
        } catch (\Throwable $e) {
            log_message('warning', 'Tracing export failed: ' . $e->getMessage());
        }
        self::$provider = null;
        self::$root = null;
        self::$scope = null;
    }
}
