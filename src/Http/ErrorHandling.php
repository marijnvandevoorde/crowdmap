<?php

declare(strict_types=1);

namespace Crowdmap\Http;

use Psr\Log\LoggerInterface;
use Slim\App;

final class ErrorHandling
{
    /** APP_DEBUG=true (or 1/yes/on) shows error details and stack traces; off by default. */
    public static function debugFromEnvironment(): bool
    {
        return filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Errors are always rendered as JSON: PHP only serves the API. Without debug the
     * client gets a generic message, never the exception or a stack trace; the error
     * is still logged.
     */
    public static function register(App $app, bool $debug, ?LoggerInterface $logger = null): void
    {
        $errorMiddleware = $app->addErrorMiddleware($debug, true, $debug, $logger);
        $errorMiddleware->getDefaultErrorHandler()->forceContentType('application/json');
    }
}
