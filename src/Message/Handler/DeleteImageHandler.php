<?php

namespace App\Message\Handler;

use App\DataTransfer\ImageManager;
use App\Message\DeleteImage;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class DeleteImageHandler implements MessageHandlerInterface {
    /**
     * @var ImageManager
     */
    private $imageManager;

    /**
     * @var MessageBusInterface
     */
    private $messageBus;

    /**
     * @var int
     */
    private $batchSize;

    public function __construct(
        ImageManager $imageManager,
        MessageBusInterface $messageBus,
        int $batchSize
    ) {
        $this->imageManager = $imageManager;
        $this->messageBus = $messageBus;
        $this->batchSize = $batchSize;
    }

    public function __invoke(DeleteImage $message): void {
        $batch = \array_slice($message->getFileNames(), 0, $this->batchSize);
        $remaining = \array_slice($message->getFileNames(), $this->batchSize);

        if (\count($batch) > 0) {
            $this->imageManager->deleteOrphanedByFileName($batch);
        }

        if (\count($remaining) > 0) {
            $this->messageBus->dispatch(new DeleteImage(...$remaining));
        }
    }
}
