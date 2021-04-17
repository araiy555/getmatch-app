<?php

namespace App\Utils;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Helper for redirecting to a canonical URL.
 */
class CanonicalRedirector {
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    public function __construct(
        RequestStack $requestStack,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->requestStack = $requestStack;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Checks that the route parameter is the canonical version provided. If
     * not, and it is safe to do so, an exception triggering a redirect to the
     * canonical URL will be thrown.
     *
     * @throws HttpException if safe to redirect and parameter not canonical
     */
    public function canonicalize(string $canonical, string $param): void {
        $request = $this->requestStack->getCurrentRequest();

        if (
            !$request ||
            $request !== $this->requestStack->getMasterRequest() ||
            !$request->isMethodCacheable()
        ) {
            return;
        }

        $params = $request->attributes->get('_route_params', []);

        if (isset($params[$param]) && (string) $params[$param] !== $canonical) {
            $route = $request->attributes->get('_route');
            $params[$param] = $canonical;

            $url = $this->urlGenerator->generate($route, $params);
            $qs = $request->getQueryString();

            if ($qs !== null) {
                $url .= '?'.$qs;
            }

            throw new HttpException(302, 'Redirecting to canonical', null, [
                'Location' => $url,
            ]);
        }
    }
}
