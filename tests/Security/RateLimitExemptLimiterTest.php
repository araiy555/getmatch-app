<?php

namespace App\Tests\Security;

use App\Security\RateLimitExemptLimiter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RateLimiter\RequestRateLimiterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimit;

/**
 * @covers \App\Security\RateLimitExemptLimiter
 */
class RateLimitExemptLimiterTest extends TestCase {
    /**
     * @var RequestRateLimiterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $decorated;

    /**
     * @var \App\Security\RateLimitExemptLimiter
     */
    private $rateLimiter;

    protected function setUp(): void {
        $this->decorated = $this->createMock(RequestRateLimiterInterface::class);
        $this->rateLimiter = new RateLimitExemptLimiter($this->decorated, [
            '127.0.0.0/8',
        ]);
    }

    public function testExemptIpsAreNotThrottled(): void {
        $request = new Request();
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $this->decorated
            ->expects($this->never())
            ->method('consume');

        $limit = $this->rateLimiter->consume($request);

        $this->assertSame(\PHP_INT_MAX, $limit->getLimit());
        $this->assertSame(\PHP_INT_MAX, $limit->getRemainingTokens());
    }

    public function testConsumingDefersToDecoratedLimiterOnNonExemptIp(): void {
        $request = new Request();
        $request->server->set('REMOTE_ADDR', '10.0.0.1');

        $limit = $this->createMock(RateLimit::class);

        $this->decorated
            ->expects($this->once())
            ->method('consume')
            ->with($request)
            ->willReturn($limit);

        $this->assertSame($limit, $this->rateLimiter->consume($request));
    }

    public function testResettingDefersToDecoratedLimiter(): void {
        $request = new Request();
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $this->decorated
            ->expects($this->once())
            ->method('reset')
            ->with($request);

        $this->rateLimiter->reset($request);
    }
}
