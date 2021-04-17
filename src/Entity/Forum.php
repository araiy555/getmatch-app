<?php

namespace App\Entity;

use App\Entity\Contracts\BackgroundImageInterface as BackgroundImage;
use App\Entity\Contracts\DomainEventsInterface as DomainEvents;
use App\Event\ForumDeleted;
use App\Event\ForumUpdated;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Doctrine\ORM\Mapping as ORM;
use Pagerfanta\Doctrine\Collections\CollectionAdapter;
use Pagerfanta\Doctrine\Collections\SelectableAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ForumRepository")
 * @ORM\Table(indexes={
 *     @ORM\Index(name="forum_featured_idx", columns={"featured"}),
 *     @ORM\Index(name="forums_light_background_image_id_idx", columns={"light_background_image_id"}),
 *     @ORM\Index(name="forums_dark_background_image_id_idx", columns={"dark_background_image_id"}),
 * }, uniqueConstraints={
 *     @ORM\UniqueConstraint(name="forums_name_idx", columns={"name"}),
 *     @ORM\UniqueConstraint(name="forums_normalized_name_idx", columns={"normalized_name"}),
 * })
 */
class Forum implements BackgroundImage, DomainEvents {
    /**
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Id()
     *
     * @var int|null
     */
    private $id;

    /**
     * @ORM\Column(type="text", unique=true)
     *
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(type="text", unique=true)
     *
     * @var string
     */
    private $normalizedName;

    /**
     * @ORM\Column(type="text")
     *
     * @var string
     */
    private $title;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @var string|null
     */
    private $description;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @var string
     */
    private $sidebar;

    /**
     * @ORM\OneToMany(targetEntity="Moderator", mappedBy="forum", cascade={"persist", "remove"})
     *
     * @var Moderator[]|Collection
     */
    private $moderators;

    /**
     * @ORM\OneToMany(targetEntity="Submission", mappedBy="forum", cascade={"remove"}, fetch="EXTRA_LAZY")
     *
     * @var Submission[]|Collection
     */
    private $submissions;

    /**
     * @ORM\Column(type="datetimetz_immutable")
     *
     * @var \DateTimeImmutable
     */
    private $created;

    /**
     * @ORM\OneToMany(targetEntity="ForumSubscription", mappedBy="forum",
     *     cascade={"persist", "remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     *
     * @var ForumSubscription[]|Collection|Selectable
     */
    private $subscriptions;

    /**
     * @ORM\OneToMany(targetEntity="ForumBan", mappedBy="forum", cascade={"persist", "remove"})
     *
     * @var ForumBan[]|Collection|Selectable
     */
    private $bans;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     *
     * @var bool
     */
    private $featured = false;

    /**
     * @ORM\JoinTable(name="forums_tags", joinColumns={
     *     @ORM\JoinColumn(name="forum_id", referencedColumnName="id"),
     * }, inverseJoinColumns={
     *     @ORM\JoinColumn(name="tag_id", referencedColumnName="id"),
     * })
     * @ORM\ManyToMany(targetEntity="ForumTag", inversedBy="forums", cascade={"persist"}, fetch="EXTRA_LAZY")
     *
     * @var ForumTag[]|Collection
     */
    private $tags;

    /**
     * @ORM\ManyToOne(targetEntity="Image", cascade={"persist"})
     *
     * @var Image|null
     */
    private $lightBackgroundImage;

    /**
     * @ORM\ManyToOne(targetEntity="Image", cascade={"persist"})
     *
     * @var Image|null
     */
    private $darkBackgroundImage;

    /**
     * @ORM\Column(type="text", options={"default": BackgroundImage::BACKGROUND_TILE})
     *
     * @var string
     */
    private $backgroundImageMode = BackgroundImage::BACKGROUND_TILE;

    /**
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @ORM\ManyToOne(targetEntity="Theme")
     *
     * @var Theme|null
     */
    private $suggestedTheme;

