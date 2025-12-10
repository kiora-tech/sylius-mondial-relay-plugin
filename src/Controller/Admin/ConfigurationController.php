<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Controller\Admin;

use Kiora\SyliusMondialRelayPlugin\Form\Type\MondialRelayConfigurationType;
use Kiora\SyliusMondialRelayPlugin\Service\MondialRelayApiV2Service;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfigurationFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ConfigurationController extends AbstractController
{
    public function __construct(
        private readonly RequestConfigurationFactoryInterface $requestConfigurationFactory,
        private readonly TranslatorInterface $translator,
        private readonly MondialRelayApiV2Service $mondialRelayApiV2Service,
        private readonly string $configFilePath,
    ) {
    }

    public function indexAction(Request $request): Response
    {
        $configuration = $this->loadConfiguration();

        $form = $this->createForm(MondialRelayConfigurationType::class, $configuration);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            try {
                $this->saveConfiguration($data);

                $this->addFlash(
                    'success',
                    $this->translator->trans('kiora_sylius_mondial_relay.ui.configuration_saved_successfully')
                );

                return $this->redirectToRoute('kiora_sylius_mondial_relay_admin_configuration_index');
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    $this->translator->trans('kiora_sylius_mondial_relay.ui.configuration_save_error', [
                        '%error%' => $e->getMessage(),
                    ])
                );
            }
        }

        return $this->render('@KioraSyliusMondialRelayPlugin/admin/configuration/index.html.twig', [
            'form' => $form->createView(),
            'configuration' => $configuration,
        ]);
    }

    public function testConnectionAction(Request $request): JsonResponse
    {
        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException('This endpoint only accepts AJAX requests');
        }

        $apiKey = $request->request->get('api_key');
        $apiSecret = $request->request->get('api_secret');
        $brandId = $request->request->get('brand_id');
        $sandbox = $request->request->getBoolean('sandbox', true);

        if (empty($apiKey) || empty($apiSecret) || empty($brandId)) {
            return new JsonResponse([
                'success' => false,
                'message' => $this->translator->trans('kiora_sylius_mondial_relay.ui.test_connection.missing_credentials'),
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Test API connection with provided credentials
            $result = $this->mondialRelayApiV2Service->testConnection($apiKey, $apiSecret, $brandId, $sandbox);

            if ($result['success']) {
                return new JsonResponse([
                    'success' => true,
                    'message' => $this->translator->trans('kiora_sylius_mondial_relay.ui.test_connection.success'),
                    'data' => $result['data'] ?? [],
                ]);
            }

            return new JsonResponse([
                'success' => false,
                'message' => $this->translator->trans('kiora_sylius_mondial_relay.ui.test_connection.failed', [
                    '%error%' => $result['error'] ?? 'Unknown error',
                ]),
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $this->translator->trans('kiora_sylius_mondial_relay.ui.test_connection.exception', [
                    '%error%' => $e->getMessage(),
                ]),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function loadConfiguration(): array
    {
        if (!file_exists($this->configFilePath)) {
            return [
                'api_key' => '',
                'api_secret' => '',
                'brand_id' => '',
                'sandbox' => true,
                'default_weight' => 1000,
                'default_collection_mode' => 'REL',
            ];
        }

        $content = file_get_contents($this->configFilePath);
        if (false === $content) {
            throw new \RuntimeException(sprintf('Cannot read configuration file: %s', $this->configFilePath));
        }

        $config = json_decode($content, true);
        if (null === $config || !is_array($config)) {
            throw new \RuntimeException('Invalid configuration file format');
        }

        return $config;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function saveConfiguration(array $data): void
    {
        $directory = dirname($this->configFilePath);
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $directory));
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (false === $json) {
            throw new \RuntimeException('Cannot encode configuration to JSON');
        }

        if (false === file_put_contents($this->configFilePath, $json)) {
            throw new \RuntimeException(sprintf('Cannot write configuration file: %s', $this->configFilePath));
        }
    }
}
