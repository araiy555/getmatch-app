<?php

namespace App\Entity\Contracts;

use App\Entity\Image;

interface BackgroundImageInterface {
    public const BACKGROUND_TILE = 'tile';
    public const BACKGROUND_CENTER = 'center';
    public const BACKGROUND_FIT_TO_PAGE = 'fit_to_page';

    public function getLightBackgroundImage(): ?Image;

    public function setLightBackgroundImage(?Image $lightBackgroundImage): void;

    public function getDarkBackgroundImage(): ?Image;

    public function setDarkBackgroundImage(?Image $darkBackgroundImage): void;

    public function getBackgroundImageMode(): string;

    public function setBackgroundImageMode(string $backgroundImageMode): void;
}
