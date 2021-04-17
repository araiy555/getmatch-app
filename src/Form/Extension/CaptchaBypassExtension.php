<?php

namespace App\Form\Extension;

use Gregwar\CaptchaBundle\Type\CaptchaType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Allows bypassing CAPTCHAs (for unit tests).
 */
final class CaptchaBypassExtension extends AbstractTypeExtension {
    /**
     * @var bool
     */
    private $bypass;

    public static function getExtendedTypes(): array {
        return [CaptchaType::class];
    }

    public function __construct(bool $bypass = false) {
        $this->bypass = $bypass;
    }

    public function configureOptions(OptionsResolver $resolver): void {
        if ($this->bypass) {
            $resolver->setDefault('bypass_code', 'bypass');
        }
    }
}
