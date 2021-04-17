<?php

namespace App\Tests\Controller\Api;

use App\Tests\WebTestCase;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;

/**
 * @covers \App\Controller\Api\ForumController
 */
class ForumControllerTest extends WebTestCase {
    use ArraySubsetAsserts;

    public function testRead(): void {
        $client = self::createUserClient();
        $client->request('GET', '/api/forums/1');

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArraySubset([
            'id' => 1,
            'name' => 'cats',
            'title' => 'Cat Memes',
            'sidebar' => 'le memes',
            'description' => 'memes',
            'featured' => true,
            'suggestedTheme' => null,
        ], $data);

        $this->assertStringContainsString('le memes', $data['renderedSidebar']);
    }

    public function testReadByName(): void {
        $client = self::createUserClient();
        $client->request('GET', '/api/forums/by_name/cats');

        $this->assertArraySubset([
            'id' => 1,
            'name' => 'cats',
        ], json_decode($client->getResponse()->getContent(), true));
    }

    public function testCreate(): void {
        $client = self::createAdminClient();
        $client->request('POST', '/api/forums', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'dogs',
            'title' => 'Dog Memes',
            'sidebar' => 'Doggy Memes',
            'description' => 'Puppy memes',
        ]));

        $this->assertResponseStatusCodeSame(201);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertGreaterThanOrEqual(3, $data['id']);
        $this->assertArraySubset([
            'name' => 'dogs',
            'title' => 'Dog Memes',
            'sidebar' => 'Doggy Memes',
            'description' => 'Puppy memes',
            'featured' => false,
            'suggestedTheme' => null,
        ], $data);
        $this->assertStringContainsString(
            'Doggy Memes',
            $data['renderedSidebar'],
        );
    }

    public function testUpdate(): void {
        $client = self::createAdminClient();
        $client->request('PUT', '/api/forums/1', [], [], [
            'HTTP_CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'sheep',
            'title' => 'sheep memes',
            'sidebar' => 'sheep memes sidebar',
            'description' => 'sheep memes description',
        ]));

        $this->assertResponseStatusCodeSame(204);
        $this->assertSame('', $client->getResponse()->getContent());

        $client->request('GET', '/api/forums/1');

        $this->assertArraySubset([
            'name' => 'sheep',
            'title' => 'sheep memes',
            'sidebar' => 'sheep memes sidebar',
            'description' => 'sheep memes description',
        ], json_decode($client->getResponse()->getContent(), true));
    }
}
