<?php

namespace App\Tests\Message\Stamp;

use App\Message\Stamp\RequestInfoStamp;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @covers \App\Message\Stamp\RequestInfoStamp
 */
class RequestInfoStampTest extends TestCase {
    public function testStampCreation(): void {
        /** @var Request|\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->getMockBuilder(Request::class)
            ->enableOriginalConstructor()
            ->onlyMethods(['getLocale', 'getClientIps'])
            ->getMock();
        $request
            ->expects($this->once())
            ->method('getClientIps')
            ->willReturn(['127.0.0.2', '10.0.0.69']);
        $request
            ->expects($this->once())
            ->method('getLocale')
            ->willReturn('nb');

        $stamp = RequestInfoStamp::createFromRequest($request);

        $this->assertSame('nb', $stamp->getLocale());
        $this->assertSame('127.0.0.2', $stamp->getClientIp());
        $this->assertSame(['127.0.0.2', '10.0.0.69'], $stamp->getClientIps());
    }
}
