<?php

namespace App\EventListener;

use Pagerfanta\Exception as Pagerfanta;
use PagerWave\Exception as PagerWave;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PaginationExceptionListener implements EventSubscriberInterface {
    public static function getSubscribedEvents(): array {
        return [
            ExceptionEvent::class => ['onKernelException'],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void {
        $e = $event->getThrowable();

        if (
            $e instanceof Pagerfanta\NotValidCurrentPageException ||
            $e instanceof PagerWave\InvalidQueryException
        ) {
            $event->setThrowable(new NotFoundHttpException($e->getMessage(), $e));
        }
    }
}
