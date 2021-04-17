<?php

namespace App\Markdown\Event;

/**
 * Event dispatched to build a hash key for Markdown context.
 */
class BuildCacheContext {
    private $context = [];

    /**
     * @var ConvertMarkdown
     */
    private $convertMarkdownEvent;

    public function __construct(ConvertMarkdown $convertMarkdownEvent) {
        $this->convertMarkdownEvent = $convertMarkdownEvent;
        $this->addToContext('content', $convertMarkdownEvent->getMarkdown());
    }

    /**
     * @return ConvertMarkdown
     */
    public function getConvertMarkdownEvent(): ConvertMarkdown {
        return $this->convertMarkdownEvent;
    }

    public function getCacheKey(): string {
        ksort($this->context);

        return hash('sha256', json_encode($this->context));
    }

    public function hasContext(string $key, ?string $value = null): bool {
        if (!\array_key_exists($key, $this->context)) {
            return false;
        }

        if (func_num_args() < 2) {
            return true;
        }

        return $this->context[$key] === $value;
    }

    public function addToContext(string $key, ?string $value = null): void {
        $this->context[$key] = $value;
    }
}
