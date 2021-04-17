<?php

namespace App\Form;

use App\DataObject\UserData;
use App\Form\Type\MarkdownType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UserBiographyType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder
            ->add('biography', MarkdownType::class, [
                'label' => 'label.biography',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => UserData::class,
            'validation_groups' => ['edit_biography'],
        ]);
    }
}
