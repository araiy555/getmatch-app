<?php

namespace App\Tests\Markdown\Listener;

use App\Markdown\Factory\ConverterFactory;
use App\Markdown\Factory\EnvironmentFactory;
use App\Markdown\Event\ConfigureCommonMark;
use App\Markdown\Event\ConvertMarkdown;
use App\Markdown\Listener\ConvertMarkdownListener;
use League\CommonMark\ConfigurableEnvironmentInterface;
use League\CommonMark\Environment;
use League\CommonMark\MarkdownConverterInterface;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @covers \App\Markdown\Listener\ConvertMarkdownListener
 */
class ConvertMarkdownListenerTest extends TestCase {
    /**
     * @var MarkdownConverterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $converter;

    /**
     * @var ConverterFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $converterFactory;

    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $dispatcher;

    /**
     * @var ConfigurableEnvironmentInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $environment;

    /**
     * @var ConvertMarkdownListener
     */
    private $listener;

    protected function setUp(): void {
        $this->converter = $this->createMock(MarkdownConverterInterface::class);
        $this->converterFactory = $this->createMock(ConverterFactory::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->environment = new Environment();
        $environmentFactory = $this->createMock(EnvironmentFactory::class);
        $environmentFactory
            ->method('createConfigurableEnvironment')
            ->willReturn($this->environment);
        $this->listener = new ConvertMarkdownListener(
            $this->converterFactory,
            $environmentFactory,
            $this->dispatcher,
        );
    }

    public function testConversion(): void {
        $convertMarkdownEvent = new ConvertMarkdown('some markdown');
        $configureCommonMarkEvent = new ConfigureCommonMark(
            $this->environment,
            $convertMarkdownEvent,
        );

        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($configureCommonMarkEvent)
            ->willReturnArgument(0);

        $this->converterFactory
            ->expects($this->once())
            ->method('createConverter')
            ->with($this->environment)
            ->willReturn($this->converter);

        $this->converter
            ->expects($this->once())
            ->method('convertToHtml')
            ->with('some markdown')
            ->willReturn('some html');

        $this->listener->onConvertMarkdown($convertMarkdownEvent);

        $this->assertSame('some html', $convertMarkdownEvent->getRenderedHtml());
    }
}
