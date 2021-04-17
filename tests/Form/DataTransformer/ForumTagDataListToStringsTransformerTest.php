<?php

namespace App\Tests\Form\DataTransformer;

use App\DataObject\ForumTagData;
use App\Form\DataTransformer\ForumTagDataListToStringsTransformer;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Form\DataTransformer\ForumTagDataListToStringsTransformer
 */
class ForumTagDataListToStringsTransformerTest extends TestCase {
    /**
     * @var ForumTagDataListToStringsTransformer
     */
    private $transformer;

    protected function setUp(): void {
        $this->transformer = new ForumTagDataListToStringsTransformer();
    }

    public function testTransformingArray(): void {
        $transformed = $this->transformer->transform($this->getTagData());

        $this->assertEqualsCanonicalizing(['bar', 'foo'], $transformed);
    }

    public function testTransformingTraversable(): void {
        $transformed = $this->transformer->transform(
            new \ArrayIterator($this->getTagData())
        );

        $this->assertEqualsCanonicalizing(['bar', 'foo'], $transformed);
    }

    public function testTransformingNull(): void {
        $this->assertSame([], $this->transformer->transform(null));
    }

    public function testReverseTransformingArray(): void {
        $transformed = $this->transformer->reverseTransform(['foo', 'bar']);

        $this->assertEqualsCanonicalizing($this->getTagData(), $transformed);
    }

    public function testReverseTransformingTraversable(): void {
        $transformed = $this->transformer->reverseTransform(new \ArrayIterator(['foo', 'bar']));

        $this->assertEqualsCanonicalizing($this->getTagData(), $transformed);
    }

    public function testReverseTransformingNull(): void {
        $this->assertSame([], $this->transformer->reverseTransform(null));
    }

    public function testTransformThrowsOnBadValueType(): void {
        $this->expectException(\TypeError::class);

        /** @noinspection PhpParamsInspection */
        $this->transformer->transform("not iterable or null");
    }

    public function testReverseTransformThrowsBadValueType(): void {
        $this->expectException(\TypeError::class);

        /** @noinspection PhpParamsInspection */
        $this->transformer->reverseTransform("not iterable or null");
    }

    private function getTagData(): array {
        $tagData1 = new ForumTagData();
        $tagData1->setName('foo');

        $tagData2 = new ForumTagData();
        $tagData2->setName('bar');

        return [$tagData1, $tagData2];
    }
}
