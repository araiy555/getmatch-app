<?php

namespace App\Form\Type;

use App\DataTransfer\ImageManager;
use App\Entity\Contracts\BackgroundImageInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Image as ImageConstraint;

final class BackgroundImageType extends AbstractType {
    /**
     * @var ImageManager
     */
    private $imageManager;

    public function __construct(ImageManager $imageManager) {
        $this->imageManager = $imageManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $imageConstraint = new ImageConstraint([
            'detectCorrupted' => true,
            'groups' => ['upload'],
            'maxSize' => '2M',
            'mimeTypes' => ['image/jpeg', 'image/gif', 'image/png'],
        ]);

        $builder
            ->add('lightBackgroundImage', FileType::class, [
                'constraints' => [$imageConstraint],
                'label' => 'background.light_background_image',
                'mapped' => false,
            ])
            ->add('removeLightBackgroundImage', SubmitType::class, [
                'label' => 'action.remove',
            ])
            ->add('darkBackgroundImage', FileType::class, [
                'constraints' => [$imageConstraint],
                'label' => 'background.dark_background_image',
                'mapped' => false,
            ])
            ->add('removeDarkBackgroundImage', SubmitType::class, [
                'label' => 'action.remove',
            ])
            ->add('backgroundImageMode', ChoiceType::class, [
                'choices' => [
                    'background.tile' => BackgroundImageInterface::BACKGROUND_TILE,
                    'background.fit_to_page' => BackgroundImageInterface::BACKGROUND_FIT_TO_PAGE,
                    'background.center' => BackgroundImageInterface::BACKGROUND_CENTER,
                ],
                'constraints' => [
                    new Choice([
                        'choices' => [
                            BackgroundImageInterface::BACKGROUND_CENTER,
                            BackgroundImageInterface::BACKGROUND_FIT_TO_PAGE,
                            BackgroundImageInterface::BACKGROUND_TILE,
                        ],
                    ]),
                ],
                'expanded' => true,
            ])
        ;

        $builder->addEventListener(FormEvents::POST_SUBMIT, static function (FormEvent $event): void {
            $form = $event->getForm();
            $data = $form->getData();

            if ($form->get('removeLightBackgroundImage')->isClicked()) {
                $data->setLightBackgroundImage(null);

                $event->stopPropagation();
            }

            if ($form->get('removeDarkBackgroundImage')->isClicked()) {
                $data->setDarkBackgroundImage(null);

                $event->stopPropagation();
            }
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $form = $event->getForm();
            $data = $event->getForm()->getData();
            \assert($data instanceof BackgroundImageInterface || $data === null);

            if (!$form->isValid()) {
                return;
            }

            $uploadMap = [];

            foreach (['lightBackgroundImage', 'darkBackgroundImage'] as $key) {
                $upload = $form->get($key)->getData();

                \assert($upload instanceof UploadedFile || !$upload);

                if ($upload) {
                    $image = $this->imageManager->findOrCreateFromFile($upload->getPathname());

                    // avoid error if the same image is chosen twice
                    $image = $uploadMap[$image->getFileName()] ?? $image;
                    $uploadMap[$image->getFileName()] = $image;

                    $data->{'set'.ucfirst($key)}($image);
                }
            }
        });
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void {
        $data = $form->getData();

        \assert($data instanceof BackgroundImageInterface);

        $view->vars['light_background_image'] = $data->getLightBackgroundImage();
        $view->vars['dark_background_image'] = $data->getDarkBackgroundImage();
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => BackgroundImageInterface::class,
            'inherit_data' => true,
            'validation_groups' => static function (FormInterface $form) {
                if (
                    !$form->get('removeLightBackgroundImage')->isClicked() &&
                    !$form->get('removeDarkBackgroundImage')->isClicked()
                ) {
                    return ['Default', 'upload'];
                }

                return ['Default'];
            },
        ]);
    }

    public function getBlockPrefix(): string {
        return 'background_image';
    }
}
