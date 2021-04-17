<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

/**
 * Adds remember me for login links.
 */
class LoginLinkGenerator {
    /**
     * @var LoginLinkHandlerInterface
     */
    private $loginLinkHandler;

    public function __construct(LoginLinkHandlerInterface $loginLinkHandler) {
        $this->loginLinkHandler = $loginLinkHandler;
    }

    public function generate(User $user): string {
        $url = $this->loginLinkHandler->createLoginLink($user);

        // hack to have remember me cookies set for login links
        return sprintf('%s&_remember_me=1', $url);
    }
}
