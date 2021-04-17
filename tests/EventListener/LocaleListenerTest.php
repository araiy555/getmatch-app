<?php

namespace App\Tests\EventListener;

use App\Event\UserUpdated;
use App\EventListener\LocaleListener;
use App\Security\Authentication;
use App\Tests\Fixtures\Factory\EntityFactory;
use App\Tests\Fixtures\TranslatorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * @covers \App\EventListener\LocaleListener
 */
class LocaleListenerTest extends TestCase {
    /**
     * @var Authentication|\PHPUnit\Framework\MockObject\MockObject
     */
    private $authentication;

    /**
     * @var LocaleListener
     */
    private $listener;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $translator;

    protected function setUp(): void {
        $this->request = new Request();
        $this->request->setLocale('en');
        $this->request->setSession(new Session(new MockArraySessionStorage()));

        $this->authentication = $this->createMock(Authentication::class);

        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->requestStack = new RequestStack();
        $this->requestStack->push($this->request);

        $this->listener = new LocaleListener(
            $this->authentication,
            $this->requestStack,
            $this->translator,
            ['en', 'nb', 'sv'],
            'en'
        );
    }

    public function testLocaleIsUnchangedIfNoValidPreferenceFound(): void {
        $this->request->headers->set('Accept-Language', 'fr, de');
        $this->listener->onKernelRequest($this->getRequestEvent());

        $this->assertSame('en', $this->request->getLocale());
    }

    public function testSetsLocaleFromAcceptLanguageHeader(): void {
        $this->request->headers->set('Accept-Language', 'de, nb-NO, en');
        $this->listener->onKernelRequest($this->getRequestEvent());

        $this->assertSame('nb', $this->request->getLocale());
    }

    public function testSetsLocaleOnRequestFromSession(): void {
        $this->request->getSession()->set('_locale', 'sv');
        $this->request->headers->set('Accept-Language', 'nb-NO, en');
        $this->havePreviousSession();

        $this->listener->onKernelRequest($this->getRequestEvent());

        $this->assertSame('sv', $this->request->getLocale());
    }

    public function testSetsLocaleOnLogin(): void {
        $user = EntityFactory::makeUser();
        $user->setLocale('nb');

        /** @var TokenInterface|\PHPUnit\Framework\MockObject\MockObject $token */
        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->atLeastOnce())
            ->method('getUser')
            ->willReturn($user);

        $this->translator
            ->expects($this->once())
            ->method('setLocale')
            ->with($this->equalTo('nb'));

        $event = new InteractiveLoginEvent($this->request, $token);
        $this->listener->onInteractiveLogin($event);

        $this->assertSame('nb', $this->request->getLocale());
    }

    public function testSessionIsUpdatedWhenChangingLocalePreference(): void {
        $before = EntityFactory::makeUser();
        $before->setLocale('en');

        $after = clone $before;
        $after->setLocale('nb');

        $this->authentication
            ->expects($this->atLeastOnce())
            ->method('getUser')
            ->willReturn($after);

        $this->listener->onUserUpdated(new UserUpdated($before, $after));

        $this->assertSame('nb', $this->request->getSession()->get('_locale'));
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testChangingLocaleWithNoRequestDoesNotFail(): void {
        $before = EntityFactory::makeUser();
        $before->setLocale('en');

        $after = clone $before;
        $after->setLocale('nb');

        $this->requestStack->pop();
        $this->listener->onUserUpdated(new UserUpdated($before, $after));
    }

    private function getRequestEvent(): RequestEvent {
        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        return new RequestEvent($kernel, $this->request, HttpKernelInterface::MASTER_REQUEST);
    }

    private function havePreviousSession(): void {
        $this->request->cookies->set($this->request->getSession()->getName(), 'some token');
    }
}
