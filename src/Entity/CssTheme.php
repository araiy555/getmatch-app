<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class CssTheme extends Theme {
    /**
     * @ORM\OneToMany(targetEntity="CssThemeRevision", mappedBy="theme", cascade={"persist", "remove"})
     * @ORM\OrderBy({"timestamp": "DESC", "id": "DESC"})
     *
     * @var CssThemeRevision
     */
    private $revisions;

    public function __construct(string $name, string $css) {
        parent::__construct($name);

        $this->revisions = new ArrayCollection();
        $this->revisions[] = new CssThemeRevision($this, $css);
    }

    public function getLatestRevision(): CssThemeRevision {
        $criteria = Criteria::create()
            ->orderBy(['timestamp' => 'DESC', 'id' => 'DESC'])
            ->setMaxResults(1);

        $revision = $this->revisions->matching($criteria)->first();

        if (!$revision) {
            throw new \DomainException(sprintf(
                'Theme %s (id=%s) does not have any revisions',
                $this->getName(),
                $this->getId()->toString()
            ));
        }

        return $revision;
    }

    public function addRevision(CssThemeRevision $revision): void {
        if (!$this->revisions->contains($revision)) {
            $this->revisions[] = $revision;
        }
    }

    public function getType(): string {
        return 'css';
    }
}
