<?php

namespace App\Repository;

use App\Entity\BundledTheme;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BundledTheme|null findOneByName(string $name)
 */
class BundledThemeRepository extends ThemeRepository {
    /**
     * @var array
     */
    private $themesConfig;

    public function __construct(ManagerRegistry $registry, array $themesConfig) {
        parent::__construct($registry, BundledTheme::class);

        $this->themesConfig = $themesConfig;
        unset($this->themesConfig['_default']);
    }

    /**
     * @return BundledTheme[]
     */
    public function findThemesToCreate(): array {
        $themes = $this->createQueryBuilder('t', 't.configKey')
            ->getQuery()
            ->execute();

        foreach (array_diff_key($this->themesConfig, $themes) as $key => $theme) {
            $newThemes[] = new BundledTheme($theme['name'], $key);
        }

        return $newThemes ?? [];
    }

    /**
     * @return BundledTheme[]
     */
    public function findThemesToRemove(): array {
        return $this->createQueryBuilder('t', 't.configKey')
            ->where('t.configKey NOT IN (:keys)')
            ->setParameter('keys', array_keys($this->themesConfig))
            ->getQuery()
            ->execute();
    }
}
