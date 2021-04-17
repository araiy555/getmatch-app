<?php

namespace App\DataTransfer;

use App\DataObject\SubmissionData;
use App\Entity\ForumLogSubmissionLock;
use App\Entity\ForumLogSubmissionRestored;
use App\Entity\Submission;
use App\Entity\User;
use App\Event\DeleteSubmission;
use App\Message\NewSubmission;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class SubmissionManager {
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var MessageBusInterface
     */
    private $messageBus;

    public function __construct(
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $eventDispatcher,
        MessageBusInterface $messageBus
    ) {
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->messageBus = $messageBus;
    }

    public function submit(
        SubmissionData $data,
        User $user,
        ?string $ip
    ): Submission {
        $submission = new Submission(
            $data->getTitle(),
            $data->getUrl(),
            $data->getBody(),
            $data->getForum(),
            $user,
            $ip,
        );
        $submission->setUserFlag($data->getUserFlag());
        $submission->setSticky($data->isSticky());
        $submission->setLocked($data->isLocked());

        if ($data->getMediaType() === Submission::MEDIA_IMAGE) {
            $submission->setUrl(null);

            if ($data->getImage()) {
                $submission->setImage($data->getImage());
                $submission->setMediaType($data->getMediaType());
            }
        }

        $this->entityManager->persist($submission);
        $this->entityManager->flush();

        $this->messageBus->dispatch(new NewSubmission($submission));

        return $submission;
    }

    public function update(
        Submission $submission,
        SubmissionData $data,
        User $updatingUser
    ): void {
        $editedAt = $data->getEditedAt();
        $moderated = $data->isModerated();

        if (
            $data->getUrl() !== $submission->getUrl() ||
            $data->getTitle() !== $submission->getTitle() ||
            $data->getBody() !== $submission->getBody()
        ) {
            $editedAt = new \DateTimeImmutable('@'.time());
            $moderated = $moderated || $updatingUser !== $submission->getUser();
        }

        if ($submission->isLocked() !== $data->isLocked()) {
            $submission->getForum()->addLogEntry(new ForumLogSubmissionLock(
                $submission,
                $updatingUser,
                $data->isLocked(),
            ));
        }

        $submission->setTitle($data->getTitle());
        $submission->setUrl($data->getUrl());
        $submission->setBody($data->getBody());
        $submission->setEditedAt($editedAt);
        $submission->setUserFlag($data->getUserFlag());
        $submission->setModerated($moderated);
        $submission->setSticky($data->isSticky());
        $submission->setLocked($data->isLocked());

        $this->entityManager->flush();
    }

    public function delete(Submission $submission): void {
        $this->eventDispatcher->dispatch(new DeleteSubmission($submission));
    }

    public function remove(
        Submission $submission,
        User $actingUser,
        string $reason
    ): void {
        $this->eventDispatcher->dispatch(
            (new DeleteSubmission($submission))
                ->asModerator($actingUser, $reason),
        );
    }

    public function purge(Submission $submission): void {
        $this->eventDispatcher->dispatch(
            (new DeleteSubmission($submission))
                ->withPermanence(),
        );
    }

    public function restore(Submission $submission, User $actingUser): void {
        $submission->restore();

        $submission->getForum()->addLogEntry(new ForumLogSubmissionRestored(
            $submission,
            $actingUser,
        ));

        $this->entityManager->flush();
    }
}
