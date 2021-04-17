<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Theme loaded from Webpack entrypoint.
 *
 * @ORM\Entity(repositoryClass="App\Repository\BundledThemeRepository")
 */
class BundledTheme extends Theme {
    /**
     * @ORM\Column(type="text", unique=true)
     *
     * @var string
     */
    private $configKey;

    public function __construct(string $name, string $configKey) {
        parent::__construct($name);

        $this->configKey = $configKey;
    }

    public function getConfigKey(): string {
        return $this->configKey;
    }

    public function getType(): string {
        return 'bundled';
    }
}
