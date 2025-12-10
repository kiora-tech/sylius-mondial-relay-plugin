<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class MondialRelayShippingGatewayConfigurationType extends AbstractType
{
    /**
     * @var array<string>
     */
    private const SUPPORTED_COUNTRIES = ['FR', 'BE', 'LU', 'NL', 'ES', 'PT', 'DE', 'AT', 'IT'];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('collection_mode', ChoiceType::class, [
                'label' => 'kiora_sylius_mondial_relay.form.shipping_gateway.collection_mode',
                'required' => true,
                'choices' => [
                    'kiora_sylius_mondial_relay.collection_mode.24r' => '24R',
                    'kiora_sylius_mondial_relay.collection_mode.24l' => '24L',
                    'kiora_sylius_mondial_relay.collection_mode.24x' => '24X',
                    'kiora_sylius_mondial_relay.collection_mode.rel' => 'REL',
                    'kiora_sylius_mondial_relay.collection_mode.ld1' => 'LD1',
                    'kiora_sylius_mondial_relay.collection_mode.lds' => 'LDS',
                    'kiora_sylius_mondial_relay.collection_mode.hom' => 'HOM',
                    'kiora_sylius_mondial_relay.collection_mode.drive' => 'DRI',
                ],
                'help' => 'kiora_sylius_mondial_relay.form.shipping_gateway.collection_mode_help',
                'attr' => [
                    'class' => 'ui fluid dropdown',
                ],
            ])
            ->add('max_weight', IntegerType::class, [
                'label' => 'kiora_sylius_mondial_relay.form.shipping_gateway.max_weight',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'kiora_sylius_mondial_relay.validation.max_weight.not_blank',
                    ]),
                    new Assert\Range([
                        'min' => 1,
                        'max' => 150000,
                        'notInRangeMessage' => 'kiora_sylius_mondial_relay.validation.max_weight.range',
                    ]),
                ],
                'data' => $options['data']['max_weight'] ?? 30000,
                'attr' => [
                    'min' => 1,
                    'max' => 150000,
                    'placeholder' => '30000',
                ],
                'help' => 'kiora_sylius_mondial_relay.form.shipping_gateway.max_weight_help',
            ])
            ->add('enabled_countries', CountryType::class, [
                'label' => 'kiora_sylius_mondial_relay.form.shipping_gateway.enabled_countries',
                'required' => true,
                'multiple' => true,
                'choices' => self::SUPPORTED_COUNTRIES,
                'choice_loader' => null,
                'data' => $options['data']['enabled_countries'] ?? ['FR'],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'kiora_sylius_mondial_relay.validation.enabled_countries.not_blank',
                    ]),
                    new Assert\Count([
                        'min' => 1,
                        'minMessage' => 'kiora_sylius_mondial_relay.validation.enabled_countries.min_count',
                    ]),
                    new Assert\Choice([
                        'choices' => self::SUPPORTED_COUNTRIES,
                        'multiple' => true,
                        'message' => 'kiora_sylius_mondial_relay.validation.enabled_countries.invalid_choice',
                    ]),
                ],
                'help' => 'kiora_sylius_mondial_relay.form.shipping_gateway.enabled_countries_help',
                'attr' => [
                    'class' => 'ui fluid multiple search dropdown',
                ],
            ])
            ->add('allow_customer_selection', ChoiceType::class, [
                'label' => 'kiora_sylius_mondial_relay.form.shipping_gateway.allow_customer_selection',
                'required' => true,
                'choices' => [
                    'kiora_sylius_mondial_relay.form.shipping_gateway.customer_selection.enabled' => true,
                    'kiora_sylius_mondial_relay.form.shipping_gateway.customer_selection.disabled' => false,
                ],
                'data' => $options['data']['allow_customer_selection'] ?? true,
                'help' => 'kiora_sylius_mondial_relay.form.shipping_gateway.allow_customer_selection_help',
                'expanded' => true,
            ])
            ->add('max_relay_points', IntegerType::class, [
                'label' => 'kiora_sylius_mondial_relay.form.shipping_gateway.max_relay_points',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'kiora_sylius_mondial_relay.validation.max_relay_points.not_blank',
                    ]),
                    new Assert\Range([
                        'min' => 1,
                        'max' => 20,
                        'notInRangeMessage' => 'kiora_sylius_mondial_relay.validation.max_relay_points.range',
                    ]),
                ],
                'data' => $options['data']['max_relay_points'] ?? 5,
                'attr' => [
                    'min' => 1,
                    'max' => 20,
                ],
                'help' => 'kiora_sylius_mondial_relay.form.shipping_gateway.max_relay_points_help',
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
        return 'kiora_sylius_mondial_relay_shipping_gateway';
    }
}
