<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Api\Exception;

/**
 * Exception thrown when Mondial Relay API authentication fails.
 *
 * This exception indicates invalid API credentials or signature errors.
 * It extends MondialRelayApiException with specific authentication context.
 */
class MondialRelayAuthenticationException extends MondialRelayApiException
{
    /**
     * @param string $message Error message describing the authentication failure
     * @param array<string, mixed> $context Additional context (e.g., API key used, request details)
     */
    public function __construct(
        string $message = 'Échec de l\'authentification API Mondial Relay. Veuillez vérifier vos identifiants.',
        array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            mondialRelayErrorCode: 1, // Authentication error code
            customMessage: $message,
            context: $context,
            previous: $previous
        );
    }
}
