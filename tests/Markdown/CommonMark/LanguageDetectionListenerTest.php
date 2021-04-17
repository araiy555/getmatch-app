<?php

namespace App\Tests\Markdown\CommonMark;

use App\Markdown\CommonMark\LanguageDetectionListener;
use App\Tests\Fixtures\Utils\MockLanguageDetector;
use League\CommonMark\Block\Element\AbstractBlock;
use League\CommonMark\Block\Element\BlockQuote;
use League\CommonMark\Block\Element\Document;
use League\CommonMark\Block\Element\Paragraph;
use League\CommonMark\Block\Element\ThematicBreak;
use League\CommonMark\Event\DocumentParsedEvent;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Markdown\CommonMark\LanguageDetectionListener
 */
class LanguageDetectionListenerTest extends TestCase {
    /**
     * @var LanguageDetectionListener
     */
    private $listener;

    protected function setUp(): void {
        $detector = new MockLanguageDetector();
        $this->listener = new LanguageDetectionListener($detector);
    }

    public function testAddsLanguageAndDirectionToParagraphs(): void {
        $paragraph1 = new Paragraph();
        $paragraph1->addLine('This is some paragraph');

        $paragraph2 = new Paragraph();
        $paragraph2->addLine('This is a quoted paragraph');

        $blockquote = new BlockQuote();
        $blockquote->appendChild($paragraph2);

        $thematicBreak = new ThematicBreak();

        $document = new Document();
        $document->appendChild($paragraph1);
        $document->appendChild($blockquote);
        $document->appendChild($thematicBreak);

        $event = new DocumentParsedEvent($document);

        ($this->listener)($event);

        $this->assertHasLanguage($paragraph1);
        $this->assertDoesNotHaveLanguage($blockquote);
        $this->assertHasLanguage($paragraph2);
        $this->assertDoesNotHaveLanguage($thematicBreak);
    }

    private function assertHasLanguage(AbstractBlock $node): void {
        $this->assertArrayHasKey('attributes', $node->data);
        $this->assertArrayHasKey('lang', $node->data['attributes']);
        $this->assertSame('en', $node->data['attributes']['lang']);
        $this->assertArrayHasKey('dir', $node->data['attributes']);
        $this->assertSame('ltr', $node->data['attributes']['dir']);
    }

    private function assertDoesNotHaveLanguage(AbstractBlock $node): void {
        $this->assertArrayNotHasKey('lang', $node->data['attributes'] ?? []);
        $this->assertArrayNotHasKey('dir', $node->data['attributes'] ?? []);
    }
}
