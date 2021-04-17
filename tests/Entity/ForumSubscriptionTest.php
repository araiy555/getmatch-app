<?php

namespace App\Tests\Entity;

use App\Entity\Forum;
use App\Entity\ForumSubscription;
use App\Entity\User;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Rfc4122\FieldsInterface;

/**
 * @covers \App\Entity\ForumSubscription
 */
class ForumSubscriptionTest extends TestCase {
    private function subscription(
        User $user = null,
        Forum $forum = null
    ): ForumSubscription {
        return new ForumSubscription(
            $user ?? EntityFactory::makeUser(),
            $forum ?? EntityFactory::makeForum(),
        );
    }

    /**
     * @testdox ID is UUIDv4
     */
    public function testIdIsUuidV4(): void {
        $fields = $this->subscription()->getId()->getFields();

        $this->assertInstanceOf(FieldsInterface::class, $fields);
        $this->assertSame(4, $fields->getVersion());
    }

    public function testGetForum(): void {
        $forum = EntityFactory::makeForum();

        $this->assertSame($forum, $this->subscription(null, $forum)->getForum());
    }

    public function testGetUser(): void {
        $user = EntityFactory::makeUser();

        $this->assertSame($user, $this->subscription($user)->getUser());
    }

    /**
     * @group time-sensitive
     */
    public function testGetSubscribedAt(): void {
        $this->assertSame(
            time(),
            $this->subscription()->getSubscribedAt()->getTimestamp(),
        );
    }
}
