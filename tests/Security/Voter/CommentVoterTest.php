<?php

namespace App\Tests\Security\Voter;

use App\Entity\ForumBan;
use App\Security\Voter\CommentVoter;
use App\Tests\Fixtures\Factory\EntityFactory;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @covers \App\Security\Voter\CommentVoter
 */
class CommentVoterTest extends VoterTestCase {
    protected function getVoter(): VoterInterface {
        return new CommentVoter($this->decisionManager);
    }

    public function testNonPrivilegedUserCannotDeleteOthersTrash(): void {
        $comment = EntityFactory::makeComment();
        $comment->trash();

        $token = $this->createToken(['ROLE_USER'], EntityFactory::makeUser());

        $this->expectRoleLookup('ROLE_ADMIN', $token);
        $this->assertDenied('purge', $comment, $token);
    }

    public function testUserCanPurgeOwnTrash(): void {
        $user = EntityFactory::makeUser();

        $comment = EntityFactory::makeComment($user);
        $comment->trash();

        $token = $this->createToken(['ROLE_USER'], $user);

        $this->expectNoRoleLookup();
        $this->assertGranted('purge', $comment, $token);
    }

    public function testAdminCanPurgeOthersTrash(): void {
        $comment = EntityFactory::makeComment();
        $comment->trash();

        $token = $this->createToken(['ROLE_ADMIN'], EntityFactory::makeUser());

        $this->expectRoleLookup('ROLE_ADMIN', $token);
        $this->assertGranted('purge', $comment, $token);
    }

    public function testCanVote(): void {
        $comment = EntityFactory::makeComment();

        $token = $this->createToken(['ROLE_USER'], EntityFactory::makeUser());

        $this->assertGranted('vote', $comment, $token);
    }

    public function testCannotVoteIfBanned(): void {
        $user = EntityFactory::makeUser();

        $comment = EntityFactory::makeComment();
        $forum = $comment->getSubmission()->getForum();
        $forum->addBan(new ForumBan($forum, $user, 'reason', true, $comment->getUser()));

        $token = $this->createToken(['ROLE_USER'], $user);

        $this->assertDenied('vote', $comment, $token);
    }
}
