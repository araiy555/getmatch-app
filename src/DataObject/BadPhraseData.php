<?php

namespace App\DataObject;

use App\Entity\BadPhrase;
use App\Validator\RegularExpression;
use App\Validator\Unique;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Unique({"phrase", "phraseType"}, entityClass="App\Entity\BadPhrase", idFields={"id"}, errorPath="phrase")
 */
class BadPhraseData {
    /**
     * @var UuidInterface|null
     */
    private $id;

    /**
     * @Assert\Length(max=150)
     * @Assert\NotBlank()
     * @RegularExpression(groups={"regex"})
     *
     * @var string|null
     */
    private $phrase;

    /**
     * @Assert\Choice(BadPhrase::TYPES)
     * @Assert\NotBlank()
     *
     * @var string|null
     */
    private $phraseType;

    public function getId(): ?UuidInterface {
        return $this->id;
    }

    public function toBadPhrase(): BadPhrase {
        return new BadPhrase($this->phrase, $this->phraseType);
    }

    public function getPhrase(): ?string {
        return $this->phrase;
    }

    public function setPhrase(?string $phrase): void {
        $this->phrase = $phrase;
    }

    public function getPhraseType(): ?string {
        return $this->phraseType;
    }

    public function setPhraseType(?string $phraseType): void {
        $this->phraseType = $phraseType;
    }
}
