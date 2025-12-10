<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class MondialRelayConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('api_key', TextType::class, [
                'label' => 'kiora_sylius_mondial_relay.form.configuration.api_key',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'kiora_sylius_mondial_relay.validation.api_key.not_blank',
                    ]),
                    new Assert\Length([
                        'min' => 8,
                        'minMessage' => 'kiora_sylius_mondial_relay.validation.api_key.min_length',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'kiora_sylius_mondial_relay.form.configuration.api_key_placeholder',
                ],
            ])
            ->add('api_secret', PasswordType::class, [
                'label' => 'kiora_sylius_mondial_relay.form.configuration.api_secret',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'kiora_sylius_mondial_relay.validation.api_secret.not_blank',
                    ]),
                    new Assert\Length([
                        'min' => 8,
                        'minMessage' => 'kiora_sylius_mondial_relay.validation.api_secret.min_length',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'kiora_sylius_mondial_relay.form.configuration.api_secret_placeholder',
                    'autocomplete' => 'off',
                ],
                'always_empty' => false,
            ])
            ->add('brand_id', TextType::class, [
                'label' => 'kiora_sylius_mondial_relay.form.configuration.brand_id',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'kiora_sylius_mondial_relay.validation.brand_id.not_blank',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[A-Z0-9]{2,8}$/',
                        'message' => 'kiora_sylius_mondial_relay.validation.brand_id.invalid_format',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'kiora_sylius_mondial_relay.form.configuration.brand_id_placeholder',
                    'maxlength' => 8,
                ],
                'help' => 'kiora_sylius_mondial_relay.form.configuration.brand_id_help',
            ])
            ->add('sandbox', CheckboxType::class, [
                'label' => 'kiora_sylius_mondial_relay.form.configuration.sandbox',
                'required' => false,
                'help' => 'kiora_sylius_mondial_relay.form.configuration.sandbox_help',
                'data' => $options['data']['sandbox'] ?? true,
            ])
            ->add('default_weight', IntegerType::class, [
                'label' => 'kiora_sylius_mondial_relay.form.configuration.default_weight',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'kiora_sylius_mondial_relay.validation.default_weight.not_blank',
                    ]),
                    new Assert\Range([
                        'min' => 1,
                        'max' => 150000,
                        'notInRangeMessage' => 'kiora_sylius_mondial_relay.validation.default_weight.range',
                    ]),
                ],
                'data' => $options['data']['default_weight'] ?? 1000,
                'attr' => [
                    'min' => 1,
                    'max' => 150000,
                    'placeholder' => '1000',
                ],
                'help' => 'kiora_sylius_mondial_relay.form.configuration.default_weight_help',
            ])
            ->add('default_collection_mode', ChoiceType::class, [
                'label' => 'kiora_sylius_mondial_relay.form.configuration.default_collection_mode',
                'required' => true,
                'choices' => [
                    'kiora_sylius_mondial_relay.collection_mode.24r' => '24R',
                    'kiora_sylius_mondial_relay.collection_mode.rel' => 'REL',
                    'kiora_sylius_mondial_relay.collection_mode.ld1' => 'LD1',
                    'kiora_sylius_mondial_relay.collection_mode.lds' => 'LDS',
                    'kiora_sylius_mondial_relay.collection_mode.hom' => 'HOM',
                ],
                'data' => $options['data']['default_collection_mode'] ?? 'REL',
                'help' => 'kiora_sylius_mondial_relay.form.configuration.default_collection_mode_help',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'kiora_sylius_mondial_relay_configuration';
    }
}
