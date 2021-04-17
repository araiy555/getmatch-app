<?php

namespace App\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Prevent submitting a form multiple times by invalidating its CSRF token ID
 * after validation passes.
 */
final class PreventMultipleSubmitExtension extends AbstractTypeExtension {
    public static function getExtendedTypes(): array {
        return [FormType::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder->addEventListener(FormEvents::POST_SUBMIT, static function (FormEvent $event): void {
            $form = $event->getForm();

            if (
                !$form->isRoot() ||
                !$form->isValid() ||
                !$form->getConfig()->getOption('csrf_protection')
            ) {
                return;
            }

            // from symfony's FormTypeCsrfExtension.php
            $tokenId = $form->getConfig()->getOption('csrf_token_id')
                ?: $form->getName()
                ?: \get_class($form->getConfig()->getType()->getInnerType());

            /* @var CsrfTokenManagerInterface */
            $tokenManager = $form->getConfig()->getOption('csrf_token_manager');
            $tokenManager->refreshToken($tokenId);
        }, -10);
    }
}
