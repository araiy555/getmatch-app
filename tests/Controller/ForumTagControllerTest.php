<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @covers \App\Controller\ForumTagController
 */
class ForumTagControllerTest extends WebTestCase {
    public function testListAsRegularUser(): void {
        $client = self::createUserClient();
        $crawler = $client->request('GET', '/tags');

        self::assertResponseStatusCodeSame(200);
        $this->assertEqualsCanonicalizing(
            ['humans', 'pets'],
            $crawler->filter('.body tr td:first-child')->each(function (Crawler $node) {
                return $node->text();
            })
        );

        $this->assertCount(0, $crawler->filter('.body tr')
            ->filterXPath("//a[normalize-space(text()) = 'Edit']"));
    }

    public function testListAsAdmin(): void {
        $client = self::createAdminClient();
        $crawler = $client->request('GET', '/tags');

        self::assertResponseStatusCodeSame(200);

        $this->assertCount(2, $crawler->filter('.body tr')
            ->filterXPath("//a[normalize-space(text()) = 'Edit']"));
    }

    public function testViewTag(): void {
        $client = self::createUserClient();
        $crawler = $client->request('GET', '/tag/pets');

        self::assertResponseStatusCodeSame(200);

        $forums = array_unique($crawler->filter('.submission__forum')
            ->each(function (Crawler $node) {
                return $node->text();
            })
        );

        $this->assertEqualsCanonicalizing(['cats', 'news'], $forums);
    }

    public function testUpdateTag(): void {
        $client = self::createAdminClient();

        $client->request('GET', '/tag/pets/edit');
        self::assertResponseStatusCodeSame(200);

        $client->submitForm('Save', [
            'forum_tag[name]' => 'animals',
            'forum_tag[description]' => 'The description about the tag about animals',
        ]);
        self::assertResponseRedirects('/tag/animals');

        $client->followRedirect();
        self::assertResponseStatusCodeSame(200);
        self::assertSelectorTextContains('.page-heading', 'animals');
        self::assertSelectorTextContains('.sidebar', 'The description about the tag about animals');
    }
}
