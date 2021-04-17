<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Twig\Environment as Twig;

final class RegistrationClosedListener implements EventSubscriberInterface {
    /**
     * @var Twig
     */
    private $twig;

    public static function getSubscribedEvents(): array {
        return [
            ExceptionEvent::class => ['onKernelException'],
        ];
    }

    public function __construct(Twig $twig) {
        $this->twig = $twig;
    }

    public function onKernelException(ExceptionEvent $event): void {
        $request = $event->getRequest();
        $exception = $event->getThrowable();

        if (
            !$exception instanceof HttpException ||
            $exception->getStatusCode() !== 403 ||
            $request->attributes->get('_route') !== 'registration'
        ) {
            return;
        }

        if ($request->isMethod('POST')) {
            $response = new RedirectResponse($request->getUri());
        } else {
            $response = new Response(
                $this->twig->render('user/registration_closed.html.twig'),
                403
            );
        }

        $event->setResponse($response);
    }
}
