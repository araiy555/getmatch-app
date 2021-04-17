<?php

namespace App\Markdown\CommonMark;

use App\Utils\LanguageDetectorInterface;
use League\CommonMark\Block\Element\Paragraph;
use League\CommonMark\Event\DocumentParsedEvent;

class LanguageDetectionListener {
    /**
     * @var LanguageDetectorInterface
     */
    private $detector;

    public function __construct(LanguageDetectorInterface $detector) {
        $this->detector = $detector;
    }

    public function __invoke(DocumentParsedEvent $event): void {
        $walker = $event->getDocument()->walker();

        while ($walkerEvent = $walker->next()) {
            $node = $walkerEvent->getNode();

            if ($walkerEvent->isEntering() && $node instanceof Paragraph) {
                $lang = $this->detector->detect($node->getStringContent());
                $dir = preg_match('/^(ar|fa|he|yi)\b/', $lang) ? 'rtl' : 'ltr';

                if ($lang) {
                    $node->data['attributes']['lang'] = $lang;
                    $node->data['attributes']['dir'] = $dir;
                }

                if ($node->next()) {
                    $walker->resumeAt($node->next());
                }
            }
        }
    }
}
