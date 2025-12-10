<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Api\Exception;

use RuntimeException;

/**
 * Exception thrown when Mondial Relay API requests fail.
 *
 * This exception wraps Mondial Relay API error codes and provides
 * user-friendly translated error messages.
 */
class MondialRelayApiException extends RuntimeException
{
    /**
     * @var array<int, string> Mapping of Mondial Relay error codes to French messages
     */
    private const ERROR_MESSAGES = [
        0 => 'Mode sandbox actif - Aucune erreur',
        1 => 'Identifiants API invalides. Veuillez vérifier votre configuration.',
        2 => 'Code postal non desservi par Mondial Relay.',
        3 => 'Service Mondial Relay temporairement indisponible. Veuillez réessayer ultérieurement.',
        9 => 'Le poids du colis dépasse les limites autorisées (max 30kg).',
        20 => 'Point relais temporairement inactif.',
        80 => 'Point relais introuvable. Veuillez vérifier l\'identifiant.',
        81 => 'Point relais actuellement saturé. Veuillez sélectionner un autre point.',
    ];

    /**
     * @param int $mondialRelayErrorCode Error code returned by Mondial Relay API
     * @param string|null $customMessage Optional custom message to override default
     * @param array<string, mixed> $context Additional context for debugging
     */
    public function __construct(
        private readonly int $mondialRelayErrorCode,
        ?string $customMessage = null,
        private readonly array $context = [],
        ?\Throwable $previous = null
    ) {
        $message = $customMessage ?? $this->getTranslatedMessage($mondialRelayErrorCode);

        parent::__construct(
            sprintf('[MR Error %d] %s', $mondialRelayErrorCode, $message),
            $mondialRelayErrorCode,
            $previous
        );
    }

    /**
     * Get the Mondial Relay error code.
     */
    public function getMondialRelayErrorCode(): int
    {
        return $this->mondialRelayErrorCode;
    }

    /**
     * Get additional context information about the error.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Check if the error is temporary and can be retried.
     */
    public function isTemporary(): bool
    {
        return in_array($this->mondialRelayErrorCode, [3, 81], true);
    }

    /**
     * Check if the error is related to configuration.
     */
    public function isConfigurationError(): bool
    {
        return in_array($this->mondialRelayErrorCode, [1], true);
    }

    /**
     * Check if the error is related to validation.
     */
    public function isValidationError(): bool
    {
        return in_array($this->mondialRelayErrorCode, [2, 9, 20, 80], true);
    }

    /**
     * Get translated error message for a given error code.
     */
    private function getTranslatedMessage(int $errorCode): string
    {
        return self::ERROR_MESSAGES[$errorCode]
            ?? sprintf('Erreur API Mondial Relay inconnue (code %d)', $errorCode);
    }
}