    /**
     * @ORM\OneToMany(targetEntity="ForumLogEntry", mappedBy="forum", cascade={"persist", "remove"})
     * @ORM\OrderBy({"timestamp": "DESC"})
     *
     * @var ForumLogEntry[]|Collection
     */
    private $logEntries;

    public function __construct(
        string $name,
        string $title,
        string $description,
        string $sidebar,
        User $user = null
    ) {
        $this->setName($name);
        $this->title = $title;
        $this->description = $description;
        $this->sidebar = $sidebar;
        $this->created = new \DateTimeImmutable('@'.time());
        $this->tags = new ArrayCollection();
        $this->bans = new ArrayCollection();
        $this->moderators = new ArrayCollection();
        $this->submissions = new ArrayCollection();
        $this->subscriptions = new ArrayCollection();
        $this->logEntries = new ArrayCollection();

        if ($user) {
            $this->addModerator(new Moderator($this, $user));
            $this->subscribe($user);
        }
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getName(): string {
        return $this->name;
    }

    public function setName(string $name): void {
        $this->name = $name;
        $this->normalizedName = self::normalizeName($name);
    }

    public function getNormalizedName(): ?string {
        return $this->normalizedName;
    }

    public function getTitle(): string {
        return $this->title;
    }

    public function setTitle(string $title): void {
        $this->title = $title;
    }

    public function getDescription(): ?string {
        return $this->description;
    }

    public function setDescription(?string $description): void {
        $this->description = $description;
    }

    public function getSidebar(): string {
        return $this->sidebar;
    }

    public function setSidebar(string $sidebar): void {
        $this->sidebar = $sidebar;
    }

    /**
     * @return Collection|Moderator[]
     */
    public function getModerators(): array {
        return $this->moderators->getValues();
    }

    /**
     * @return Pagerfanta|Moderator[]
     */
    public function getPaginatedModerators(int $page, int $maxPerPage = 25): Pagerfanta {
        $criteria = Criteria::create()->orderBy(['timestamp' => 'ASC']);

        $moderators = new Pagerfanta(new SelectableAdapter($this->moderators, $criteria));
        $moderators->setMaxPerPage($maxPerPage);
        $moderators->setCurrentPage($page);

        return $moderators;
    }

    public function userIsModerator($user, bool $adminsAreMods = true): bool {
        if (!$user instanceof User) {
            return false;
        }

        if ($adminsAreMods && $user->isAdmin()) {
            return true;
        }

        // optimised to significantly lessen the number of SQL queries performed
        // when logged in as the user being checked.
        $user->getModeratorTokens()->get(-1);

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('forum', $this));

        return \count($user->getModeratorTokens()->matching($criteria)) > 0;
    }

    public function addModerator(Moderator $moderator): void {
        if (!$this->moderators->contains($moderator)) {
            $this->moderators->add($moderator);
        }
    }

    public function userCanDelete($user): bool {
        if (!$user instanceof User) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if (!$this->userIsModerator($user)) {
            return false;
        }

        return \count($this->submissions) === 0;
    }

    public function getSubmissionCount(): int {
        return \count($this->submissions);
    }

    public function addSubmission(Submission $submission): void {
        if ($submission->getForum() !== $this) {
            throw new \InvalidArgumentException(
                'Forum does not belong to submission',
            );
        }

        if (!$this->submissions->contains($submission)) {
            $this->submissions->add($submission);
        }
    }

    public function removeSubmission(Submission $submission): void {
        $this->submissions->removeElement($submission);
    }

    public function getCreated(): \DateTimeImmutable {
        return $this->created;
    }

    public function getSubscriptionCount(): int {
        return \count($this->subscriptions);
    }

    public function isSubscribed(User $user): bool {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('user', $user));

