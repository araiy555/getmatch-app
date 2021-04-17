<?php

namespace App\Tests\Markdown\CommonMark;

use App\Markdown\CommonMark\SyntaxHighlightRenderer;
use League\CommonMark\Block\Element\AbstractBlock;
use League\CommonMark\Block\Element\FencedCode;
use League\CommonMark\ElementRendererInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Markdown\CommonMark\SyntaxHighlightRenderer
 */
class SyntaxHighlightRendererTest extends TestCase {
    /**
     * @var SyntaxHighlightRenderer
     */
    private $renderer;

    protected function setUp(): void {
        $this->renderer = new SyntaxHighlightRenderer();
    }

    public function testRender(): void {
        $element = $this->createElement(true);

        $this->assertRenderedElementEquals(
            <<<EOHTML
            <pre><code data-controller="syntax-highlight" data-syntax-highlight-language-value="php">some code</code></pre>
            EOHTML,
            $element
        );
    }

    public function testRenderWithoutLanguage(): void {
        $element = $this->createElement(false);

        $this->assertRenderedElementEquals(
            <<<EOHTML
            <pre><code>some code</code></pre>
            EOHTML,
            $element,
        );
    }

    public function throwsOnIncorrectElement(): void {
        $this->expectException(\InvalidArgumentException::class);
        $element = $this->createMock(AbstractBlock::class);

        $this->renderer->render(
            $element,
            $this->createMock(ElementRendererInterface::class)
        );
    }

    private function createElement(bool $withLanguage): FencedCode {
        $element = $this->createMock(FencedCode::class);
        $element
            ->expects($this->once())
            ->method('getStringContent')
            ->willReturn('some code');
        $element
            ->expects($this->once())
            ->method('getInfoWords')
            ->willReturn($withLanguage ? ['php'] : []);

        return $element;
    }

    private function assertRenderedElementEquals(
        string $expectedHtml,
        FencedCode $element
    ): void {
        $renderedHtml = (string) $this->renderer->render(
            $element,
            $this->createMock(ElementRendererInterface::class),
        );

        $this->assertXmlStringEqualsXmlString($expectedHtml, $renderedHtml);
    }
}
