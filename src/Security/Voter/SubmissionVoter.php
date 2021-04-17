<?php

namespace App\Security\Voter;

use App\Entity\Submission;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class SubmissionVoter extends Voter {
    public const ATTRIBUTES = [
        'view',
        'delete_own',
        'edit',
        'lock',
        'mod_delete',
        'pin',
        'restore',
        'purge',
        'vote',
    ];

    /**
     * @var AccessDecisionManagerInterface
     */
    private $decisionManager;

    public function __construct(AccessDecisionManagerInterface $decisionManager) {
        $this->decisionManager = $decisionManager;
    }

    protected function supports(string $attribute, $subject): bool {
        return $subject instanceof Submission && \in_array($attribute, self::ATTRIBUTES, true);
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
        case 'edit':
            return $this->canEdit($subject, $user);
        case 'lock':
            return $this->canLock($subject, $user);
        case 'mod_delete':
            return $this->canModDelete($subject, $user);
        case 'pin':
            return $this->canPin($subject, $user);
        case 'purge':
            return $this->canPurge($subject, $token);
        case 'restore':
            return $this->canRestore($subject, $user);
        case 'vote':
            return $this->canVote($subject, $user);
        default:
            throw new \InvalidArgumentException("Invalid attribute '$attribute'");
        }
    }

    private function canView(Submission $submission, TokenInterface $token): bool {
        if (\in_array($submission->getVisibility(), [
            Submission::VISIBILITY_VISIBLE,
            Submission::VISIBILITY_SOFT_DELETED,
        ], true)) {
            return true;
        }

        if ($token->getUser() === $submission->getUser()) {
            return true;
        }

        return $submission->getForum()->userIsModerator($token->getUser());
    }

    private function canDeleteOwn(Submission $submission, User $user): bool {
        if ($submission->getVisibility() !== Submission::VISIBILITY_VISIBLE) {
            return false;
        }

        if ($submission->getUser() !== $user) {
            return false;
        }

        return true;
    }

    private function canModDelete(Submission $submission, User $user): bool {
        if ($submission->getVisibility() !== Submission::VISIBILITY_VISIBLE) {
            return false;
        }

        if ($submission->getUser() === $user) {
            return false;
        }

        if (!$submission->getForum()->userIsModerator($user)) {
            return false;
        }

        return true;
    }

    private function canEdit(Submission $submission, User $user): bool {
        if ($submission->getVisibility() !== Submission::VISIBILITY_VISIBLE) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($submission->getUser() !== $user) {
            return false;
        }

        if ($submission->isModerated()) {
            return false;
        }

        return true;
    }

    private function canPin(Submission $submission, User $user): bool {
        if ($submission->getVisibility() !== Submission::VISIBILITY_VISIBLE) {
            return false;
        }

        return $submission->getForum()->userIsModerator($user);
    }

    private function canLock(Submission $submission, User $user): bool {
        return $submission->getForum()->userIsModerator($user);
    }

    private function canRestore(Submission $submission, User $user): bool {
        if ($submission->getVisibility() !== Submission::VISIBILITY_TRASHED) {
            return false;
        }

        if (!$submission->getForum()->userIsModerator($user)) {
            return false;
        }

        return true;
    }

    private function canPurge(Submission $submission, TokenInterface $token): bool {
        return $submission->isTrashed() && (
            $submission->getUser() === $token->getUser() ||
            $this->decisionManager->decide($token, ['ROLE_ADMIN'])
        );
    }

    private function canVote(Submission $submission, User $user): bool {
        return !$submission->getForum()->userIsBanned($user);
    }
}
