<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class FixCharsetListener implements EventSubscriberInterface {
    /**
     * Add UTF-8 character set to XML and JSON responses that don't have this.
     */
    public function fixResponseCharset(ResponseEvent $event): void {
        if ($event->getRequestType() !== HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        $response = $event->getResponse();
        $contentType = $response->headers->get('Content-Type');
        $charset = $response->getCharset() ?: 'UTF-8';

        if (preg_match('#[/+](?:json|xml)\b(?!;.*charset=)#i', $contentType)) {
            $contentType = sprintf('%s; charset=%s', $contentType, $charset);

            $response->headers->set('Content-Type', $contentType);
        }
    }

    public static function getSubscribedEvents(): array {
        return [
            KernelEvents::RESPONSE => ['fixResponseCharset', -10],
        ];
    }
}
