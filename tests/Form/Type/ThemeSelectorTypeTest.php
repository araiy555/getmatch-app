<?php

namespace App\Tests\Form\Type;

use App\Entity\BundledTheme;
use App\Entity\Site;
use App\Form\Type\ThemeSelectorType;
use App\Repository\SiteRepository;
use App\Repository\ThemeRepository;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * @covers \App\Form\Type\ThemeSelectorType
 */
class ThemeSelectorTypeTest extends TypeTestCase {
    /**
     * @var Site
     */
    private $site;

    /**
     * @var SiteRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $siteRepository;

    /**
     * @var ThemeRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $themeRepository;

    /**
     * @var string[][]
     */
    private $themesConfig;

    protected function setUp(): void {
        $this->site = new Site();

        $this->siteRepository = $this->createMock(SiteRepository::class);
        $this->siteRepository
            ->method('findCurrentSite')
            ->willReturn($this->site);

        $this->themeRepository = $this->createMock(ThemeRepository::class);
        $this->themeRepository
            ->method('findAll')
            ->willReturn([
                new BundledTheme('Postmill', 'postmill'),
                new BundledTheme('Postmill Classic', 'postmill-classic'),
            ]);

        $this->themesConfig = [
            '_default' => 'postmill',
            'postmill' => [
                'name' => 'Postmill',
                'entrypoint' => 'themes/postmill',
            ],
            'postmill-classic' => [
                'name' => 'Postmill Classic',
                'entrypoint' => 'themes/postmill-classic',
            ],
        ];

        parent::setUp();
    }

    public function testHasDefaultIndicatorWithoutSiteSetting(): void {
        $form = $this->factory->create(ThemeSelectorType::class);
        $view = $form->createView();

        $this->assertCount(2, $view->vars['choices']);
        $this->assertSame('Postmill*', $view->vars['choices'][0]->label);
        $this->assertSame('Postmill Classic', $view->vars['choices'][1]->label);
    }

    public function testHasDefaultIndicatorWithSiteSetting(): void {
        $this->site->setDefaultTheme($this->themeRepository->findAll()[1]);

        $form = $this->factory->create(ThemeSelectorType::class);
        $view = $form->createView();

        $this->assertCount(2, $view->vars['choices']);
        $this->assertSame('Postmill', $view->vars['choices'][0]->label);
        $this->assertSame('Postmill Classic*', $view->vars['choices'][1]->label);
    }

    protected function getExtensions(): array {
        return [
            new PreloadedExtension([
                new ThemeSelectorType(
                    $this->siteRepository,
                    $this->themeRepository,
                    $this->themesConfig
                ),
            ], []),
        ];
    }
}
