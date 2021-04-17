<?php

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

class UserBlockData {
    /**
     * @Assert\Length(max=100)
     *
     * @var string|null
     */
    private $comment;

    public function getComment(): ?string {
        return $this->comment;
    }

    public function setComment(?string $comment): void {
        $this->comment = $comment;
    }
}
