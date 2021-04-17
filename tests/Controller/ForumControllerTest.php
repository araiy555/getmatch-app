<?php

namespace App\Tests\Controller;

use App\Entity\ForumBan;
use App\Entity\ForumLogEntry;
use App\Tests\WebTestCase;
use Symfony\Bridge\PhpUnit\ClockMock;

/**
 * @covers \App\Controller\ForumController
 */
class ForumControllerTest extends WebTestCase {
    public function testCanCreateForum(): void {
        $client = self::createUserClient();
        $crawler = $client->request('GET', '/create_forum');

        $client->submit($crawler->selectButton('Create forum')->form([
            'forum[name]' => 'dogs',
            'forum[title]' => 'dogs & puppies',
            'forum[description]' => 'the doggo forum',
            'forum[sidebar]' => 'rules: post pups',
        ]));

        self::assertResponseRedirects('/f/dogs');

        $crawler = $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main h1', '/f/dogs');
        self::assertSelectorTextContains('.forum-title', 'dogs & puppies');
        self::assertSelectorTextContains('.forum-sidebar-content p', 'rules: post pups');
        $this->assertSame('the doggo forum', $crawler->filter('meta[name="description"]')->attr('content'));
    }

    public function testCanEditForum(): void {
        $client = self::createAdminClient();
        $crawler = $client->request('GET', '/f/cats/edit');

        $client->submit($crawler->selectButton('Save changes')->form([
            'forum[name]' => 'kittens',
        ]));

        self::assertResponseRedirects('/f/kittens/edit');
        $client->followRedirect();

        self::assertResponseIsSuccessful();
    }

    public function testCanDeleteForum(): void {
        $client = self::createAdminClient();
        $crawler = $client->request('GET', '/f/cats/delete');
        $client->submit($crawler->selectButton('Delete forum')->form([
            'confirm_deletion[name]' => 'cats',
            'confirm_deletion[confirm]' => true,
        ]));

        self::assertResponseRedirects('/');

        $client->request('GET', '/f/cats');
        self::assertResponseStatusCodeSame(404);
    }

    public function testCanSubscribeAndUnsubscribeToForumFromForumView(): void {
        $client = self::createAdminClient();
        $crawler = $client->request('GET', '/f/news');

        $client->submit($crawler->selectButton('Subscribe')->form());
        self::assertResponseRedirects('http://localhost/f/news');

        $crawler = $client->followRedirect();
        self::assertSelectorExists('.subscribe-button--unsubscribe');

        $client->submit($crawler->selectButton('Unsubscribe')->form());
        self::assertResponseRedirects('http://localhost/f/news');

        $client->followRedirect();
        self::assertSelectorExists('.subscribe-button--subscribe');
    }

    public function testCanSubscribeAndUnsubscribeToForumFromForumList(): void {
        $client = self::createAdminClient();
        $crawler = $client->request('GET', '/forums');

        $client->submit($crawler->filter('.subscribe-button--subscribe')->form());
        self::assertResponseRedirects('http://localhost/forums');

        $crawler = $client->followRedirect();

        $this->assertCount(2, $crawler->filter('.subscribe-button--unsubscribe'));
    }

    /**
     * @group time-sensitive
     */
    public function testCanBanUser(): void {
        ClockMock::register(ForumBan::class);

        $client = self::createUserClient();
        $crawler = $client->request('GET', '/f/news/ban/zach');

        $client->submit($crawler->selectButton('Ban')->form([
            'forum_ban[reason]' => 'troll',
            'forum_ban[expires][date]' => '3017-07-07',
            'forum_ban[expires][time]' => '12:00',
        ]));

        self::assertResponseRedirects('/f/news/bans');
        $client->followRedirect();

        $nowFormatted = \IntlDateFormatter::create('en',
            \IntlDateFormatter::SHORT,
            \IntlDateFormatter::NONE,
            date_default_timezone_get()
        )->format(time());

        self::assertSelectorTextContains('main tbody tr td:nth-child(1)', 'zach');
        self::assertSelectorTextContains('main tbody tr td:nth-child(2)', 'troll');
        self::assertSelectorTextContains('main tbody tr td:nth-child(3)', $nowFormatted);
        self::assertSelectorTextContains('main tbody tr td:nth-child(4)', '7/7/17, 12:00 PM');
    }

