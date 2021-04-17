<?php

namespace App\Form\Type;

use App\Entity\Forum;
use App\Entity\UserFlags;
use App\Security\Authentication;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class UserFlagType extends AbstractType {
    /**
     * @var Authentication
     */
    private $authentication;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    public function __construct(
        Authentication $authentication,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->authentication = $authentication;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void {
        if (\count($options['choices']) <= 1) {
            // is there a better way?
            $view->vars['row_attr']['hidden'] = true;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'forum' => null,
            'choices' => [
                'none' => UserFlags::FLAG_NONE,
            ],
            'choice_label' => static function ($key, $name) {
                return "user_flag.{$name}_label";
            },
            'label' => 'user_flag.post_as_label',
        ]);

        $resolver->setAllowedTypes('forum', [Forum::class, 'null']);

        $resolver->setNormalizer('choices', function (Options $options, $choices) {
            if ($options['forum']) {
                $user = $this->authentication->getUser();

                if ($options['forum']->userIsModerator($user, false)) {
                    $choices['moderator'] = UserFlags::FLAG_MODERATOR;
                }
            }

            if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
                $choices['admin'] = UserFlags::FLAG_ADMIN;
            }

            return $choices;
        });
    }

    public function getParent(): string {
        return ChoiceType::class;
    }
}
