<?php

namespace App\Tests\Serializer;

use App\Entity\Comment;
use App\Entity\Exception\BannedFromForumException;
use App\Entity\Forum;
use App\Entity\Message;
use App\Serializer\EntityRetrievingDenormalizer;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Serializer\EntityRetrievingDenormalizer
 */
class EntityRetrievingDenormalizerTest extends TestCase {
    /**
     * @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $entityManager;

    /**
     * @var EntityRetrievingDenormalizer
     */
    private $normalizer;

    public function setUp(): void {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->normalizer = new EntityRetrievingDenormalizer($this->entityManager);
    }

    /**
     * @dataProvider entityProvider
     * @param mixed $id
     */
    public function testSupportsEntities($id, string $type): void {
        $this->assertTrue($this->normalizer->supportsDenormalization($id, $type));
    }

    /**
     * @dataProvider invalidEntityProvider
     * @param mixed $id
     */
    public function testDoesNotSupportInvalidDataAndType($id, string $type): void {
        $this->assertFalse($this->normalizer->supportsDenormalization($id, $type));
    }

    /**
     * @dataProvider entityProvider
     * @param mixed $id
     */
    public function testCanDenormalizeEntities($id, string $type): void {
        $this->entityManager
            ->expects($this->once())
            ->method('find')
            ->with($this->equalTo($type), $this->equalTo($id))
            ->willReturn($this->createMock($type));

        $denormalized = $this->normalizer->denormalize($id, $type);

        $this->assertInstanceOf($type, $denormalized);
    }

    public function entityProvider(): iterable {
        yield [1, Comment::class];
        yield [2, Forum::class];
        yield ['12341234-1234-1234-1234-123412341234', Message::class];
    }

    public function invalidEntityProvider(): iterable {
        yield [1, BannedFromForumException::class];
        yield [null, Forum::class];
        yield [[], Forum::class];
        yield [(object) [], Message::class];
    }
}
