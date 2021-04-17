<?php

namespace App\Tests\EventListener;

use App\EventListener\PaginationExceptionListener;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use PagerWave\Exception\InvalidQueryException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @covers \App\EventListener\PaginationExceptionListener
 */
class PaginationExceptionListenerTest extends TestCase {
    public function testSetsExceptionOnPagerfantaException(): void {
        $e = new OutOfRangeCurrentPageException();

        /** @var HttpKernelInterface|\PHPUnit\Framework\MockObject\MockObject $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new ExceptionEvent($kernel, new Request(), HttpKernelInterface::MASTER_REQUEST, $e);

        (new PaginationExceptionListener())->onKernelException($event);

        $this->assertInstanceOf(NotFoundHttpException::class, $event->getThrowable());
        $this->assertSame($e, $event->getThrowable()->getPrevious());
    }

    public function testSetsExceptionOnPagerWaveException(): void {
        $e = new InvalidQueryException();

        /** @var HttpKernelInterface|\PHPUnit\Framework\MockObject\MockObject $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new ExceptionEvent($kernel, new Request(), HttpKernelInterface::MASTER_REQUEST, $e);

        (new PaginationExceptionListener())->onKernelException($event);

        $this->assertInstanceOf(NotFoundHttpException::class, $event->getThrowable());
        $this->assertSame($e, $event->getThrowable()->getPrevious());
    }

    public function testIgnoresExceptionIfNotPagerfanta(): void {
        $e = new \Exception();
        $event = $this->createExceptionEvent($e);

        (new PaginationExceptionListener())->onKernelException($event);

        $this->assertSame($e, $event->getThrowable());
    }

    private function createExceptionEvent(\Throwable $throwable): ExceptionEvent {
        /** @var HttpKernelInterface|\PHPUnit\Framework\MockObject\MockObject $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        return new ExceptionEvent(
            $kernel,
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            $throwable
        );
    }
}
