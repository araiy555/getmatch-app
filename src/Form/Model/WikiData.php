<?php

namespace App\Form\Model;

use App\Entity\User;
use App\Entity\WikiPage;
use App\Entity\WikiRevision;
use App\Validator\NoBadPhrases;
use Symfony\Component\Validator\Constraints as Assert;

class WikiData {
    /**
     * @Assert\NotBlank()
     * @Assert\Length(min=1, max=80)
     * @NoBadPhrases()
     *
     * @var string|null
     */
    private $title;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(min=1, max=250000)
     * @NoBadPhrases()
     *
     * @var string|null
     */
    private $body;

    public static function createFromPage(WikiPage $page): self {
        $self = new self();

        $self->title = $page->getLatestRevision()->getTitle();
        $self->body = $page->getLatestRevision()->getBody();

        return $self;
    }

    public function toPage(string $path, User $user): WikiPage {
        return new WikiPage($path, $this->title, $this->body, $user);
    }

    public function updatePage(WikiPage $page, User $user): void {
        $revision = new WikiRevision($page, $this->title, $this->body, $user);

        $page->addRevision($revision);
    }

    public function getTitle(): ?string {
        return $this->title;
    }

    public function setTitle(?string $title): void {
        $this->title = $title;
    }

    public function getBody(): ?string {
        return $this->body;
    }

    public function setBody(?string $body): void {
        $this->body = $body;
    }
}
