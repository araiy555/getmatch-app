<?php

namespace App\DataObject;

use App\Entity\ForumTag;
use App\Validator\Unique;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Unique(entityClass="App\Entity\ForumTag", errorPath="name",
 *     fields={"normalizedName"}, idFields="id", groups={"update_forum_tag"})
 */
class ForumTagData {
    /**
     * @var UuidInterface|null
     */
    private $id;

    /**
     * @Assert\Length(min=3, max=40, minMessage="tag.too_short", maxMessage="tag.too_long",
     *      groups={"create_forum", "update_forum", "update_forum_tag"})
     * @Assert\NotBlank(groups={"create_forum", "update_forum", "update_forum_tag"})
     * @Assert\Regex("/^\w+$/", message="tag.invalid_characters",
     *     groups={"create_forum", "update_forum", "update_forum_tag"})
     *
     * @Groups({"abbreviated_relations"})
     *
     * @var string|null
     */
    private $name;

    /**
     * @var string|null
     */
    private $normalizedName;

    /**
     * @Assert\Length(max=1500, groups={"update_forum_tag"})
     *
     * @var string|null
     */
    private $description;

    public static function createFromForumTag(ForumTag $forumTag): self {
        $self = new self();
        $self->id = $forumTag->getId();
        $self->setName($forumTag->getName());
        $self->description = $forumTag->getDescription();

        return $self;
    }

    public function toForumTag(): ForumTag {
        return new ForumTag($this->name, $this->description);
    }

    public function updateForumTag(ForumTag $forumTag): void {
        $forumTag->setName($this->name);
        $forumTag->setDescription($this->description);
    }

    public function getId(): ?UuidInterface {
        return $this->id;
    }

    public function getName(): ?string {
        return $this->name;
    }

    public function setName(?string $name): void {
        $this->name = $name;
        $this->normalizedName = isset($name)
            ? ForumTag::normalizeName($name)
            : null;
    }

    public function getNormalizedName(): ?string {
        return $this->normalizedName;
    }

    public function getDescription(): ?string {
        return $this->description;
    }

    public function setDescription(?string $description): void {
        $this->description = $description;
    }
}
