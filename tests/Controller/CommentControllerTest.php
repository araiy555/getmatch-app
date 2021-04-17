<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCase;

/**
 * @covers \App\Controller\CommentController
 */
class CommentControllerTest extends WebTestCase {
    public function testCommentListing(): void {
        $client = self::createClient();
        $crawler = $client->request('GET', '/comments');

        $this->assertStringContainsString(
            'YET ANOTHER BORING COMMENT.',
            $crawler->filter('.comment__body')->eq(0)->html(),
        );

        $this->assertStringContainsString(
            'This is a reply to the previous comment.',
            $crawler->filter('.comment__body')->eq(1)->html(),
        );

        $this->assertStringContainsString(
            'This is a comment body. It is quite neat.',
            $crawler->filter('.comment__body')->eq(2)->html(),
        );
    }

    public function testCanPostCommentInReplyToSubmission(): void {
        $client = self::createUserClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/f/cats/3');

        $crawler = $client->submit($crawler->selectButton('Post')->form([
            'reply_to_submission_3[comment]' => 'i think that is a neat idea!',
        ]));

        $this->assertStringContainsString(
            'i think that is a neat idea!',
            $crawler->filter('.comment__body')->html(),
        );
        $this->assertCount(0, $crawler->selectLink('Parent'));
    }

    public function testCanPostCommentInReplyToComment(): void {
        $client = self::createUserClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/f/cats/3/-/comment/3');

        $crawler = $client->submit($crawler->selectButton('Post')->form([
            'reply_to_comment_3[comment]' => 'squirrel',
        ]));

        $this->assertStringContainsString(
            'squirrel',
            $crawler->filter('.comment__body')->html(),
        );
        $this->assertCount(1, $crawler->selectLink('Parent'));
    }

    public function testBadCommentSubmitRedirectsToErrorForm(): void {
        $client = self::createUserClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/f/cats/3/-/comment/3');
        $crawler = $client->submit($crawler->selectButton('Post')->form([
            'reply_to_comment_3[comment]' => ' ',
        ]));

        $this->assertTrue($client->getRequest()->isMethod('POST'));
        $this->assertSame('The comment must not be empty.', $crawler->filter('.form-error-list li')->text());
    }

    public function testCannotPostCommentContainingBannedPhrase(): void {
        $client = self::createUserClient();
        $client->request('GET', '/f/cats/3');

        $client->submitForm('Post', [
            'reply_to_submission_3[comment]' => 'olson peg',
        ]);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.form-error-list', 'The field contains a banned word or phrase.');
    }

    public function testCommentJson(): void {
        $client = self::createClient();
        $client->request('GET', '/f/cats/3/-/comment/3.json');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(3, $data['id']);
        $this->assertSame('YET ANOTHER BORING COMMENT.', $data['body']);
        $this->assertSame('2017-05-03T01:00:00+00:00', $data['timestamp']);
        $this->assertSame(2, $data['user']['id']);
        $this->assertSame('zach', $data['user']['username']);
        $this->assertSame(3, $data['submission']['id']);
        $this->assertSame(1, $data['submission']['forum']['id']);
        $this->assertSame('cats', $data['submission']['forum']['name']);
        $this->assertSame('visible', $data['visibility']);
        $this->assertNull($data['editedAt']);
        $this->assertSame('none', $data['userFlag']);
        $this->assertSame(1, $data['netScore']);
        $this->assertSame(1, $data['upvotes']);
        $this->assertSame(0, $data['downvotes']);
        $this->assertNull($data['parentId']);
        $this->assertSame(0, $data['replyCount']);
        $this->assertStringContainsString(
            'YET ANOTHER BORING COMMENT',
            $data['renderedBody'],
        );
    }

    public function testCanEditOwnComment(): void {
        $client = self::createUserClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/f/cats/3');
        $crawler = $client->click($crawler->filter('.comment')->selectLink('Edit')->link());
        $crawler = $client->submit($crawler->selectButton('Save')->form([
            'comment[comment]' => 'edited comment',
        ]));

        $this->assertStringContainsString(
            'edited comment',
            $crawler->filter('.comment__body')->html(),
        );
    }

