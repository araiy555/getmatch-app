<?php

namespace App\Markdown\CommonMark;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class CategoryLinkParser extends AbstractLocalLinkParser {
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator) {
        $this->urlGenerator = $urlGenerator;
    }

    public function getPrefix(): string {
        return 'c';
    }

    public function getUrl(string $suffix): string {
        return $this->urlGenerator->generate('forum_tag', ['name' => $suffix]);
    }

    public function getRegex(): string {
        return '/^\w{3,40}\b/';
    }
}
