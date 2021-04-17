<?php

namespace App\Security\Voter;

use App\Entity\Message;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class MessageVoter extends Voter {
    public const ATTRIBUTES = ['delete'];

    protected function supports(string $attribute, $subject): bool {
        return $subject instanceof Message && \in_array($attribute, self::ATTRIBUTES, true);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool {
        if (!$subject instanceof Message) {
            throw new \InvalidArgumentException('$subject must be '.Message::class);
        }

        switch ($attribute) {
        case 'delete':
            return $subject->getSender() === $token->getUser();
        default:
            throw new \LogicException('Unknown attribute '.$attribute);
        }
    }
}
