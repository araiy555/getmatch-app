<?php

namespace App\Markdown\CommonMark;

use League\CommonMark\ElementRendererInterface;
use League\CommonMark\HtmlElement;
use League\CommonMark\Inline\Element\AbstractInline;
use League\CommonMark\Inline\Element\Image;
use League\CommonMark\Inline\Renderer\InlineRendererInterface;
use League\CommonMark\Util\ConfigurationAwareInterface;
use League\CommonMark\Util\ConfigurationInterface;
use League\CommonMark\Util\RegexHelper;

final class ImageWithFixedClassRenderer implements InlineRendererInterface, ConfigurationAwareInterface {
    protected $config;

    public function render(
        AbstractInline $inline,
        ElementRendererInterface $htmlRenderer
    ): HtmlElement {
        if (!$inline instanceof Image) {
            throw new \InvalidArgumentException(sprintf(
                'Incompatible inline type: %s',
                \get_class($inline)
            ));
        }

        $allowUnsafeLinks = $this->config->get('allow_unsafe_links');
        $url = $inline->getUrl();
        $checkLinkUnsafe = RegexHelper::isLinkPotentiallyUnsafe($url);

        $attr = [];

        $attr['class'] = 'inserted-image';
        $attr['src'] = $url;

        if (!$allowUnsafeLinks && $checkLinkUnsafe) {
            $attr['src'] = '';
        }

        $alt = $htmlRenderer->renderInlines($inline->children());
        $alt = \preg_replace('/\<[^>]*alt="([^"]*)"[^>]*\>/', '$1', $alt);
        $attr['alt'] = \preg_replace('/\<[^>]*\>/', '', $alt);

        return new HtmlElement('img', $attr, '', true);
    }

    public function setConfiguration(
        ConfigurationInterface $configuration
    ) {
        $this->config = $configuration;
    }
}
