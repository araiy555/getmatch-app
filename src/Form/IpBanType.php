<?php

namespace App\Form;

use App\Form\DataTransformer\UserTransformer;
use App\Form\Model\IpBanData;
use App\Repository\UserRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class IpBanType extends AbstractType {
    /**
     * @var UserRepository
     */
    private $userRepository;

    public function __construct(UserRepository $userRepository) {
        $this->userRepository = $userRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder
            ->add('ip', TextType::class, [
                'label' => 'label.ip_address',
            ])
            ->add('reason', TextType::class, [
                'help' => 'help.ban_reason',
                'label' => 'label.reason_for_banning',
            ])
            ->add('expires', DateTimeType::class, [
                'date_widget' => 'single_text',
                'label' => 'label.expires',
                'time_widget' => 'single_text',
                'required' => false,
            ])
            ->add('user', TextType::class, [
                'label' => 'label.user_associated_with_ip',
                'required' => false,
            ])
        ;

        $builder->get('user')->addModelTransformer(
            new UserTransformer($this->userRepository)
        );
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => IpBanData::class,
        ]);
    }
}
