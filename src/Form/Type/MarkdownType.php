<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class MarkdownType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder->addViewTransformer(new CallbackTransformer(
            static function (?string $data) {
                return $data;
            },
            static function (?string $data) {
                if ($data === null || ($data = rtrim($data)) === '') {
                    return null;
                }

                return $data;
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'trim' => false,
        ]);
    }

    public function getParent(): string {
        return TextareaType::class;
    }
}
