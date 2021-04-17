<?php

namespace App\Repository;

use App\Entity\Forum;
use App\Entity\ForumBan;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

class ForumBanRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, ForumBan::class);
    }

    /**
     * Find all bans in a forum that haven't been undone and which haven't
     * expired.
     *
     * @return Pagerfanta|ForumBan[]
     */
    public function findValidBansInForum(Forum $forum, int $page, int $maxPerPage = 25): Pagerfanta {
        $now = new \DateTimeImmutable();

        $qb = $this->createQueryBuilder('m')
            ->leftJoin(ForumBan::class, 'b',
                'WITH', 'm.user = b.user AND '.
                        'm.forum = b.forum AND '.
                        'm.timestamp < b.timestamp'
            )
            ->where('b.timestamp IS NULL')
            ->andWhere('m.banned = TRUE')
            ->andWhere('m.forum = :forum')
            ->andWhere('m.expires IS NULL OR m.expires >= :now')
            ->orderBy('m.timestamp', 'DESC')
            ->setParameter('forum', $forum)
            ->setParameter('now', $now, Types::DATETIMETZ_IMMUTABLE);

        $pager = new Pagerfanta(new QueryAdapter($qb));
        $pager->setMaxPerPage($maxPerPage);
        $pager->setCurrentPage($page);

        return $pager;
    }

    /**
     * @return Pagerfanta|ForumBan[]
     */
    public function findActiveBansByUser(User $user, int $page, int $maxPerPage = 25): Pagerfanta {
        $now = new \DateTimeImmutable();

        $qb = $this->createQueryBuilder('m')
            ->leftJoin(ForumBan::class, 'b',
                'WITH', 'm.user = b.user AND '.
                        'm.forum = b.forum AND '.
                        'm.timestamp < b.timestamp'
            )
            ->where('b.timestamp IS NULL')
            ->andWhere('m.banned = TRUE')
            ->andWhere('m.user = :user')
            ->andWhere('m.expires IS NULL OR m.expires >= :now')
            ->orderBy('m.timestamp', 'DESC')
            ->setParameter('user', $user)
            ->setParameter('now', $now, Types::DATETIMETZ_IMMUTABLE);

        $pager = new Pagerfanta(new QueryAdapter($qb));
        $pager->setMaxPerPage($maxPerPage);
        $pager->setCurrentPage($page);

        return $pager;
    }
}
