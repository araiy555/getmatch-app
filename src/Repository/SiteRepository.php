<?php

namespace App\Repository;

use App\Entity\Site;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Ramsey\Uuid\Uuid;

class SiteRepository extends ServiceEntityRepository {
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(ManagerRegistry $registry, LoggerInterface $logger = null) {
        parent::__construct($registry, Site::class);

        $this->logger = $logger ?? new NullLogger();
    }

    public function findCurrentSite(): Site {
        // we currently don't support multi-site
        $site = $this->find(Uuid::NIL);

        if (!$site instanceof Site) {
            $site = new Site();
            $site->setSiteName($_SERVER['SITE_NAME'] ?? $site->getSiteName());
            $this->_em->persist($site);
            $this->_em->flush();
        }

        return $site;
    }

    /**
     * Returns a site name, regardless of database availability.
     */
    public function getCurrentSiteName(): string {
        try {
            return $this->findCurrentSite()->getSiteName();
        } catch (\Exception $e) {
            $this->logger->error((string) $e);

            return $_SERVER['SITE_NAME'] ?? '[name unavailable]';
        }
    }
}
