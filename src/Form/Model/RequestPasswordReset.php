<?php

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

class RequestPasswordReset {
    /**
     * @Assert\Email()
     * @Assert\NotBlank()
     *
     * @var string|null
     */
    private $email;

    public function getEmail(): ?string {
        return $this->email;
    }

    public function setEmail(?string $email): void {
        $this->email = $email;
    }
}
