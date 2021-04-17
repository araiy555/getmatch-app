<?php

namespace App\Repository;

use App\Entity\WikiPage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

/**
 * @method WikiPage|null findOneByNormalizedPath(string $path)
 */
class WikiPageRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, WikiPage::class);
    }

    public function findOneCaseInsensitively(?string $path): ?WikiPage {
        if ($path === null) {
            return null;
        }

        return $this->findOneByNormalizedPath(WikiPage::normalizePath($path));
    }

    /**
     * @return Pagerfanta|WikiPage[]
     */
    public function findAllPages(int $page): Pagerfanta {
        $qb = $this->createQueryBuilder('wp')
            ->orderBy('wp.normalizedPath', 'ASC');

        $pager = new Pagerfanta(new QueryAdapter($qb));
        $pager->setMaxPerPage(25);
        $pager->setCurrentPage($page);

        return $pager;
    }
}
