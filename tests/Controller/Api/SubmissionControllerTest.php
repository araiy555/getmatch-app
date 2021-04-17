<?php

namespace App\Tests\Controller\Api;

use App\Tests\WebTestCase;

/**
 * @covers \App\Controller\Api\SubmissionController
 */
class SubmissionControllerTest extends WebTestCase {
    public function testListSubmissions(): void {
        $client = self::createUserClient();
        $client->request('GET', '/api/submissions');

        self::assertResponseStatusCodeSame(200);

        $this->assertSame([3, 2, 1], array_column(
            json_decode($client->getResponse()->getContent(), true)['entries'],
            'id'
        ));
    }

    public function testCannotListSubmissionsWithInvalidSortMode(): void {
        $client = self::createUserClient();
        $client->request('GET', '/api/submissions?sortBy=poo');

        self::assertResponseStatusCodeSame(400);
    }

    public function testGetSubmission(): void {
        $client = self::createUserClient();
        $client->request('GET', '/api/submissions/3');

        self::assertResponseStatusCodeSame(200);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(3, $data['id']);
        $this->assertSame('Submission with a body', $data['title']);
        $this->assertNull($data['url']);
        $this->assertSame("I'm bad at making stuff up.", $data['body']);
        $this->assertSame('url', $data['mediaType']);
        $this->assertSame(1, $data['commentCount']);
        $this->assertSame('2017-04-28T10:00:00+00:00', $data['timestamp']);
        $this->assertSame('2017-05-03T01:00:00+00:00', $data['lastActive']);
        $this->assertSame('visible', $data['visibility']);
        $this->assertEquals(['id' => 1, 'name' => 'cats'], $data['forum']);
        $this->assertEquals(['id' => 2, 'username' => 'zach'], $data['user']);
        $this->assertSame(1, $data['netScore']);
        $this->assertSame(1, $data['upvotes']);
        $this->assertSame(0, $data['downvotes']);
        $this->assertNull($data['image']);
        $this->assertFalse($data['sticky']);
        $this->assertNull($data['editedAt']);
        $this->assertFalse($data['moderated']);
        $this->assertSame('none', $data['userFlag']);
        $this->assertFalse($data['locked']);
        $this->assertSame('submission-with-a-body', $data['slug']);
        $this->assertStringContainsString(
            "I'm bad at making stuff up.",
            $data['renderedBody'],
        );
        $this->assertNull($data['thumbnail_1x']);
        $this->assertNull($data['thumbnail_2x']);
    }

    public function testPostSubmission(): void {
        $client = self::createUserClient();

        $client->request('POST', '/api/submissions', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'A submission posted via the API',
            'body' => 'very cool',
            'forum' => 2,
        ]));

        self::assertResponseStatusCodeSame(201);

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsInt($data['id']);
        $this->assertSame('A submission posted via the API', $data['title']);
        $this->assertSame('very cool', $data['body']);
        $this->assertStringContainsString('very cool', $data['renderedBody']);
        $this->assertSame(2, $data['forum']['id']);
        $this->assertSame('news', $data['forum']['name']);
    }

    public function testUpdateSubmission(): void {
        $client = self::createUserClient();

        $client->request('PUT', '/api/submissions/3', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'url' => 'http://www.example.com/',
            'title' => 'updated title',
            'body' => 'updated body',
        ]));

        self::assertResponseStatusCodeSame(204);

        $client->request('GET', '/api/submissions/3');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('http://www.example.com/', $data['url']);
        $this->assertSame('updated title', $data['title']);
        $this->assertSame('updated body', $data['body']);
    }

    public function testSoftDeleteOwnSubmission(): void {
        $client = self::createUserClient();
        $client->request('DELETE', '/api/submissions/3');

        self::assertResponseStatusCodeSame(204);

        $client->request('GET', '/api/submissions/3');

        self::assertResponseStatusCodeSame(200);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(3, $data['id']);
        $this->assertSame('', $data['title']);
        $this->assertNull($data['body']);
        $this->assertSame('soft_deleted', $data['visibility']);
    }

    public function testCannotDeleteSubmissionOfOtherUser(): void {
        $client = self::createUserClient();
        $client->request('DELETE', '/api/submissions/2');

        self::assertResponseStatusCodeSame(403);
    }

    public function testCanReadSubmissionComments(): void {
        $client = self::createUserClient();
        $client->request('GET', '/api/submissions/1/comments');

        self::assertResponseStatusCodeSame(200);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $data);

        $comment = $data[0];
        $this->assertSame(1, $comment['id']);
        $this->assertSame("This is a comment body. It is quite neat.\n\n*markdown*", $comment['body']);
        $this->assertSame('2017-05-01T12:00:00+00:00', $comment['timestamp']);
        $this->assertEquals(['id' => 1, 'username' => 'emma'], $comment['user']);
        $this->assertEquals([
            'id' => 1,
            'forum' => [
                'id' => 2,
                'name' => 'news',
            ],
            'user' => [
                'id' => 1,
                'username' => 'emma',
            ],
            'slug' => 'a-submission-with-a-url-and-body',
        ], $comment['submission']);
        $this->assertNull($comment['parentId']);
        $this->assertSame(1, $comment['replyCount']);
        $this->assertSame('visible', $comment['visibility']);
        $this->assertNull($comment['editedAt']);
        $this->assertFalse($comment['moderated']);
        $this->assertSame('none', $comment['userFlag']);
        $this->assertSame(1, $comment['netScore']);
        $this->assertSame(1, $comment['upvotes']);
        $this->assertSame(0, $comment['downvotes']);
        $this->assertStringContainsString(
            'This is a comment body. It is quite neat.',
            $comment['renderedBody'],
        );
        $this->assertArrayHasKey('replies', $comment);

        $reply = $comment['replies'][0];
        $this->assertSame(2, $reply['id']);
        $this->assertSame('This is a reply to the previous comment.', $reply['body']);
        $this->assertSame('2017-05-02T14:00:00+00:00', $reply['timestamp']);
        $this->assertEquals([
            'id' => 2,
            'username' => 'zach',
        ], $reply['user']);
        $this->assertEquals([
            'id' => 1,
            'forum' => [
                'id' => 2,
                'name' => 'news',
            ],
            'user' => [
                'id' => 1,
                'username' => 'emma',
            ],
            'slug' => 'a-submission-with-a-url-and-body',
        ], $reply['submission']);
        $this->assertSame(1, $reply['parentId']);
        $this->assertSame([], $reply['replies']);
        $this->assertSame(0, $reply['replyCount']);
        $this->assertSame('visible', $reply['visibility']);
        $this->assertNull($reply['editedAt']);
        $this->assertFalse($reply['moderated']);
        $this->assertSame('none', $reply['userFlag']);
        $this->assertSame(1, $reply['netScore']);
        $this->assertSame(1, $reply['upvotes']);
        $this->assertSame(0, $reply['downvotes']);
        $this->assertStringContainsString(
            'This is a reply to the previous comment.',
            $reply['renderedBody'],
        );
    }
}
