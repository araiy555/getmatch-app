<?php

namespace App\Form\Type;

use App\Form\DataTransformer\ForumTagDataListToStringsTransformer;
use App\Form\DataTransformer\TagArrayToStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

final class ForumTagsType extends AbstractType {
    /**
     * @var TagArrayToStringTransformer
     */
    private $tagArrayToStringTransformer;

    /**
     * @var ForumTagDataListToStringsTransformer
     */
    private $forumTagDataListToStringsTransformer;

    public function __construct(
        TagArrayToStringTransformer $tagArrayToStringTransformer,
        ForumTagDataListToStringsTransformer $forumTagDataListToStringsTransformer
    ) {
        $this->tagArrayToStringTransformer = $tagArrayToStringTransformer;
        $this->forumTagDataListToStringsTransformer = $forumTagDataListToStringsTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder
            ->addModelTransformer($this->tagArrayToStringTransformer)
            ->addModelTransformer($this->forumTagDataListToStringsTransformer)
        ;
    }

    public function getParent(): string {
        return TextareaType::class;
    }
}
