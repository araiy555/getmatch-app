<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCase;

/**
 * @covers \App\Controller\SiteController
 */
class SiteControllerTest extends WebTestCase {
    public function testHealthCheck(): void {
        $client = self::createClient();
        $client->request('GET', '/site/health_check');

        self::assertResponseIsSuccessful();
        $this->assertSame('It works!', $client->getResponse()->getContent());
    }

    public function testCanChangeSiteName(): void {
        $this->submitSiteSettings([
            'site_settings[siteName]' => 'Crap Site',
        ]);

        $client = self::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertSame('Crap Site', $crawler->filter('title')->text());
    }

    public function testCanDisableWiki(): void {
        $this->submitSiteSettings([
            'site_settings[wikiEnabled]' => false,
        ]);

        $client = self::createUserClient();

        $client->request('GET', '/wiki');
        $this->assertTrue($client->getResponse()->isNotFound());

        $crawler = $client->request('GET', '/');
        $this->assertCount(0, $crawler->filter('a[href$="/wiki"]'));
    }

    public function testCanRestrictCreationOfNewForumsToAdmins(): void {
        $this->submitSiteSettings([
            'site_settings[forumCreateRole]' => 'ROLE_ADMIN',
        ]);

        $client = self::createUserClient();

        $crawler = $client->request('GET', '/forums');
        $this->assertCount(0, $crawler->filter('a[href$="/create_forum"]'));

        $client->request('GET', '/create_forum');
        $this->assertTrue($client->getResponse()->isForbidden());
    }

    public function testCanRestrictImageUploadsToAdmins(): void {
        $this->submitSiteSettings([
            'site_settings[imageUploadRole]' => 'ROLE_ADMIN',
        ]);

        $client = self::createUserClient();
        $crawler = $client->request('GET', '/submit');

        $this->assertCount(0, $crawler->filter('input[type="file"]'));
    }

    public function testCanRestrictWikiEditingToAdmins(): void {
        $this->submitSiteSettings([
            'site_settings[wikiEditRole]' => 'ROLE_ADMIN',
        ]);

        $client = self::createUserClient();
        $client->request('GET', '/wiki/_edit/index');

        $this->assertTrue($client->getResponse()->isForbidden());
    }

    private function submitSiteSettings(array $settings): void {
        $client = self::createAdminClient();
        $crawler = $client->request('GET', '/site/settings');

        $form = $crawler->selectButton('Save')->form($settings);

        $client->submit($form);

        self::assertResponseRedirects();

        self::ensureKernelShutdown();
    }
}
