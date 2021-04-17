<?php

namespace App\EventListener;

use App\Entity\ForumLogCommentDeletion;
use App\Entity\ForumLogSubmissionDeletion;
use App\Event\DeleteComment;
use App\Event\DeleteSubmission;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class DeleteListener implements EventSubscriberInterface {
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var SiteRepository
     */
    private $sites;

    public static function getSubscribedEvents(): array {
        return [
            DeleteSubmission::class => ['onDeleteSubmission'],
            DeleteComment::class => ['onDeleteComment'],
        ];
    }

    public function __construct(
        EntityManagerInterface $entityManager,
        SiteRepository $sites
    ) {
        $this->entityManager = $entityManager;
        $this->sites = $sites;
    }

    public function onDeleteSubmission(DeleteSubmission $event): void {
        $useTrash = $this->sites->findCurrentSite()->isTrashEnabled();
        $submission = $event->getSubmission();

        if ($useTrash && !$event->isPermanent() && $event->isModDelete()) {
            $submission->trash();
        } elseif ($submission->hasVisibleComments()) {
            $submission->softDelete();
        } else {
            $submission->getForum()->removeSubmission($submission);
            $this->entityManager->remove($submission);
        }

        if ($event->isModDelete()) {
            $moderator = $event->getModerator();
            $reason = $event->getReason();

            $this->entityManager->persist(
                new ForumLogSubmissionDeletion($submission, $moderator, $reason)
            );
        }

        $this->entityManager->flush();
    }

    public function onDeleteComment(DeleteComment $event): void {
        $useTrash = $this->sites->findCurrentSite()->isTrashEnabled();
        $comments = [$event->getComment()];

        if ($event->isRecursive()) {
            foreach ($event->getComment()->getChildrenRecursive() as $child) {
                $comments[] = $child;
            }
        }

        $modDelete = $event->isModDelete();
        $moderator = $event->getModerator();
        $reason = $event->getReason();
        $permanent = $event->isPermanent();

        foreach ($comments as $comment) {
            if (!$permanent && $useTrash && $modDelete) {
                $comment->trash();
            } else {
                $comment->softDelete();

                if (!$comment->isThreadVisible()) {
                    $comment->getSubmission()->removeComment($comment);
                    $this->entityManager->remove($comment);
                }
            }

            if ($modDelete) {
                $this->entityManager->persist(
                    new ForumLogCommentDeletion($comment, $moderator, $reason)
                );
            }
        }

        $this->entityManager->flush();
    }
}
