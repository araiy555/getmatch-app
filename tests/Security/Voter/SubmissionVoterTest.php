<?php

namespace App\Tests\Security\Voter;

use App\Entity\ForumBan;
use App\Security\Voter\SubmissionVoter;
use App\Tests\Fixtures\Factory\EntityFactory;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @covers \App\Security\Voter\SubmissionVoter
 */
class SubmissionVoterTest extends VoterTestCase {
    protected function getVoter(): VoterInterface {
        return new SubmissionVoter($this->decisionManager);
    }

    public function testNonPrivilegedUserCannotDeleteOthersTrash(): void {
        $submission = EntityFactory::makeSubmission();
        $submission->trash();

        $token = $this->createToken(['ROLE_USER'], EntityFactory::makeUser());

        $this->expectRoleLookup('ROLE_ADMIN', $token);
        $this->assertDenied('purge', $submission, $token);
    }

    public function testUserCanPurgeOwnTrash(): void {
        $user = EntityFactory::makeUser();
        $submission = EntityFactory::makeSubmission(null, $user);
        $submission->trash();

        $token = $this->createToken(['ROLE_USER'], $user);

        $this->expectNoRoleLookup();
        $this->assertGranted('purge', $submission, $token);
    }

    public function testAdminCanPurgeOthersTrash(): void {
        $submission = EntityFactory::makeSubmission();
        $submission->trash();

        $token = $this->createToken(['ROLE_USER', 'ROLE_ADMIN'], EntityFactory::makeUser());

        $this->expectRoleLookup('ROLE_ADMIN', $token);
        $this->assertGranted('purge', $submission, $token);
    }

    public function testCanVote(): void {
        $submission = EntityFactory::makeSubmission();

        $token = $this->createToken(['ROLE_USER'], EntityFactory::makeUser());

        $this->assertGranted('vote', $submission, $token);
    }

    public function testCannotVoteIfBanned(): void {
        $user = EntityFactory::makeUser();

        $submission = EntityFactory::makeSubmission();
        $forum = $submission->getForum();
        $forum->addBan(new ForumBan($forum, $user, 'reason', true, $submission->getUser()));

        $token = $this->createToken(['ROLE_USER'], $user);

        $this->assertDenied('vote', $submission, $token);
    }
}
