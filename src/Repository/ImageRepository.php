<?php

namespace App\Repository;

use App\Entity\Contracts\VisibilityInterface;
use App\Entity\Forum;
use App\Entity\Image;
use App\Entity\Submission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Image|null find($id, $lockMode = null, $lockVersion = null)
 * @method Image|null findOneBy(array $criteria, array $orderBy = null)
 * @method Image|null findOneBySha256(string $sha256, array $orderBy = null)
 * @method Image[]    findAll()
 * @method Image[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Image[]    findByFileName(string|string[] $fileNames)
 */
class ImageRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Image::class);
    }

    /**
     * @param Image[] $images
     *
     * @return Image[]|array
     */
    public function filterOrphaned(array $images): array {
        return $this->createQueryBuilder('i')
            ->andWhere('i IN (?1)')
            ->andWhere('i NOT IN (SELECT IDENTITY(f1.lightBackgroundImage) FROM '.Forum::class.' f1 WHERE f1.lightBackgroundImage IS NOT NULL)')
            ->andWhere('i NOT IN (SELECT IDENTITY(f2.darkBackgroundImage) FROM '.Forum::class.' f2 WHERE f2.darkBackgroundImage IS NOT NULL)')
            ->andWhere('i NOT IN (SELECT IDENTITY(s.image) FROM '.Submission::class.' s WHERE s.image IS NOT NULL AND s.visibility <> ?2)')
            ->setParameter(1, $images)
            ->setParameter(2, VisibilityInterface::VISIBILITY_SOFT_DELETED)
            ->getQuery()
            ->execute();
    }
}
