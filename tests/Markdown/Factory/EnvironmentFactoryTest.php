<?php

namespace App\Tests\Markdown\Factory;

use App\Markdown\Factory\EnvironmentFactory;
use League\CommonMark\ConfigurableEnvironmentInterface;
use League\CommonMark\Environment;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @covers \App\Markdown\Factory\EnvironmentFactory
 */
class EnvironmentFactoryTest extends TestCase {
    public function testCreateEnvironment(): void {
        $environment = new Environment();

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('get')
            ->with(ConfigurableEnvironmentInterface::class)
            ->willReturn($environment);

        $factory = new EnvironmentFactory($container);

        $this->assertSame($environment, $factory->createConfigurableEnvironment());
    }
}
