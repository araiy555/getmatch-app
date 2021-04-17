<?php

namespace App\Repository;

use App\Entity\CommentVote;
use App\Repository\Contracts\PrunesIpAddresses;
use App\Repository\Traits\PrunesIpAddressesTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CommentVoteRepository extends ServiceEntityRepository implements PrunesIpAddresses {
    use PrunesIpAddressesTrait;

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, CommentVote::class);
    }
}
