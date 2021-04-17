<?php

namespace App\Form;

use App\DataObject\BadPhraseData;
use App\Entity\BadPhrase;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class BadPhraseType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder
            ->add('phrase', TextType::class, [
                'label' => 'bad_phrase.phrase_to_ban',
            ])
            ->add('phraseType', ChoiceType::class, [
                'choices' => [
                    'bad_phrase.type_text' => BadPhrase::TYPE_TEXT,
                    'label.regex' => BadPhrase::TYPE_REGEX,
                ],
                'label' => 'label.type',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => BadPhraseData::class,
            'validation_groups' => function (FormInterface $form) {
                $groups = ['Default'];

                if ($form->get('phraseType')->getData() === BadPhrase::TYPE_REGEX) {
                    $groups[] = 'regex';
                }

                return $groups;
            },
        ]);
    }
}
