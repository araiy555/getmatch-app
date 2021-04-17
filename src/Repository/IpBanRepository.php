<?php

namespace App\Repository;

use App\Entity\IpBan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\Collections\SelectableAdapter;
use Pagerfanta\Pagerfanta;

class IpBanRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, IpBan::class);
    }

    /**
     * @return Pagerfanta|IpBan[]
     */
    public function findAllPaginated(int $page, $maxPerPage = 25): Pagerfanta {
        $criteria = Criteria::create()->orderBy(['timestamp' => 'DESC']);

        $bans = new Pagerfanta(new SelectableAdapter($this, $criteria));
        $bans->setMaxPerPage($maxPerPage);
        $bans->setCurrentPage($page);

        return $bans;
    }

    public function findActiveBans(string $ip): array {
        $now = new \DateTimeImmutable('@'.time());

        $qb = $this->createQueryBuilder('b');
        $qb
            ->where('InetContainsOrEquals(b.ip, :ip) = TRUE')
            ->andWhere($qb->expr()->orX('b.expires IS NULL', 'b.expires >= :now'))
            ->setParameter('ip', $ip, 'inet')
            ->setParameter('now', $now, Types::DATETIMETZ_IMMUTABLE);

        return $qb->getQuery()->execute();
    }

    public function ipIsBanned(string $ip): bool {
        $now = new \DateTimeImmutable('@'.time());

        $qb = $this->createQueryBuilder('b');
        $qb
            ->select('COUNT(b)')
            ->where('InetContainsOrEquals(b.ip, :ip) = TRUE')
            ->andWhere($qb->expr()->orX('b.expires IS NULL', 'b.expires >= :now'))
            ->setParameter('ip', $ip, 'inet')
            ->setParameter('now', $now, Types::DATETIMETZ_IMMUTABLE);

        return $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
