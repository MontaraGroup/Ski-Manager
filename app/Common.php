<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the framework's
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 * that you'd like to use throughout your entire application
 *
 * @see: https://codeigniter.com/user_guide/extending/common.html
 */

if (! function_exists('initSentry')) {
    /**
     * Initializes the Sentry SDK for both Web and CLI runtimes.
     */
    function initSentry(): void
    {
        if (class_exists('\\Sentry\\SentrySdk') && \Sentry\SentrySdk::getCurrentHub()->getClient() !== null) {
            return;
        }

        $sentryDsn = getenv('SENTRY_DSN') ?: (function_exists('env') ? env('SENTRY_DSN') : ($_ENV['SENTRY_DSN'] ?? null));
        if (! empty($sentryDsn) && class_exists('\\Sentry\\ClientBuilder')) {
            \Sentry\init([
                'dsn'                  => $sentryDsn,
                'environment'          => defined('ENVIRONMENT') ? ENVIRONMENT : (getenv('CI_ENVIRONMENT') ?: 'production'),
                'release'              => 'ski-manager@' . (getenv('APP_REVISION') ?: '1.0.0'),
                'traces_sample_rate'   => 1.0,
                'profiles_sample_rate' => 1.0,
                'send_default_pii'     => false,
                'ignore_exceptions'    => [
                    \CodeIgniter\Exceptions\PageNotFoundException::class,
                    // Malformed client URIs (e.g. "/&") are rejected with a 400 by the router; expected, not an app error.
                    \CodeIgniter\HTTP\Exceptions\BadRequestException::class,
                ],
            ]);
        }
    }
}

