<?php

namespace App\Markdown;

use App\Markdown\Event\ConvertMarkdown;
use Psr\EventDispatcher\EventDispatcherInterface;

class MarkdownConverter {
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher) {
        $this->dispatcher = $dispatcher;
    }

    public function convertToHtml(string $markdown, array $context = []): string {
        $event = new ConvertMarkdown($markdown);
        $event->mergeAttributes($context);

        $this->dispatcher->dispatch($event);

        return $event->getRenderedHtml();
    }
}
