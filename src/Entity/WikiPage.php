<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Pagerfanta\Doctrine\Collections\SelectableAdapter;
use Pagerfanta\Pagerfanta;

/**
 * @ORM\Entity(repositoryClass="App\Repository\WikiPageRepository")
 * @ORM\Table(uniqueConstraints={
 *     @ORM\UniqueConstraint(name="wiki_pages_path_idx", columns={"path"}),
 *     @ORM\UniqueConstraint(name="wiki_pages_normalized_path_idx", columns={"normalized_path"}),
 * })
 */
class WikiPage {
    /**
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue()
     * @ORM\Id()
     *
     * @var int|null
     */
    private $id;

    /**
     * @ORM\Column(type="text", unique=true)
     *
     * @var string|null
     */
    private $path;

    /**
     * @ORM\Column(type="text", unique=true)
     *
     * @var string|null
     */
    private $normalizedPath;

    /**
     * @ORM\OneToMany(targetEntity="WikiRevision", mappedBy="page", cascade={"persist", "remove"})
     *
     * @var WikiRevision[]|Collection
     */
    private $revisions;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     *
     * @var bool
     */
    private $locked = false;

    public function __construct(
        string $path,
        string $title,
        string $body,
        User $user
    ) {
        $this->setPath($path);
        $this->revisions = new ArrayCollection();

        new WikiRevision($this, $title, $body, $user);
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getPath(): string {
        return $this->path;
    }

    public function setPath(string $path): void {
        $this->path = $path;
        $this->normalizedPath = self::normalizePath($path);
    }

    public function getNormalizedPath(): string {
        return $this->normalizedPath;
    }

    /**
     * @return WikiRevision[]
     */
    public function getRevisions(): array {
        return $this->revisions->getValues();
    }

    public function getLatestRevision(): WikiRevision {
        $criteria = Criteria::create()
            ->orderBy(['timestamp' => 'DESC'])
            ->setMaxResults(1);

        return $this->revisions->matching($criteria)->first();
    }

    public function addRevision(WikiRevision $revision): void {
        if (!$this->revisions->contains($revision)) {
            $this->revisions->add($revision);
        }
    }

    /**
     * @return Pagerfanta|WikiRevision[]
     */
    public function getPaginatedRevisions(int $page, int $maxPerPage = 25): Pagerfanta {
        $criteria = Criteria::create()->orderBy(['timestamp' => 'DESC']);

        $revisions = new Pagerfanta(new SelectableAdapter($this->revisions, $criteria));
        $revisions->setMaxPerPage($maxPerPage);
        $revisions->setCurrentPage($page);

        return $revisions;
    }

    public function isLocked(): bool {
        return $this->locked;
    }

    public function setLocked(bool $locked): void {
        $this->locked = $locked;
    }

    public static function normalizePath(string $path): string {
        return strtolower(str_replace('-', '_', $path));
    }
}
