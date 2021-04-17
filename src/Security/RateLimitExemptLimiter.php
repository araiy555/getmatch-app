<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\RateLimiter\RequestRateLimiterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimit;

/**
 * Decorator for rate limiter that removes the limit for allowed IP addresses.
 */
class RateLimitExemptLimiter implements RequestRateLimiterInterface {
    /**
     * @var RequestRateLimiterInterface
     */
    private $decorated;

    /**
     * @var string[]
     */
    private $ipWhitelist;

    public function __construct(
        RequestRateLimiterInterface $decorated,
        array $ipWhitelist
    ) {
        $this->decorated = $decorated;
        // TODO: $ipWhitelist should not contain null values
        $this->ipWhitelist = array_filter($ipWhitelist);
    }

    public function consume(Request $request): RateLimit {
        if (IpUtils::checkIp($request->getClientIp(), $this->ipWhitelist)) {
            return new RateLimit(PHP_INT_MAX, new \DateTimeImmutable('@'.time()), true, PHP_INT_MAX);
        }

        return $this->decorated->consume($request);

    }

    public function reset(Request $request): void {
        $this->decorated->reset($request);
    }
}
