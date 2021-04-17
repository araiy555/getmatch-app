<?php

namespace App\Tests\Repository;

use App\Entity\User;
use App\Repository\UserRepository;

/**
 * @covers \App\Repository\UserRepository
 */
class UserRepositoryTest extends RepositoryTestCase {
    /**
     * @var UserRepository
     */
    private $repository;

    protected function setUp(): void {
        parent::setUp();

        $this->repository = $this->entityManager->getRepository(User::class);
    }

    public function testFindIpsUsedByUser(): void {
        $user = $this->repository->findOneByUsername('zach');
        $ips = iterator_to_array($this->repository->findIpsUsedByUser($user));

        $this->assertEqualsCanonicalizing([
            '127.8.9.0',       // submission
            '192.168.0.4',     // message
            '192.168.0.55',    // registration ips
            '255.241.124.124', // comment reply
            '8.8.8.8',         // comment
        ], $ips);
    }
}
