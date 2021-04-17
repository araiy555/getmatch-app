<?php

namespace App\DataObject;

use App\Entity\CssTheme;
use App\Entity\CssThemeRevision;
use Symfony\Component\Validator\Constraints as Assert;

class CssThemeData {
    /**
     * @Assert\Length(max=60)
     * @Assert\NotBlank()
     *
     * @var string|null
     */
    public $name;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(max=2000)
     *
     * @var string|null
     */
    public $css;

    public static function fromTheme(CssTheme $theme): self {
        $self = new self();
        $self->name = $theme->getName();
        $self->css = $theme->getLatestRevision()->getCss();

        return $self;
    }

    public function updateTheme(CssTheme $theme): void {
        $theme->setName($this->name);

        if ($theme->getLatestRevision()->getCss() !== $this->css) {
            $theme->addRevision(new CssThemeRevision($theme, $this->css));
        }
    }
}
