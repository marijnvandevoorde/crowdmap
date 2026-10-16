<?php

declare(strict_types=1);

namespace Crowdmap\Tests\Unit\Http;

use Crowdmap\Http\ErrorHandling;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

class ErrorHandlingTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('APP_DEBUG');
    }

    private function request(bool $debug, string $path = '/api/boom', string $accept = 'text/html'): array
    {
        $app = AppFactory::create();
        ErrorHandling::register($app, $debug, new NullLogger());
        $app->get('/api/boom', function () {
            throw new \RuntimeException('secret internals at /app/src/Thing.php');
        });

        $request = (new ServerRequestFactory())->createServerRequest('GET', $path)
            ->withHeader('Accept', $accept);
        $response = $app->handle($request);

        return [$response, (string) $response->getBody()];
    }

    public function testWithoutDebugReturnsGenericJsonWithoutDetails(): void
    {
        [$response, $body] = $this->request(false);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringStartsWith('application/json', $response->getHeaderLine('Content-Type'));
        $json = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('message', $json);
        $this->assertArrayNotHasKey('exception', $json);
        $this->assertStringNotContainsString('secret internals', $body);
        $this->assertStringNotContainsString('Thing.php', $body);
    }

    public function testNotFoundIsJsonToo(): void
    {
        [$response, $body] = $this->request(false, '/api/nope');

        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringStartsWith('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertIsArray(json_decode($body, true, flags: JSON_THROW_ON_ERROR));
    }

    public function testWithDebugIncludesDetails(): void
    {
        [$response, $body] = $this->request(true);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('secret internals', $body);
    }

    public function testDebugIsOffUnlessEnabled(): void
    {
        putenv('APP_DEBUG');
        $this->assertFalse(ErrorHandling::debugFromEnvironment());
        putenv('APP_DEBUG=false');
        $this->assertFalse(ErrorHandling::debugFromEnvironment());
        putenv('APP_DEBUG=true');
        $this->assertTrue(ErrorHandling::debugFromEnvironment());
    }
}
