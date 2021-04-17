<?php

namespace App\Tests\DataTransfer;

use App\DataTransfer\ImageManager;
use App\Entity\Image;
use App\Repository\ImageRepository;
use App\Storage\StorageInterface;
use App\Utils\ImageNameGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\DataTransfer\ImageManager
 */
class ImageManagerTest extends TestCase {
    private const FILE_NAME = __DIR__.'/../Resources/120px-12-Color-SVG.svg.png';
    private const SHA256 = 'a91d6c2201d32b8c39bff1143a5b29e74b740248c5d65810ddcbfa16228d49e9';
    private const WIDTH = 120;
    private const HEIGHT = 120;

    /**
     * @var string
     */
    private $sha256;

    /**
     * @var CacheManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cacheManager;

    /**
     * @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $entityManager;

    /**
     * @var ImageRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $images;

    /**
     * @var ImageNameGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $imageNameGenerator;

    /**
     * @var StorageInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $storage;

    /**
     * @var ImageManager
     */
    private $manager;

    protected function setUp(): void {
        $this->sha256 = hash_file('sha256', self::FILE_NAME, true);

        $this->cacheManager = $this->createMock(CacheManager::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->images = $this->getMockBuilder(ImageRepository::class)
            ->disableOriginalConstructor()
            ->addMethods(['findByFileName', 'findOneBySha256'])
            ->onlyMethods(['filterOrphaned'])
            ->getMock();
        $this->imageNameGenerator = $this->createMock(ImageNameGeneratorInterface::class);
        $this->storage = $this->createMock(StorageInterface::class);
        $this->manager = new ImageManager(
            $this->cacheManager,
            $this->entityManager,
            $this->imageNameGenerator,
            $this->images,
            $this->storage,
        );
    }

    public function testCanFindExistingImage(): void {
        $image = new Image(self::FILE_NAME, self::SHA256, self::WIDTH, self::HEIGHT);

        $this->images
            ->expects($this->once())
            ->method('findOneBySha256')
            ->with(hex2bin(self::SHA256))
            ->willReturn($image);

        $found = $this->manager->findOrCreateFromFile(self::FILE_NAME);

        $this->assertSame($image, $found);
    }

    public function testCanFindExistingImageAndUpdateDimensions(): void {
        $image = new Image(self::FILE_NAME, self::SHA256, null, null);

        $this->images
            ->expects($this->once())
            ->method('findOneBySha256')
            ->with(hex2bin(self::SHA256))
            ->willReturn($image);

        $found = $this->manager->findOrCreateFromFile(self::FILE_NAME);

        $this->assertSame($image, $found);
        $this->assertSame(self::WIDTH, $found->getWidth());
        $this->assertSame(self::HEIGHT, $found->getHeight());
    }

    public function testCanCreateImageIfNonExistent(): void {
        $this->images
            ->expects($this->once())
            ->method('findOneBySha256')
            ->with(hex2bin(self::SHA256))
            ->willReturn(null);

        $this->imageNameGenerator
            ->expects($this->once())
            ->method('generateName')
            ->with(self::FILE_NAME)
            ->willReturn('a.png');

        $this->storage
            ->expects($this->once())
            ->method('store')
            ->with(self::FILE_NAME, 'a.png');

        $found = $this->manager->findOrCreateFromFile(self::FILE_NAME);

        $this->assertEquals('a.png', $found->getFileName());
        $this->assertEquals(self::SHA256, $found->getSha256());
        $this->assertEquals(self::WIDTH, $found->getWidth());
        $this->assertEquals(self::HEIGHT, $found->getHeight());
    }

    public function testCanDeleteOrphanedImagesByFileName(): void {
        $fileNames = ['a.png', 'b.png', 'c.png'];
        $images = [
            new Image('a.png', self::SHA256, self::WIDTH, self::HEIGHT),
            new Image('b.png', self::SHA256, self::WIDTH, self::HEIGHT),
        ];

        $this->images
            ->expects($this->once())
            ->method('findByFileName')
            ->with($fileNames)
            ->willReturn($images);

        $this->images
            ->expects($this->once())
            ->method('filterOrphaned')
            ->with($images)
            ->willReturn($images);

        $this->entityManager->expects($this->once())->method('beginTransaction');

        $this->entityManager
            ->expects($this->exactly(2))
            ->method('remove')
            ->withConsecutive([$images[0]], [$images[1]]);

        $this->entityManager->expects($this->once())->method('flush');
        $this->entityManager->expects($this->once())->method('commit');

        $this->storage
            ->expects($this->exactly(2))
            ->method('prune')
            ->withConsecutive([$images[0]], [$images[1]]);

        $this->cacheManager
            ->expects($this->exactly(2))
            ->method('remove')
            ->withConsecutive([$images[0]], [$images[1]]);

        $this->manager->deleteOrphanedByFileName($fileNames);
    }

    public function testImagesAreNotDeletedIfDatabaseFailureOccurs(): void {
        $fileNames = ['a.png', 'b.png'];
        $images = [
            new Image('a.png', self::SHA256, self::WIDTH, self::HEIGHT),
            new Image('b.png', self::SHA256, self::WIDTH, self::HEIGHT),
        ];

        $this->images
            ->expects($this->once())
            ->method('findByFileName')
            ->with($fileNames)
            ->willReturn($images);

        $this->images
            ->expects($this->once())
            ->method('filterOrphaned')
            ->with($images)
            ->willReturn($images);

        $this->storage
            ->expects($this->never())
            ->method('prune');

        $this->cacheManager
            ->expects($this->never())
            ->method('remove');

        $this->entityManager
            ->expects($this->once())
            ->method('rollback');

        $e = new \Exception('oh no it broke');

        $this->entityManager
            ->method('flush')
            ->willThrowException($e);

        $this->expectExceptionObject($e);
        $this->manager->deleteOrphanedByFileName($fileNames);
    }
}
