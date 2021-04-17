<?php

namespace App\Tests\Serializer;

use App\Serializer\CursorNormalizer;
use PagerWave\Cursor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @covers \App\Serializer\CursorNormalizer
 */
class CursorNormalizerTest extends TestCase {
    public function testNormalizesCursor(): void {
        /** @var NormalizerInterface|\PHPUnit\Framework\MockObject\MockObject $decorated */
        $decorated = $this->createMock(NormalizerInterface::class);
        $decorated
            ->expects($this->once())
            ->method('normalize')
            ->with(['foo', 'bar'], 'json', ['some' => 'context'])
            ->willReturn(['foo', 'bar']);

        $cursor = new Cursor(['foo', 'bar'], ['id' => 4], 'http://example.com/?next[id]=4');

        $normalizer = new CursorNormalizer();
        $normalizer->setNormalizer($decorated);

        $this->assertEquals([
            'entries' => ['foo', 'bar'],
            'nextPage' => 'http://example.com/?next[id]=4',
        ], $normalizer->normalize($cursor, 'json', ['some' => 'context']));
    }

    public function testSupportCursor(): void {
        $normalizer = new CursorNormalizer();

        $this->assertTrue($normalizer->supportsNormalization(new Cursor([1, 2, 3], [], '')));
    }
}
