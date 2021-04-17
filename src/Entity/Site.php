<?php

namespace App\Entity;

use App\Entity\Constants\SubmissionLinkDestination;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * In the future, we might use this for multi-site support. But for now, this is
 * a single-row table where some global settings are stored.
 *
 * @ORM\Entity(repositoryClass="App\Repository\SiteRepository")
 * @ORM\Table(indexes={
 *     @ORM\Index(name="site_default_theme_idx", columns={"default_theme_id"})
 * })
 */
class Site {
    /**
     * @ORM\Column(type="uuid")
     * @ORM\Id()
     *
     * @var UuidInterface
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     *
     * @var string
     */
    private $siteName = 'Postmill';

    /**
     * @ORM\Column(type="boolean", options={"default": true})
     *
     * @var bool
     */
    private $registrationOpen = true;

    /**
     * @ORM\Column(type="text", options={"default": Submission::SORT_HOT})
     *
     * @var string
     */
    private $defaultSortMode = Submission::SORT_HOT;

    /**
     * @ORM\ManyToOne(targetEntity="Theme")
     *
     * @var Theme|null
     */
    private $defaultTheme;

    /**
     * @ORM\Column(type="boolean")
     *
     * @var bool
     */
    private $wikiEnabled = true;

    /**
     * @ORM\Column(type="text")
     *
     * @var string
     */
    private $forumCreateRole = 'ROLE_USER';

    /**
     * @ORM\Column(type="text")
     *
     * @var string
     */
    private $imageUploadRole = 'ROLE_USER';

    /**
     * @ORM\Column(type="text")
     *
     * @var string
     */
    private $wikiEditRole = 'ROLE_USER';

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     *
     * @var bool
     */
    private $trashEnabled = false;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     *
     * @var bool
     */
    private $registrationCaptchaEnabled = false;

    /**
     * @ORM\Column(type="boolean", options={"default": true})
     *
     * @var bool
     */
    private $urlImagesEnabled = true;

    /**
     * @ORM\Column(type="text", options={"default": SubmissionLinkDestination::URL})
     *
     * @var string
     */
    private $submissionLinkDestination = SubmissionLinkDestination::URL;

    public function __construct() {
        $this->id = Uuid::fromString(Uuid::NIL);
    }

    public function getId(): UuidInterface {
        return $this->id;
    }

    public function getSiteName(): string {
        return $this->siteName;
    }

    public function setSiteName(string $siteName): void {
        $this->siteName = $siteName;
    }

    public function isRegistrationOpen(): bool {
        return $this->registrationOpen;
    }

    public function setRegistrationOpen(bool $registrationOpen): void {
        $this->registrationOpen = $registrationOpen;
    }

    public function getDefaultSortMode(): string {
        return $this->defaultSortMode;
    }

    public function setDefaultSortMode(string $defaultSortMode): void {
        $this->defaultSortMode = $defaultSortMode;
    }

    public function getDefaultTheme(): ?Theme {
        return $this->defaultTheme;
    }

    public function setDefaultTheme(?Theme $defaultTheme): void {
        $this->defaultTheme = $defaultTheme;
    }

    public function isWikiEnabled(): bool {
        return $this->wikiEnabled;
    }

    public function setWikiEnabled(bool $wikiEnabled): void {
        $this->wikiEnabled = $wikiEnabled;
    }

    public function getForumCreateRole(): string {
        return $this->forumCreateRole;
    }

    public function setForumCreateRole(string $forumCreateRole): void {
        if (!\in_array($forumCreateRole, User::ROLES, true)) {
            throw new \InvalidArgumentException("Invalid role '$forumCreateRole'");
        }

        $this->forumCreateRole = $forumCreateRole;
    }

    public function getImageUploadRole(): string {
        return $this->imageUploadRole;
    }

    public function setImageUploadRole(string $imageUploadRole): void {
        if (!\in_array($imageUploadRole, User::ROLES, true)) {
            throw new \InvalidArgumentException("Invalid role '$imageUploadRole'");
        }

        $this->imageUploadRole = $imageUploadRole;
    }

    public function getWikiEditRole(): string {
        return $this->wikiEditRole;
    }

    public function setWikiEditRole(string $wikiEditRole): void {
        if (!\in_array($wikiEditRole, User::ROLES, true)) {
            throw new \InvalidArgumentException("Invalid role '$wikiEditRole'");
        }

        $this->wikiEditRole = $wikiEditRole;
    }

    public function isTrashEnabled(): bool {
        return $this->trashEnabled;
    }

    public function setTrashEnabled(bool $trashEnabled): void {
        $this->trashEnabled = $trashEnabled;
    }

    public function isRegistrationCaptchaEnabled(): bool {
        return $this->registrationCaptchaEnabled;
    }

    public function setRegistrationCaptchaEnabled(bool $registrationCaptchaEnabled): void {
        $this->registrationCaptchaEnabled = $registrationCaptchaEnabled;
    }

    public function isUrlImagesEnabled(): bool {
        return $this->urlImagesEnabled;
    }

    public function setUrlImagesEnabled(bool $urlImagesEnabled): void {
        $this->urlImagesEnabled = $urlImagesEnabled;
    }

    public function getSubmissionLinkDestination(): string {
        return $this->submissionLinkDestination;
    }

    public function setSubmissionLinkDestination(string $submissionLinkDestination): void {
        SubmissionLinkDestination::assertValidDestination($submissionLinkDestination);

        $this->submissionLinkDestination = $submissionLinkDestination;
    }
}
