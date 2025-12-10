<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Api\Client;

use Kiora\SyliusMondialRelayPlugin\Api\DTO\RelayPointCollection;
use Kiora\SyliusMondialRelayPlugin\Api\DTO\RelayPointDTO;
use Kiora\SyliusMondialRelayPlugin\Api\DTO\RelayPointSearchCriteria;
use Kiora\SyliusMondialRelayPlugin\Api\Exception\MondialRelayApiException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * SOAP client for Mondial Relay API v1.
 *
 * Uses the legacy SOAP API for relay point search functionality.
 * This API is required because the REST API v2 (Connect API) does not
 * provide relay point search endpoints.
 *
 * @see https://api.mondialrelay.com/Web_Services.asmx?WSDL
 */
final class MondialRelaySoapClient
{
    private const WSDL_URL = 'https://api.mondialrelay.com/Web_Services.asmx?WSDL';

    private ?\SoapClient $soapClient = null;

    public function __construct(
        private readonly string $enseigne,
        private readonly string $privateKey,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Search for relay points near a location.
     *
     * Uses WSI4_PointRelais_Recherche SOAP method.
     */
    public function findRelayPoints(RelayPointSearchCriteria $criteria): RelayPointCollection
    {
        $params = [
            'Enseigne' => $this->enseigne,
            'Pays' => $criteria->countryCode,
            'NumPointRelais' => '',
            'Ville' => $criteria->city ?? '',
            'CP' => $criteria->postalCode ?? '',
            'Latitude' => $criteria->latitude ? (string) $criteria->latitude : '',
            'Longitude' => $criteria->longitude ? (string) $criteria->longitude : '',
            'Taille' => '',
            'Poids' => $criteria->weight ? (string) $criteria->weight : '',
            'Action' => $criteria->deliveryMode ?? '',
            'DelaiEnvoi' => '0',
            'RayonRecherche' => $criteria->radius ? (string) ($criteria->radius * 1000) : '', // Convert km to meters
            'TypeActivite' => '',
            'NACE' => '',
            'NombreResultats' => (string) $criteria->limit,
        ];

        // Generate security hash
        $params['Security'] = $this->generateSecurityHash($params);

        $this->getLogger()->debug('Mondial Relay SOAP request: WSI4_PointRelais_Recherche', [
            'params' => array_diff_key($params, ['Security' => '']),
        ]);

        try {
            $client = $this->getSoapClient();
            $response = $client->WSI4_PointRelais_Recherche($params);

            $result = $response->WSI4_PointRelais_RechercheResult ?? null;

            if ($result === null) {
                throw new MondialRelayApiException(
                    mondialRelayErrorCode: 99,
                    customMessage: 'Réponse SOAP vide'
                );
            }

            // Check for API errors
            $stat = $result->STAT ?? '99';
            if ($stat !== '0') {
                throw new MondialRelayApiException(
                    mondialRelayErrorCode: (int) $stat,
                    customMessage: $this->getErrorMessage($stat)
                );
            }

            // Parse relay points
            $relayPoints = [];
            $pointsRelais = $result->PointsRelais->PointRelais_Details ?? [];

            // Handle single result (not in array)
            if (!is_array($pointsRelais) && is_object($pointsRelais)) {
                $pointsRelais = [$pointsRelais];
            }

            foreach ($pointsRelais as $point) {
                $relayPoints[] = $this->parseRelayPoint($point);
            }

            $this->getLogger()->debug('Mondial Relay SOAP response', [
                'stat' => $stat,
                'count' => count($relayPoints),
            ]);

            return new RelayPointCollection($relayPoints, count($relayPoints));
        } catch (MondialRelayApiException $e) {
            throw $e;
        } catch (\SoapFault $e) {
            $this->getLogger()->error('Mondial Relay SOAP fault', [
                'faultCode' => $e->faultcode,
                'faultString' => $e->faultstring,
            ]);

            throw new MondialRelayApiException(
                mondialRelayErrorCode: 3,
                customMessage: 'Erreur de communication SOAP: ' . $e->faultstring,
                previous: $e
            );
        } catch (\Exception $e) {
            $this->getLogger()->error('Mondial Relay SOAP error', [
                'error' => $e->getMessage(),
            ]);

            throw new MondialRelayApiException(
                mondialRelayErrorCode: 3,
                customMessage: 'Erreur inattendue: ' . $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * Get detailed information about a specific relay point.
     *
     * Uses WSI2_DetailPointRelais SOAP method.
     */
    public function getRelayPoint(string $relayPointId, string $countryCode): ?RelayPointDTO
    {
        $params = [
            'Enseigne' => $this->enseigne,
            'Pays' => $countryCode,
            'NumPointRelais' => $relayPointId,
        ];

        $params['Security'] = $this->generateSecurityHash($params);

        try {
            $client = $this->getSoapClient();
            $response = $client->WSI2_DetailPointRelais($params);

            $result = $response->WSI2_DetailPointRelaisResult ?? null;

            if ($result === null) {
                return null;
            }

            $stat = $result->STAT ?? '99';
            if ($stat !== '0') {
                if ($stat === '24') {
                    // Point relais not found
                    return null;
                }
                throw new MondialRelayApiException(
                    mondialRelayErrorCode: (int) $stat,
                    customMessage: $this->getErrorMessage($stat)
                );
            }

            return $this->parseRelayPoint($result);
        } catch (MondialRelayApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->getLogger()->error('Error getting relay point details', [
                'relayPointId' => $relayPointId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Parse a relay point from SOAP response.
     */
    private function parseRelayPoint(object $data): RelayPointDTO
    {
        return new RelayPointDTO(
            relayPointId: trim($data->Num ?? ''),
            name: trim($data->LgAdr1 ?? ''),
            street: trim($data->LgAdr3 ?? ''),
            postalCode: trim($data->CP ?? ''),
            city: trim($data->Ville ?? ''),
            countryCode: trim($data->Pays ?? ''),
            latitude: isset($data->Latitude) ? (float) str_replace(',', '.', $data->Latitude) : 0.0,
            longitude: isset($data->Longitude) ? (float) str_replace(',', '.', $data->Longitude) : 0.0,
            distanceMeters: isset($data->Distance) ? (int) $data->Distance : null,
            openingHours: $this->parseOpeningHoursForDto($data),
            services: [],
            photoUrl: isset($data->URL_Photo) ? trim($data->URL_Photo) : null,
            informations: isset($data->Information) ? trim($data->Information) : null,
            isActive: true,
            exceptionalClosures: [],
        );
    }

    /**
     * Parse opening hours from SOAP response for DTO format.
     *
     * Converts Mondial Relay format to DTO expected format:
     * array<string, array<int, array{open: string, close: string}>>
     *
     * @return array<string, array<int, array{open: string, close: string}>>
     */
    private function parseOpeningHoursForDto(object $data): array
    {
        $dayMappings = [
            'monday' => 'Horaires_Lundi',
            'tuesday' => 'Horaires_Mardi',
            'wednesday' => 'Horaires_Mercredi',
            'thursday' => 'Horaires_Jeudi',
            'friday' => 'Horaires_Vendredi',
            'saturday' => 'Horaires_Samedi',
            'sunday' => 'Horaires_Dimanche',
        ];

        $openingHours = [];

        foreach ($dayMappings as $englishDay => $fieldName) {
            if (isset($data->$fieldName)) {
                // Handle both array and single string formats
                $hoursData = $data->$fieldName;
                $hoursString = '';

                if (is_object($hoursData) && isset($hoursData->string)) {
                    $strings = $hoursData->string;
                    if (is_array($strings)) {
                        $hoursString = implode(' ', $strings);
                    } else {
                        $hoursString = (string) $strings;
                    }
                } elseif (is_string($hoursData)) {
                    $hoursString = $hoursData;
                }

                $slots = $this->parseHoursStringToSlots(trim($hoursString));
                if (!empty($slots)) {
                    $openingHours[$englishDay] = $slots;
                }
            }
        }

        return $openingHours;
    }

    /**
     * Parse hours string from Mondial Relay format to slot array.
     *
     * Format: "0830 1200 1400 1900" (morning open, morning close, afternoon open, afternoon close)
     *
     * @return array<int, array{open: string, close: string}>
     */
    private function parseHoursStringToSlots(string $hours): array
    {
        if (empty($hours) || $hours === '0000 0000 0000 0000') {
            return [];
        }

        $parts = explode(' ', $hours);
        if (count($parts) < 4) {
            return [];
        }

        $slots = [];

        // Morning slot
        if ($parts[0] !== '0000' && $parts[1] !== '0000') {
            $slots[] = [
                'open' => sprintf('%s:%s', substr($parts[0], 0, 2), substr($parts[0], 2, 2)),
                'close' => sprintf('%s:%s', substr($parts[1], 0, 2), substr($parts[1], 2, 2)),
            ];
        }

        // Afternoon slot
        if ($parts[2] !== '0000' && $parts[3] !== '0000') {
            $slots[] = [
                'open' => sprintf('%s:%s', substr($parts[2], 0, 2), substr($parts[2], 2, 2)),
                'close' => sprintf('%s:%s', substr($parts[3], 0, 2), substr($parts[3], 2, 2)),
            ];
        }

        return $slots;
    }

    /**
     * Generate security hash for Mondial Relay SOAP API.
     *
     * The hash is MD5 of concatenated parameters values + private key.
     *
     * @param array<string, string> $params
     */
    private function generateSecurityHash(array $params): string
    {
        // Remove Security if present
        unset($params['Security']);

        // Concatenate all values
        $hashString = implode('', $params);

        // Add private key
        $hashString .= $this->privateKey;

        return strtoupper(md5($hashString));
    }

    /**
     * Get human-readable error message for Mondial Relay error codes.
     */
    private function getErrorMessage(string $code): string
    {
        $messages = [
            '0' => 'Opération effectuée avec succès',
            '1' => 'Enseigne invalide',
            '2' => 'Numéro d\'enseigne vide ou inexistant',
            '3' => 'Compte enseigne non actif',
            '5' => 'Numéro de Compte enseigne non autorisé',
            '7' => 'Numéro de client invalide (non spécifié)',
            '8' => 'Erreur SQL',
            '9' => 'Enseigne non autorisée',
            '10' => 'Expédition non autorisée',
            '11' => 'Numéro de compte enseigne invalide',
            '12' => 'Pays de livraison non autorisé',
            '20' => 'Poids du colis invalide',
            '21' => 'Taille du colis invalide',
            '22' => 'Taille + Poids du colis invalide',
            '24' => 'Numéro de Point Relais invalide',
            '25' => 'Numéro de Point Relais non renseigné',
            '26' => 'Point Relais indisponible',
            '27' => 'Pays Point Relais invalide',
            '28' => 'Poids ou Taille du colis invalide pour ce Point Relais',
            '29' => 'Point Relais non autorisé',
            '30' => 'Expédition non créée',
            '31' => 'Colis inexistant',
            '32' => 'Colis déjà existant',
            '33' => 'Expédition trop ancienne',
            '34' => 'Code de suivi invalide',
            '35' => 'Plus de 200 colis dans la recherche',
            '36' => 'Dates de recherche invalides',
            '37' => 'Plage de dates trop grande',
            '38' => 'Texte trop long',
            '39' => 'Texte de notification trop long',
            '40' => 'Adresse invalide',
            '44' => 'Nombre de jours avant livraison invalide',
            '45' => 'Nombre de jours avant disponibilité invalide',
            '46' => 'Instruction de livraison invalide',
            '47' => 'Enseigne de retour non autorisée',
            '48' => 'Mode de collecte invalide',
            '49' => 'Mode de livraison invalide',
            '60' => 'Code Pays invalide',
            '61' => 'Ville invalide',
            '62' => 'Code Postal invalide',
            '63' => 'Adresse invalide',
            '64' => 'Adresse1 invalide',
            '65' => 'Adresse2 invalide',
            '66' => 'Nom invalide',
            '67' => 'Prénom invalide',
            '68' => 'Adresse non trouvée par Street Matching',
            '69' => 'Rayon de recherche trop élevé',
            '70' => 'Données manquantes pour la recherche',
            '71' => 'Coordonnées GPS invalides',
            '74' => 'Langue invalide',
            '78' => 'Mode de collecte invalide pour les retours',
            '79' => 'Assurance non autorisée',
            '80' => 'Code tracing invalide',
            '81' => 'Code postal invalide',
            '82' => 'Ville invalide',
            '83' => 'Pays invalide',
            '84' => 'Numéro de téléphone invalide',
            '85' => 'Adresse e-mail invalide',
            '86' => 'Code postal invalide pour le pays',
            '87' => 'Format de téléphone invalide',
            '88' => 'Numéro de mobile invalide',
            '89' => 'Format de mobile invalide',
            '90' => 'Pas de Point Relais dans la zone',
            '94' => 'Le Pays du destinataire n\'est pas autorisé par l\'enseigne',
            '95' => 'Numéro de compte incorrect',
            '96' => 'Paramètre Action invalide',
            '97' => 'Clé de sécurité invalide',
            '98' => 'Erreur de service',
            '99' => 'Erreur générique',
        ];

        return $messages[$code] ?? 'Erreur inconnue (code ' . $code . ')';
    }

    /**
     * Get or create SOAP client.
     */
    private function getSoapClient(): \SoapClient
    {
        if ($this->soapClient === null) {
            $this->soapClient = new \SoapClient(self::WSDL_URL, [
                'trace' => true,
                'exceptions' => true,
                'cache_wsdl' => WSDL_CACHE_BOTH,
                'connection_timeout' => 30,
                'encoding' => 'UTF-8',
            ]);
        }

        return $this->soapClient;
    }

    private function getLogger(): LoggerInterface
    {
        return $this->logger ?? new NullLogger();
    }
}
