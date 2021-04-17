<?php

namespace App\Form;

use App\DataObject\CommentData;
use App\Entity\Comment;
use App\Entity\Forum;
use App\Form\Type\HoneypotType;
use App\Form\Type\MarkdownType;
use App\Form\Type\UserFlagType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CommentType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        if ($options['honeypot']) {
            $builder->add('email', HoneypotType::class);
        }

        $builder->add('comment', MarkdownType::class, [
            'label' => 'label.comment',
            'max_chars' => Comment::MAX_BODY_LENGTH,
            'property_path' => 'body',
        ]);

        $builder->add('userFlag', UserFlagType::class, [
            'forum' => $options['forum'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => CommentData::class,
            'forum' => null, // for UserFlagTrait
            'honeypot' => true,
            'validation_groups' => static function (FormInterface $form) {
                $groups = ['Default'];

                if ($form->getData() && $form->getData()->getId()) {
                    $groups[] = 'update';
                } else {
                    $groups[] = 'create';
                }

                return $groups;
            },
        ]);

        $resolver->setAllowedTypes('forum', ['null', Forum::class]); // ditto
        $resolver->setAllowedTypes('honeypot', ['bool']);
    }
}
