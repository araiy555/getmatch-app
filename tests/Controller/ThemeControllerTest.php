<?php

namespace App\Tests\Controller;

use App\Entity\BundledTheme;
use App\Entity\CssThemeRevision;
use App\Entity\Theme;
use App\Repository\CssThemeRevisionRepository;
use App\Tests\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @covers \App\Controller\ThemeController
 */
class ThemeControllerTest extends WebTestCase {
    public function testCanListThemes(): void {
        $client = self::createAdminClient();
        $crawler = $client->request('GET', '/site/themes');

        self::assertResponseStatusCodeSame(200);

        $data = $crawler->filter('.body tbody tr')
            ->each(static function (Crawler $node): array {
                return [
                    $node->filter('td:nth-child(1)')->text(),
                    $node->filter('td:nth-child(2)')->text(),
                ];
            });

        $this->assertSame([
            ['My Custom Theme', 'CSS'],
            ['Postmill (default)', 'Bundled'],
            ['Postmill Classic', 'Bundled'],
        ], $data);
    }

    public function testCanRenderCss(): void {
        $client = self::createClient();

        /** @var CssThemeRevision $revision */
        $revision = self::$container
            ->get(CssThemeRevisionRepository::class)
            ->findOneBy([]);
        /** @var Theme $theme */
        $theme = $revision->getTheme();

        $client->request('GET', '/site/themes/css/'.$theme->getId().'/revision/'.$revision->getId().'.css');

        self::assertResponseStatusCodeSame(200);
        $this->assertTrue($client->getResponse()->isImmutable());
        $this->assertSame(':root { --bg-page: #0aa }', $client->getResponse()->getContent());
    }

    public function testCanCreateCssTheme(): void {
        $client = self::createAdminClient();
        $client->request('GET', '/site/themes/css/create');

        $client->submitForm('Save', [
            'css_theme[name]' => 'The new theme',
            'css_theme[css]' => '.class{}',
        ]);

        self::assertResponseRedirects('/site/themes');

        /** @var CssThemeRevision $revision */
        $revision = self::$container
            ->get(CssThemeRevisionRepository::class)
            ->findOneBy([], ['timestamp' => 'DESC']);

        $this->assertSame('.class{}', $revision->getCss());
    }

    public function testCanEditCssTheme(): void {
        $client = self::createAdminClient();
        $client->request('GET', '/site/themes');

        $client->clickLink('Edit');
        self::assertResponseIsSuccessful();

        $client->submitForm('Save', [
            'css_theme[css]' => '.newCss{}',
        ]);

        self::assertResponseRedirects('/site/themes');

        /** @var CssThemeRevision $revision */
        $revision = self::$container
            ->get(CssThemeRevisionRepository::class)
            ->findOneBy([], ['timestamp' => 'DESC']);

        $this->assertSame('.newCss{}', $revision->getCss());
    }

    public function testCanDeleteTheme(): void {
        $client = self::createAdminClient();
        $crawler = $client->request('GET', '/site/themes');
        $this->assertCount(3, $crawler->filter('.body tbody tr'));

        $client->submitForm('Delete');
        $crawler = $client->followRedirect();
        $this->assertCount(2, $crawler->filter('.body tbody tr'));
    }

    public function testCanSyncThemes(): void {
        $client = self::createAdminClient();

        /** @var EntityManagerInterface $em */
        $em = self::$container->get(EntityManagerInterface::class);
        $em->persist(new BundledTheme('To be removed', 'to-be-removed'));
        $em->remove($em->getRepository(BundledTheme::class)->findOneByName('Postmill'));
        $em->flush();

        $crawler = $client->request('GET', '/site/themes');
        $list = $crawler
            ->filter('.body ul li ins, .body ul li del')
            ->each(static function (Crawler $crawler): string {
                $type = ['ins' => '+', 'del' => '-'][$crawler->nodeName()] ?? 'UNKNOWN';

                return sprintf('%s %s', $type, $crawler->text());
            });

        $this->assertSame(['+ Postmill', '- To be removed'], $list);

        $client->submitForm('Sync themes');
        self::assertResponseRedirects('/site/themes');

        $crawler = $client->followRedirect();
        $rowCrawler = $crawler->filter('.body table tbody tr');
        $this->assertEmpty($rowCrawler->filterXPath("//td[normalize-space(text()) = 'To be removed']"));
        $this->assertCount(1, $rowCrawler->filterXPath("//td[normalize-space(text()) = 'Postmill']"));
    }
}
