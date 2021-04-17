<?php

namespace App\Tests\Utils;

use App\Utils\CanonicalRedirector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @covers \App\Utils\CanonicalRedirector
 */
class CanonicalRedirectorTest extends TestCase {
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $urlGenerator;

    /**
     * @var CanonicalRedirector
     */
    private $redirector;

    protected function setUp(): void {
        $this->requestStack = new RequestStack();
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->redirector = new CanonicalRedirector(
            $this->requestStack,
            $this->urlGenerator
        );
    }

    /**
     * @dataProvider provideCanonicalizableHttpMethods
     */
    public function testCanonicalization(string $method): void {
        $this->createRequest($method);

        $this->assertCanonicalization();
    }

    public function provideCanonicalizableHttpMethods(): \Generator {
        yield ['GET'];
        yield ['HEAD'];
    }

    public function testCanonicalizationPreservesQueryString(): void {
        $request = $this->createRequest();
        $request->server->set('QUERY_STRING', 'foo=bar');

        $this->assertCanonicalization('/generated?foo=bar');
    }

    public function testDoesNotCanonicalizeWhenParamIsCanonical(): void {
        $this->createRequest('GET', 'canon');

        $this->assertNoCanonicalization();
    }

    public function testDoesNotCanonicalizeWithEmptyRequestStack(): void {
        $this->assertNoCanonicalization();
    }

    public function testDoesNotCanonicalizeOnSubRequest(): void {
        $this->createRequest();
        $this->createRequest();

        $this->assertNoCanonicalization();
    }

    /**
     * @dataProvider provideNonCacheableMethods
     */
    public function testDoesNotCanonicalizeWithNonCacheableHttpMethods(string $method): void {
        $this->createRequest($method);

        $this->assertNoCanonicalization();
    }

    public function provideNonCacheableMethods(): \Generator {
        yield ['DELETE'];
        yield ['PATCH'];
        yield ['POST'];
        yield ['PUT'];
    }

    /**
     * @param string|mixed $paramValue
     */
    private function createRequest(string $method = 'GET', $paramValue = 'non-canon'): Request {
        $request = Request::create('/', $method);
        $request->attributes->replace([
            '_route' => 'route',
            '_route_params' => ['param' => $paramValue],
        ]);

        $this->requestStack->push($request);

        return $request;
    }

    private function assertNoCanonicalization(): void {
        $this->urlGenerator
            ->expects($this->never())
            ->method('generate');

        $this->redirector->canonicalize('canon', 'param');
    }

    private function assertCanonicalization(string $expectedUrl = '/generated'): void {
        $this->urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with('route', ['param' => 'canon'])
            ->willReturn('/generated');

        try {
            $this->redirector->canonicalize('canon', 'param');
            $this->fail("Expected canonicalization didn't happen");
        } catch (HttpException $e) {
            $this->assertSame(302, $e->getStatusCode());
            $this->assertArrayHasKey('Location', $e->getHeaders());
            $this->assertSame($expectedUrl, $e->getHeaders()['Location']);
        }
    }
}
