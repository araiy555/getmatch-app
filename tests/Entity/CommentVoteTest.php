<?php

namespace App\Tests\Entity;

use App\Entity\CommentVote;
use App\Entity\Contracts\Votable;
use App\Tests\Fixtures\Factory\EntityFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\CommentVote
 */
class CommentVoteTest extends TestCase {
    public function testGetComment(): void {
        $comment = EntityFactory::makeComment();
        $vote = new CommentVote(
            Votable::VOTE_UP,
            EntityFactory::makeUser(),
            null,
            $comment,
        );

        $this->assertSame($comment, $vote->getComment());
    }

    public function testCommentHasVoteAfterInit(): void {
        $comment = EntityFactory::makeComment();
        $user = EntityFactory::makeUser();
        $vote = new CommentVote(Votable::VOTE_UP, $user, null, $comment);

        $this->assertSame($vote, $comment->getUserVote($user));
    }
}
