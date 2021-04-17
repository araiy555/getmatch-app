<?php

namespace App\Tests\Markdown;

use App\Markdown\Event\ConvertMarkdown;
use App\Markdown\MarkdownConverter;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @covers \App\Markdown\MarkdownConverter
 */
class MarkdownConverterTest extends TestCase {
    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $dispatcher;

    /**
     * @var MarkdownConverter
     */
    private $converter;

    protected function setUp(): void {
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->converter = new MarkdownConverter($this->dispatcher);
    }

    public function testCanConvert(): void {
        $event = new ConvertMarkdown('some markdown');

        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($event)
            ->willReturnCallback(function ($event) {
                $event->setRenderedHtml('some html');

                return $event;
            });

        $renderedHtml = $this->converter->convertToHtml('some markdown');

        $this->assertSame('some html', $renderedHtml);
    }

    public function testCanConvertWithContext(): void {
        $event = new ConvertMarkdown('some markdown');
        $event->mergeAttributes([
            'some' => 'context',
        ]);

        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($event)
            ->willReturnCallback(function ($event) {
                $event->setRenderedHtml('some html');

                return $event;
            });

        $this->converter->convertToHtml('some markdown', ['some' => 'context']);
    }
}
