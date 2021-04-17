<?php

namespace App\Message;

class SendPasswordResetEmail {
    /**
     * @var string
     */
    private $emailAddress;

    public function __construct(string $emailAddress) {
        $this->emailAddress = $emailAddress;
    }

    public function getEmailAddress(): string {
        return $this->emailAddress;
    }
}
