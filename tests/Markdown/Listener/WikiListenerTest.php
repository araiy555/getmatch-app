<?php

namespace App\Tests\Markdown\Listener;

use App\Markdown\Event\BuildCacheContext;
use App\Markdown\Event\ConfigureCommonMark;
use App\Markdown\Event\ConvertMarkdown;
use App\Markdown\Listener\WikiListener;
use App\Utils\SluggerInterface;
use League\CommonMark\Block\Element\Heading;
use League\CommonMark\Block\Element\ListItem;
use League\CommonMark\Block\Element\Paragraph;
use League\CommonMark\DocParser;
use League\CommonMark\Environment;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalink;
use League\CommonMark\Extension\TableOfContents\Node\TableOfContents;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @covers \App\Markdown\Listener\WikiListener
 */
class WikiListenerTest extends TestCase {
    /**
     * @var SluggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $slugger;

    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $translator;

    /**
     * @var WikiListener
     */
    private $listener;

    protected function setUp(): void {
        $this->slugger = $this->createMock(SluggerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->listener = new WikiListener($this->slugger, $this->translator);
    }

    public function testAddsToCacheContextWhenActive(): void {
        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('nav.permalink')
            ->willReturn('Permalink');

        $convertEvent = new ConvertMarkdown('some markdown');
        $convertEvent->addAttribute(WikiListener::ATTR_ENABLE_WIKI_MODE, true);

        $buildCacheEvent = new BuildCacheContext($convertEvent);

        $this->listener->onBuildCacheContext($buildCacheEvent);

        $this->assertTrue($buildCacheEvent->hasContext(
            WikiListener::CACHE_ATTR_PERMALINK_LABEL,
            'Permalink',
        ));
    }

    public function testDoesNotAddToCacheContextWhenDormant(): void {
        $this->translator
            ->expects($this->never())
            ->method('trans');

        $convertEvent = new ConvertMarkdown('some markdown');
        $buildCacheEvent = new BuildCacheContext($convertEvent);

        $this->listener->onBuildCacheContext($buildCacheEvent);

        $this->assertFalse($buildCacheEvent->hasContext(
            WikiListener::CACHE_ATTR_PERMALINK_LABEL,
        ));
    }

    public function testTableOfContentsIsGeneratedWhenActive(): void {
        $convertEvent = new ConvertMarkdown(<<<EOMARKDOWN
        Introduction.

        ## First heading.

        Some text.

        ## Second heading.
        EOMARKDOWN);
        $convertEvent->addAttribute(WikiListener::ATTR_ENABLE_WIKI_MODE, true);

        $environment = Environment::createCommonMarkEnvironment();
        $configureCommonMarkEvent = new ConfigureCommonMark($environment, $convertEvent);

        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('nav.permalink')
            ->willReturn('Permalink');

        $this->listener->onConfigureCommonMark($configureCommonMarkEvent);

        $this->slugger
            ->expects($this->exactly(2))
            ->method('slugify')
            ->withConsecutive(['First heading.'], ['Second heading.'])
            ->willReturnOnConsecutiveCalls('first-heading', 'second-heading');

        $parser = new DocParser($environment);
        $nodes = $parser->parse($convertEvent->getMarkdown())->children();

        $this->assertCount(5, $nodes);
        $this->assertInstanceOf(Paragraph::class, $nodes[0]);
        $this->assertInstanceOf(TableOfContents::class, $nodes[1]);
        $this->assertCount(2, $nodes[1]->children());
        $this->assertContainsOnlyInstancesOf(ListItem::class, $nodes[1]->children());
        $this->assertCount(2, $nodes[2]->children());
        $this->assertInstanceOf(HeadingPermalink::class, $nodes[2]->children()[1]);
        $this->assertSame('first-heading', $nodes[2]->children()[1]->getSlug());
    }

    public function testTableOfContentsIsNotGeneratedWhileDormant(): void {
        $convertEvent = new ConvertMarkdown(<<<EOMARKDOWN
        Introduction.

        ## First heading.

        Some text.

        ## Second heading.
        EOMARKDOWN);

        $environment = Environment::createCommonMarkEnvironment();
        $configureCommonMarkEvent = new ConfigureCommonMark($environment, $convertEvent);

        $this->translator
            ->expects($this->never())
            ->method('trans');

        $this->listener->onConfigureCommonMark($configureCommonMarkEvent);

        $this->slugger
            ->expects($this->never())
            ->method('slugify');

        $parser = new DocParser($environment);
        $nodes = $parser->parse($convertEvent->getMarkdown())->children();

        $this->assertCount(4, $nodes);
        $this->assertInstanceOf(Paragraph::class, $nodes[0]);
        $this->assertInstanceOf(Heading::class, $nodes[1]);
        $this->assertCount(1, $nodes[1]->children());
    }
}