        return \count($this->subscriptions->matching($criteria)) > 0;
    }

    public function subscribe(User $user): void {
        if (!$this->isSubscribed($user)) {
            $this->subscriptions->add(new ForumSubscription($user, $this));
        }
    }

    public function unsubscribe(User $user): void {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('user', $user));

        $subscription = $this->subscriptions->matching($criteria)->first();

        if ($subscription) {
            $this->subscriptions->removeElement($subscription);
        }
    }

    public function userIsBanned(User $user): bool {
        if ($user->isAdmin()) {
            // should we check for mod permissions too?
            return false;
        }

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('user', $user))
            ->orderBy(['timestamp' => 'DESC'])
            ->setMaxResults(1);

        $ban = $this->bans->matching($criteria)->first();
        \assert(!$ban || $ban instanceof ForumBan);

        if (!$ban || !$ban->isBan()) {
            return false;
        }

        return !$ban->isExpired();
    }

    /**
     * @return Pagerfanta|ForumBan[]
     */
    public function getPaginatedBansByUser(User $user, int $page, int $maxPerPage = 25): Pagerfanta {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('user', $user))
            ->orderBy(['timestamp' => 'DESC']);

        $pager = new Pagerfanta(new SelectableAdapter($this->bans, $criteria));
        $pager->setMaxPerPage($maxPerPage);
        $pager->setCurrentPage($page);

        return $pager;
    }

    public function addBan(ForumBan $ban): void {
        if (!$this->bans->contains($ban)) {
            $this->bans->add($ban);

            $this->addLogEntry(new ForumLogBan($ban));
        }
    }

    public function isFeatured(): bool {
        return $this->featured;
    }

    public function setFeatured(bool $featured): void {
        $this->featured = $featured;
    }

    /**
     * @return ForumTag[]
     */
    public function getTags(): array {
        $criteria = Criteria::create()
            ->orderBy(['normalizedName' => 'ASC']);

        return $this->tags->matching($criteria)->toArray();
    }

    public function hasTag(ForumTag $tag): bool {
        return $this->tags->contains($tag);
    }

    public function addTags(ForumTag ...$tags): void {
        foreach ($tags as $tag) {
            if (!$this->tags->contains($tag)) {
                $this->tags->add($tag);
            }

            if (!$tag->hasForum($this)) {
                $tag->addForum($this);
            }
        }
    }

    public function removeTags(ForumTag ...$tags): void {
        foreach ($tags as $tag) {
            if ($this->tags->contains($tag)) {
                $this->tags->removeElement($tag);
            }

            if ($tag->hasForum($this)) {
                $tag->removeForum($this);
            }
        }
    }

    public function getLightBackgroundImage(): ?Image {
        return $this->lightBackgroundImage;
    }

    public function setLightBackgroundImage(?Image $lightBackgroundImage): void {
        $this->lightBackgroundImage = $lightBackgroundImage;
    }

    public function getDarkBackgroundImage(): ?Image {
        return $this->darkBackgroundImage;
    }

    public function setDarkBackgroundImage(?Image $darkBackgroundImage): void {
        $this->darkBackgroundImage = $darkBackgroundImage;
    }

    public function getBackgroundImageMode(): string {
        return $this->backgroundImageMode;
    }

    public function setBackgroundImageMode(string $backgroundImageMode): void {
        $this->backgroundImageMode = $backgroundImageMode;
    }

    public function getSuggestedTheme(): ?Theme {
        return $this->suggestedTheme;
    }

    public function setSuggestedTheme(?Theme $suggestedTheme): void {
        $this->suggestedTheme = $suggestedTheme;
    }

    /**
     * @return Pagerfanta|ForumLogEntry[]
     */
    public function getPaginatedLogEntries(int $page, int $max = 50): Pagerfanta {
        $pager = new Pagerfanta(new CollectionAdapter($this->logEntries));
        $pager->setMaxPerPage($max);
        $pager->setCurrentPage($page);

        return $pager;
    }

    public function addLogEntry(ForumLogEntry $entry): void {
        if (!$this->logEntries->contains($entry)) {
            $this->logEntries->add($entry);
        }
    }

    public static function normalizeName(string $name): string {
        return mb_strtolower($name, 'UTF-8');
    }

    public function onCreate(): Event {
        return new Event();
    }

    public function onUpdate($previous): Event {
        \assert($previous instanceof self);

        return new ForumUpdated($previous, $this);
    }

    public function onDelete(): Event {
        return new ForumDeleted($this);
    }
}
