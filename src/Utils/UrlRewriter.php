<?php

namespace App\Utils;

use Symfony\Component\Routing\RequestContext;

/**
 * Rewrite URLs matching the list of trusted hosts to have the current host and
 * scheme.
 *
 * URLs with ports are currently left alone, as they are assumed to belong to
 * other services.
 */
class UrlRewriter {
    private const REGEX_TEMPLATE = '!^'.
        '(?<scheme>https?)://'.
        '(?<credentials>[^/]*@)?'.
        '(?:%s)'.
        '(?::(?<port>:\d{1,5}))?'.
        '(?<relative_url>/.*)?'.
    '$!iu';

    /**
     * @var RequestContext
     */
    private $requestContext;

    /**
     * @var string
     */
    private $regex;

    public function __construct(
        RequestContext $requestContext,
        TrustedHosts $trustedHosts
    ) {
        $fragments = $trustedHosts->getRegexFragments(true);

        if (!$fragments) {
            $host = $requestContext->getHost();
            $fragments = TrustedHosts::makeRegexFragments($host);
        }

        $this->requestContext = $requestContext;
        $this->regex = sprintf(
            self::REGEX_TEMPLATE,
            implode('|', $fragments)
        );
    }

    public function rewrite(string $url): string {
        if ($this->regex && preg_match($this->regex, $url, $matches) && empty($matches['port'])) {
            return sprintf('%s://%s%s%s',
                $this->requestContext->getScheme(),
                $matches['credentials'] ?? '',
                $this->requestContext->getHost(),
                $matches['relative_url'] ?? ''
            );
        }

        return $url;
    }
}
