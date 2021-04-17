<?php

namespace App\Form\Extension;

use App\Security\Authentication;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UserTimezoneExtension extends AbstractTypeExtension {
    /**
     * @var Authentication
     */
    private $authentication;

    public function __construct(Authentication $authentication) {
        $this->authentication = $authentication;
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setNormalizer('view_timezone', function (Options $options, $value) {
            $user = $this->authentication->getUser();

            if ($user && $value === null) {
                $value = $user->getTimezone()->getName();
            }

            return $value;
        });
    }

    public static function getExtendedTypes(): iterable {
        return [DateTimeType::class, DateType::class];
    }
}
