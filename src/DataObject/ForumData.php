<?php

namespace App\DataObject;

use App\Entity\Contracts\BackgroundImageInterface;
use App\Entity\Forum;
use App\Entity\Image;
use App\Entity\Theme;
use App\Serializer\Contracts\NormalizeMarkdownInterface;
use App\Validator\NoBadPhrases;
use App\Validator\Unique;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Unique("normalizedName", idFields={"id"}, groups={"create_forum", "update_forum"},
 *     entityClass="App\Entity\Forum", errorPath="name",
 *     message="forum.duplicate_name")
 */
class ForumData implements BackgroundImageInterface, NormalizeMarkdownInterface {
    /**
     * @Groups({"forum:read", "abbreviated_relations"})
     *
     * @var int|null
     */
    private $id;

    /**
     * @Assert\NotBlank(groups={"create_forum", "update_forum"})
     * @Assert\Length(min=3, max=25, groups={"create_forum", "update_forum"})
     * @Assert\Regex("/^\w+$/",
     *     message="forum.name_characters",
     *     groups={"create_forum", "update_forum"}
     * )
     * @NoBadPhrases(groups={"create_forum", "update_forum"})
     *
     * @Groups({"forum:read", "forum:create", "forum:update", "abbreviated_relations"})
     */
    private $name;

    /**
     * @var string|null
     */
    private $normalizedName;

    /**
     * @Assert\Length(max=100, groups={"create_forum", "update_forum"})
     * @Assert\NotBlank(groups={"create_forum", "update_forum"})
     * @NoBadPhrases(groups={"create_forum", "update_forum"})
     *
     * @Groups({"forum:read", "forum:create", "forum:update"})
     *
     * @var string|null
     */
    private $title;

    /**
     * @Assert\Length(max=1500, groups={"create_forum", "update_forum"})
     * @Assert\NotBlank(groups={"create_forum", "update_forum"})
     * @NoBadPhrases(groups={"create_forum", "update_forum"})
     *
     * @Groups({"forum:read", "forum:create", "forum:update"})
     *
     * @var string|null
     */
    private $sidebar;

    /**
     * @Assert\Length(max=300, groups={"create_forum", "update_forum"})
     * @Assert\NotBlank(groups={"create_forum", "update_forum"})
     * @NoBadPhrases(groups={"create_forum", "update_forum"})
     *
     * @Groups({"forum:read", "forum:create", "forum:update"})
     *
     * @var string|null
     */
    private $description;

    /**
     * @Groups({"forum:read"})
     *
     * @var bool
     */
    private $featured = false;

    /**
     * @var Image|null
     */
    private $lightBackgroundImage;

    /**
     * @var Image|null
     */
    private $darkBackgroundImage;

    /**
     * @var string
     */
    private $backgroundImageMode = BackgroundImageInterface::BACKGROUND_TILE;

    /**
     * @Groups({"forum:read"})
     *
     * @var Theme|null
     */
    private $suggestedTheme;

    /**
     * @Assert\Count(max=5, groups={"create_forum", "update_forum"})
     * @Assert\Valid(groups={"create_forum", "update_forum"})
     *
     * @var ForumTagData[]|null
     */
    private $tags = [];

    /**
     * @var \Closure
     */
    private $tagsGenerator;

    public static function createFromForum(Forum $forum): self {
        $self = new self();
        $self->id = $forum->getId();
        $self->setName($forum->getName());
        $self->title = $forum->getTitle();
        $self->sidebar = $forum->getSidebar();
        $self->description = $forum->getDescription();
        $self->featured = $forum->isFeatured();
        $self->lightBackgroundImage = $forum->getLightBackgroundImage();
        $self->darkBackgroundImage = $forum->getDarkBackgroundImage();
        $self->backgroundImageMode = $forum->getBackgroundImageMode();
        $self->suggestedTheme = $forum->getSuggestedTheme();
        $self->tags = null; // skip validation unless changed
        $self->tagsGenerator = static function () use ($forum) {
            return array_map(
                ForumTagData::class.'::createFromForumTag',
                $forum->getTags()
            );
        };

        return $self;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getName(): ?string {
        return $this->name;
    }

    /**
     * For unique validator.
     */
    public function getNormalizedName(): ?string {
        return $this->normalizedName;
    }

    public function setName(?string $name): void {
        $this->name = $name;
        $this->normalizedName = $name !== null ? Forum::normalizeName($name) : null;
    }

    public function getTitle(): ?string {
        return $this->title;
    }

    public function setTitle(?string $title): void {
        $this->title = $title;
    }

    public function getSidebar(): ?string {
        return $this->sidebar;
    }

    public function setSidebar(?string $sidebar): void {
        $this->sidebar = $sidebar;
    }

    public function getDescription(): ?string {
        return $this->description;
    }

    public function setDescription(?string $description): void {
        $this->description = $description;
    }

    public function isFeatured(): bool {
        return $this->featured;
    }

    public function setFeatured(bool $featured): void {
        $this->featured = $featured;
    }

    public function getSuggestedTheme(): ?Theme {
        return $this->suggestedTheme;
    }

    public function setSuggestedTheme(?Theme $suggestedTheme): void {
        $this->suggestedTheme = $suggestedTheme;
    }

    public function getMarkdownFields(): iterable {
        yield 'sidebar';
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

    /**
     * @return ForumTagData[]
     */
    public function getTags(): array {
        if (!isset($this->tags)) {
            $this->tags = ($this->tagsGenerator)();
        }

        return $this->tags;
    }

    /**
     * @param ForumTagData[] $tags
     */
    public function setTags(array $tags): void {
        $this->tags = $tags;
    }
}
