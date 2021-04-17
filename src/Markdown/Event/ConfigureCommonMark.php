<?php

namespace App\Markdown\Event;

use League\CommonMark\ConfigurableEnvironmentInterface;

class ConfigureCommonMark {
    /**
     * @var ConfigurableEnvironmentInterface
     */
    private $environment;

    /**
     * @var ConvertMarkdown
     */
    private $convertMarkdownEvent;

    public function __construct(
        ConfigurableEnvironmentInterface $environment,
        ConvertMarkdown $convertMarkdownEvent
    ) {
        $this->environment = $environment;
        $this->convertMarkdownEvent = $convertMarkdownEvent;
    }

    public function getEnvironment(): ConfigurableEnvironmentInterface {
        return $this->environment;
    }

    public function getConvertMarkdownEvent(): ConvertMarkdown {
        return $this->convertMarkdownEvent;
    }
}
