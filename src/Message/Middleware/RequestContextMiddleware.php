<?php

namespace App\Message\Middleware;

use App\Message\Stamp\RequestInfoStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Routing\RequestContext;

final class RequestContextMiddleware implements MiddlewareInterface {
    /**
     * @var RequestContext
     */
    private $requestContext;

    public function __construct(RequestContext $requestContext) {
        $this->requestContext = $requestContext;
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope {
        $requestInfo = $envelope->last(RequestInfoStamp::class);
        \assert(!$requestInfo || $requestInfo instanceof RequestInfoStamp);

        if ($requestInfo) {
            $oldContext = clone $this->requestContext;
            $this->copyToCurrentRequestContext($requestInfo->getRequestContext());
        }

        try {
            return $stack->next()->handle($envelope, $stack);
        } finally {
            if (isset($oldContext)) {
                $this->copyToCurrentRequestContext($oldContext);
            }
        }
    }

    private function copyToCurrentRequestContext(RequestContext $context): void {
        $this->requestContext->setBaseUrl($context->getBaseUrl());
        $this->requestContext->setPathInfo($context->getPathInfo());
        $this->requestContext->setHost($context->getHost());
        $this->requestContext->setHttpPort($context->getHttpPort());
        $this->requestContext->setHttpsPort($context->getHttpsPort());
        $this->requestContext->setQueryString($context->getQueryString());
        $this->requestContext->setScheme($context->getScheme());
        $this->requestContext->setMethod($context->getMethod());
        $this->requestContext->setParameters($context->getParameters() ?? []);
    }
}
