<?php

namespace App\Form;

use App\Form\Model\RequestPasswordReset;
use Gregwar\CaptchaBundle\Type\CaptchaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class RequestPasswordResetType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'label.email_address',
            ])
            ->add('verification', CaptchaType::class, [
                'label' => 'label.verification',
                'as_url' => true,
                'reload' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => RequestPasswordReset::class,
            'label_format' => 'request_password_reset_form.%name%',
        ]);
    }
}
