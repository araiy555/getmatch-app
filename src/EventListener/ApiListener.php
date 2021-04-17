<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApiListener implements EventSubscriberInterface {
    public const HEADER = 'X-Experimental-Api';

    /**
     * @var bool
     */
    private $enableExperimentalRestApi;

    public static function getSubscribedEvents(): array {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 200],
        ];
    }

    public function __construct(bool $enableExperimentalRestApi) {
        $this->enableExperimentalRestApi = $enableExperimentalRestApi;
    }

    public function onKernelRequest(RequestEvent $event): void {
        $request = $event->getRequest();

        if (strpos($request->getPathInfo(), '/api') !== 0) {
            return;
        }

        if (!$this->enableExperimentalRestApi) {
            throw new NotFoundHttpException('REST API is disabled');
        }

        if (!$request->headers->has(self::HEADER)) {
            throw new AccessDeniedHttpException('missing API header');
        }
    }
}
