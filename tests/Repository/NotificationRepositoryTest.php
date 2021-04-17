<?php

namespace App\Tests\Repository;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @covers \App\Repository\NotificationRepository
 */
class NotificationRepositoryTest extends RepositoryTestCase {
    /**
     * @var NotificationRepository
     */
    private $repository;

    protected function setUp(): void {
        parent::setUp();

        $this->entityManager = self::$container->get(EntityManagerInterface::class);
        $this->repository = self::$container->get(NotificationRepository::class);
    }

    public function testGetsOnlyNotificationsBySpecifiedUser(): void {
        $notifications = $this->repository->findAll();

        $ids = array_map(function ($notification) {
            return $notification->getId();
        }, $notifications);

        $userIds = array_unique(array_map(function ($notification) {
            return $notification->getUser()->getId();
        }, $notifications));

        $this->assertGreaterThanOrEqual(2, \count($userIds));
        $user = $notifications[array_key_first($notifications)]->getUser();

        $found = $this->repository->findByUserAndIds($user, $ids);

        $this->assertSameSize($found, array_filter(
            $notifications,
            function (Notification $notification) use ($user) {
                return $notification->getUser() === $user;
            }
        ));
    }
}
