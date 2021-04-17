<?php

namespace App\Repository;

use App\Entity\Message;
use App\Repository\Contracts\PrunesIpAddresses;
use App\Repository\Traits\PrunesIpAddressesTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MessageRepository extends ServiceEntityRepository implements PrunesIpAddresses {
    use PrunesIpAddressesTrait;

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Message::class);
    }
}
