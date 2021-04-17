<?php

namespace App\Tests\EventListener;

use App\Entity\User;
use App\Event\UserCreated;
use App\EventListener\MakeFirstUserAdminListener;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\EventListener\MakeFirstUserAdminListener
 */
class MakeFirstUserAdminListenerTest extends TestCase {
    /**
     * @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $manager;

    /**
     * @var User|\PHPUnit\Framework\MockObject\MockObject
     */
    private $user;

    protected function setUp(): void {
        $this->manager = $this->createMock(EntityManagerInterface::class);
        $this->user = $this->createMock(User::class);
    }

    public function testFirstUserGivenAdmin(): void {
        $this->manager
            ->expects($this->once())
            ->method('flush');

        $this->user
            ->method('getId')
            ->willReturn(1);

        $listener = new MakeFirstUserAdminListener($this->manager, true);
        $listener->onUserCreated(new UserCreated($this->user));
    }

    public function testFirstUserNotGivenAdminIfFeatureIsDisabled(): void {
        $this->manager
            ->expects($this->never())
            ->method('flush');

        $this->user
            ->method('getId')
            ->willReturn(1);

        $listener = new MakeFirstUserAdminListener($this->manager, false);
        $listener->onUserCreated(new UserCreated($this->user));
    }

    public function testSecondUserIsNotGivenAdmin(): void {
        $this->manager
            ->expects($this->never())
            ->method('flush');

        $this->user
            ->method('getId')
            ->willReturn(2);

        $listener = new MakeFirstUserAdminListener($this->manager, false);
        $listener->onUserCreated(new UserCreated($this->user));
    }
}
