<?php

namespace App\Form\EventListener;

use App\DataObject\UserData;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

final class PasswordEncodingSubscriber implements EventSubscriberInterface {
    /**
     * @var UserPasswordEncoderInterface
     */
    private $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder) {
        $this->encoder = $encoder;
    }

    public function onPostSubmit(FormEvent $event): void {
        if (!$event->getForm()->isValid()) {
            return;
        }

        /* @var UserData $user */
        $user = $event->getForm()->getData();

        if (!$user instanceof UserData) {
            throw new \UnexpectedValueException(
                'Form data must be instance of '.UserData::class
            );
        }

        if ($user->getPlainPassword() !== null) {
            $encoded = $this->encoder->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($encoded);
        }
    }

    public static function getSubscribedEvents(): array {
        return [
            FormEvents::POST_SUBMIT => ['onPostSubmit', -200],
        ];
    }
}
