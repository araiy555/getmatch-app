<?php

namespace App\Tests\Security;

use App\Security\FormLoginAuthenticationFailureHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Exception\:enticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

/**
 * @covers \App\Security\FormLoginAuthenticationFailureHandler
 */
class FormLoginAuthenticationFailureHandlerTest extends TestCase {
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var AuthenticationFailureHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $decorated;

    /**
     * @var FormLoginAuthenticationFailureHandler
     */
    private $handler;

    protected function setUp(): void {
        $this->session = new Session(new MockArraySessionStorage());
        $this->decorated = $this->createMock(AuthenticationFailureHandlerInterface::class);
        $this->handler = new FormLoginAuthenticationFailureHandler($this->decorated);
    }

    public function testAddsToSessionIfPresentInRequest(): void {
        $request = new Request();
        $request->setSession($this->session);
        $request->request->set('_remember_me', 'on');

        $exception = new AuthenticationException();

        $this->decorated
            ->expects($this->once())
            ->method('onAuthenticationFailure')
            ->with($request, $exception)
            ->willReturn(new Response());

        $this->handler->onAuthenticationFailure($request, $exception);

        $this->assertTrue($this->session->get('remember_me'));
    }

    public function testDoesNotAddToSessionIfNotPresentInRequest(): void {
        $request = new Request();
        $request->setSession($this->session);

        $exception = new AuthenticationException();

        $this->decorated
            ->expects($this->once())
            ->method('onAuthenticationFailure')
            ->with($request, $exception)
            ->willReturn(new Response());

        $this->handler->onAuthenticationFailure($request, $exception);

        $this->assertFalse($this->session->get('remember_me'));
    }
}
