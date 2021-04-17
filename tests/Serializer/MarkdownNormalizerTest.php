<?php

namespace App\Tests\Serializer;

use App\Markdown\MarkdownConverter;
use App\Serializer\Contracts\NormalizeMarkdownInterface;
use App\Serializer\MarkdownNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @covers \App\Serializer\MarkdownNormalizer
 */
class MarkdownNormalizerTest extends TestCase {
    public function testSupportMethodReturnsTrueForCorrectDataTypes(): void {
        /** @var MarkdownConverter|\PHPUnit\Framework\MockObject\MockObject $converter */
        $converter = $this->createMock(MarkdownConverter::class);
        $normalizer = new MarkdownNormalizer($converter);
        $data = $this->createMock(NormalizeMarkdownInterface::class);

        $this->assertTrue($normalizer->supportsNormalization($data));
    }

    public function testSupportMethodReturnsFalseToAvoidRecursion(): void {
        /** @var MarkdownConverter|\PHPUnit\Framework\MockObject\MockObject $converter */
        $converter = $this->createMock(MarkdownConverter::class);
        $normalizer = new MarkdownNormalizer($converter);
        $data = $this->createMock(NormalizeMarkdownInterface::class);

        $this->assertFalse($normalizer->supportsNormalization($data, null, [
            MarkdownNormalizer::NORMALIZED_MARKER => [
                spl_object_id($data) => true,
            ],
        ]));
    }

    public function testCanNormalizeMarkdownFields(): void {
        $entity = $this->createMock(NormalizeMarkdownInterface::class);
        $entity
            ->expects($this->once())
            ->method('getMarkdownFields')
            ->willReturn([
                'header',
                'body' => 'foo',
                'footer',
                'nonexistent',
            ]);

        /** @var MarkdownConverter|\PHPUnit\Framework\MockObject\MockObject $converter */
        $converter = $this->createMock(MarkdownConverter::class);
        $converter
            ->expects($this->exactly(2))
            ->method('convertToHtml')
            ->withConsecutive(
                [$this->equalTo('The header')],
                [$this->equalTo('The body')]
            )
            ->willReturnOnConsecutiveCalls(
                'rendered header',
                'rendered body'
            );

        /** @var NormalizerInterface|\PHPUnit\Framework\MockObject\MockObject $decoratedNormalizer */
        $decoratedNormalizer = $this->createMock(NormalizerInterface::class);
        $decoratedNormalizer
            ->expects($this->once())
            ->method('normalize')
            ->with(
                $this->equalTo($entity),
                $this->isNull(),
                $this->equalTo([MarkdownNormalizer::NORMALIZED_MARKER => [spl_object_id($entity) => true]])
            )
            ->willReturn([
                'header' => 'The header',
                'body' => 'The body',
                'footer' => null,
            ]);

        $normalizer = new MarkdownNormalizer($converter);
        $normalizer->setNormalizer($decoratedNormalizer);

        $this->assertEquals([
            'header' => 'The header',
            'renderedHeader' => 'rendered header',
            'body' => 'The body',
            'foo' => 'rendered body',
            'footer' => null,
            'renderedFooter' => null,
        ], $normalizer->normalize($entity));
    }
}
