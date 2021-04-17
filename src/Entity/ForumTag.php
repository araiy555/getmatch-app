<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ForumTagRepository")
 * @ORM\Table(uniqueConstraints={
 *     @ORM\UniqueConstraint(name="forum_tag_name_idx", columns={"name"}),
 *     @ORM\UniqueConstraint(name="forum_tag_normalized_name_idx", columns={"normalized_name"}),
 * })
 */
class ForumTag {
    /**
     * @ORM\Column(type="uuid")
     * @ORM\Id()
     *
     * @var UuidInterface
     */
    private $id;

    /**
     * @ORM\ManyToMany(targetEntity="Forum", mappedBy="tags", fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"name": "DESC"})
     *
     * @var Forum[]|Collection
     */
    private $forums;

    /**
     * @ORM\Column(type="text")
     *
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(type="text")
     *
     * @var string
     */
    private $normalizedName;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @var string
     */
    private $description;

    public function __construct(
        string $name,
        string $description = null
    ) {
        $this->id = Uuid::uuid4();
        $this->forums = new ArrayCollection();
        $this->setName($name);
        $this->description = $description;
    }

    public function getId(): UuidInterface {
        return $this->id;
    }

    public function getForums(): array {
        $criteria = Criteria::create()
            ->orderBy(['normalizedName' => 'ASC']);

        return $this->forums->matching($criteria)->getValues();
    }

    public function hasForum(Forum $forum): bool {
        return $this->forums->contains($forum);
    }

    public function addForum(Forum $forum): void {
        if (!$this->forums->contains($forum)) {
            $this->forums->add($forum);
        }

        if (!$forum->hasTag($this)) {
            $forum->addTags($this);
        }
    }

    public function removeForum(Forum $forum): void {
        $this->forums->removeElement($forum);

        if ($forum->hasTag($this)) {
            $forum->removeTags($this);
        }
    }

    public function getForumCount(): int {
        return $this->forums->count();
    }

    public function getName(): string {
        return $this->name;
    }

    public function setName(string $name): void {
        $this->name = $name;
        $this->normalizedName = self::normalizeName($name);
    }

    public function getNormalizedName(): string {
        return $this->normalizedName;
    }

    public static function normalizeName(string $name): string {
        return mb_strtolower($name, 'UTF-8');
    }

    public function getDescription(): ?string {
        return $this->description;
    }

    public function setDescription(?string $description): void {
        $this->description = $description;
    }
}
