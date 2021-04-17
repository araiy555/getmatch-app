<?php

namespace App\Markdown\CommonMark;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ForumLinkParser extends AbstractLocalLinkParser {
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator) {
        $this->urlGenerator = $urlGenerator;
    }

    public function getPrefix(): string {
        return 'f';
    }

    public function getRegex(): string {
        return '/^(?:\w{3,25}\+){0,70}\w{3,25}\b/';
    }

    public function getUrl(string $suffix): string {
        if (strpos($suffix, '+') !== false) {
            return $this->urlGenerator->generate('multi', [
                'names' => $suffix,
            ]);
        }

        return $this->urlGenerator->generate('forum', [
            'forum_name' => $suffix,
        ]);
    }
}
