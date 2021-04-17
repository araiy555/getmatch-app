<?php

namespace App\Repository;

use App\Entity\CssTheme;
use App\Entity\CssThemeRevision;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CssThemeRevisionRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, CssThemeRevision::class);
    }

    public function findOneByThemeAndId(CssTheme $theme, string $id): ?CssThemeRevision {
        $revision = $this->findOneBy([
            'theme' => $theme,
            'id' => $id,
        ]);
        \assert($revision instanceof CssThemeRevision || $revision === null);

        return $revision;
    }
}
