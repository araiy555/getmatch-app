<?php

namespace App\Tests\Validator;

use App\Entity\Forum;
use App\Entity\ForumBan;
use App\Entity\User;
use App\Security\Authentication;
use App\Tests\Fixtures\Factory\EntityFactory;
use App\Validator\NotForumBanned;
use App\Validator\NotForumBannedValidator;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \App\Validator\NotForumBannedValidator
 */
class NotForumBannedValidatorTest extends ConstraintValidatorTestCase {
    /**
     * @var Authentication
     */
    private $authentication;

    /**
     * @var Forum
     */
    private $forum;

    protected function setUp(): void {
        $this->authentication = $this->createMock(Authentication::class);
        $this->forum = EntityFactory::makeForum();

        parent::setUp();
    }

    protected function createValidator(): ConstraintValidator {
        return new NotForumBannedValidator($this->authentication);
    }

    public function testNoViolationWhenUnauthenticated(): void {
        $this->validator->validate($this->forum, new NotForumBanned());

        $this->assertNoViolation();
    }

    public function testRaisesOnBannedUser(): void {
        $user = $this->login();
        $this->forum->addBan(new ForumBan($this->forum, $user, 'a', true, EntityFactory::makeUser()));

        $this->validator->validate($this->forum, new NotForumBanned());

        $this->buildViolation('forum.banned')
            ->setCode(NotForumBanned::FORUM_BANNED_ERROR)
            ->assertRaised();
    }

    public function testNoViolationOnExpiredBan(): void {
        $user = $this->login();
        $this->forum->addBan(new ForumBan($this->forum, $user, 'a', true, EntityFactory::makeUser(), new \DateTime('yesterday')));

        $this->validator->validate($this->forum, new NotForumBanned());

        $this->assertNoViolation();
    }

    public function testRaisesOnExpiringBan(): void {
        $user = $this->login();

        $this->forum->addBan(new ForumBan($this->forum, $user, 'a', true, EntityFactory::makeUser(), new \DateTime('tomorrow')));

        $this->validator->validate($this->forum, new NotForumBanned());

        $this->buildViolation('forum.banned')
            ->setCode(NotForumBanned::FORUM_BANNED_ERROR)
            ->assertRaised();
    }

    public function testNoViolationOnNullForum(): void {
        $this->login();
        $data = (object) ['forum' => null];

        $this->validator->validate($data, new NotForumBanned(['forumPath' => 'forum']));

        $this->assertNoViolation();
    }

    private function login(): User {
        $user = EntityFactory::makeUser();

        $this->authentication
            ->method('getUser')
            ->willReturn($user);

        return $user;
    }
}
