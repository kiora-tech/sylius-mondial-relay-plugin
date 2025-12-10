<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Controller\Admin;

use Kiora\SyliusMondialRelayPlugin\Service\MondialRelayLabelGeneratorInterface;
use Kiora\SyliusMondialRelayPlugin\Service\MondialRelayQrCodeGeneratorInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Core\Repository\ShipmentRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ShipmentController extends AbstractController
{
    public function __construct(
        private readonly ShipmentRepositoryInterface $shipmentRepository,
        private readonly MondialRelayLabelGeneratorInterface $labelGenerator,
        private readonly MondialRelayQrCodeGeneratorInterface $qrCodeGenerator,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function generateLabelAction(Request $request, int $shipmentId): Response
    {
        $shipment = $this->findShipmentOr404($shipmentId);

        try {
            $result = $this->labelGenerator->generateLabel($shipment);

            if (!$result['success']) {
                $this->addFlash('error', $this->translator->trans(
                    'kiora_sylius_mondial_relay.ui.shipment.label_generation_failed',
                    ['%error%' => $result['error'] ?? 'Unknown error']
                ));

                return $this->redirectToShipmentDetail($shipment);
            }

            $this->addFlash('success', $this->translator->trans(
                'kiora_sylius_mondial_relay.ui.shipment.label_generated_successfully'
            ));

            return $this->redirectToShipmentDetail($shipment);
        } catch (\Exception $e) {
            $this->addFlash('error', $this->translator->trans(
                'kiora_sylius_mondial_relay.ui.shipment.label_generation_exception',
                ['%error%' => $e->getMessage()]
            ));

            return $this->redirectToShipmentDetail($shipment);
        }
    }

    public function downloadLabelAction(int $shipmentId): Response
    {
        $shipment = $this->findShipmentOr404($shipmentId);

        try {
            $labelPath = $this->labelGenerator->getLabelPath($shipment);

            if (null === $labelPath || !file_exists($labelPath)) {
                throw new NotFoundHttpException('Label not found. Please generate it first.');
            }

            $response = new BinaryFileResponse($labelPath);
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                sprintf('mondial-relay-label-%s.pdf', $shipment->getId())
            );

            return $response;
        } catch (NotFoundHttpException $e) {
            $this->addFlash('error', $this->translator->trans(
                'kiora_sylius_mondial_relay.ui.shipment.label_not_found'
            ));

            return $this->redirectToShipmentDetail($shipment);
        } catch (\Exception $e) {
            $this->addFlash('error', $this->translator->trans(
                'kiora_sylius_mondial_relay.ui.shipment.label_download_exception',
                ['%error%' => $e->getMessage()]
            ));

            return $this->redirectToShipmentDetail($shipment);
        }
    }

    public function generateQrCodeAction(Request $request, int $shipmentId): Response
    {
        if ($request->isXmlHttpRequest()) {
            return $this->handleAjaxQrCodeGeneration($shipmentId);
        }

        $shipment = $this->findShipmentOr404($shipmentId);

        try {
            $result = $this->qrCodeGenerator->generateQrCode($shipment);

            if (!$result['success']) {
                $this->addFlash('error', $this->translator->trans(
                    'kiora_sylius_mondial_relay.ui.shipment.qr_code_generation_failed',
                    ['%error%' => $result['error'] ?? 'Unknown error']
                ));

                return $this->redirectToShipmentDetail($shipment);
            }

            $this->addFlash('success', $this->translator->trans(
                'kiora_sylius_mondial_relay.ui.shipment.qr_code_generated_successfully'
            ));

            return $this->redirectToShipmentDetail($shipment);
        } catch (\Exception $e) {
            $this->addFlash('error', $this->translator->trans(
                'kiora_sylius_mondial_relay.ui.shipment.qr_code_generation_exception',
                ['%error%' => $e->getMessage()]
            ));

            return $this->redirectToShipmentDetail($shipment);
        }
    }

    private function handleAjaxQrCodeGeneration(int $shipmentId): JsonResponse
    {
        try {
            $shipment = $this->findShipmentOr404($shipmentId);
            $result = $this->qrCodeGenerator->generateQrCode($shipment);

            if (!$result['success']) {
                return new JsonResponse([
                    'success' => false,
                    'message' => $this->translator->trans(
                        'kiora_sylius_mondial_relay.ui.shipment.qr_code_generation_failed',
                        ['%error%' => $result['error'] ?? 'Unknown error']
                    ),
                ], Response::HTTP_BAD_REQUEST);
            }

            return new JsonResponse([
                'success' => true,
                'message' => $this->translator->trans(
                    'kiora_sylius_mondial_relay.ui.shipment.qr_code_generated_successfully'
                ),
                'data' => [
                    'qr_code_url' => $result['qr_code_url'] ?? null,
                    'tracking_number' => $result['tracking_number'] ?? null,
                ],
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $this->translator->trans(
                    'kiora_sylius_mondial_relay.ui.shipment.qr_code_generation_exception',
                    ['%error%' => $e->getMessage()]
                ),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function findShipmentOr404(int $shipmentId): ShipmentInterface
    {
        $shipment = $this->shipmentRepository->find($shipmentId);

        if (null === $shipment) {
            throw new NotFoundHttpException(sprintf('Shipment with ID %d not found', $shipmentId));
        }

        return $shipment;
    }

    private function redirectToShipmentDetail(ShipmentInterface $shipment): Response
    {
        $order = $shipment->getOrder();

        if (null === $order) {
            return $this->redirectToRoute('sylius_admin_shipment_index');
        }

        return $this->redirectToRoute('sylius_admin_order_show', [
            'id' => $order->getId(),
        ]);
    }
}
