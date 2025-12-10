<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Tests\Unit\Form\Type;

use Kiora\SyliusMondialRelayPlugin\Form\Type\MondialRelayConfigurationType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

final class MondialRelayConfigurationTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        return [
            new ValidatorExtension($validator),
        ];
    }

    public function testSubmitValidData(): void
    {
        $formData = [
            'api_key' => 'test_api_key_12345678',
            'api_secret' => 'test_secret_12345678',
            'brand_id' => 'BDTEST01',
            'sandbox' => true,
            'default_weight' => 1000,
            'default_collection_mode' => 'REL',
        ];

        $form = $this->factory->create(MondialRelayConfigurationType::class);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        $data = $form->getData();

        $this->assertEquals('test_api_key_12345678', $data['api_key']);
        $this->assertEquals('test_secret_12345678', $data['api_secret']);
        $this->assertEquals('BDTEST01', $data['brand_id']);
        $this->assertTrue($data['sandbox']);
        $this->assertEquals(1000, $data['default_weight']);
        $this->assertEquals('REL', $data['default_collection_mode']);
    }

    public function testSubmitInvalidApiKeyTooShort(): void
    {
        $formData = [
            'api_key' => 'short', // Less than 8 characters
            'api_secret' => 'test_secret_12345678',
            'brand_id' => 'BDTEST01',
            'sandbox' => false,
            'default_weight' => 1000,
            'default_collection_mode' => 'REL',
        ];

        $form = $this->factory->create(MondialRelayConfigurationType::class);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertCount(1, $form->get('api_key')->getErrors());
    }

    public function testSubmitInvalidApiSecretTooShort(): void
    {
        $formData = [
            'api_key' => 'test_api_key_12345678',
            'api_secret' => 'short', // Less than 8 characters
            'brand_id' => 'BDTEST01',
            'sandbox' => false,
            'default_weight' => 1000,
            'default_collection_mode' => 'REL',
        ];

        $form = $this->factory->create(MondialRelayConfigurationType::class);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertCount(1, $form->get('api_secret')->getErrors());
    }

    public function testSubmitInvalidBrandIdFormat(): void
    {
        $formData = [
            'api_key' => 'test_api_key_12345678',
            'api_secret' => 'test_secret_12345678',
            'brand_id' => 'invalid-format', // Contains invalid characters
            'sandbox' => false,
            'default_weight' => 1000,
            'default_collection_mode' => 'REL',
        ];

        $form = $this->factory->create(MondialRelayConfigurationType::class);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertCount(1, $form->get('brand_id')->getErrors());
    }

    public function testSubmitEmptyApiKey(): void
    {
        $formData = [
            'api_key' => '',
            'api_secret' => 'test_secret_12345678',
            'brand_id' => 'BDTEST01',
            'sandbox' => false,
            'default_weight' => 1000,
            'default_collection_mode' => 'REL',
        ];

        $form = $this->factory->create(MondialRelayConfigurationType::class);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertGreaterThan(0, $form->get('api_key')->getErrors()->count());
    }

    public function testSubmitEmptyApiSecret(): void
    {
        $formData = [
            'api_key' => 'test_api_key_12345678',
            'api_secret' => '',
            'brand_id' => 'BDTEST01',
            'sandbox' => false,
            'default_weight' => 1000,
            'default_collection_mode' => 'REL',
        ];

        $form = $this->factory->create(MondialRelayConfigurationType::class);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertGreaterThan(0, $form->get('api_secret')->getErrors()->count());
    }

    public function testSubmitEmptyBrandId(): void
    {
        $formData = [
            'api_key' => 'test_api_key_12345678',
            'api_secret' => 'test_secret_12345678',
            'brand_id' => '',
            'sandbox' => false,
            'default_weight' => 1000,
            'default_collection_mode' => 'REL',
        ];

        $form = $this->factory->create(MondialRelayConfigurationType::class);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertGreaterThan(0, $form->get('brand_id')->getErrors()->count());
    }

    public function testSubmitInvalidWeightTooLow(): void
    {
        $formData = [
            'api_key' => 'test_api_key_12345678',
            'api_secret' => 'test_secret_12345678',
            'brand_id' => 'BDTEST01',
            'sandbox' => false,
            'default_weight' => 0, // Must be at least 1
            'default_collection_mode' => 'REL',
        ];

        $form = $this->factory->create(MondialRelayConfigurationType::class);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertCount(1, $form->get('default_weight')->getErrors());
    }

    public function testSubmitInvalidWeightTooHigh(): void
    {
        $formData = [
            'api_key' => 'test_api_key_12345678',
            'api_secret' => 'test_secret_12345678',
            'brand_id' => 'BDTEST01',
            'sandbox' => false,
            'default_weight' => 150001, // Max is 150000
            'default_collection_mode' => 'REL',
        ];

        $form = $this->factory->create(MondialRelayConfigurationType::class);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertCount(1, $form->get('default_weight')->getErrors());
    }

    public function testSandboxDefaultValue(): void
    {
        $form = $this->factory->create(MondialRelayConfigurationType::class, [
            'sandbox' => true,
        ]);

        $this->assertTrue($form->get('sandbox')->getData());
    }

    public function testDefaultWeightDefaultValue(): void
    {
        $form = $this->factory->create(MondialRelayConfigurationType::class, [
            'default_weight' => 1000,
        ]);

        $this->assertEquals(1000, $form->get('default_weight')->getData());
    }

    public function testDefaultCollectionModeDefaultValue(): void
    {
        $form = $this->factory->create(MondialRelayConfigurationType::class, [
            'default_collection_mode' => 'REL',
        ]);

        $this->assertEquals('REL', $form->get('default_collection_mode')->getData());
    }

    public function testValidBrandIdFormats(): void
    {
        $validBrandIds = ['BD', 'BDTEST', 'BD12', 'BDTEST01', '12345678'];

        foreach ($validBrandIds as $brandId) {
            $formData = [
                'api_key' => 'test_api_key_12345678',
                'api_secret' => 'test_secret_12345678',
                'brand_id' => $brandId,
                'sandbox' => false,
                'default_weight' => 1000,
                'default_collection_mode' => 'REL',
            ];

            $form = $this->factory->create(MondialRelayConfigurationType::class);
            $form->submit($formData);

            $this->assertTrue($form->isValid(), "Brand ID '{$brandId}' should be valid");
        }
    }

    public function testInvalidBrandIdFormats(): void
    {
        $invalidBrandIds = ['bd', 'BD test', 'BD-TEST', 'a', 'TOOLONGID'];

        foreach ($invalidBrandIds as $brandId) {
            $formData = [
                'api_key' => 'test_api_key_12345678',
                'api_secret' => 'test_secret_12345678',
                'brand_id' => $brandId,
                'sandbox' => false,
                'default_weight' => 1000,
                'default_collection_mode' => 'REL',
            ];

            $form = $this->factory->create(MondialRelayConfigurationType::class);
            $form->submit($formData);

            $this->assertFalse($form->isValid(), "Brand ID '{$brandId}' should be invalid");
        }
    }

    public function testAllCollectionModesAreValid(): void
    {
        $collectionModes = ['24R', 'REL', 'LD1', 'LDS', 'HOM'];

        foreach ($collectionModes as $mode) {
            $formData = [
                'api_key' => 'test_api_key_12345678',
                'api_secret' => 'test_secret_12345678',
                'brand_id' => 'BDTEST01',
                'sandbox' => false,
                'default_weight' => 1000,
                'default_collection_mode' => $mode,
            ];

            $form = $this->factory->create(MondialRelayConfigurationType::class);
            $form->submit($formData);

            $this->assertTrue($form->isValid(), "Collection mode '{$mode}' should be valid");
            $this->assertEquals($mode, $form->getData()['default_collection_mode']);
        }
    }

    public function testFormView(): void
    {
        $form = $this->factory->create(MondialRelayConfigurationType::class);
        $view = $form->createView();

        $this->assertArrayHasKey('api_key', $view->children);
        $this->assertArrayHasKey('api_secret', $view->children);
        $this->assertArrayHasKey('brand_id', $view->children);
        $this->assertArrayHasKey('sandbox', $view->children);
        $this->assertArrayHasKey('default_weight', $view->children);
        $this->assertArrayHasKey('default_collection_mode', $view->children);
    }

    public function testBlockPrefix(): void
    {
        $form = $this->factory->create(MondialRelayConfigurationType::class);

        $this->assertEquals('kiora_sylius_mondial_relay_configuration', $form->getConfig()->getBlockPrefix());
    }

    public function testCompleteValidSubmission(): void
    {
        $formData = [
            'api_key' => 'my_production_api_key_123',
            'api_secret' => 'my_production_secret_456',
            'brand_id' => 'MRBRAND',
            'sandbox' => false,
            'default_weight' => 2500,
            'default_collection_mode' => '24R',
        ];

        $form = $this->factory->create(MondialRelayConfigurationType::class);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        $data = $form->getData();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('api_key', $data);
        $this->assertArrayHasKey('api_secret', $data);
        $this->assertArrayHasKey('brand_id', $data);
        $this->assertArrayHasKey('sandbox', $data);
        $this->assertArrayHasKey('default_weight', $data);
        $this->assertArrayHasKey('default_collection_mode', $data);

        $this->assertEquals('my_production_api_key_123', $data['api_key']);
        $this->assertEquals('my_production_secret_456', $data['api_secret']);
        $this->assertEquals('MRBRAND', $data['brand_id']);
        $this->assertFalse($data['sandbox']);
        $this->assertEquals(2500, $data['default_weight']);
        $this->assertEquals('24R', $data['default_collection_mode']);
    }

    public function testMinimumValidWeight(): void
    {
        $formData = [
            'api_key' => 'test_api_key_12345678',
            'api_secret' => 'test_secret_12345678',
            'brand_id' => 'BDTEST01',
            'sandbox' => false,
            'default_weight' => 1, // Minimum valid weight
            'default_collection_mode' => 'REL',
        ];

        $form = $this->factory->create(MondialRelayConfigurationType::class);
        $form->submit($formData);

        $this->assertTrue($form->isValid());
        $this->assertEquals(1, $form->getData()['default_weight']);
    }

    public function testMaximumValidWeight(): void
    {
        $formData = [
            'api_key' => 'test_api_key_12345678',
            'api_secret' => 'test_secret_12345678',
            'brand_id' => 'BDTEST01',
            'sandbox' => false,
            'default_weight' => 150000, // Maximum valid weight
            'default_collection_mode' => 'REL',
        ];

        $form = $this->factory->create(MondialRelayConfigurationType::class);
        $form->submit($formData);

        $this->assertTrue($form->isValid());
        $this->assertEquals(150000, $form->getData()['default_weight']);
    }
}
