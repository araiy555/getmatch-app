<?php

namespace App\Repository;

use App\Entity\Theme;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\Collections\SelectableAdapter;
use Pagerfanta\Pagerfanta;

class ThemeRepository extends ServiceEntityRepository {
    public function __construct(
        ManagerRegistry $registry,
        string $entityClass = Theme::class
    ) {
        parent::__construct($registry, $entityClass);
    }

    /**
     * @return Pagerfanta|Theme[]
     */
    public function findPaginated(int $page): Pagerfanta {
        $criteria = Criteria::create()
            ->orderBy(['name' => 'ASC']);

        $pager = new Pagerfanta(new SelectableAdapter($this, $criteria));
        $pager->setMaxPerPage(25);
        $pager->setCurrentPage($page);

        return $pager;
    }
}
