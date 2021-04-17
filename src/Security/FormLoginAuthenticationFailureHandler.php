<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

/**
 * Custom failure handler to remember the value of the "remember me" checkbox.
 */
class FormLoginAuthenticationFailureHandler implements AuthenticationFailureHandlerInterface {
    /**
     * @var :enticationFailureHandlerInterface
     */
    private $decorated;

    public function __construct(AuthenticationFailureHandlerInterface $decorated) {
        $this->decorated = $decorated;
    }

    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): Response {
        $request->getSession()->set(
            'remember_me',
            $request->request->getBoolean('_remember_me'),
        );

        return $this->decorated->onAuthenticationFailure($request, $exception);
    }
}
