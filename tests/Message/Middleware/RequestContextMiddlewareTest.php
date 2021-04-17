<?php

namespace App\Tests\Message\Middleware;

use App\Message\Middleware\RequestContextMiddleware;
use App\Message\Stamp\RequestInfoStamp;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Test\Middleware\MiddlewareTestCase;
use Symfony\Component\Routing\RequestContext;

/**
 * @covers \App\Message\Middleware\RequestContextMiddleware
 */
class RequestContextMiddlewareTest extends MiddlewareTestCase {
    public function testSetsAndRestoresRequestContext(): void {
        /** @var RequestContext|\PHPUnit\Framework\MockObject\MockObject $requestContext */
        $requestContext = $this->getMockBuilder(RequestContext::class)
            ->onlyMethods(['getHost', 'setHost'])
            ->getMock();
        $requestContext
            ->expects($this->once())
            ->method('getHost')
            ->willReturn('old.example.com');
        $requestContext
            ->expects($this->exactly(2))
            ->method('setHost')
            ->withConsecutive(
                [$this->equalTo('new.example.com')],
                [$this->equalTo('old.example.com')]
            )
            ->willReturn('localhost');

        /** @var Request|\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->getMockBuilder(Request::class)
            ->enableOriginalConstructor()
            ->onlyMethods(['getHost'])
            ->getMock();
        $request
            ->method('getHost')
            ->willReturn('new.example.com');

        $middleware = new RequestContextMiddleware($requestContext);

        $middleware->handle(new Envelope((object) [], [
            RequestInfoStamp::createFromRequest($request),
        ]), $this->getStackMock());
    }
}
