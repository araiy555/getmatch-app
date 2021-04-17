<?php

namespace App\Twig;

use App\Entity\Theme;
use App\Repository\SiteRepository;
use App\Utils\UrlRewriter;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Twig extension which makes certain parameters available as template
 * functions.
 */
final class AppExtension extends AbstractExtension {
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var SiteRepository
     */
    private $siteRepository;

    /**
     * @var UrlRewriter
     */
    private $urlRewriter;

    /**
     * @var string|null
     */
    private $appBranch;

    /**
     * @var string|null
     */
    private $appVersion;

    /**
     * @var array
     */
    private $fontsConfig;

    private $themesConfig;

    /**
     * @var string
     */
    private $uploadRoot;

    public function __construct(
        RequestStack $requestStack,
        SiteRepository $siteRepository,
        UrlRewriter $urlRewriter,
        ?string $appBranch,
        ?string $appVersion,
        array $fontsConfig,
        array $themesConfig,
        string $uploadRoot
    ) {
        $this->requestStack = $requestStack;
        $this->siteRepository = $siteRepository;
        $this->urlRewriter = $urlRewriter;
        $this->appBranch = $appBranch;
        $this->appVersion = $appVersion;
        $this->fontsConfig = $fontsConfig;
        $this->themesConfig = $themesConfig;
        $this->uploadRoot = $uploadRoot;
    }

    public function getFunctions(): array {
        return [
            new TwigFunction('site_name', [$this, 'getSiteName']),
            new Twigfunction('site_theme', [$this, 'getSiteTheme']),
            new TwigFunction('registration_open', [$this, 'isRegistrationOpen']),
            new TwigFunction('app_branch', [$this, 'getAppBranch']),
            new TwigFunction('app_version', [$this, 'getAppVersion']),
            new TwigFunction('font_list', [$this, 'getFontList']),
            new TwigFunction('font_names', [$this, 'getFontNames']),
            new TwigFunction('font_entrypoint', [$this, 'getFontEntrypoint']),
            new TwigFunction('theme_list', [$this, 'getThemeList']),
            new TwigFunction('theme_entrypoint', [$this, 'getThemeEntrypoint']),
            new TwigFunction('upload_url', [$this, 'getUploadUrl']),
        ];
    }

    public function getFilters(): array {
        return [
            new TwigFilter('rewrite_url', [$this->urlRewriter, 'rewrite']),
        ];
    }

    public function getSiteName(): string {
        return $this->siteRepository->getCurrentSiteName();
    }

    public function getSiteTheme(): ?Theme {
        return $this->siteRepository->findCurrentSite()->getDefaultTheme();
    }

    public function isRegistrationOpen(): bool {
        return $this->siteRepository->findCurrentSite()->isRegistrationOpen();
    }

    public function getAppBranch(): ?string {
        return $this->appBranch;
    }

    public function getAppVersion(): ?string {
        return $this->appVersion;
    }

    public function getFontList(): array {
        return array_keys($this->fontsConfig);
    }

    public function getFontNames(string $font): array {
        $key = strtolower($font);

        return $this->fontsConfig[$key]['alias'] ?? [$font];
    }

    public function getFontEntrypoint(string $font): ?string {
        $font = strtolower($font);

        return $this->fontsConfig[$font]['entrypoint'] ?? null;
    }

    public function getThemeList(): array {
        return array_keys($this->themesConfig);
    }

    public function getThemeEntrypoint(string $name): string {
        if ($name === '_default') {
            $name = $this->themesConfig['_default'];
        }

        $config = $this->themesConfig[strtolower($name)]['entrypoint'];

        if (\is_array($config)) {
            throw new \RuntimeException('object entrypoints are no longer supported');
        }

        return $config;
    }

    public function getUploadUrl(string $path): string {
        $path = rtrim($this->uploadRoot, '/').'/'.$path;
        $request = $this->requestStack->getCurrentRequest();

        if ($request && strpos($path, '//') === false) {
            $path = sprintf('%s%s%s',
                $request->getSchemeAndHttpHost(),
                $request->getBasePath(),
                $path
            );
        }

        return $path;
    }
}
