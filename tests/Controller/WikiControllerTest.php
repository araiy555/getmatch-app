<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCase;

/**
 * @covers \App\Controller\WikiController
 */
class WikiControllerTest extends WebTestCase {
    public function testWikiFrontPage(): void {
        $client = self::createClient();
        $client->request('GET', '/wiki');

        self::assertSelectorTextContains('.wiki-article__title', 'This is the title');
        self::assertSelectorTextContains('.wiki-article__body', 'and this is the body');
    }

    public function testCanCreateNewPage(): void {
        $client = self::createUserClient();
        $crawler = $client->request('GET', '/wiki/_create/foo');

        $client->submit($crawler->selectButton('Save')->form([
            'wiki[title]' => 'New page title',
            'wiki[body]' => 'New page body',
        ]));

        $client->request('GET', '/wiki/foo');

        self::assertSelectorTextContains('.wiki-article__title', 'New page title');
        self::assertSelectorTextContains('.wiki-article__body', 'New page body');
    }

    public function testCanEditWiki(): void {
        $client = self::createUserClient();
        $crawler = $client->request('GET', '/wiki/_edit/index');

        $client->submit($crawler->selectButton('Save')->form([
            'wiki[title]' => 'New title',
            'wiki[body]' => 'New body',
        ]));

        $client->request('GET', '/wiki');

        self::assertSelectorTextContains('.wiki-article__title', 'New title');
        self::assertSelectorTextContains('.wiki-article__body', 'New body');
    }

    public function testCanDeleteWikiPage(): void {
        $client = self::createAdminClient();

        $crawler = $client->request('GET', '/wiki');
        $client->submit($crawler->selectButton('Delete')->form());
        $client->request('GET', '/wiki');

        self::assertResponseStatusCodeSame(404);
    }

    public function testCanRestrictEditingOfWikiPagesToAdmins(): void {
        $client = self::createAdminClient();

        $crawler = $client->request('GET', '/wiki');
        $client->submit($crawler->selectButton('Lock')->form());
        self::ensureKernelShutdown();

        $client = self::createUserClient();
        $client->request('GET', '/wiki/_edit/index');

        self::assertResponseStatusCodeSame(403);
    }
}
