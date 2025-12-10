<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ValidCoordinatesValidator extends ConstraintValidator
{
    private const MIN_LATITUDE = -90.0;
    private const MAX_LATITUDE = 90.0;
    private const MIN_LONGITUDE = -180.0;
    private const MAX_LONGITUDE = 180.0;

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidCoordinates) {
            throw new UnexpectedTypeException($constraint, ValidCoordinates::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        // Convert to float for validation
        if (is_string($value)) {
            if (!is_numeric($value)) {
                $this->context->buildViolation($constraint->invalidFormatMessage)
                    ->setParameter('{{ value }}', $value)
                    ->addViolation();

                return;
            }

            $value = (float) $value;
        }

        if (!is_float($value) && !is_int($value)) {
            $this->context->buildViolation($constraint->invalidFormatMessage)
                ->setParameter('{{ value }}', (string) $value)
                ->addViolation();

            return;
        }

        $floatValue = (float) $value;

        if ($constraint->type === 'latitude') {
            if ($floatValue < self::MIN_LATITUDE || $floatValue > self::MAX_LATITUDE) {
                $this->context->buildViolation($constraint->invalidLatitudeMessage)
                    ->setParameter('{{ value }}', (string) $value)
                    ->addViolation();
            }
        } elseif ($constraint->type === 'longitude') {
            if ($floatValue < self::MIN_LONGITUDE || $floatValue > self::MAX_LONGITUDE) {
                $this->context->buildViolation($constraint->invalidLongitudeMessage)
                    ->setParameter('{{ value }}', (string) $value)
                    ->addViolation();
            }
        }
    }
}
