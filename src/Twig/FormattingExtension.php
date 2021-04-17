<?php

namespace App\Twig;

use App\Markdown\MarkdownConverter;
use App\Utils\DifferInterface;
use App\Utils\SluggerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class FormattingExtension extends AbstractExtension {
    /**
     * @var DifferInterface
     */
    private $differ;

    /**
     * @var MarkdownConverter
     */
    private $markdownConverter;

    /**
     * @var SluggerInterface
     */
    private $slugger;

    public function __construct(
        DifferInterface $differ,
        MarkdownConverter $markdownConverter,
        SluggerInterface $slugger
    ) {
        $this->differ = $differ;
        $this->markdownConverter = $markdownConverter;
        $this->slugger = $slugger;
    }

    public function getFilters(): array {
        return [
            new TwigFilter('search_highlight', __CLASS__.'::highlightSearch', [
                'is_safe' => ['html'],
                'pre_escape' => 'html',
            ]),
            new TwigFilter('diff', [$this->differ, 'diff']),
            new TwigFilter('markdown', [$this->markdownConverter, 'convertToHtml']),
            new TwigFilter('slugify', [$this->slugger, 'slugify']),
        ];
    }

    public static function highlightSearch(string $html): string {
        return preg_replace('!&lt;b&gt;(.*?)&lt;/b&gt;!', '<mark>\1</mark>', $html);
    }
}
