<?php

namespace App\Repository;

use App\Entity\UserBan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

class UserBanRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, UserBan::class);
    }

    /**
     * @return Pagerfanta|UserBan[]
     */
    public function findActiveBans(int $page, int $maxPerPage = 25): Pagerfanta {
        $now = new \DateTimeImmutable();
        $qb = $this->createQueryBuilder('m')
            ->leftJoin(UserBan::class, 'b',
                'WITH', 'm.user = b.user AND '.
                        'm.timestamp < b.timestamp'
            )
            ->where('b.timestamp IS NULL')
            ->andWhere('m.banned = TRUE')
            ->andWhere('m.expires IS NULL OR m.expires >= :now')
            ->orderBy('m.timestamp', 'DESC')
            ->setParameter('now', $now, Types::DATETIMETZ_IMMUTABLE);

        $pager = new Pagerfanta(new QueryAdapter($qb));
        $pager->setMaxPerPage($maxPerPage);
        $pager->setCurrentPage($page);

        return $pager;
    }
}
