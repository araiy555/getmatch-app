<?php

namespace App\EventListener;

use App\Repository\IpBanRepository;
use App\Security\Authentication;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Show the user a landing page if they are banned.
 */
final class BanListener implements EventSubscriberInterface {
    /**
     * @var Authentication
     */
    private $authentication;

    /**
     * @var IpBanRepository
     */
    private $repository;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    public static function getSubscribedEvents(): array {
        return [
            // the priority must be less than 8, as the token storage won't be
            // populated otherwise!
            KernelEvents::REQUEST => ['onKernelRequest', 4],
        ];
    }

    public function __construct(
        IpBanRepository $repository,
        Authentication $authentication,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->authentication = $authentication;
        $this->repository = $repository;
        $this->urlGenerator = $urlGenerator;
    }

    public function onKernelRequest(RequestEvent $event): void {
        $request = $event->getRequest();

        // Don't check for bans on subrequests or requests that are 'safe' (i.e.
        // they're considered read-only). As only POST/PUT/etc. requests should
        // result in the state of the application mutating, banned users should
        // not be able to do any damage with GET/HEAD requests.
        if (!$event->isMasterRequest() || $request->isMethodSafe()) {
            return;
        }

        $user = $this->authentication->getUser();

        if ($user && $user->isBanned()) {
            $event->setResponse($this->getRedirectResponse());

            return;
        }

        if ($user && $user->isWhitelistedOrAdmin()) {
            // don't check for ip bans
            return;
        }

        if ($this->repository->ipIsBanned($request->getClientIp())) {
            $event->setResponse($this->getRedirectResponse());
        }
    }

    private function getRedirectResponse(): RedirectResponse {
        return new RedirectResponse($this->urlGenerator->generate('banned'));
    }
}
