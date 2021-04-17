<?php

namespace App\Tests\DataTransfer;

use App\DataTransfer\UserManager;
use App\Entity\Notification;
use App\Repository\NotificationRepository;
use App\Tests\Fixtures\Factory\EntityFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\DataTransfer\UserManager
 */
class UserManagerTest extends TestCase {
    /**
     * @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $entityManager;

    /**
     * @var NotificationRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $notifications;

    /**
     * @var UserManager
     */
    private $manager;

    protected function setUp(): void {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->notifications = $this->createMock(NotificationRepository::class);
        $this->manager = new UserManager($this->entityManager, $this->notifications);
    }

    public function testRemoveNotifications(): void {
        $user = EntityFactory::makeUser();

        $notification = $this->getMockBuilder(Notification::class)
            ->setConstructorArgs([$user])
            ->getMockForAbstractClass();

        $user->sendNotification($notification);
        $user->sendNotification(
            $this->getMockBuilder(Notification::class)
                ->setConstructorArgs([$user])
                ->getMockForAbstractClass()
        );

        $this->assertEquals(2, $user->getNotificationCount());

        $this->notifications
            ->expects($this->once())
            ->method('findByUserAndIds')
            ->with($user, [$notification->getId()])
            ->willReturn([$notification]);

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($notification);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->manager->clearNotificationsById($user, [$notification->getId()]);

        $this->assertEquals(1, $user->getNotificationCount());
    }
}
