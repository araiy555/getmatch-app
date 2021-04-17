<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BadPhraseRepository")
 * @ORM\Table(uniqueConstraints={
 *     @ORM\UniqueConstraint(name="bad_phrases_phrase_type_idx", columns={"phrase", "phrase_type"}),
 * })
 */
class BadPhrase {
    public const TYPES = [self::TYPE_TEXT, self::TYPE_REGEX];
    public const TYPE_TEXT = 'text';
    public const TYPE_REGEX = 'regex';

    /**
     * @ORM\Column(type="uuid")
     * @ORM\Id()
     *
     * @var UuidInterface
     */
    private $id;

    /**
     * @ORM\Column(type="datetimetz_immutable")
     *
     * @var \DateTimeImmutable
     */
    private $timestamp;

    /**
     * @ORM\Column(type="text")
     *
     * @var string
     */
    private $phrase;

    /**
     * @ORM\Column(type="text", options={"default": BadPhrase::TYPE_TEXT})
     *
     * @var string
     */
    private $phraseType;

    public function __construct(string $phrase, string $phraseType) {
        if (!\in_array($phraseType, self::TYPES, true)) {
            throw new \InvalidArgumentException("Invalid type '$phraseType'");
        }

        if ($phraseType === self::TYPE_REGEX) {
            $return = @preg_match('@'.addcslashes($phrase, '@').'@', '');

            if ($return === 1) {
                throw new \DomainException('Regex must not match empty string');
            }

            if ($return !== 0) {
                throw new \DomainException('Bad regex', preg_last_error());
            }
        }

        $this->id = Uuid::uuid4();
        $this->timestamp = new \DateTimeImmutable('@'.time());
        $this->phrase = $phrase;
        $this->phraseType = $phraseType;
    }

    public function getId(): UuidInterface {
        return $this->id;
    }

    public function getTimestamp(): \DateTimeImmutable {
        return $this->timestamp;
    }

    public function getPhrase(): string {
        return $this->phrase;
    }

    public function getPhraseType(): string {
        return $this->phraseType;
    }
}