    public function testCanHardDeleteOwnCommentWithoutReply(): void {
        $client = self::createUserClient();

        $crawler = $client->request('GET', '/f/cats/3/-/comment/3');
        $client->submit($crawler->filter('#comment_3')->selectButton('Delete')->form());

        $client->request('GET', '/f/cats/3/-/comment/3');
        $this->assertTrue($client->getResponse()->isNotFound());
    }

    public function testCanSoftDeleteOwnCommentWithReply(): void {
        $client = self::createAdminClient();

        $crawler = $client->request('GET', '/f/news/1/-/comment/1');
        $client->submit($crawler->filter('.comment')->selectButton('Delete')->form());

        $crawler = $client->request('GET', '/f/news/1/-/comment/1');
        $this->assertCount(1, $crawler->filter('.comment--visibility-soft-deleted'));
    }

    /**
     * @dataProvider selfDeleteReferrerProvider
     */
    public function testRedirectsProperlyAfterDelete(string $expected, string $referrer): void {
        $client = self::createUserClient();
        $crawler = $client->request('GET', $referrer);

        $client->submit($crawler->filter('.comment')->selectButton('Delete')->form());

        self::assertResponseRedirects($expected, null, "expected: $expected, referrer: $referrer");
    }

    /**
     * @covers \App\Controller\UserController::notifications
     */
    public function testCanReceiveCommentNotifications(): void {
        $client = self::createAdminClient();
        $crawler = $client->request('GET', '/f/cats/3/-/comment/3');

        $form = $crawler->selectButton('Post')->form([
            'reply_to_comment_3[comment]' => 'You will be notified about this comment.',
        ]);

        $client->submit($form);
        self::assertResponseRedirects();
        self::ensureKernelShutdown();

        $client = self::createUserClient();
        $client->request('GET', '/notifications');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.comment');
        self::assertSelectorTextContains('.comment__body', 'You will be notified about this comment.');
    }

    public function testCanDeleteAsModerator(): void {
        $client = self::createUserClient();
        $crawler = $client->request('GET', '/f/news/1/-/comment/1/delete');

        $client->submit($crawler->selectButton('Delete')->form([
            'delete_reason[reason]' => 'i hate this post',
        ]));

        self::assertResponseRedirects('/f/news/1/a-submission-with-a-url-and-body');

        $client->followRedirect();
        self::assertSelectorTextSame('.submission__nav > ul > li > a', '1 comment');
    }

    public function testCanRestoreDeletedComments(): void {
        $client = self::createUserClient();

        $crawler = $client->request('GET', '/f/cats/3/-/comment/4');
        self::assertSelectorExists('.comment--visibility-trashed');
        self::assertSelectorTextNotContains('.comment__body', 'trashed comment');

        $client->submit($crawler->filter('.comment')->selectButton('Restore')->form());
        self::assertResponseRedirects();

        $client->followRedirect();
        self::assertSelectorTextSame('.submission__nav > ul > li > a', '2 comments');
        self::assertSelectorNotExists('.comment--visibility-trashed');
        self::assertSelectorTextContains('.comment__body', 'trashed comment');
    }

    /**
     * @see https://gitlab.com/postmill/Postmill/-/issues/67
     */
    public function testCanDeleteCommentWithNotification(): void {
        $client = self::createAdminClient();

        $crawler = $client->request('GET', '/f/cats/3/-/comment/3');
        $client->submit($crawler->selectButton('Post')->form([
            'reply_to_comment_3[comment]' => 'I am mentioning /u/third',
        ]));

        self::assertResponseRedirects();

        $crawler = $client->request('GET', '/f/cats/3/-/comment/3/delete_thread');
        $client->submit($crawler->selectButton('Delete thread')->form([
            'delete_reason[reason]' => 'I am deleting',
        ]));

        self::assertResponseRedirects('/f/cats/3/submission-with-a-body');
    }

    public function selfDeleteReferrerProvider(): iterable {
        yield ['http://localhost/f/cats/3', '/f/cats/3'];
        yield ['http://localhost/f/cats/3/with-slug', '/f/cats/3/with-slug'];
        yield ['/f/cats/3/submission-with-a-body', '/f/cats/3/-/comment/3'];
        yield ['/f/cats/3/submission-with-a-body', '/f/cats/3/with-slug/comment/3'];
    }
}
