<?php

namespace App\Security\Voter;

use App\Entity\Comment;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class CommentVoter extends Voter {
    public const ATTRIBUTES = [
        'view',
        'delete_own',
        'edit',
        'mod_delete',
        'purge',
        'restore',
        'vote',
    ];

    private $decisionManager;

    public function __construct(AccessDecisionManagerInterface $decisionManager) {
        $this->decisionManager = $decisionManager;
    }

    protected function supports(string $attribute, $subject): bool {
        return $subject instanceof Comment && \in_array($attribute, self::ATTRIBUTES, true);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool {
        if ($attribute === 'view') {
            return $this->canView($subject, $token);
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        switch ($attribute) {
        case 'delete_own':
            return $this->canDeleteOwn($subject, $user);
        case 'mod_delete':
            return $this->canModDelete($subject, $user);
        case 'edit':
            return $this->canEdit($subject, $token);
        case 'purge':
            return $this->canPurge($subject, $token);
        case 'restore':
            return $this->canRestore($subject, $user);
        case 'vote':
            return $this->canVote($subject, $user);
        default:
            throw new \InvalidArgumentException('Unknown attribute '.$attribute);
        }
    }

    private function canView(Comment $comment, TokenInterface $token): bool {
        if (\in_array($comment->getVisibility(), [
            Comment::VISIBILITY_VISIBLE,
            Comment::VISIBILITY_SOFT_DELETED,
        ], true)) {
            return true;
        }

        $user = $token->getUser();

        if ($user === $comment->getUser()) {
            return true;
        }

        return $comment->getSubmission()->getForum()->userIsModerator($user);
    }

    private function canDeleteOwn(Comment $comment, User $user): bool {
        if ($comment->getVisibility() !== Comment::VISIBILITY_VISIBLE) {
            return false;
        }

        if ($comment->getUser() !== $user) {
            return false;
        }

        return true;
    }

    private function canModDelete(Comment $comment, User $user): bool {
        if ($comment->getVisibility() !== Comment::VISIBILITY_VISIBLE) {
            return false;
        }

        if (!$comment->getSubmission()->getForum()->userIsModerator($user)) {
            return false;
        }

        return true;
    }

    private function canEdit(Comment $comment, TokenInterface $token): bool {
        if ($comment->getVisibility() === Comment::VISIBILITY_SOFT_DELETED) {
            return false;
        }

        if ($this->decisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        if ($comment->isModerated()) {
            return false;
        }

        return $comment->getUser() === $token->getUser();
    }

    private function canRestore(Comment $comment, User $user): bool {
        if ($comment->getVisibility() !== Comment::VISIBILITY_TRASHED) {
            return false;
        }

        if (!$comment->getSubmission()->getForum()->userIsModerator($user)) {
            return false;
        }

        return true;
    }

    private function canPurge(Comment $comment, TokenInterface $token): bool {
        return $comment->isTrashed() && (
            $comment->getUser() === $token->getUser() ||
            $this->decisionManager->decide($token, ['ROLE_ADMIN'])
        );
    }

    private function canVote(Comment $comment, User $user): bool {
        return !$comment->getSubmission()->getForum()->userIsBanned($user);
    }
}
