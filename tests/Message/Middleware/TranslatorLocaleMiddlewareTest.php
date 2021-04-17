<?php

namespace App\Tests\Message\Middleware;

use App\Message\Middleware\TranslatorLocaleMiddleware;
use App\Message\Stamp\RequestInfoStamp;
use App\Tests\Fixtures\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Test\Middleware\MiddlewareTestCase;

/**
 * @covers \App\Message\Middleware\TranslatorLocaleMiddleware
 */
class TranslatorLocaleMiddlewareTest extends MiddlewareTestCase {
    public function testSetsAndRestoresLocale(): void {
        /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject $translator */
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->once())
            ->method('getLocale')
            ->willReturn('en');
        $translator
            ->expects($this->exactly(2))
            ->method('setLocale')
            ->withConsecutive(
                [$this->equalTo('nb')],
                [$this->equalTo('en')]
            );

        $request = new Request();
        $request->setLocale('nb');

        $envelope = Envelope::wrap((object) [], [
            RequestInfoStamp::createFromRequest($request),
        ]);

        $middleware = new TranslatorLocaleMiddleware($translator);
        $middleware->handle($envelope, $this->getStackMock());
    }
}
