<?php

namespace App\Security\Voter;

use App\Entity\Forum;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class ForumVoter extends Voter {
    public const ATTRIBUTES = ['moderator', 'delete'];

    protected function supports(string $attribute, $subject): bool {
        return $subject instanceof Forum && \in_array($attribute, self::ATTRIBUTES, true);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool {
        if (!$subject instanceof Forum) {
            throw new \InvalidArgumentException('$subject must be '.Forum::class);
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        switch ($attribute) {
        case 'moderator':
            return $subject->userIsModerator($user);
        case 'delete':
            return $subject->userCanDelete($user);
        default:
            throw new \InvalidArgumentException('Bad attribute '.$attribute);
        }
    }
}
