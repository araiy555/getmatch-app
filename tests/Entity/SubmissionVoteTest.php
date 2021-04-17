<?php

namespace App\Tests\Entity;

use App\Entity\Contracts\Votable;
use App\Entity\SubmissionVote;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\SubmissionVote
 */
class SubmissionVoteTest extends TestCase {
    public function testGetSubmission(): void {
        $submission = EntityFactory::makeSubmission();
        $vote = new SubmissionVote(
            Votable::VOTE_UP,
            EntityFactory::makeUser(),
            null,
            $submission,
        );

        $this->assertSame($submission, $vote->getSubmission());
    }

    public function testSubmissionHasVoteAfterInit(): void {
        $submission = EntityFactory::makeSubmission();
        $user = EntityFactory::makeUser();
        $vote = new SubmissionVote(Votable::VOTE_UP, $user, null, $submission);

        $this->assertSame($vote, $submission->getUserVote($user));
    }
}