    public function testCanAddModerator(): void {
        $client = self::createAdminClient();
        $crawler = $client->request('GET', '/f/cats/add_moderator');

        $client->submit($crawler->selectButton('Add as moderator')->form([
            'moderator[user]' => 'third',
        ]));

        self::assertResponseRedirects('/f/cats/moderators');

        $client->followRedirect();
        self::assertSelectorTextContains('main tbody tr:nth-child(3) td:first-child', 'third');
    }

    public function testCannotAddExistingModerator(): void {
        $client = self::createAdminClient();
        $crawler = $client->request('GET', '/f/cats/add_moderator');

        $client->submit($crawler->selectButton('Add as moderator')->form([
            'moderator[user]' => 'zach',
        ]));

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.form-error-list');
    }

    public function testCanRemoveModerator(): void {
        $client = self::createAdminClient();
        $crawler = $client->request('GET', '/f/cats/moderators');

        $client->submit($crawler->filter('main tbody tr:nth-child(2)')->selectButton('Remove')->form());

        self::assertResponseRedirects('/f/cats/moderators');

        $client->followRedirect();
        self::assertSelectorNotExists('main tbody tr:nth-child(2)');
    }

    public function testMultiForumView(): void {
        $client = self::createClient();
        $crawler = $client->request('GET', '/f/cats+news');

        self::assertResponseIsSuccessful();
        $this->assertCount(3, $crawler->filter('.submission'));
    }

    public function testMultiForumViewAcceptsSomeMissingForums(): void {
        $client = self::createClient();
        $crawler = $client->request('GET', '/f/cats+nope');

        self::assertResponseIsSuccessful();
        $this->assertCount(2, $crawler->filter('.submission'));
    }

    public function testMultiForumView404sOnNoForums(): void {
        $client = self::createClient();
        $client->request('GET', '/f/nope+nah');

        self::assertResponseStatusCodeSame(404);
    }

    public function testCommentListing(): void {
        $client = self::createClient();
        $crawler = $client->request('GET', '/f/cats/comments');

        $this->assertCount(1, $crawler->filter('.comment'));
        self::assertSelectorTextContains('.comment__body p', 'YET ANOTHER BORING COMMENT.');
    }

    public function testListAll(): void {
        $client = self::createClient();
        $crawler = $client->request('GET', '/forums/all');

        $this->assertCount(2, $crawler->filter('.body .columns h2'));
        $this->assertCount(2, $crawler->filter('.body .columns a'));
    }

    /**
     * @group time-sensitive
     * @see https://gitlab.com/postmill/Postmill/-/issues/61
     */
    public function testTitleOfSoftDeletedSubmissionIsVisibleInForumLog(): void {
        ClockMock::register(ForumLogEntry::class);

        $client = self::createAdminClient();

        $crawler = $client->request('GET', '/f/cats/3/-/comment/3/delete');
        $client->submit($crawler->selectButton('Delete')->form([
            'delete_reason[reason]' => 'aaa',
        ]));

        sleep(2);

        $crawler = $client->request('GET', '/f/cats/3/-/mod_delete');
        $client->submit($crawler->selectButton('Delete')->form([
            'delete_reason[reason]' => 'foob',
        ]));

        $client->request('GET', '/f/cats/moderation_log');

        self::assertSelectorTextContains(
            '.moderation-log__entry:nth-child(1) p',
            'emma moderator deleted submission "Submission with a body" by zach. Reason: "foob"'
        );

        self::assertSelectorTextContains(
            '.moderation-log__entry:nth-child(2) p',
            'emma moderator deleted comment by zach in "Submission with a body". Reason: "aaa"'
        );
    }
}
