<?php

namespace App\Markdown\Listener;

use App\Markdown\Factory\ConverterFactory;
use App\Markdown\Factory\EnvironmentFactory;
use App\Markdown\Event\ConfigureCommonMark;
use App\Markdown\Event\ConvertMarkdown;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ConvertMarkdownListener implements EventSubscriberInterface {
    /**
     * @var ConverterFactory
     */
    private $converterFactory;

    /**
     * @var EnvironmentFactory
     */
    private $environmentFactory;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public static function getSubscribedEvents(): array {
        return [
            ConvertMarkdown::class => ['onConvertMarkdown'],
        ];
    }

    public function __construct(
        ConverterFactory $converterFactory,
        EnvironmentFactory $environmentFactory,
        EventDispatcherInterface $dispatcher
    ) {
        $this->converterFactory = $converterFactory;
        $this->environmentFactory = $environmentFactory;
        $this->dispatcher = $dispatcher;
    }

    public function onConvertMarkdown(ConvertMarkdown $event): void {
        $environment = $this->environmentFactory->createConfigurableEnvironment();

        $configureEvent = new ConfigureCommonMark($environment, $event);
        $this->dispatcher->dispatch($configureEvent);

        $converter = $this->converterFactory->createConverter($environment);
        $html = $converter->convertToHtml($event->getMarkdown());

        $event->setRenderedHtml($html);
    }
}
