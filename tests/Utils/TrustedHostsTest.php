<?php

namespace App\Tests\Utils;

use App\Utils\TrustedHosts;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Utils\TrustedHosts
 */
class TrustedHostsTest extends TestCase {
    /**
     * @param string[]|string $hosts
     * @dataProvider provideHosts
    */
    public function testCanGetFragments(array $expected, $hosts, bool $noAnchors): void {
        $this->assertEquals($expected, TrustedHosts::makeRegexFragments($hosts, $noAnchors));
    }

    public function provideHosts(): \Generator {
        yield [
            ['^.*\.example\.com$', '^www\.example\.org$'],
            '*.example.com,www.example.org',
            false,
        ];

        yield [
            ['.*\.example\.com', 'www\.example\.org'],
            '*.example.com,www.example.org',
            true,
        ];

        yield [
            ['^.*\.example\.com$', '^www\.example\.org$'],
            ['*.example.com', 'www.example.org'],
            false,
        ];

        yield [
            ['.*\.example\.com', 'www\.example\.org'],
            ['*.example.com', 'www.example.org'],
            true,
        ];
    }
}
