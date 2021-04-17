<?php

namespace App\Form\Type;

use App\Entity\Forum;
use App\Repository\ForumRepository;
use App\Security\Authentication;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\ChoiceList;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ForumSelectorType extends AbstractType {
    /**
     * @var ForumRepository
     */
    private $forums;

    /**
     * @var Authentication
     */
    private $authentication;

    public function __construct(ForumRepository $forums, Authentication $authentication) {
        $this->forums = $forums;
        $this->authentication = $authentication;
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void {
        $view->vars['attr']['data-forum-selector'] = true;
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $user = $this->authentication->getUser();

        $cacheKey = sprintf('%d', $user ? $user->getId() : 0);

        $isSubscribed = function (Forum $forum) use (&$subsCache, $user): bool {
            if (!isset($subsCache)) {
                $subsCache = $user ? $this->forums->findSubscribedForumNames($user) : null;
            }

            return isset($subsCache[$forum->getId()]);
        };

        $resolver->setDefaults([
            'choice_attr' => ChoiceList::attr($this, static function (Forum $forum) use ($isSubscribed): array {
                $attr['data-featured'] = $forum->isFeatured();
                $attr['data-subscribed'] = $isSubscribed($forum);
                $attr['data-name'] = $forum->getName();

                return $attr;
            }, $cacheKey),
            'choice_label' => ChoiceList::label($this, static function (Forum $forum) use ($isSubscribed) {
                $label = $forum->getName();

                if ($forum->isFeatured()) {
                    $label .= " \u{2B50}"; // star
                }

                if ($isSubscribed($forum)) {
                    $label .= " \u{2764}\u{FE0F}"; // heart
                }

                return trim($label);
            }, $cacheKey),
            'choice_loader' => ChoiceList::lazy($this, function () use ($isSubscribed): array {
                $forums = $this->forums->findAll();

                usort($forums, static function (Forum $a, Forum $b) use ($isSubscribed): int {
                    return $isSubscribed($b) <=> $isSubscribed($a)
                        ?: $b->isFeatured() <=> $a->isFeatured()
                        ?: $a->getNormalizedName() <=> $b->getNormalizedName();
                });

                return $forums;
            }, $cacheKey),
            'choice_translation_domain' => false,
            'choice_value' => ChoiceList::value($this, static function (?Forum $forum): string {
                return (string) ($forum ? $forum->getId() : '');
            }, $cacheKey),
            'placeholder' => 'placeholder.choose_one',
        ]);
    }

    public function getParent(): string {
        return ChoiceType::class;
    }
}
