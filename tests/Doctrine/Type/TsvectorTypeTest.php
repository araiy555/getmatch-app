<?php

namespace App\Tests\Doctrine\Type;

use App\Doctrine\Type\TsvectorType;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Doctrine\Type\TsvectorType
 */
class TsvectorTypeTest extends TestCase {
    /**
     * @var TsvectorType
     */
    private $type;

    /**
     * @var PostgreSqlPlatform|\PHPUnit\Framework\MockObject\MockObject
     */
    private $platform;

    public static function setUpBeforeClass(): void {
        if (!Type::hasType('tsvector')) {
            Type::addType('tsvector', TsvectorType::class);
        }
    }

    protected function setUp(): void {
        $this->type = Type::getType('tsvector');
        $this->platform = $this->createMock(PostgreSqlPlatform::class);
    }

    public function testMetadata(): void {
        $this->assertSame('TSVECTOR', $this->type->getSQLDeclaration([], $this->platform));
        $this->assertSame('tsvector', $this->type->getName());
        $this->assertTrue($this->type->requiresSQLCommentHint($this->platform));
    }

    public function testSqlConversion(): void {
        $this->assertSame(
            'TO_TSVECTOR(SELECT 1)',
            $this->type->convertToDatabaseValueSQL('SELECT 1', $this->platform)
        );
    }
}
