<?php

namespace App\Tests\Doctrine;

use App\Doctrine\NamingStrategy;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Doctrine\NamingStrategy
 */
class NamingStrategyTest extends TestCase {
    /**
     * @var NamingStrategy
     */
    private $namingStrategy;

    protected function setUp(): void {
        $this->namingStrategy = new NamingStrategy();
    }

    /**
     * @dataProvider nameProvider
     */
    public function testNamesAreCorrectlyTransformed(string $to, string $from): void {
        $this->assertSame($to, $this->namingStrategy->classToTableName($from));
    }

    /**
     * @noinspection ClassConstantCanBeUsedInspection
     */
    public function nameProvider(): iterable {
        yield ['comments', 'App\Entity\Comment'];
        yield ['comment_votes', 'App\Entity\CommentVote'];
        yield ['forum_bans', 'App\Entity\ForumBan'];
        yield ['forum_categories', 'App\Entity\ForumCategory'];
        yield ['forum_log_entries', 'App\Entity\ForumLogEntry'];
        yield ['forums', 'App\Entity\Forum'];
        yield ['forum_subscriptions', 'App\Entity\ForumSubscription'];
        yield ['ip_bans', 'App\Entity\IpBan'];
        yield ['messages', 'App\Entity\Message'];
        yield ['message_threads', 'App\Entity\MessageThread'];
        yield ['moderators', 'App\Entity\Moderator'];
        yield ['notifications', 'App\Entity\Notification'];
        yield ['sites', 'App\Entity\Site'];
        yield ['submissions', 'App\Entity\Submission'];
        yield ['submission_votes', 'App\Entity\SubmissionVote'];
        yield ['themes', 'App\Entity\Theme'];
        yield ['user_bans', 'App\Entity\UserBan'];
        yield ['user_blocks', 'App\Entity\UserBlock'];
        yield ['users', 'App\Entity\User'];
        yield ['wiki_pages', 'App\Entity\WikiPage'];
        yield ['wiki_revisions', 'App\Entity\WikiRevision'];
    }
}
