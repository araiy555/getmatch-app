<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCase;
use App\Utils\UrlMetadataFetcherInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \App\Controller\:Controller
 */
class AjaxControllerTest extends WebTestCase {
    public function testFetchTitle(): void {
        $client = self::createUserClient();

        $urlMetadataFetcher = $this->createMock(UrlMetadataFetcherInterface::class);
        $urlMetadataFetcher
            ->expects($this->once())
            ->method('fetchTitle')
            ->with('http://www.example.com/')
            ->willReturn('Example dot com');

        self::$container->set(UrlMetadataFetcherInterface::class, $urlMetadataFetcher);

        $client->request('POST', '/ft.json', [
            'url' => 'http://www.example.com/',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json; charset=UTF-8');
    }

    public function testFetchTitleReturnsBadRequestOnMalformedUrl(): void {
        $client = self::createUserClient();
        $client->request('POST', 'ft.json', [
            'url' => 'htttp:/www.goglle.come',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testFetchTitleReturnsBadRequestOnMissingUrl(): void {
        $client = self::createUserClient();
        $client->request('POST', 'ft.json');

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testFetchTitleReturnsNotFoundOnMissingTitle(): void {
        $client = self::createUserClient();

        $urlMetadataFetcher = $this->createMock(UrlMetadataFetcherInterface::class);
        $urlMetadataFetcher
            ->expects($this->once())
            ->method('fetchTitle')
            ->with('http://www.example.com/')
            ->willReturn(null);

        self::$container->set(UrlMetadataFetcherInterface::class, $urlMetadataFetcher);

        $client->request('POST', '/ft.json', [
            'url' => 'http://www.example.com/',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testPopperLoggedOut(): void {
        $client = self::createClient();
        $client->request('GET', '/_up/emma');

        self::assertResponseIsSuccessful();
    }

    public function testPopperForOtherUserHasCorrectNumberOfButtons(): void {
        self::createUserClient()->request('GET', '/_up/emma');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[href="/user/emma"]');
        self::assertSelectorExists('[href="/user/emma/block_user"]');
        self::assertSelectorExists('[href="/user/emma/compose_message"]');
    }

    public function testPopperForSelfHasOnlyProfileButton(): void {
        self::createUserClient()->request('GET', '/_up/zach');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[href="/user/zach"]');
        self::assertSelectorNotExists('[href="/user/zach/block_user"]');
        self::assertSelectorNotExists('[href="/user/zach/compose_message"]');
    }

    public function testPopperForBlockedUserHasWorkingUnblockButton(): void {
        $client = self::createUserClient();
        $crawler = $client->request('GET', '/user/emma/block_user');
        $client->submit($crawler->selectButton('Block')->form());
        self::assertResponseRedirects('/user/zach/block_list');

        $crawler = $client->request('GET', '/_up/emma');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[action="/user/emma/unblock_user"]');
        self::assertSelectorNotExists('[href="/user/emma/block_user"]');

        $client->submit($crawler->selectButton('Unblock')->form());
        self::assertResponseRedirects('http://localhost/_up/emma');

        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[href="/user/emma/block_user"]');
        self::assertSelectorNotExists('[action="/user/emma/unblock_user"]');
    }
}
