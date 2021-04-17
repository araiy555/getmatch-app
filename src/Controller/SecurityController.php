<?php

namespace App\Controller;

use App\Security\PasswordResetHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class SecurityController extends AbstractController {
    public function login(Request $request, AuthenticationUtils $auth, PasswordResetHelper $resetHelper): Response {
        return $this->render('user/login.html.twig', [
            'can_reset_password' => $resetHelper->canReset(),
            'error' => $auth->getLastAuthenticationError(),
            'last_username' => $auth->getLastUsername(),
            'remember_me' => $request->getSession()->get('remember_me'),
        ]);
    }
}
