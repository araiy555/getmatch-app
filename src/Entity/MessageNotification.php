<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class MessageNotification extends Notification {
    /**
     * @ORM\ManyToOne(targetEntity="Message", inversedBy="notifications", cascade={"persist"})
     *
     * @var Message
     */
    private $message;

    public function __construct(User $receiver, Message $message) {
        parent::__construct($receiver);

        $this->message = $message;
    }

    public function getMessage(): Message {
        return $this->message;
    }

    public function getType(): string {
        return 'message';
    }
}
