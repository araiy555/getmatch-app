<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class Authentication {
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage) {
        $this->tokenStorage = $tokenStorage;
    }

    public function getUser(): ?User {
        $token = $this->tokenStorage->getToken();

        if (!$token) {
            return null;
        }

        $user = $token->getUser();

        if (!\is_object($user)) {
            return null;
        }

        if (!$user instanceof User) {
            throw new \RuntimeException(sprintf(
                'Unknown user object in token (expected %s, got %s)',
                User::class,
                get_debug_type($user),
            ));
        }

        return $user;
    }

    /**
     * @throws \RuntimeException if user is not authenticated
     */
    public function getUserOrThrow(): User {
        $user = $this->getUser();

        if (!$user) {
            throw new \RuntimeException('User is not authenticated');
        }

        return $user;
    }
}
