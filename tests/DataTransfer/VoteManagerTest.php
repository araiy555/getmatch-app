<?php

namespace App\Tests\DataTransfer;

use App\DataTransfer\VoteManager;
use App\Entity\Contracts\Votable;
use App\Tests\Fixtures\Factory\EntityFactory;
use App\Tests\Fixtures\VotableStub;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\DataTransfer\VoteManager
 */
class VoteManagerTest extends TestCase {
    /**
     * @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $entityManager;
    private $voteManager;
    private $votable;

    protected function setUp(): void {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->voteManager = new VoteManager($this->entityManager);
        $this->votable = new VotableStub();
    }

    public function testAddVote(): void {
        $user = EntityFactory::makeUser();

        $this->entityManager
            ->expects($this->never())
            ->method('remove');

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->votable->createVote(Votable::VOTE_UP, $user, null));

        $this->voteManager->vote($this->votable, $user, Votable::VOTE_UP, null);
    }

    public function testReplaceVote(): void {
        $user = EntityFactory::makeUser();

        $vote = $this->votable->createVote(Votable::VOTE_DOWN, $user, null);
        $this->votable->addVote($vote);

        $this->entityManager
            ->expects($this->never())
            ->method('remove');

        $this->entityManager
            ->expects($this->never())
            ->method('persist');

        $this->voteManager->vote($this->votable, $user, Votable::VOTE_UP, null);
    }

    public function testRemoveVote(): void {
        $user = EntityFactory::makeUser();

        $vote = $this->votable->createVote(Votable::VOTE_DOWN, $user, null);
        $this->votable->addVote($vote);

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($vote);

        $this->entityManager
            ->expects($this->never())
            ->method('persist');

        $this->voteManager->vote($this->votable, $user, Votable::VOTE_NONE, null);
    }
}
