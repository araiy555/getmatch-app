<?php

namespace App\Tests\Markdown\Event;

use App\Markdown\Event\ConfigureCommonMark;
use App\Markdown\Event\ConvertMarkdown;
use League\CommonMark\ConfigurableEnvironmentInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Markdown\Event\ConfigureCommonMark
 */
class ConfigureCommonMarkTest extends TestCase {
    public function testConstruction(): void {
        $environment = $this->createMock(ConfigurableEnvironmentInterface::class);
        $convertMarkdownEvent = new ConvertMarkdown('some markdown');

        $event = new ConfigureCommonMark($environment, $convertMarkdownEvent);

        $this->assertSame($environment, $event->getEnvironment());
        $this->assertSame($convertMarkdownEvent, $event->getConvertMarkdownEvent());
    }
}
