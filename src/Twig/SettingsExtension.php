<?php

namespace App\Twig;

use App\Repository\SiteRepository;
use App\Security\Authentication;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Make settings accessible to templates.
 *
 * @todo extend with more settings
 */
class SettingsExtension extends AbstractExtension {
    /**
     * @var Authentication
     */
    private $authentication;

    /**
     * @var SiteRepository
     */
    private $sites;

    public function __construct(Authentication $authentication, SiteRepository $sites) {
        $this->authentication = $authentication;
        $this->sites = $sites;
    }

    public function getFunctions(): array {
        return [
            new TwigFunction('submission_link_destination', [$this, 'getSubmissionLinkDestination']),
        ];
    }

    public function getSubmissionLinkDestination(): string {
        $user = $this->authentication->getUser();

        if ($user) {
            $destination = $user->getSubmissionLinkDestination();
        }

        if (!isset($destination)) {
            $destination = $this->sites
                ->findCurrentSite()
                ->getSubmissionLinkDestination();
        }

        return $destination;
    }
}
