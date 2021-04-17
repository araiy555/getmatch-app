<?php

namespace App\Tests\Controller;

use App\Entity\UserBlock;
use App\Repository\UserRepository;
use App\Tests\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @covers \App\Controller\FrontController
 */
class FrontControllerTest extends WebTestCase {
    public function testShowsCorrectNumberOfSubmissionsOnFrontPage(): void {
        $client = self::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertCount(2, $crawler->filter('.submission'));
    }

    public function testShowsCorrectNumberOfSubmissionsWhenBlockingUser(): void {
        $client = self::createAdminClient();

        $users = self::$container->get(UserRepository::class);
        $blocker = $users->loadUserByUsername('emma');
        $blocked = $users->loadUserByUsername('zach');

        $em = self::$container->get(EntityManagerInterface::class);
        $em->persist(new UserBlock($blocker, $blocked, null));
        $em->flush();

        $crawler = $client->request('GET', '/');

        $this->assertCount(1, $crawler->filter('.submission'));
    }

    public function testTrashListsAllTrashedPostsInForumsUserModerates(): void {
        $client = self::createUserClient();
        $crawler = $client->request('GET', '/trash');

        self::assertResponseStatusCodeSame(200);
        $this->assertCount(1, $crawler->filter('.submission'));
        $this->assertCount(1, $crawler->filter('.comment'));
    }

    public function testTrashListsNothingForUsersWhoModerateNoForums(): void {
        $client = self::createClientWithAuthenticatedUser('third');
        $crawler = $client->request('GET', '/trash');

        self::assertResponseStatusCodeSame(200);
        $this->assertCount(0, $crawler->filter('.submission'));
        $this->assertCount(0, $crawler->filter('.comment'));
    }
}
