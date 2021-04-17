<?php

namespace App\Markdown\CommonMark;

use League\CommonMark\Block\Element\AbstractBlock;
use League\CommonMark\Block\Element\FencedCode;
use League\CommonMark\Block\Renderer\BlockRendererInterface;
use League\CommonMark\ElementRendererInterface;
use League\CommonMark\HtmlElement;
use League\CommonMark\Util\Xml;

final class SyntaxHighlightRenderer implements BlockRendererInterface {
    public function render(
        AbstractBlock $block,
        ElementRendererInterface $htmlRenderer,
        bool $inTightList = false
    ): HtmlElement {
        if (!$block instanceof FencedCode) {
            throw new \InvalidArgumentException(sprintf(
                '$block must be instance of %s, %s given',
                FencedCode::class,
                get_debug_type($block),
            ));
        }

        $attr = [];
        $language = $block->getInfoWords()[0] ?? null;

        if ($language) {
            $attr['data-controller'] = 'syntax-highlight';
            $attr['data-syntax-highlight-language-value'] = $language;
        }

        return new HtmlElement(
            'pre',
            [],
            new HtmlElement('code', $attr, Xml::escape($block->getStringContent()))
        );
    }
}
