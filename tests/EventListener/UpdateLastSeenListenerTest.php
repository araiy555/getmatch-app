<?php

namespace App\Tests\EventListener;

use App\Entity\User;
use App\EventListener\UpdateLastSeenListener;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * @covers \App\EventListener\UpdateLastSeenListener
 * @group time-sensitive
 */
class UpdateLastSeenListenerTest extends TestCase {
    /**
     * @var MockObject|EntityManagerInterface
     */
    private $entityManager;

    public static function setUpBeforeClass(): void {
        ClockMock::register(UpdateLastSeenListener::class);
    }

    protected function setUp(): void {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
    }

    public function testUpdatesUserTokens(): void {
        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        /** @var MockObject|User $user */
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();

        $user
            ->expects($this->once())
            ->method('updateLastSeen');

        $event = new InteractiveLoginEvent(
            new Request(),
            new PostAuthenticationGuardToken($user, 'main', ['ROLE_USER'])
        );

        $listener = new UpdateLastSeenListener($this->entityManager);
        $listener->onInteractiveLogin($event);
    }

    public function testDoesNothingOnAnonymousToken(): void {
        $this->entityManager
            ->expects($this->never())
            ->method('flush');

        $event = new InteractiveLoginEvent(
            new Request(),
            new AnonymousToken('gsagsagda', 'anon.', [])
        );

        $listener = new UpdateLastSeenListener($this->entityManager);
        $listener->onInteractiveLogin($event);
    }
}
