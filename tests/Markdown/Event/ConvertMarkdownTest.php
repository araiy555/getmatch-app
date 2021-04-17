<?php

namespace App\Tests\Markdown\Event;

use App\Markdown\Event\ConvertMarkdown;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Markdown\Event\ConvertMarkdown
 */
class ConvertMarkdownTest extends TestCase {
    /**
     * @var ConvertMarkdown
     */
    private $event;

    protected function setUp(): void {
        $this->event = new ConvertMarkdown('some markdown');
    }

    public function testMarkdownAccessors(): void {
        $this->assertSame('some markdown', $this->event->getMarkdown());
        $this->event->setMarkdown('some other markdown');
        $this->assertSame('some other markdown', $this->event->getMarkdown());
    }

    public function testRenderedHtmlAccessors(): void {
        $this->event->setRenderedHtml('some html');
        $this->assertSame('some html', $this->event->getRenderedHtml());
    }

    public function testAttributeAccessors(): void {
        $this->assertNull($this->event->getAttribute('a'));
        $this->event->addAttribute('a', 'b');
        $this->assertSame('b', $this->event->getAttribute('a'));
        $this->event->removeAttribute('a');
        $this->assertNull($this->event->getAttribute('a'));
        $this->event->mergeAttributes(['a' => 'c', 'b' => 'd']);
        $this->assertSame('c', $this->event->getAttribute('a'));
        $this->assertSame('d', $this->event->getAttribute('b'));
    }
}
