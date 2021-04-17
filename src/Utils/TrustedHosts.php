<?php

namespace App\Utils;

/**
 * Utility for dealing with the mess that is the TRUSTED_HOSTS env variable.
 *
 * A trusted host string looks like this: `*.example.com,example.org`
 */
class TrustedHosts {
    /**
     * @var string[]
     */
    private $trustedHosts;

    public function __construct(array $trustedHosts) {
        // FIXME: $trustedHosts shouldn't contain null/empty values
        $this->trustedHosts = array_filter($trustedHosts);
    }

    /**
     * @return string[]
     */
    public function getRegexFragments(bool $noAnchors = false): array {
        return self::makeRegexFragments($this->trustedHosts, $noAnchors);
    }

    /**
     * @param string|string[] $hosts
     *
     * @return string[]
     */
    public static function makeRegexFragments($hosts, bool $noAnchors = false): array {
        if (\is_string($hosts)) {
            $hosts = explode(',', $hosts);
        }

        if (!\is_array($hosts)) {
            throw new \InvalidArgumentException(sprintf(
                '$hosts must be string or array of strings, %s given',
                get_debug_type($hosts),
            ));
        }

        return array_map(function (string $host) use ($noAnchors) {
            $quoted = str_replace('\*', '.*', preg_quote($host));

            if ($noAnchors) {
                return $quoted;
            }

            return "^{$quoted}$";
        }, $hosts);
    }
}
