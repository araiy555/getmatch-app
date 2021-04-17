<?php

namespace App\Form;

use App\DataObject\ForumData;
use App\Form\Type\BackgroundImageType;
use App\Form\Type\ThemeSelectorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class ForumAppearanceType extends AbstractType {
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker) {
        $this->authorizationChecker = $authorizationChecker;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder
            ->add('suggestedTheme', ThemeSelectorType::class, [
                'label' => 'label.suggested_theme',
                'required' => false,
            ]);

        if ($this->authorizationChecker->isGranted('upload_image')) {
            $builder->add('backgroundImage', BackgroundImageType::class, [
                'label' => 'label.background_image',
                'required' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => ForumData::class,
            'validation_groups' => ['appearance'],
        ]);
    }
}
