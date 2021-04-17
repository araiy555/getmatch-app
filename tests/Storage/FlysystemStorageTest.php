<?php

namespace App\Tests\Storage;

use App\Storage\FlysystemStorage;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Storage\FlysystemStorage
 */
class FlysystemStorageTest extends TestCase {
    /**
     * @var FilesystemInterface|MockObject
     */
    private $filesystem;

    /**
     * @var FlysystemStorage
     */
    private $manager;

    protected function setUp(): void {
        $this->filesystem = $this->createMock(FilesystemInterface::class);
        $this->manager = new FlysystemStorage($this->filesystem);
    }

    public function testCanStoreImage(): void {
        $this->filesystem
            ->expects($this->once())
            ->method('writeStream')
            ->with(
                $this->equalTo('destination.png'),
                $this->callback('is_resource')
            )
            ->willReturn(true);

        $this->manager->store(
            __DIR__.'/../Resources/120px-12-Color-SVG.svg.png',
            'destination.png'
        );
    }

    public function testStoreHandlesCollidingFileNames(): void {
        $this->filesystem
            ->expects($this->once())
            ->method('writeStream')
            ->with(
                $this->equalTo('destination.png'),
                $this->callback('is_resource')
            )
            ->willThrowException(new FileExistsException('destination.png'));

        $this->manager->store(
            __DIR__.'/../Resources/120px-12-Color-SVG.svg.png',
            'destination.png'
        );
    }

    public function testCanPruneImage(): void {
        $this->filesystem
            ->expects($this->once())
            ->method('delete')
            ->with('image.png');

        $this->manager->prune('image.png');
    }

    public function testPruneHandlesNonExistentFiles(): void {
        $this->filesystem
            ->expects($this->once())
            ->method('delete')
            ->with('image.png')
            ->willThrowException(new FileNotFoundException('image.png'));

        $this->manager->prune('image.png');
    }
}
