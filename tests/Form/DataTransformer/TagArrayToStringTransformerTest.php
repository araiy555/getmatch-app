<?php

namespace App\Tests\Form\DataTransformer;

use App\Form\DataTransformer\TagArrayToStringTransformer;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Form\DataTransformer\TagArrayToStringTransformer
 */
class TagArrayToStringTransformerTest extends TestCase {
    /**
     * @var TagArrayToStringTransformer
     */
    private $transformer;

    protected function setUp(): void {
        $this->transformer = new TagArrayToStringTransformer();
    }

    public function testTransformsArrayToSortedCommaSeparatedString(): void {
        $this->assertSame('bar, foo', $this->transformer->transform(['foo', 'bar']));
    }

    public function testTransformsEmptyArrayToEmptyString(): void {
        $this->assertSame('', $this->transformer->transform([]));
    }

    public function testTransformsNullToEmptyString(): void {
        $this->assertSame('', $this->transformer->transform(null));
    }

    public function testTransformThrowsOnNonArrayNonNullValue(): void {
        $this->expectException(\TypeError::class);

        /** @noinspection PhpParamsInspection */
        $this->transformer->transform("not null not array");
    }

    /**
     * @dataProvider provideTagStrings
     */
    public function testReverseTransformsStringToArray(string $tagString): void {
        $this->assertSame(['bar', 'foo'], $this->transformer->reverseTransform($tagString));
    }

    public function provideTagStrings(): \Generator {
        yield ['bar, foo'];
        yield ['bar,  foo'];
        yield [' bar ,,,,  foo ,,'];
    }

    /**
     * @dataProvider provideEmptyTagStrings
     */
    public function testReverseTransformsEmptyStringToEmptyArray(string $tagString): void {
        $this->assertSame([], $this->transformer->reverseTransform($tagString));
    }

    public function provideEmptyTagStrings(): \Generator {
        yield [''];
        yield [','];
        yield [' , '];
        yield [' ,,, , ,, ,'];
    }

    public function testReverseTransformsNullToEmptyArray(): void {
        $this->assertSame([], $this->transformer->reverseTransform(null));
    }

    public function testReverseTransformThrowsOnNonStringNonNullValue(): void {
        $this->expectException(\TypeError::class);

        /** @noinspection PhpParamsInspection */
        $this->transformer->reverseTransform([]);
    }
}
