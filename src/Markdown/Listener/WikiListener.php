<?php

namespace App\Markdown\Listener;

use App\Markdown\Event\BuildCacheContext;
use App\Markdown\Event\ConfigureCommonMark;
use App\Utils\SluggerInterface;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;
use League\CommonMark\Normalizer\TextNormalizerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class WikiListener implements EventSubscriberInterface, TextNormalizerInterface {
    public const ATTR_ENABLE_WIKI_MODE = 'wiki_mode';
    public const CACHE_ATTR_PERMALINK_LABEL = __CLASS__.' permalink label';

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var SluggerInterface
     */
    private $slugger;

    public static function getSubscribedEvents(): array {
        return [
            BuildCacheContext::class => ['onBuildCacheContext'],
            ConfigureCommonMark::class => ['onConfigureCommonMark'],
        ];
    }

    public function __construct(SluggerInterface $slugger, TranslatorInterface $translator) {
        $this->slugger = $slugger;
        $this->translator = $translator;
    }

    public function onBuildCacheContext(BuildCacheContext $event): void {
        if ($event->getConvertMarkdownEvent()->getAttribute(self::ATTR_ENABLE_WIKI_MODE)) {
            $event->addToContext(self::ATTR_ENABLE_WIKI_MODE);

            $event->addToContext(
                self::CACHE_ATTR_PERMALINK_LABEL,
                $this->translator->trans('nav.permalink'),
            );
        }
    }

    public function onConfigureCommonMark(ConfigureCommonMark $event): void {
        if ($event->getConvertMarkdownEvent()->getAttribute(self::ATTR_ENABLE_WIKI_MODE)) {
            $event->getEnvironment()->addExtension(new HeadingPermalinkExtension());
            $event->getEnvironment()->addExtension(new TableOfContentsExtension());
            $event->getEnvironment()->mergeConfig([
                'heading_permalink' => [
                    'insert' => 'after',
                    'title' => $this->translator->trans('nav.permalink'),
                    'slug_normalizer' => $this,
                ],
                'table_of_contents' => [
                    'position' => 'before-headings',
                    'style' => 'ordered',
                ],
            ]);
        }
    }

    public function normalize(string $text, $context = null): string {
        return $this->slugger->slugify($text);
    }
}
