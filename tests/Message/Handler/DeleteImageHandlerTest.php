<?php

namespace App\Tests\Message\Handler;

use App\DataTransfer\ImageManager;
use App\Message\DeleteImage;
use App\Message\Handler\DeleteImageHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @covers \App\Message\Handler\DeleteImageHandler
 */
class DeleteImageHandlerTest extends TestCase {
    private const BATCH_SIZE = 5;

    /**
     * @var ImageManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $imageManager;

    /**
     * @var MessageBusInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $messageBus;

    /**
     * @var DeleteImageHandler
     */
    private $handler;

    protected function setUp(): void {
        $this->imageManager = $this->createMock(ImageManager::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->handler = new DeleteImageHandler(
            $this->imageManager,
            $this->messageBus,
            self::BATCH_SIZE,
        );
    }

    public function testNoImagesInBatchEvokesNoAction() {
        $this->imageManager
            ->expects($this->never())
            ->method('deleteOrphanedByFileName');

        $this->messageBus
            ->expects($this->never())
            ->method('dispatch');

        ($this->handler)(new DeleteImage());
    }

    public function testCanHandleSingleBatch(): void {
        $fileNames = array_map(static function (int $i) {
            return "{$i}.png";
        }, range(1, self::BATCH_SIZE));

        $this->imageManager
            ->expects($this->once())
            ->method('deleteOrphanedByFileName')
            ->with($fileNames);

        $this->messageBus
            ->expects($this->never())
            ->method('dispatch');

        ($this->handler)(new DeleteImage(...$fileNames));
    }

    public function testCanHandleMultipleBatches(): void {
        $fileNames = array_map(static function (int $i) {
            return "{$i}.png";
        }, range(1, self::BATCH_SIZE * 3));

        $this->imageManager
            ->expects($this->once())
            ->method('deleteOrphanedByFileName')
            ->with(\array_slice($fileNames, 0, self::BATCH_SIZE));

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with(new DeleteImage(...\array_slice($fileNames, self::BATCH_SIZE)))
            ->willReturnCallback(function ($message) {
                return new Envelope($message, []);
            });

        ($this->handler)(new DeleteImage(...$fileNames));
    }
}
