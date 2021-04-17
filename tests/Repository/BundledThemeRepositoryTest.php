<?php

namespace App\Tests\Repository;

use App\Entity\BundledTheme;
use App\Repository\BundledThemeRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @covers \App\Repository\BundledThemeRepository
 */
class BundledThemeRepositoryTest extends RepositoryTestCase {
    public function testSyncedConfigAndDatabase(): void {
        $repository = $this->entityManager->getRepository(BundledTheme::class);

        $this->assertEmpty($repository->findThemesToCreate());
        $this->assertEmpty($repository->findThemesToRemove());
    }

    public function testThemeNotPresentInDatabase(): void {
        $themesConfig = json_decode(file_get_contents(__DIR__.'/../Fixtures/themes.json'), true);
        $themesConfig['new-theme'] = [
            'name' => 'New Theme',
            'entrypoint' => 'what/ever',
        ];

        $repository = new BundledThemeRepository(
            self::$container->get(ManagerRegistry::class),
            $themesConfig
        );

        $themesToCreate = $repository->findThemesToCreate();
        $this->assertCount(1, $themesToCreate);

        $theme = array_pop($themesToCreate);
        $this->assertSame('New Theme', $theme->getName());
        $this->assertSame('new-theme', $theme->getConfigKey());

        $this->assertEmpty($repository->findThemesToRemove());
    }

    public function testThemeNotPresentInConfig(): void {
        $themesConfig = json_decode(file_get_contents(__DIR__.'/../Fixtures/themes.json'), true);
        unset($themesConfig['postmill-classic']);

        $repository = new BundledThemeRepository(
            self::$container->get(ManagerRegistry::class),
            $themesConfig
        );

        $this->assertEmpty($repository->findThemesToCreate());

        $themesToRemove = $repository->findThemesToRemove();
        $this->assertCount(1, $themesToRemove);

        $theme = array_pop($themesToRemove);
        $this->assertSame('Postmill Classic', $theme->getName());
    }
}
