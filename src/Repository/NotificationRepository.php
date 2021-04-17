<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NotificationRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Notification::class);
    }

    /**
     * @param array<string|\Stringable> $ids
     */
    public function findByUserAndIds(User $user, array $ids): array {
        return $this->findBy(['id' => $ids, 'user' => $user], [], 100);
    }
}
