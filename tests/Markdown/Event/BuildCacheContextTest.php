<?php

namespace App\Tests\Markdown\Event;

use App\Markdown\Event\BuildCacheContext;
use App\Markdown\Event\ConvertMarkdown;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Markdown\Event\BuildCacheContext
 */
class BuildCacheContextTest extends TestCase {
    /**
     * @var ConvertMarkdown
     */
    private $convertMarkdownEvent;

    /**
     * @var BuildCacheContext
     */
    private $event;

    protected function setUp(): void {
        $this->convertMarkdownEvent = new ConvertMarkdown('some markdown');
        $this->event = new BuildCacheContext($this->convertMarkdownEvent);
    }

    public function testHasConvertMarkdownEvent(): void {
        $this->assertSame(
            $this->convertMarkdownEvent,
            $this->event->getConvertMarkdownEvent(),
        );
    }

    public function testInitialCacheKeyMatches(): void {
        $this->assertSame(hash('sha256', json_encode([
            'content' => 'some markdown',
        ])), $this->event->getCacheKey());
    }

    public function testAddingContextWithNoValue(): void {
        $this->event->addToContext('noodle soup');

        $this->assertSame(hash('sha256', json_encode([
            'content' => 'some markdown',
            'noodle soup' => null,
        ])), $this->event->getCacheKey());
    }

    public function testAddingContextWithValue(): void {
        $this->event->addToContext('soup', 'noodle soup');

        $this->assertSame(hash('sha256', json_encode([
            'content' => 'some markdown',
            'soup' => 'noodle soup',
        ])), $this->event->getCacheKey());
    }

    public function testValuesAddedNonAlphabetically(): void {
        $this->event->addToContext('soup', 'noodle soup');
        $this->event->addToContext('noodle soup');
        $this->event->addToContext('a');

        $this->assertSame(hash('sha256', json_encode([
            'a' => null,
            'content' => 'some markdown',
            'noodle soup' => null,
            'soup' => 'noodle soup',
        ])), $this->event->getCacheKey());
    }

    public function testCheckingIfContextIsSet(): void {
        $this->assertTrue($this->event->hasContext('content'));
        $this->assertTrue($this->event->hasContext('content', 'some markdown'));
        $this->assertFalse($this->event->hasContext('content', 'mismatch'));
        $this->assertFalse($this->event->hasContext('not content'));
    }
}
