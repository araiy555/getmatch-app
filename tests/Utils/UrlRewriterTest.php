<?php

namespace App\Tests\Utils;

use App\Utils\TrustedHosts;
use App\Utils\UrlRewriter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RequestContext;

/**
 * @covers \App\Utils\UrlRewriter
 */
class UrlRewriterTest extends TestCase {
    /**
     * @var UrlRewriter
     */
    private $rewriter;

    protected function setUp(): void {
        $context = new RequestContext('', 'GET', 'localhost', 'https');

        $this->rewriter = new UrlRewriter($context, new TrustedHosts([
            'localhost',
            '10.0.0.13',
        ]));
    }

    /**
     * @dataProvider provideUrls
     */
    public function testCanRewriteUrls(string $expected, string $url): void {
        $this->assertSame($expected, $this->rewriter->rewrite($url), "expected $expected, url: $url");
    }

    public function provideUrls(): iterable {
        yield ['https://localhost', 'http://10.0.0.13'];
        yield ['https://localhost/', 'http://10.0.0.13/'];
        yield ['https://foo@localhost/', 'https://foo@10.0.0.13/'];
        yield ['https://localhost/', 'https://localhost/'];
        yield ['https://foo@localhost/all-cats-are-beautiful/123', 'https://foo@10.0.0.13/all-cats-are-beautiful/123'];
        yield ['https://foo@localhost/path?query=string#hash', 'https://foo@10.0.0.13/path?query=string#hash'];
        yield ['http://foo@10.0.0.13:443', 'http://foo@10.0.0.13:443'];
        yield ['http://www.localhost', 'http://www.localhost'];
        yield ['http://localhostfoo', 'http://localhostfoo'];
        yield ['http://10.0.0.13./', 'http://10.0.0.13./'];
        yield ['http://localhost:8080', 'http://localhost:8080'];
    }
}
