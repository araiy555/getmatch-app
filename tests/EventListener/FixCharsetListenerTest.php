<?php

namespace App\Tests\EventListener;

use App\EventListener\FixCharsetListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @covers \App\EventListener\FixCharsetListener
 */
class FixCharsetListenerTest extends TestCase {
    /**
     * @var Response
     */
    private $response;

    /**
     * @var ResponseEvent
     */
    private $event;

    protected function setUp(): void {
        /** @var HttpKernelInterface|\PHPUnit\Framework\MockObject\MockObject $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        $this->response = new Response();

        $this->event = new ResponseEvent(
            $kernel,
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            $this->response
        );
    }

    /**
     * @dataProvider provideCharsets
     */
    public function testCharsetIsAsExpected(string $expected, string $contentType): void {
        $this->response->headers->set('Content-Type', $contentType);

        (new FixCharsetListener())->fixResponseCharset($this->event);

        $this->assertSame($expected, $this->response->headers->get('Content-Type'));
    }

    public function provideCharsets(): iterable {
        yield ['application/json; charset=UTF-8', 'application/json'];
        yield ['application/ld+json; charset=UTF-8', 'application/ld+json'];
        yield ['application/xml; charset=UTF-8', 'application/xml'];
        yield ['application/xml; foo=bar; charset=UTF-8', 'application/xml; foo=bar'];
        yield ['application/xml; foo=bar; charset=ISO-8859-1', 'application/xml; foo=bar; charset=ISO-8859-1'];
        yield ['application/xml;chArSeT=UtF-8', 'application/xml;chArSeT=UtF-8'];
        yield ['application/xhtml+xml; charset=UTF-8', 'application/xhtml+xml'];
        yield ['application/xhtml+xml; charset=UTF-8', 'application/xhtml+xml; charset=UTF-8'];
        yield ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        yield ['image/svg+xml; charset=UTF-8', 'image/svg+xml'];
        yield ['image/svg+xml; charset=UTF-8', 'image/svg+xml; charset=UTF-8'];
        yield ['image/svg+xml; charset=ISO-8859-1', 'image/svg+xml; charset=ISO-8859-1'];
        yield ['text/html; charset=ISO-8859-1', 'text/html; charset=ISO-8859-1'];
        yield ['text/html', 'text/html'];
        yield ['application/octet-stream', 'application/octet-stream'];
    }
}
