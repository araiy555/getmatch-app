<?php

namespace App\Tests\Security\Voter;

use App\Repository\ModeratorRepository;
use App\Security\Voter\UserVoter;
use App\Tests\Fixtures\Factory\EntityFactory;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @covers \App\Security\Voter\UserVoter
 */
class UserVoterTest extends VoterTestCase {
    /**
     * @var ModeratorRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $moderators;

    protected function setUp(): void {
        $this->moderators = $this->createMock(ModeratorRepository::class);

        parent::setUp();
    }

    protected function getVoter(): VoterInterface {
        return new UserVoter($this->decisionManager, $this->moderators);
    }

    public function testNewUserWithoutPostsCannotSetBiography(): void {
        $user = EntityFactory::makeUser();
        $token = $this->createToken(['ROLE_USER'], $user);

        $this->assertDenied('edit_biography', $user, $token);
    }

    public function testNewUserWithSubmissionCanSetBiography(): void {
        $user = EntityFactory::makeUser();
        EntityFactory::makeSubmission(null, $user, null);
        $token = $this->createToken(['ROLE_USER'], $user);

        $this->assertGranted('edit_biography', $user, $token);
    }

    public function testNewUserWithCommentCanSetBiography(): void {
        $user = EntityFactory::makeUser();
        EntityFactory::makeComment($user);
        $token = $this->createToken(['ROLE_USER'], $user);

        $this->assertGranted('edit_biography', $user, $token);
    }

    public function testWhitelistedUserWithNoPostsCanSetBiography(): void {
        $user = EntityFactory::makeUser();

        $token = $this->createToken(['ROLE_WHITELISTED'], $user);

        $this->decisionManager
            ->expects($this->exactly(2))
            ->method('decide')
            ->withConsecutive([$token, ['ROLE_ADMIN']], [$token, ['ROLE_WHITELISTED']])
            ->willReturnOnConsecutiveCalls(false, true);

        $this->assertGranted('edit_biography', $user, $token);
    }

    public function testUserCannotSetOtherUsersBiography(): void {
        $subject = EntityFactory::makeUser();

        $tokenUser = EntityFactory::makeUser();
        $token = $this->createToken(['ROLE_WHITELISTED'], $tokenUser);

        $this->assertDenied('edit_biography', $subject, $token);
    }

    public function testAnonymousUserCannotSetBiography(): void {
        $subject = EntityFactory::makeUser();

        $token = $this->createToken([], null);

        $this->assertDenied('edit_biography', $subject, $token);
    }

    public function testAdminCanSetOtherUsersBiography(): void {
        $subject = EntityFactory::makeUser();

        $token = $this->createToken(['ROLE_ADMIN'], EntityFactory::makeUser());
        $this->expectRoleLookup('ROLE_ADMIN', $token);

        $this->assertGranted('edit_biography', $subject, $token);
    }
}
