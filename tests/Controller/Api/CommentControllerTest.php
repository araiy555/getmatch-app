<?php

namespace App\Tests\Controller\Api;

use App\Tests\WebTestCase;

/**
 * @covers \App\Controller\Api\CommentController
 */
class CommentControllerTest extends WebTestCase {
    public function testCanListComments(): void {
        $client = self::createUserClient();
        $client->request('GET', '/api/comments');

        self::assertResponseStatusCodeSame(200);

        $this->assertSame([3, 2, 1], array_column(
            json_decode($client->getResponse()->getContent(), true)['entries'],
            'id'
        ));
    }

    public function testCanReadComment(): void {
        $client = self::createUserClient();
        $client->request('GET', '/api/comments/3');

        self::assertResponseStatusCodeSame(200);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(3, $data['id']);
        $this->assertSame('YET ANOTHER BORING COMMENT.', $data['body']);
        $this->assertSame('2017-05-03T01:00:00+00:00', $data['timestamp']);
        $this->assertEquals(['id' => 2, 'username' => 'zach'], $data['user']);
        $this->assertEquals([
            'id' => 3,
            'forum' => ['id' => 1, 'name' => 'cats'],
            'user' => ['id' => 2, 'username' => 'zach'],
            'slug' => 'submission-with-a-body',
        ], $data['submission']);
        $this->assertNull($data['parentId']);
        $this->assertSame(0, $data['replyCount']);
        $this->assertSame('visible', $data['visibility']);
        $this->assertNull($data['editedAt']);
        $this->assertFalse($data['moderated']);
        $this->assertSame('none', $data['userFlag']);
        $this->assertSame(1, $data['netScore']);
        $this->assertSame(1, $data['upvotes']);
        $this->assertSame(0, $data['downvotes']);
        $this->assertStringContainsString(
            'YET ANOTHER BORING COMMENT',
            $data['renderedBody'],
        );
    }

    public function testCanUpdateComment(): void {
        $client = self::createUserClient();
        $client->request('PUT', '/api/comments/3', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'body' => 'this is the new comment body',
        ]));

        self::assertResponseStatusCodeSame(204);

        $client->request('GET', '/api/comments/3');

        $this->assertSame(
            'this is the new comment body',
            json_decode($client->getResponse()->getContent(), true)['body']
        );
    }

    public function testCannotUpdateCommentOfOtherUser(): void {
        $client = self::createUserClient();
        $client->request('PUT', '/api/comments/1', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'body' => 'shouldn\'t be stored',
        ]));

        self::assertResponseStatusCodeSame(403);
    }
}
