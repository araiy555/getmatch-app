<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CssThemeRevisionRepository")
 */
class CssThemeRevision {
    /**
     * @ORM\Column(type="uuid")
     * @ORM\Id()
     *
     * @var UuidInterface
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="CssTheme", inversedBy="revisions")
     *
     * @var CssTheme
     */
    private $theme;

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
    private $css;

    public function __construct(CssTheme $theme, string $css) {
        $this->id = Uuid::uuid4();
        $this->theme = $theme;
        $this->timestamp = new \DateTimeImmutable('@'.time());
        $this->css = $css;

        $theme->addRevision($this);
    }

    public function getId(): UuidInterface {
        return $this->id;
    }

    public function getTheme(): CssTheme {
        return $this->theme;
    }

    public function getTimestamp(): \DateTimeImmutable {
        return $this->timestamp;
    }

    public function getCss(): string {
        return $this->css;
    }
}
