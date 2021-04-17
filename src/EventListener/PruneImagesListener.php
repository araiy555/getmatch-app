<?php

namespace App\EventListener;

use App\Entity\Image;
use App\Event\DeleteSubmission;
use App\Event\ForumDeleted;
use App\Event\ForumUpdated;
use App\Event\SubmissionUpdated;
use App\Message\DeleteImage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class PruneImagesListener implements EventSubscriberInterface {
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var MessageBusInterface
     */
    private $messageBus;

    public static function getSubscribedEvents(): array {
        return [
            ForumDeleted::class => ['onDeleteForum'],
            DeleteSubmission::class => ['onDeleteSubmission', -8],
            ForumUpdated::class => ['onEditForum'],
            SubmissionUpdated::class => ['onEditSubmission'],
        ];
    }

    public function __construct(EntityManagerInterface $entityManager, MessageBusInterface $messageBus) {
        $this->entityManager = $entityManager;
        $this->messageBus = $messageBus;
    }

    public function onDeleteSubmission(DeleteSubmission $event): void {
        if ($this->entityManager->contains($event->getSubmission())) {
            return; // entity was soft-deleted
        }

        $image = $event->getSubmission()->getImage();

        if ($image) {
            $this->messageBus->dispatch(new DeleteImage($image->getFileName()));
        }
    }

    public function onEditSubmission(SubmissionUpdated $event): void {
        $before = $event->getBefore()->getImage();
        $after = $event->getAfter()->getImage();

        if ($before && $before !== $after) {
            $message = new DeleteImage($before->getFileName());

            $this->messageBus->dispatch($message);
        }
    }

    public function onDeleteForum(ForumDeleted $event): void {
        $images = array_map(static function (Image $image) {
            return $image->getFileName();
        }, array_filter([
            $event->getForum()->getLightBackgroundImage(),
            $event->getForum()->getDarkBackgroundImage(),
        ]));

        if ($images) {
            $this->messageBus->dispatch(new DeleteImage(...$images));
        }
    }

    public function onEditForum(ForumUpdated $event): void {
        $images = [];

        $before = $event->getBefore()->getLightBackgroundImage();
        $after = $event->getAfter()->getLightBackgroundImage();

        if ($before && $before !== $after) {
            $images[] = $before->getFileName();
        }

        $before = $event->getBefore()->getDarkBackgroundImage();
        $after = $event->getAfter()->getDarkBackgroundImage();

        if ($before && $before !== $after) {
            $images[] = $before->getFileName();
        }

        if ($images) {
            $this->messageBus->dispatch(new DeleteImage(...$images));
        }
    }
}
