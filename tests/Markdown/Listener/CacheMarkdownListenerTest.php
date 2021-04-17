<?php

namespace App\Tests\Markdown\Listener;

use App\Markdown\Event\BuildCacheContext;
use App\Markdown\Event\ConvertMarkdown;
use App\Markdown\Listener\CacheMarkdownListener;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @covers \App\Markdown\Listener\CacheMarkdownListener
 */
class CacheMarkdownListenerTest extends TestCase {
    /**
     * @var CacheItemPoolInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cache;

    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $dispatcher;

    /**
     * @var CacheMarkdownListener
     */
    private $listener;

    protected function setUp(): void {
        $this->cache = $this->createMock(CacheItemPoolInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->listener = new CacheMarkdownListener($this->cache, $this->dispatcher);
    }

    public function testCacheHit(): void {
        $event = new ConvertMarkdown('some markdown');

        $contextEvent = $this->expectDispatchingContextEvent($event);
        $cacheItem = $this->expectCacheHit($contextEvent);

        $this->cache
            ->expects($this->once())
            ->method('getItem')
            ->with($contextEvent->getCacheKey())
            ->willReturn($cacheItem);

        $this->listener->preConvertMarkdown($event);
        $this->assertSame('some html', $event->getRenderedHtml());
        $this->assertTrue($event->isPropagationStopped());
    }

    public function testCacheMiss(): void {
        $event = new ConvertMarkdown('some markdown');

        $contextEvent = $this->expectDispatchingContextEvent($event);
        $cacheItem = $this->expectCacheMiss($contextEvent);

        $this->cache
            ->expects($this->once())
            ->method('getItem')
            ->with($contextEvent->getCacheKey())
            ->willReturn($cacheItem);

        $this->listener->preConvertMarkdown($event);

        $this->assertSame('', $event->getRenderedHtml());
        $this->assertFalse($event->isPropagationStopped());

        $event->setRenderedHtml('some html');

        $cacheItem
            ->expects($this->once())
            ->method('set')
            ->with('some html');

        $this->cache
            ->expects($this->once())
            ->method('save')
            ->with($cacheItem);

        $this->listener->postConvertMarkdown($event);
    }

    public function testCacheMissWithStoringSkipped(): void {
        $event = new ConvertMarkdown('some markdown');
        $event->addAttribute(CacheMarkdownListener::ATTR_NO_CACHE_STORE, true);

        $contextEvent = $this->expectDispatchingContextEvent($event);
        $cacheItem = $this->expectCacheMiss($contextEvent);

        $this->listener->preConvertMarkdown($event);

        $cacheItem
            ->expects($this->never())
            ->method('set');

        $this->cache
            ->expects($this->never())
            ->method('save');

        $this->listener->postConvertMarkdown($event);
    }

    /**
     * @return CacheItemInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function expectCacheHit(BuildCacheContext $context): CacheItemInterface {
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem
            ->expects($this->once())
            ->method('isHit')
            ->willReturn(true);
        $cacheItem
            ->expects($this->once())
            ->method('get')
            ->willReturn('some html');

        $this->cache
            ->expects($this->once())
            ->method('getItem')
            ->with($context->getCacheKey())
            ->willReturn($cacheItem);

        return $cacheItem;
    }

    /**
     * @return CacheItemInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function expectCacheMiss(BuildCacheContext $context): CacheItemInterface {
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem
            ->expects($this->once())
            ->method('isHit')
            ->willReturn(false);

        $this->cache
            ->expects($this->once())
            ->method('getItem')
            ->with($context->getCacheKey())
            ->willReturn($cacheItem);

        return $cacheItem;
    }

    private function expectDispatchingContextEvent(
        ConvertMarkdown $convertMarkdownEvent
    ): BuildCacheContext {
        $contextEvent = new BuildCacheContext($convertMarkdownEvent);

        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($contextEvent)
            ->willReturnArgument(0);

        return $contextEvent;
    }
}
