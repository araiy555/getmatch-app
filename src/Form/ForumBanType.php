<?php

namespace App\Form;

use App\Form\Model\ForumBanData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ForumBanType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder->add('reason', TextareaType::class, [
            'help' => $options['intent'] === 'ban' ? 'help.ban_reason_logged' : null,
            'label' => 'label.reason',
        ]);

        if ($options['intent'] === 'ban') {
            $builder->add('expires', DateTimeType::class, [
                'label' => 'label.expires',
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
                'required' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => ForumBanData::class,
            'intent' => null,
            'validation_groups' => static function (FormInterface $form) {
                return [$form->getConfig()->getOption('intent')];
            },
        ]);

        $resolver->setRequired('intent');
        $resolver->setAllowedValues('intent', ['ban', 'unban']);
    }
}
