<?php

namespace App\Form\Model;

use App\Entity\Constants\SubmissionLinkDestination;
use App\Entity\Site;
use App\Entity\Submission;
use App\Entity\Theme;
use App\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

class SiteData {
    /**
     * @Assert\NotBlank()
     * @Assert\Length(min=1, max=60)
     *
     * @var string
     */
    public $siteName;

    /**
     * @var bool
     */
    public $registrationOpen;

    /**
     * @Assert\Choice({Submission::SORT_HOT, Submission::SORT_ACTIVE, Submission::SORT_NEW})
     * @Assert\NotBlank()
     *
     * @var string
     */
    public $defaultSortMode;

    /**
     * @var Theme|null
     */
    public $defaultTheme;

    /**
     * @var bool
     */
    public $wikiEnabled;

    /**
     * @Assert\Choice(User::ROLES)
     * @Assert\NotBlank()
     *
     * @var string
     */
    public $forumCreateRole;

    /**
     * @Assert\Choice(User::ROLES)
     * @Assert\NotBlank()
     *
     * @var string
     */
    public $imageUploadRole;

    /**
     * @Assert\Choice(User::ROLES)
     * @Assert\NotBlank()
     *
     * @var string
     */
    public $wikiEditRole;

    /**
     * @var bool
     */
    public $trashEnabled;

    /**
     * @var bool
     */
    public $registrationCaptchaEnabled;

    /**
     * @var bool
     */
    public $urlImagesEnabled;

    /**
     * @Assert\Choice(SubmissionLinkDestination::OPTIONS)
     * @Assert\NotBlank()
     *
     * @var string
     */
    public $submissionLinkDestination;

    public static function createFromSite(Site $site): self {
        $self = new self();
        $self->siteName = $site->getSiteName();
        $self->registrationOpen = $site->isRegistrationOpen();
        $self->defaultSortMode = $site->getDefaultSortMode();
        $self->defaultTheme = $site->getDefaultTheme();
        $self->wikiEnabled = $site->isWikiEnabled();
        $self->forumCreateRole = $site->getForumCreateRole();
        $self->imageUploadRole = $site->getImageUploadRole();
        $self->wikiEditRole = $site->getWikiEditRole();
        $self->trashEnabled = $site->isTrashEnabled();
        $self->registrationCaptchaEnabled = $site->isRegistrationCaptchaEnabled();
        $self->urlImagesEnabled = $site->isUrlImagesEnabled();
        $self->submissionLinkDestination = $site->getSubmissionLinkDestination();

        return $self;
    }

    public function updateSite(Site $site): void {
        $site->setSiteName($this->siteName);
        $site->setRegistrationOpen($this->registrationOpen);
        $site->setDefaultSortMode($this->defaultSortMode);
        $site->setDefaultTheme($this->defaultTheme);
        $site->setWikiEnabled($this->wikiEnabled);
        $site->setForumCreateRole($this->forumCreateRole);
        $site->setImageUploadRole($this->imageUploadRole);
        $site->setWikiEditRole($this->wikiEditRole);
        $site->setTrashEnabled($this->trashEnabled);
        $site->setRegistrationCaptchaEnabled($this->registrationCaptchaEnabled);
        $site->setUrlImagesEnabled($this->urlImagesEnabled);
        $site->setSubmissionLinkDestination($this->submissionLinkDestination);
    }
}
