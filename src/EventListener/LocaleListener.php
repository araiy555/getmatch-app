<?php

namespace App\EventListener;

use App\Entity\User;
use App\Event\UserUpdated;
use App\Security\Authentication;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Manage locale for a request, based on user's setting.
 *
 * @see https://symfony.com/doc/current/session/locale_sticky_session.html
 */
final class LocaleListener implements EventSubscriberInterface {
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var Authentication
     */
    private $authentication;

    /**
     * @var TranslatorInterface|LocaleAwareInterface
     */
    private $translator;

    /**
     * @var string[]
     */
    private $availableLocales;

    /**
     * @var string
     */
    private $defaultLocale;

    public static function getSubscribedEvents(): array {
        return [
            RequestEvent::class => ['onKernelRequest', 20],
            InteractiveLoginEvent::class => ['onInteractiveLogin'],
            UserUpdated::class => ['onUserUpdated'],
        ];
    }

    public function __construct(
        Authentication $authentication,
        RequestStack $requestStack,
        TranslatorInterface $translator,
        array $availableLocales,
        string $defaultLocale
    ) {
        if (!$translator instanceof LocaleAwareInterface) {
            throw new \InvalidArgumentException(
                '$translator must be instance of '.LocaleAwareInterface::class
            );
        }

        $this->requestStack = $requestStack;
        $this->authentication = $authentication;
        $this->translator = $translator;
        $this->availableLocales = $availableLocales;
        $this->defaultLocale = $defaultLocale;
    }

    public function onKernelRequest(RequestEvent $event): void {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();

        if ($request->hasPreviousSession()) {
            $locale = $request->getSession()->get('_locale');
        }

        if (!isset($locale)) {
            // Default locale must be first, or the wrong locale is used if
            // the Accept-Language header doesn't contain an available locale.
            $default = [$this->defaultLocale];

            $locale = $request->getPreferredLanguage(
                array_merge($default, array_diff($this->availableLocales, $default))
            );
        }

        if (isset($locale)) {
            $request->setLocale($locale);
        }
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void {
        $user = $event->getAuthenticationToken()->getUser();

        if ($user instanceof User) {
            $locale = $user->getLocale();
            $event->getRequest()->getSession()->set('_locale', $locale);
            $event->getRequest()->setLocale($locale);

            // Because security.interactive_login runs after kernel.request,
            // where the translator gets its locale, we must manually set the
            // locale on the translator. There is no way around this.
            $this->translator->setLocale($locale);
        }
    }

    public function onUserUpdated(UserUpdated $event): void {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return;
        }

        $updatedUser = $event->getAfter();

        if (
            $this->authentication->getUser() === $updatedUser &&
            $event->getBefore()->getLocale() !== $updatedUser->getLocale()
        ) {
            $request->getSession()->set('_locale', $updatedUser->getLocale());
        }
    }
}
