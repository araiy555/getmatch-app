<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCase;

/**
 * @covers \App\Controller\VoteController
 */
class VoteControllerTest extends WebTestCase {
    public function testInitialScoreIsOne(): void {
        self::createClient()->request('GET', '/f/cats/3');
        self::assertSelectorTextContains('.vote__net-score', '1');
    }

    public function testRetractUpvote(): void {
        $client = self::createUserClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/f/cats/3');

        $client->submit($crawler->filter('.vote__up')->form());

        self::assertResponseStatusCodeSame(200);
        self::assertSelectorTextContains('.vote__net-score', '0');
    }

    /**
     * @see https://gitlab.com/postmill/Postmill/-/issues/57
     */
    public function testCanSwitchVoteChoice(): void {
        $client = self::createUserClient();
        $crawler = $client->request('GET', '/f/cats/3');

        $client->submit($crawler->filter('.vote__down')->form());

        self::assertResponseRedirects();
    }

    public function testJsonVote(): void {
        $client = self::createUserClient();

        $crawler = $client->request('GET', '/f/cats/3');

        $data = $crawler->filter('.vote__up')->form()->getValues();

        $client->request('POST', '/sv/3.json', $data);

        self::assertResponseStatusCodeSame(200);
        $this->assertJson($client->getResponse()->getContent());
        $this->assertEquals([
            'netScore' => 0,
        ], json_decode($client->getResponse()->getContent(), true));

        $client->request('GET', '/f/cats/3');

        self::assertSelectorTextContains('.vote__net-score', '0');
    }
}
