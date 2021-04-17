<?php

namespace App\Repository;

use App\Entity\MessageThread;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

class MessageThreadRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, MessageThread::class);
    }

    /**
     * @return MessageThread[]|Pagerfanta
     */
    public function findUserMessages(User $user, int $page = 1): Pagerfanta {
        $qb = $this->createQueryBuilder('mt')
            ->where(':user MEMBER OF mt.participants')
            ->orderBy('mt.id', 'DESC')
            ->setParameter(':user', $user);

        $pager = new Pagerfanta(new QueryAdapter($qb));
        $pager->setMaxPerPage(25);
        $pager->setCurrentPage($page);

        return $pager;
    }
}
