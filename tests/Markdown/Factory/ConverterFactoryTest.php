<?php

namespace App\Tests\Markdown\Factory;

use App\Markdown\Factory\ConverterFactory;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Markdown\Factory\ConverterFactory
 */
class ConverterFactoryTest extends TestCase {
    public function testCreateConverter(): void {
        $environment = new Environment();
        $factory = new ConverterFactory();
        $converter = $factory->createConverter($environment);

        $this->assertInstanceOf(CommonMarkConverter::class, $converter);
    }
}
