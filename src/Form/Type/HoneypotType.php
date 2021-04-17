<?php

namespace App\Form\Type;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Hidden form field that should never be filled out by the user, only by poorly
 * written bots.
 */
final class HoneypotType extends AbstractType {
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(RequestStack $requestStack, LoggerInterface $logger = null) {
        $this->requestStack = $requestStack;
        $this->logger = $logger ?: new NullLogger();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            if ((string) $event->getData() !== '') {
                $ip = $this->requestStack->getCurrentRequest()->getClientIp();

                $this->logger->info('Honeypot triggered for IP {ip}', [
                    'ip' => $ip,
                ]);

                $event->getForm()->addError(new FormError('Go away, bot'));
            }
        });
    }

    public function getBlockPrefix(): string {
        return 'honeypot';
    }

    public function getParent(): string {
        return TextType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'trim' => false,
            'mapped' => false,
            'required' => false,
        ]);
    }
}
