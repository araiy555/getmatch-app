<?php

namespace App\Tests\Entity;

use App\Entity\Contracts\Votable;
use App\Entity\Exception\BadVoteChoiceException;
use App\Entity\User;
use App\Entity\Vote;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\Vote
 */
class VoteTest extends TestCase {
    private function vote(
        int $choice = Votable::VOTE_UP,
        User $user = null,
        string $ip = null
    ): Vote {
        $user = $user ?? EntityFactory::makeUser();

        return $this->getMockBuilder(Vote::class)
            ->setConstructorArgs([$choice, $user, $ip])
            ->getMockForAbstractClass();
    }

    /**
     * @dataProvider provideValidChoices
     */
    public function testAcceptsValidChoice(int $choice): void {
        $this->expectNotToPerformAssertions();

        $this->vote($choice, EntityFactory::makeUser());
    }

    /**
     * @dataProvider provideValidChoices
     */
    public function testGetChoice(int $choice): void {
        $this->assertSame($choice, $this->vote($choice)->getChoice());
    }

    /**
     * @dataProvider provideValidChoices
     */
    public function testGetUpvote(int $choice, bool $isUpvote): void {
        $this->assertSame($isUpvote, $this->vote($choice)->getUpvote());
    }

    public function provideValidChoices(): iterable {
        yield 'upvote' => [Votable::VOTE_UP, true];
        yield 'downvote' => [Votable::VOTE_DOWN, false];
    }

    public function testGetId(): void {
        $this->assertNull($this->vote()->getId());
    }

    public function testGetIdWithPropertySet(): void {
        $vote = $this->vote(Votable::VOTE_UP, EntityFactory::makeUser());
        $r = (new \ReflectionClass(Vote::class))->getProperty('id');
        $r->setAccessible(true);
        $r->setValue($vote, 123);
        $r->setAccessible(false);

        $this->assertSame(123, $vote->getId());
    }

    /**
     * @group time-sensitive
     */
    public function testGetTimestamp(): void {
        $this->assertSame(time(), $this->vote()->getTimestamp()->getTimestamp());
    }

    public function testGetUser(): void {
        $user = EntityFactory::makeUser();

        $this->assertSame($user, $this->vote(Votable::VOTE_UP, $user)->getUser());
    }

    /**
     * @dataProvider provideInvalidChoices
     */
    public function testDoesNotAcceptInvalidChoice(
        string $expectedMessage,
        int $choice
    ): void {
        $this->expectException(BadVoteChoiceException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->vote($choice);
    }

    public function provideInvalidChoices(): iterable {
        yield 'none vote' => ['A vote entity cannot have a "none" status', Votable::VOTE_NONE];
        yield 'bad vote value' => ['Unknown choice', 2];
        yield 'bad negative vote value' => ['Unknown choice', -412];
    }

    public function testDoesNotAcceptBadIpAddress(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Bad IP address');

        $this->vote(Votable::VOTE_UP, null, '256.256.256.256');
    }

    /**
     * @dataProvider provideExpectedIpWhitelistMap
     */
    public function testSavesIpDependingOnUserWhitelisting(
        ?string $expectedIp,
        bool $whitelisted
    ): void {
        $user = EntityFactory::makeUser();
        $user->setWhitelisted($whitelisted);
        $vote = $this->vote(Votable::VOTE_UP, $user, '127.0.0.1');

        $this->assertSame($expectedIp, $vote->getIp());
    }

    public function provideExpectedIpWhitelistMap(): iterable {
        yield 'no ip' => [null, true];
        yield 'ip given' => ['127.0.0.1', false];
    }
}
