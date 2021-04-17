<?php

namespace App\Form\DataTransformer;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Transforms usernames into {@link User} objects and vice versa.
 */
final class UserTransformer implements DataTransformerInterface {
    /**
     * @var UserRepository
     */
    private $userRepository;

    public function __construct(UserRepository $userRepository) {
        $this->userRepository = $userRepository;
    }

    public function transform($value): ?string {
        if ($value instanceof User) {
            return $value->getUsername();
        }

        if ($value !== null) {
            throw new \InvalidArgumentException('$value must be '.User::class.' or null');
        }

        return null;
    }

    public function reverseTransform($value): ?User {
        if ($value === null || $value === '') {
            return null;
        }

        $user = $this->userRepository->loadUserByUsername($value);

        if (!$user) {
            $e = new TransformationFailedException('No such user');
            $e->setInvalidMessage('user.none_by_username');

            throw $e;
        }

        return $user;
    }
}
