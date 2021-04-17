<?php

namespace App\Form;

use App\Form\Model\UserBanData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class BanUserType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder
            ->add('reason', TextareaType::class, [
                'help' => 'help.ban_reason',
                'label' => 'label.reason',
            ])
            ->add('expires', DateTimeType::class, [
                'date_widget' => 'single_text',
                'label' => 'label.expires',
                'required' => false,
                'time_widget' => 'single_text',
            ])
            ->add('ban_ip', CheckboxType::class, [
                'label' => 'label.ban_ip_addresses',
                'mapped' => false,
                'required' => false,
            ])
            ->add('ips', TextareaType::class, [
                'label' => 'label.ip_addresses',
                'required' => false,
            ])
        ;

        $builder->get('ips')->addModelTransformer(new CallbackTransformer(
            static function ($value) {
                if ($value instanceof \Traversable) {
                    $value = iterator_to_array($value);
                }

                return implode(', ', (array) $value);
            },
            static function ($value) {
                return preg_split('/[,;\r\n\t ]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => UserBanData::class,
            'validation_groups' => static function (FormInterface $form) {
                $groups = ['ban_user'];

                if ($form->get('ban_ip')->getData()) {
                    $groups[] = 'ban_ip';
                }

                return $groups;
            },
        ]);
    }
}
