<?php

namespace App\DataTransfer;

use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserManager {
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var NotificationRepository
     */
    private $notifications;

    public function __construct(
        EntityManagerInterface $entityManager,
        NotificationRepository $notifications
    ) {
        $this->entityManager = $entityManager;
        $this->notifications = $notifications;
    }

    /**
     * @param array<string|\Stringable> $ids
     */
    public function clearNotificationsById(User $user, array $ids): void {
        $notifications = $this->notifications->findByUserAndIds($user, $ids);

        foreach ($notifications as $notification) {
            $user->clearNotification($notification);
            $this->entityManager->remove($notification);
        }

        $this->entityManager->flush();
    }
}
