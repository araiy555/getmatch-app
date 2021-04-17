<?php

namespace App\Security\Voter;

use App\Entity\Site;
use App\Entity\User;
use App\Entity\WikiPage;
use App\Repository\SiteRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class WikiVoter extends Voter {
    public const ATTRIBUTES = ['write', 'delete', 'lock'];

    /**
     * @var AccessDecisionManagerInterface
     */
    private $decisionManager;

    /**
     * @var SiteRepository
     */
    private $siteRepository;

    public function __construct(
        AccessDecisionManagerInterface $decisionManager,
        SiteRepository $siteRepository
    ) {
        $this->decisionManager = $decisionManager;
        $this->siteRepository = $siteRepository;
    }

    protected function supports(string $attribute, $subject): bool {
        return $subject instanceof WikiPage &&
            \in_array($attribute, self::ATTRIBUTES, true);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool {
        if (!$token->getUser() instanceof User) {
            return false;
        }

        switch ($attribute) {
        case 'write':
            return $this->canWrite($subject, $token);
        case 'delete':
        case 'lock':
            // todo: make this configurable
            return $this->decisionManager->decide($token, ['ROLE_ADMIN']);
        default:
            throw new \LogicException("Unknown attribute '$attribute'");
        }
    }

    private function canWrite(WikiPage $page, TokenInterface $token): bool {
        if (!$token->getUser() instanceof User) {
            return false;
        }

        if ($this->decisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        if ($page->isLocked()) {
            return false;
        }

        $site = $this->siteRepository->findCurrentSite();

        \assert($site instanceof Site);

        return $this->decisionManager->decide($token, [$site->getWikiEditRole()]);
    }
}
