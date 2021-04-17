<?php

namespace App\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Adds a convenient option `max_chars` option to help with client-side form
 * input length validation.
 */
final class CharactersRemainingExtension extends AbstractTypeExtension {
    public function finishView(FormView $view, FormInterface $form, array $options): void {
        if ($options['max_chars']) {
            // TODO: figure out if this can be done in templates instead
            $controller = &$view->vars['attr']['data-controller'];
            $controller = trim(($controller ?? '').' remaining-chars');

            $action = &$view->vars['attr']['data-action'];
            $action = trim(($action ?? '').' remaining-chars#validate');

            $view->vars['attr']['data-remaining-chars-max-value'] = $options['max_chars'];
        }
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'max_chars' => null,
        ]);

        $resolver->setAllowedTypes('max_chars', ['null', 'int']);
    }

    public static function getExtendedTypes(): array {
        return [TextType::class];
    }
}
