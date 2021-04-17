<?php

namespace App\Tests\Fixtures;

use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface as BaseTranslatorInterface;

interface TranslatorInterface extends BaseTranslatorInterface, LocaleAwareInterface {
}
