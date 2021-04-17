<?php

namespace App\Form\Type;

use App\Entity\BundledTheme;
use App\Entity\Theme;
use App\Repository\SiteRepository;
use App\Repository\ThemeRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ThemeSelectorType extends AbstractType {
    /**
     * @var SiteRepository
     */
    private $siteRepository;

    /**
     * @var ThemeRepository
     */
    private $themeRepository;

    /**
     * @var array
     */
    private $themesConfig;

    public function __construct(
        SiteRepository $siteRepository,
        ThemeRepository $themeRepository,
        array $themesConfig
    ) {
        $this->siteRepository = $siteRepository;
        $this->themeRepository = $themeRepository;
        $this->themesConfig = $themesConfig;
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $defaultTheme = $this->siteRepository->findCurrentSite()->getDefaultTheme();
        $defaultKey = $this->themesConfig['_default'];

        $resolver->setDefaults([
            'choice_loader' => new CallbackChoiceLoader(function (): array {
                return $this->themeRepository->findAll();
            }),
            'choice_label' => static function (Theme $theme, $key, $value) use ($defaultTheme, $defaultKey) {
                $name = $theme->getName();

                if (
                    ($defaultTheme && $theme === $defaultTheme) ||
                    (!$defaultTheme && $theme instanceof BundledTheme && $theme->getConfigKey() === $defaultKey)
                ) {
                    $name .= '*';
                }

                return $name;
            },
            'choice_value' => static function (?Theme $theme): ?string {
                return $theme ? $theme->getId()->toString() : null;
            },
            'choice_translation_domain' => false,
            'help' => 'help.theme_selector',
            'placeholder' => 'placeholder.default',
        ]);
    }

    public function getParent(): string {
        return ChoiceType::class;
    }
}
