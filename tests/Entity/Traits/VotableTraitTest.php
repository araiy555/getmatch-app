<?php

namespace App\Tests\Entity\Traits;

use App\Entity\Contracts\Votable;
use App\Entity\Traits\VotableTrait;
use App\Tests\Fixtures\Factory\EntityFactory;
use App\Tests\Fixtures\VotableStub;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\Traits\VotableTrait
 */
class VotableTraitTest extends TestCase {
    /**
     * @var VotableTrait
     */
    private $votable;

    protected function setUp(): void {
        $this->votable = new VotableStub();
    }

    public function testVoteScores(): void {
        $user = EntityFactory::makeUser();

        $this->assertSame(0, $this->votable->getNetScore());
        $this->assertSame(0, $this->votable->getUpvotes());
        $this->assertSame(0, $this->votable->getDownvotes());

        $vote = $this->votable->createVote(Votable::VOTE_UP, $user, null);
        $this->votable->getVotes()->add($vote);
        $this->assertSame(1, $this->votable->getNetScore());
        $this->assertSame(1, $this->votable->getUpvotes());
        $this->assertSame(0, $this->votable->getDownvotes());

        $vote->setChoice(Votable::VOTE_DOWN);
        $this->assertSame(-1, $this->votable->getNetScore());
        $this->assertSame(0, $this->votable->getUpvotes());
        $this->assertSame(1, $this->votable->getDownvotes());
    }

    public function testGetUserVote(): void {
        $user1 = EntityFactory::makeUser();
        $vote1 = $this->votable->createVote(Votable::VOTE_UP, $user1, null);
        $this->votable->addVote($vote1);

        $user2 = EntityFactory::makeUser();

        $this->assertSame($vote1, $this->votable->getUserVote($user1));
        $this->assertNull($this->votable->getUserVote($user2));
    }

    public function testGetUserChoice(): void {
        $user1 = EntityFactory::makeUser();
        $this->votable->getVotes()->add(
            $this->votable->createVote(Votable::VOTE_UP, $user1, null)
        );

        $user2 = EntityFactory::makeUser();
        $this->votable->getVotes()->add(
            $this->votable->createVote(Votable::VOTE_DOWN, $user2, null)
        );

        $user3 = EntityFactory::makeUser();

        $this->assertSame(Votable::VOTE_UP, $this->votable->getUserChoice($user1));
        $this->assertSame(Votable::VOTE_DOWN, $this->votable->getUserChoice($user2));
        $this->assertSame(Votable::VOTE_NONE, $this->votable->getUserChoice($user3));
    }
}
