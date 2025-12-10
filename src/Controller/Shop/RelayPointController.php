<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Controller\Shop;

use Kiora\SyliusMondialRelayPlugin\Api\Client\MondialRelayApiClientInterface;
use Kiora\SyliusMondialRelayPlugin\Api\DTO\RelayPointSearchCriteria;
use Kiora\SyliusMondialRelayPlugin\Api\Exception\MondialRelayApiException;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Core\Repository\ShipmentRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Shop controller for relay point selection widget.
 *
 * Provides AJAX endpoints for:
 * - Searching relay points based on customer address
 * - Selecting and storing relay point for a shipment
 */
final class RelayPointController extends AbstractController
{
    public function __construct(
        private readonly MondialRelayApiClientInterface $apiClient,
        private readonly ShipmentRepositoryInterface $shipmentRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Search relay points near customer's shipping address.
     *
     * Query parameters:
     * - postalCode: Required postal code
     * - city: Optional city name
     * - countryCode: Required ISO country code (FR, BE, etc.)
     * - latitude: Optional GPS latitude
     * - longitude: Optional GPS longitude
     * - radius: Optional search radius in meters (default: 20000)
     * - limit: Optional max results (default: 20, max: 50)
     *
     * @return JsonResponse Array of relay points with distance, opening hours, etc.
     */
    public function searchAction(Request $request): JsonResponse
    {
        try {
            $criteria = $this->buildSearchCriteriaFromRequest($request);

            $collection = $this->apiClient->findRelayPoints($criteria);

            return $this->json([
                'success' => true,
                'data' => [
                    'relayPoints' => array_map(
                        fn($dto) => $dto->toArray(),
                        $collection->relayPoints
                    ),
                    'total' => $collection->total,
                    'searchCriteria' => [
                        'postalCode' => $criteria->postalCode,
                        'city' => $criteria->city,
                        'countryCode' => $criteria->countryCode,
                        'radius' => $criteria->radius,
                    ],
                ],
            ]);
        } catch (MondialRelayApiException $e) {
            $this->logger->error('Relay point search failed', [
                'error' => $e->getMessage(),
                'query' => $request->query->all(),
            ]);

            return $this->json([
                'success' => false,
                'error' => [
                    'message' => 'Impossible de rechercher les points relais. Veuillez réessayer.',
                    'code' => $e->getMondialRelayErrorCode(),
                ],
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        } catch (BadRequestHttpException $e) {
            return $this->json([
                'success' => false,
                'error' => [
                    'message' => $e->getMessage(),
                    'code' => 'invalid_request',
                ],
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            $this->logger->error('Unexpected error in relay point search', [
                'exception' => $e,
            ]);

            return $this->json([
                'success' => false,
                'error' => [
                    'message' => 'Une erreur inattendue s\'est produite.',
                    'code' => 'internal_error',
                ],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Select and store a relay point for the current order's shipment.
     *
     * POST body:
     * - relayPointId: Mondial Relay point ID
     * - name: Relay point name
     * - street: Street address
     * - postalCode: Postal code
     * - city: City name
     * - countryCode: Country code
     * - latitude: GPS latitude
     * - longitude: GPS longitude
     *
     * @param int $shipmentId Shipment identifier
     */
    public function selectAction(Request $request, int $shipmentId): JsonResponse
    {
        try {
            $shipment = $this->findShipmentForCurrentUser($shipmentId);

            $data = $this->validateSelectionData($request);

            // Store relay point information in shipment
            // This will be implemented using MondialRelayShipmentInterface
            $this->storeRelayPointSelection($shipment, $data);

            $this->logger->info('Relay point selected for shipment', [
                'shipmentId' => $shipmentId,
                'relayPointId' => $data['relayPointId'],
            ]);

            return $this->json([
                'success' => true,
                'data' => [
                    'shipmentId' => $shipmentId,
                    'relayPointId' => $data['relayPointId'],
                    'message' => 'Point relais sélectionné avec succès.',
                ],
            ]);
        } catch (NotFoundHttpException $e) {
            return $this->json([
                'success' => false,
                'error' => [
                    'message' => $e->getMessage(),
                    'code' => 'not_found',
                ],
            ], Response::HTTP_NOT_FOUND);
        } catch (AccessDeniedException $e) {
            return $this->json([
                'success' => false,
                'error' => [
                    'message' => 'Accès non autorisé.',
                    'code' => 'access_denied',
                ],
            ], Response::HTTP_FORBIDDEN);
        } catch (BadRequestHttpException $e) {
            return $this->json([
                'success' => false,
                'error' => [
                    'message' => $e->getMessage(),
                    'code' => 'invalid_request',
                ],
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            $this->logger->error('Unexpected error selecting relay point', [
                'exception' => $e,
                'shipmentId' => $shipmentId,
            ]);

            return $this->json([
                'success' => false,
                'error' => [
                    'message' => 'Une erreur s\'est produite lors de la sélection.',
                    'code' => 'internal_error',
                ],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Build search criteria from request parameters.
     *
     * @throws BadRequestHttpException If required parameters are missing
     */
    private function buildSearchCriteriaFromRequest(Request $request): RelayPointSearchCriteria
    {
        $postalCode = $request->query->get('postalCode');
        $countryCode = $request->query->get('countryCode');

        if (!$postalCode || !$countryCode) {
            throw new BadRequestHttpException('Les paramètres postalCode et countryCode sont requis.');
        }

        $latitude = $request->query->get('latitude');
        $longitude = $request->query->get('longitude');

        // Create criteria with coordinates if available, otherwise with postal code
        if ($latitude && $longitude) {
            $criteria = RelayPointSearchCriteria::fromCoordinates(
                latitude: (float) $latitude,
                longitude: (float) $longitude,
                countryCode: (string) $countryCode
            );
        } else {
            $criteria = RelayPointSearchCriteria::fromPostalCode(
                postalCode: (string) $postalCode,
                countryCode: (string) $countryCode,
                city: $request->query->get('city')
            );
        }

        // Apply optional parameters
        if ($radius = $request->query->get('radius')) {
            $criteria = $criteria->withRadius((int) $radius);
        }

        if ($limit = $request->query->get('limit')) {
            $limit = min((int) $limit, 50); // Cap at 50
            $criteria = $criteria->withLimit($limit);
        }

        return $criteria;
    }

    /**
     * Validate relay point selection data from request.
     *
     * @return array<string, mixed> Validated data
     *
     * @throws BadRequestHttpException If validation fails
     */
    private function validateSelectionData(Request $request): array
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            throw new BadRequestHttpException('Corps de requête JSON invalide.');
        }

        $required = ['relayPointId', 'name', 'street', 'postalCode', 'city', 'countryCode', 'latitude', 'longitude'];

        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                throw new BadRequestHttpException(sprintf('Le champ "%s" est requis.', $field));
            }
        }

        // Validate coordinates
        $latitude = (float) $data['latitude'];
        $longitude = (float) $data['longitude'];

        if ($latitude < -90 || $latitude > 90) {
            throw new BadRequestHttpException('Latitude invalide.');
        }

        if ($longitude < -180 || $longitude > 180) {
            throw new BadRequestHttpException('Longitude invalide.');
        }

        return $data;
    }

    /**
     * Find shipment and verify it belongs to current user's order.
     *
     * @throws NotFoundHttpException If shipment not found
     * @throws AccessDeniedException If shipment doesn't belong to current user
     */
    private function findShipmentForCurrentUser(int $shipmentId): ShipmentInterface
    {
        /** @var ShipmentInterface|null $shipment */
        $shipment = $this->shipmentRepository->find($shipmentId);

        if (!$shipment) {
            throw new NotFoundHttpException('Expédition introuvable.');
        }

        // Get order from shipment
        $order = $shipment->getOrder();

        if (!$order instanceof OrderInterface) {
            throw new NotFoundHttpException('Commande introuvable.');
        }

        // Verify order belongs to current user (in checkout context)
        // In Sylius 2, we check if order is in cart state and belongs to current channel
        if ($order->getState() !== OrderInterface::STATE_CART) {
            throw new AccessDeniedException('Cette expédition n\'est plus modifiable.');
        }

        // Additional security: verify order token matches session
        // This prevents users from selecting relay points for other users' orders
        $tokenValue = $this->getOrderTokenFromSession();
        if ($tokenValue && $order->getTokenValue() !== $tokenValue) {
            throw new AccessDeniedException('Accès non autorisé à cette expédition.');
        }

        return $shipment;
    }

    /**
     * Store relay point selection in shipment.
     *
     * @param array<string, mixed> $data Relay point data
     */
    private function storeRelayPointSelection(ShipmentInterface $shipment, array $data): void
    {
        // This will use MondialRelayShipmentInterface methods
        // For now, we'll store in shipment details as a temporary solution
        // TODO: Implement proper MondialRelayShipmentTrait integration

        /** @var array<string, mixed> $details */
        $details = $shipment->getDetails() ?? [];

        $details['mondial_relay'] = [
            'relay_point_id' => $data['relayPointId'],
            'name' => $data['name'],
            'address' => [
                'street' => $data['street'],
                'postal_code' => $data['postalCode'],
                'city' => $data['city'],
                'country_code' => $data['countryCode'],
            ],
            'coordinates' => [
                'latitude' => (float) $data['latitude'],
                'longitude' => (float) $data['longitude'],
            ],
            'selected_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];

        // Store opening hours if provided
        if (isset($data['openingHours']) && is_array($data['openingHours'])) {
            $details['mondial_relay']['opening_hours'] = $data['openingHours'];
        }

        $shipment->setDetails($details);

        // Persist changes
        $this->shipmentRepository->add($shipment);
    }

    /**
     * Get order token from session.
     *
     * In Sylius 2, the cart token is stored in session to track the current order.
     */
    private function getOrderTokenFromSession(): ?string
    {
        if (!$this->container->has('request_stack')) {
            return null;
        }

        $session = $this->container->get('request_stack')->getSession();

        return $session->get('_sylius_cart_token');
    }
}
