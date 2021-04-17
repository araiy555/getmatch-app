<?php

namespace App\Tests\Markdown\Functional;

use App\Markdown\MarkdownConverter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @coversNothing
 */
class MarkdownConverterTest extends KernelTestCase {
    /**
     * @var MarkdownConverter
     */
    private $converter;

    protected function setUp(): void {
        self::bootKernel();
        $this->converter = self::$container->get(MarkdownConverter::class);
    }

    /**
     * @dataProvider provideMarkdownAndHtmlEquivalents
     */
    public function testConversion(string $expectedHtml, string $markdown): void {
        $renderedHtml = $this->converter->convertToHtml($markdown);

        $expected = new \DOMDocument();
        $expected->loadHTML(trim($expectedHtml));

        $rendered = new \DOMDocument();
        $rendered->loadHTML(trim($renderedHtml));

        $this->assertXmlStringEqualsXmlString($expected->saveHTML(), $rendered->saveHTML());
    }

    public function provideMarkdownAndHtmlEquivalents(): \Generator {
        yield 'html is escaped' => [
            <<<EOHTML
            &lt;p&gt;some markdown&lt;/p&gt;
            EOHTML,
            <<<EOMARKDOWN
            <p>some markdown</p>
            EOMARKDOWN,
        ];

        yield 'special link syntax' => [
            <<<EOHTML
            <p lang="en" dir="ltr"><a href="/tag/bar">c/bar</a></p>
            <p lang="en" dir="ltr"><a href="/f/foo">/f/foo</a></p>
            <p lang="en" dir="ltr"><a href="/user/emma">/u/emma</a></p>
            <p lang="en" dir="ltr"><a href="/wiki/some_wiki_page">/w/some_wiki_page</a></p>
            EOHTML,
            <<<EOMARKDOWN
            c/bar

            /f/foo

            /u/emma

            /w/some_wiki_page
            EOMARKDOWN,
        ];

        yield 'internal and external links' => [
            <<<EOHTML
            <p lang="en" dir="ltr"><a href="http://www.example.com" rel="nofollow noopener noreferrer">some link</a></p>
            <p lang="en" dir="ltr"><a href="/some/path">some path</a></p>
            EOHTML,
            <<<EOMARKDOWN
            [some link](http://www.example.com)

            [some path](/some/path)
            EOMARKDOWN,
        ];

        yield 'javascript links are stripped away' => [
            <<<EOHTML
            <p lang="en" dir="ltr"><a>no href</a></p>
            EOHTML,
            <<<EOMARKDOWN
            [no href](javascript:alert('a'))
            EOMARKDOWN,
        ];

        yield 'auto-linking' => [
            <<<EOHTML
            <p lang="en" dir="ltr"><a href="http://www.example.com" rel="nofollow noopener noreferrer">http://www.example.com</a>
            EOHTML,
            <<<EOMARKDOWN
            http://www.example.com
            EOMARKDOWN,
        ];

        yield 'syntax highlighting' => [
            <<<EOHTML
            <pre><code data-controller="syntax-highlight" data-syntax-highlight-language-value="php">&lt;?php
            echo &quot;some code&quot;;
            </code></pre>
            EOHTML,
            <<<EOMARKDOWN
            ~~~php
            <?php
            echo "some code";
            ~~~
            EOMARKDOWN,
        ];
    }
}
