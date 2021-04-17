<?php

namespace App\Repository;

use App\Entity\ForumTag;
use App\Utils\CanonicalRedirector;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\Collections\SelectableAdapter;
use Pagerfanta\Pagerfanta;

/**
 * @method ForumTag[]    findByNormalizedName(string|string[] $normalizedName)
 * @method ForumTag|null findOneByNormalizedName(string $normalizedName)
 */
class ForumTagRepository extends ServiceEntityRepository {
    /**
     * @var CanonicalRedirector
     */
    private $canonicalRedirector;

    public function __construct(
        ManagerRegistry $registry,
        CanonicalRedirector $canonicalRedirector
    ) {
        parent::__construct($registry, ForumTag::class);

        $this->canonicalRedirector = $canonicalRedirector;
    }

    public function findPaginated(int $page): Pagerfanta {
        $criteria = Criteria::create()->orderBy(['normalizedName' => 'ASC']);

        $pager = new Pagerfanta(new SelectableAdapter($this, $criteria));
        $pager->setMaxPerPage(25);
        $pager->setCurrentPage($page);

        return $pager;
    }

    public function findByNameOrRedirectToCanonical(?string $name, string $param): ?ForumTag {
        if ($name === null) {
            return null;
        }

        $tag = $this->findOneByNormalizedName(ForumTag::normalizeName($name));

        if ($tag) {
            $this->canonicalRedirector->canonicalize($tag->getName(), $param);
        }

        return $tag;
    }
}
