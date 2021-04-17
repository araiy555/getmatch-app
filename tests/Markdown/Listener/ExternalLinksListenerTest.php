<?php

namespace App\Tests\Markdown\Listener;

use App\Markdown\Event\BuildCacheContext;
use App\Markdown\Event\ConfigureCommonMark;
use App\Markdown\Event\ConvertMarkdown;
use App\Markdown\Listener\ExternalLinksListener;
use App\Security\Authentication;
use App\Tests\Fixtures\Factory\EntityFactory;
use App\Utils\TrustedHosts;
use League\CommonMark\Environment;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @covers \App\Markdown\Listener\ExternalLinksListener
 */
class ExternalLinksListenerTest extends TestCase {
    /**
     * @var ExternalLinksListener
     */
    private $listener;

    /**
     * @var RequestStack|\PHPUnit\Framework\MockObject\MockObject
     */
    private $requestStack;

    /**
     * @var Authentication|\PHPUnit\Framework\MockObject\MockObject
     */
    private $authentication;

    /**
     * @var TrustedHosts|\PHPUnit\Framework\MockObject\MockObject
     */
    private $trustedHosts;

    protected function setUp(): void {
        $this->authentication = $this->createMock(Authentication::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->trustedHosts = $this->createMock(TrustedHosts::class);

        $this->listener = new ExternalLinksListener(
            $this->authentication,
            $this->requestStack,
            $this->trustedHosts
        );
    }

    public function testTrustedHostsAddedToCacheContext(): void {
        $this->trustedHosts
            ->expects($this->once())
            ->method('getRegexFragments')
            ->willReturn(['^localhost$', '^10\.0\.0\.3$']);

        $this->requestStack
            ->expects($this->never())
            ->method('getCurrentRequest');

        $event = new BuildCacheContext(new ConvertMarkdown('some markdown'));
        $this->listener->addHostRegexContext($event);

        $this->assertTrue($event->hasContext(
            ExternalLinksListener::HOST_REGEX_CONTEXT_KEY,
            '/^10\.0\.0\.3$|^localhost$/',
        ));
    }

    public function testCurrentHostAddedToCacheContextIfNoTrustedHosts(): void {
        $this->trustedHosts
            ->expects($this->once())
            ->method('getRegexFragments')
            ->willReturn([]);

        $this->requestStack
            ->expects($this->atLeastOnce())
            ->method('getCurrentRequest')
            ->willReturnCallback(function () {
                $request = new Request();
                $request->headers->set('Host', '192.168.0.1');

                return $request;
            });

        $event = new BuildCacheContext(new ConvertMarkdown('some markdown'));
        $this->listener->addHostRegexContext($event);

        $this->assertTrue($event->hasContext(
            ExternalLinksListener::HOST_REGEX_CONTEXT_KEY,
            '/^192\.168\.0\.1$/'
        ));
    }

    public function testNoOpenLinksInNewTabForNonAuthenticatedUsers(): void {
        $this->authentication
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $environment = new Environment();
        $event = new ConfigureCommonMark($environment, new ConvertMarkdown('some markdown'));
        $this->listener->onConfigureCommonMark($event);

        $this->assertFalse($environment->getConfig('external_link/open_in_new_window'));
    }

    /**
     * @dataProvider provideOpenExternalLinksInNewTabChoices
     */
    public function testConfiguresOpenLinksInNewTabBasedOnUserSetting(bool $openInNewTab): void {
        $this->authentication
            ->expects($this->once())
            ->method('getUser')
            ->willReturnCallback(function () use ($openInNewTab) {
                $user = EntityFactory::makeUser();
                $user->setOpenExternalLinksInNewTab($openInNewTab);

                return $user;
            });

        $environment = new Environment();
        $event = new ConfigureCommonMark($environment, new ConvertMarkdown('some markdown'));
        $this->listener->onConfigureCommonMark($event);

        $this->assertSame(
            $openInNewTab,
            $environment->getConfig('external_link/open_in_new_window')
        );
    }

    /**
     * @dataProvider provideOpenExternalLinksInNewTabChoices
     */
    public function testOpenLinksInNewTabAddsToCacheContext(bool $openInNewTab): void {
        $this->authentication
            ->expects($this->once())
            ->method('getUser')
            ->willReturnCallback(function () use ($openInNewTab) {
                $user = EntityFactory::makeUser();
                $user->setOpenExternalLinksInNewTab($openInNewTab);

                return $user;
            });

        $event = new BuildCacheContext(new ConvertMarkdown('some markdown'));
        $this->listener->addOpenInNewTabContext($event);

        $this->assertSame(
            $openInNewTab,
            $event->hasContext('open_external_links_in_new_tab')
        );
    }

    public function provideOpenExternalLinksInNewTabChoices(): \Generator {
        yield [true];
        yield [false];
    }
}
