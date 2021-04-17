<?php

namespace App\Tests\Twig;

use App\Entity\BundledTheme;
use App\Entity\Site;
use App\Entity\Theme;
use App\Repository\SiteRepository;
use App\Twig\AppExtension;
use App\Utils\UrlRewriter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @covers \App\Twig\AppExtension
 */
class AppExtensionTest extends TestCase {
    /**
     * @var AppExtension
     */
    private $extension;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var SiteRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $siteRepository;

    protected function setUp(): void {
        $this->requestStack = new RequestStack();
        $this->siteRepository = $this->createMock(SiteRepository::class);

        /** @var UrlRewriter|\PHPUnit\Framework\MockObject\MockObject $urlRewriter */
        $urlRewriter = $this->createMock(UrlRewriter::class);

        $this->extension = new AppExtension(
            $this->requestStack,
            $this->siteRepository,
            $urlRewriter,
            '6.9',
            'v4.2.0',
            [
                'default' => [
                    'alias' => ['Roboto', 'sans-serif'],
                    'entrypoint' => 'fonts/roboto',
                ],
                'roboto' => ['entrypoint' => 'fonts/roboto'],
                'ubuntu' => ['entrypoint' => 'fonts/ubuntu'],
            ],
            [
                '_default' => 'postmill',
                'postmill' => [
                    'name' => 'Postmill',
                    'entrypoint' => 'themes/postmill',
                ],
                'postmill-classic' => [
                    'name' => 'Postmill Classic',
                    'entrypoint' => 'themes/postmill-classic',
                ],
            ],
            '/root'
        );
    }

    public function testGetSiteName(): void {
        $this->siteRepository
            ->expects($this->once())
            ->method('getCurrentSiteName')
            ->willReturn('Postmill');

        $this->assertSame('Postmill', $this->extension->getSiteName());
    }

    /**
     * @dataProvider provideBooleanStates
     */
    public function testRegistrationOpen(bool $registrationOpen): void {
        $site = new Site();
        $site->setRegistrationOpen($registrationOpen);

        $this->siteRepository
            ->expects($this->once())
            ->method('findCurrentSite')
            ->willReturn($site);

        $this->assertSame($registrationOpen, $this->extension->isRegistrationOpen());
    }

    public function testGetAppBranch(): void {
        $this->assertSame('6.9', $this->extension->getAppBranch());
    }

    public function testGetAppVersion(): void {
        $this->assertSame('v4.2.0', $this->extension->getAppVersion());
    }

    public function testGetNamesForNonAliasedFont(): void {
        $this->assertSame(
            ['Comic Sans MS'],
            $this->extension->getFontNames('Comic Sans MS')
        );
    }

    public function testGetNamesForAliasFont(): void {
        $this->assertSame(
            ['Roboto', 'sans-serif'],
            $this->extension->getFontNames('default')
        );
    }

    public function testGetFontList(): void {
        $this->assertSame(
            ['default', 'roboto', 'ubuntu'],
            $this->extension->getFontList()
        );
    }

    public function testGetFontEntrypoint(): void {
        $this->assertSame(
            'fonts/roboto',
            $this->extension->getFontEntrypoint('Roboto')
        );
    }

    public function testGetThemeList(): void {
        $this->assertSame(
            ['_default', 'postmill', 'postmill-classic'],
            $this->extension->getThemeList()
        );
    }

    public function testGetThemeEntrypoint(): void {
        $this->assertSame(
            'themes/postmill',
            $this->extension->getThemeEntrypoint('postmill')
        );
    }

    public function testGetSiteTheme(): void {
        $theme = new BundledTheme('the-name', 'the-key');
        $site = new Site();
        $site->setDefaultTheme($theme);

        $this->siteRepository
            ->method('findCurrentSite')
            ->willReturn($site);

        $this->assertSame($theme, $this->extension->getSiteTheme());
    }

    public function testGetUploadUrlInRequestContext(): void {
        $this->requestStack->push(Request::create('http://localhost/'));

        $this->assertSame(
            'http://localhost/root/submission_images/foo.jpg',
            $this->extension->getUploadUrl('submission_images/foo.jpg')
        );
    }

    public function testGetUploadOutsideRequestContext(): void {
        $this->assertSame(
            '/root/submission_images/foo.jpg',
            $this->extension->getUploadUrl('submission_images/foo.jpg')
        );
    }

    public function provideBooleanStates(): iterable {
        yield [true];
        yield [false];
    }
}
