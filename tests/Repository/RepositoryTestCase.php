<?php

namespace App\Tests\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class RepositoryTestCase extends KernelTestCase {
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    protected function setUp(): void {
        self::bootKernel();

        $this->entityManager = self::$container->get(EntityManagerInterface::class);
    }

    protected function tearDown(): void {
        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null;
    }
}
