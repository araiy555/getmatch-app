<?php

namespace App\Form\EventListener;

use App\DataObject\SubmissionData;
use App\DataTransfer\ImageManager;
use App\Entity\Submission;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Handle submission image uploads.
 */
final class SubmissionImageListener implements EventSubscriberInterface {
    /**
     * @var ImageManager
     */
    private $imageManager;

    public function __construct(ImageManager $imageManager) {
        $this->imageManager = $imageManager;
    }

    public static function getSubscribedEvents(): array {
        return [
            FormEvents::POST_SUBMIT => ['onPostSubmit', -200],
        ];
    }

    public function onPostSubmit(PostSubmitEvent $event): void {
        if (!$event->getForm()->isValid()) {
            return;
        }

        $data = $event->getData();
        \assert($data instanceof SubmissionData);

        $upload = $event->getForm()->get('image')->getData();
        \assert($upload instanceof UploadedFile || $upload === null);

        if ($upload && !$data->getImage() && $data->getMediaType() === Submission::MEDIA_IMAGE) {
            $image = $this->imageManager->findOrCreateFromFile($upload->getPathname());

            $data->setImage($image);
        }
    }
}
