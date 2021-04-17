<?php

namespace App\Form;

use App\DataObject\SubmissionData;
use App\Entity\Submission;
use App\Form\EventListener\SubmissionImageListener;
use App\Form\Type\ForumSelectorType;
use App\Form\Type\HoneypotType;
use App\Form\Type\MarkdownType;
use App\Form\Type\UserFlagType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\Image as ImageConstraint;

final class SubmissionType extends AbstractType {
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var SubmissionImageListener
     */
    private $submissionImageListener;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        SubmissionImageListener $submissionImageListener
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->submissionImageListener = $submissionImageListener;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void {
        if ($options['honeypot']) {
            $builder->add('email', HoneypotType::class);
        }

        $data = $builder->getData();
        \assert($data instanceof SubmissionData);

        $editing = $data->getId() !== null;

        $builder
            ->add('title', TextareaType::class, [
                'label' => 'label.title',
                'max_chars' => Submission::MAX_TITLE_LENGTH,
            ])

            ->add('body', MarkdownType::class, [
                'label' => 'label.body',
                'max_chars' => Submission::MAX_BODY_LENGTH,
                'required' => false,
            ]);

        if (!$editing || $data->getMediaType() === Submission::MEDIA_URL) {
            $builder->add('url', UrlType::class, [
                'label' => 'label.url',
                // TODO: indicate that this check must be 8-bit
                'max_chars' => Submission::MAX_URL_LENGTH,
                'required' => false,
            ]);
        }

        if (!$editing && $this->authorizationChecker->isGranted('upload_image')) {
            $builder
                ->add('mediaType', ChoiceType::class, [
                    'choices' => [
                        'label.url' => Submission::MEDIA_URL,
                        'label.image' => Submission::MEDIA_IMAGE,
                    ],
                    'choice_name' => static function ($key) {
                        return $key;
                    },
                    'data' => Submission::MEDIA_URL,
                    'expanded' => true,
                    'label' => 'label.media_type',
                ])
                ->add('image', FileType::class, [
                    'constraints' => [
                        new ImageConstraint([
                            'detectCorrupted' => true,
                            'groups' => 'image',
                            'mimeTypes' => ['image/jpeg', 'image/gif', 'image/png'],
                        ]),
                    ],
                    'label' => 'label.upload_image',
                    'mapped' => false,
                    'required' => false,
                ]);

            $builder->addEventSubscriber($this->submissionImageListener);
        }

        if (!$editing) {
            $builder->add('forum', ForumSelectorType::class, [
                'label' => 'label.forum',
            ]);
        }

        $builder->add('userFlag', UserFlagType::class, [
            'forum' => $options['forum'] ?? null,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => SubmissionData::class,
            'honeypot' => true,
            'validation_groups' => function (FormInterface $form) {
                return $this->getValidationGroups($form);
            },
        ]);

        $resolver->setAllowedTypes('honeypot', ['bool']);
    }

    private function getValidationGroups(FormInterface $form): array {
        $groups = ['Default'];
        $whitelisted = $this->authorizationChecker->isGranted('ROLE_WHITELISTED');
        $editing = $form->getData() && $form->getData()->getId();

        if (!$editing) {
            $groups[] = 'create';

            if (!$whitelisted) {
                $groups[] = 'unwhitelisted_user_create';
            }

            if ($form->has('mediaType')) {
                $groups[] = 'media';

                $mediaType = $form->get('mediaType')->getData();

                if (\in_array($mediaType, Submission::MEDIA_TYPES, true)) {
                    $groups[] = $mediaType;
                }
            } else {
                $groups[] = 'url';
            }
        } else {
            $groups[] = 'update';

            if ($form->getData()->getMediaType() === Submission::MEDIA_URL) {
                $groups[] = 'url';
            }

            if (!$whitelisted) {
                $groups[] = 'unwhitelisted_user_update';
            }
        }

        return $groups;
    }
}
