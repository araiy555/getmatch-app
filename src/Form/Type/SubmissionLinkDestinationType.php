<?php

namespace App\Form\Type;

use App\Entity\Constants\SubmissionLinkDestination;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SubmissionLinkDestinationType extends AbstractType {
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'choices' => [
                'submission_link_destination.url' => SubmissionLinkDestination::URL,
                'submission_link_destination.submission' => SubmissionLinkDestination::SUBMISSION,
            ],
            'label' => 'submission_link_destination.label',
        ]);
    }

    public function getParent(): string {
        return ChoiceType::class;
    }
}
