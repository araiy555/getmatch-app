<?php

namespace App\Markdown\CommonMark;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class WikiLinkParser extends AbstractLocalLinkParser {
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator) {
        $this->urlGenerator = $urlGenerator;
    }

    public function getPrefix(): string {
        return 'w';
    }

    public function getUrl(string $suffix): string {
        return $this->urlGenerator->generate('wiki', ['path' => $suffix]);
    }

    public function getRegex(): string {
        return '!^[A-Za-z][A-Za-z0-9_-]*(/[A-Za-z][A-Za-z0-9_-]*)*\b!';
    }
}
