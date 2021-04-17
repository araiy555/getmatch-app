<?php

namespace App\EventListener;

use App\Event\UserCreated;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class MakeFirstUserAdminListener implements EventSubscriberInterface {
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var bool
     */
    private $makeFirstUserAdmin;

    public static function getSubscribedEvents(): array {
        return [
            UserCreated::class => ['onUserCreated'],
        ];
    }

    public function __construct(
        EntityManagerInterface $entityManager,
        bool $makeFirstUserAdmin
    ) {
        $this->entityManager = $entityManager;
        $this->makeFirstUserAdmin = $makeFirstUserAdmin;
    }

    public function onUserCreated(UserCreated $event): void {
        if ($this->makeFirstUserAdmin && $event->getUser()->getId() === 1) {
            $event->getUser()->setAdmin(true);

            $this->entityManager->flush();
        }
    }
}
