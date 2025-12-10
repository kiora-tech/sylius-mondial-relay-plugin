<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint to validate GPS coordinates.
 *
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
class ValidCoordinates extends Constraint
{
    public string $invalidLatitudeMessage = 'The latitude "{{ value }}" is not valid. It must be between -90 and 90.';
    public string $invalidLongitudeMessage = 'The longitude "{{ value }}" is not valid. It must be between -180 and 180.';
    public string $invalidFormatMessage = 'The coordinate "{{ value }}" is not in a valid format.';

    public string $type = 'latitude'; // 'latitude' or 'longitude'

    public function __construct(
        string $type = 'latitude',
        ?string $invalidLatitudeMessage = null,
        ?string $invalidLongitudeMessage = null,
        ?string $invalidFormatMessage = null,
        ?array $groups = null,
        mixed $payload = null
    ) {
        parent::__construct([], $groups, $payload);

        $this->type = $type;
        $this->invalidLatitudeMessage = $invalidLatitudeMessage ?? $this->invalidLatitudeMessage;
        $this->invalidLongitudeMessage = $invalidLongitudeMessage ?? $this->invalidLongitudeMessage;
        $this->invalidFormatMessage = $invalidFormatMessage ?? $this->invalidFormatMessage;
    }
}
