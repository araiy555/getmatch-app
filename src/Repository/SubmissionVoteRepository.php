<?php

namespace App\Repository;

use App\Entity\SubmissionVote;
use App\Repository\Contracts\PrunesIpAddresses;
use App\Repository\Traits\PrunesIpAddressesTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SubmissionVoteRepository extends ServiceEntityRepository implements PrunesIpAddresses {
    use PrunesIpAddressesTrait;

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, SubmissionVote::class);
    }
}
