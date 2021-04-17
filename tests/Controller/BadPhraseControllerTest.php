<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @covers \App\Controller\BadPhraseController
 */
class BadPhraseControllerTest extends WebTestCase {
    public function testSearch(): void {
        $client = self::createAdminClient();

        $crawler = $client->request('GET', '/site/bad_phrases');
        self::assertResponseIsSuccessful();

        $crawler = $client->submit($crawler->filter('.body')->selectButton('Search')->form([
            'bad_phrase_search[query]' => 'orson pig',
        ]));
        self::assertResponseIsSuccessful();
        $this->assertCount(2, $crawler->filter('.body tbody tr'));
        $this->assertSame(['orson pig', 'o...n p.g'], $crawler
            ->filter('.body tbody tr td:nth-child(2)')
            ->each(static function (Crawler $crawler): string {
                return $crawler->text();
            }
        ));
    }
}
