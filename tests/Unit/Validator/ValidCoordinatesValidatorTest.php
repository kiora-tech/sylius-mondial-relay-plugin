<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Tests\Unit\Validator;

use Kiora\SyliusMondialRelayPlugin\Validator\Constraints\ValidCoordinates;
use Kiora\SyliusMondialRelayPlugin\Validator\Constraints\ValidCoordinatesValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

final class ValidCoordinatesValidatorTest extends TestCase
{
    private ValidCoordinatesValidator $validator;
    private ExecutionContextInterface $context;
    private ConstraintViolationBuilderInterface $violationBuilder;

    protected function setUp(): void
    {
        $this->validator = new ValidCoordinatesValidator();
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);

        $this->validator->initialize($this->context);
    }

    public function testValidateThrowsExceptionWithWrongConstraintType(): void
    {
        $constraint = $this->createMock(Constraint::class);

        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(48.8566, $constraint);
    }

    public function testValidateWithNullValue(): void
    {
        $constraint = new ValidCoordinates(type: 'latitude');

        $this->context
            ->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate(null, $constraint);
    }

    public function testValidateWithEmptyString(): void
    {
        $constraint = new ValidCoordinates(type: 'latitude');

        $this->context
            ->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate('', $constraint);
    }

    public function testValidLatitudeAsFloat(): void
    {
        $constraint = new ValidCoordinates(type: 'latitude');

        $this->context
            ->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate(48.8566, $constraint);
    }

    public function testValidLatitudeAsString(): void
    {
        $constraint = new ValidCoordinates(type: 'latitude');

        $this->context
            ->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate('48.8566', $constraint);
    }

    public function testValidLatitudeAsInteger(): void
    {
        $constraint = new ValidCoordinates(type: 'latitude');

        $this->context
            ->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate(48, $constraint);
    }

    public function testValidLongitudeAsFloat(): void
    {
        $constraint = new ValidCoordinates(type: 'longitude');

        $this->context
            ->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate(2.3522, $constraint);
    }

    public function testValidLongitudeAsString(): void
    {
        $constraint = new ValidCoordinates(type: 'longitude');

        $this->context
            ->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate('2.3522', $constraint);
    }

    public function testInvalidLatitudeTooHigh(): void
    {
        $constraint = new ValidCoordinates(type: 'latitude');

        $this->violationBuilder
            ->expects($this->once())
            ->method('setParameter')
            ->with('{{ value }}', '91')
            ->willReturnSelf();

        $this->violationBuilder
            ->expects($this->once())
            ->method('addViolation');

        $this->context
            ->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->invalidLatitudeMessage)
            ->willReturn($this->violationBuilder);

        $this->validator->validate(91.0, $constraint);
    }

    public function testInvalidLatitudeTooLow(): void
    {
        $constraint = new ValidCoordinates(type: 'latitude');

        $this->violationBuilder
            ->expects($this->once())
            ->method('setParameter')
            ->with('{{ value }}', '-91')
            ->willReturnSelf();

        $this->violationBuilder
            ->expects($this->once())
            ->method('addViolation');

        $this->context
            ->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->invalidLatitudeMessage)
            ->willReturn($this->violationBuilder);

        $this->validator->validate(-91.0, $constraint);
    }

    public function testValidLatitudeAtBoundaries(): void
    {
        $constraint = new ValidCoordinates(type: 'latitude');

        $this->context
            ->expects($this->never())
            ->method('buildViolation');

        // Test upper boundary
        $this->validator->validate(90.0, $constraint);

        // Test lower boundary
        $this->validator->validate(-90.0, $constraint);
    }

    public function testInvalidLongitudeTooHigh(): void
    {
        $constraint = new ValidCoordinates(type: 'longitude');

        $this->violationBuilder
            ->expects($this->once())
            ->method('setParameter')
            ->with('{{ value }}', '181')
            ->willReturnSelf();

        $this->violationBuilder
            ->expects($this->once())
            ->method('addViolation');

        $this->context
            ->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->invalidLongitudeMessage)
            ->willReturn($this->violationBuilder);

        $this->validator->validate(181.0, $constraint);
    }

    public function testInvalidLongitudeTooLow(): void
    {
        $constraint = new ValidCoordinates(type: 'longitude');

        $this->violationBuilder
            ->expects($this->once())
            ->method('setParameter')
            ->with('{{ value }}', '-181')
            ->willReturnSelf();

        $this->violationBuilder
            ->expects($this->once())
            ->method('addViolation');

        $this->context
            ->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->invalidLongitudeMessage)
            ->willReturn($this->violationBuilder);

        $this->validator->validate(-181.0, $constraint);
    }

    public function testValidLongitudeAtBoundaries(): void
    {
        $constraint = new ValidCoordinates(type: 'longitude');

        $this->context
            ->expects($this->never())
            ->method('buildViolation');

        // Test upper boundary
        $this->validator->validate(180.0, $constraint);

        // Test lower boundary
        $this->validator->validate(-180.0, $constraint);
    }

    public function testInvalidFormatNonNumericString(): void
    {
        $constraint = new ValidCoordinates(type: 'latitude');

        $this->violationBuilder
            ->expects($this->once())
            ->method('setParameter')
            ->with('{{ value }}', 'invalid')
            ->willReturnSelf();

        $this->violationBuilder
            ->expects($this->once())
            ->method('addViolation');

        $this->context
            ->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->invalidFormatMessage)
            ->willReturn($this->violationBuilder);

        $this->validator->validate('invalid', $constraint);
    }

    public function testInvalidFormatArray(): void
    {
        $constraint = new ValidCoordinates(type: 'latitude');

        $this->violationBuilder
            ->expects($this->once())
            ->method('setParameter')
            ->with('{{ value }}', 'Array')
            ->willReturnSelf();

        $this->violationBuilder
            ->expects($this->once())
            ->method('addViolation');

        $this->context
            ->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->invalidFormatMessage)
            ->willReturn($this->violationBuilder);

        $this->validator->validate([], $constraint);
    }

    public function testValidNegativeCoordinates(): void
    {
        $latitudeConstraint = new ValidCoordinates(type: 'latitude');
        $longitudeConstraint = new ValidCoordinates(type: 'longitude');

        $this->context
            ->expects($this->never())
            ->method('buildViolation');

        // Southern hemisphere
        $this->validator->validate(-33.8688, $latitudeConstraint);

        // Western hemisphere
        $this->validator->validate(-151.2093, $longitudeConstraint);
    }

    public function testValidZeroCoordinates(): void
    {
        $latitudeConstraint = new ValidCoordinates(type: 'latitude');
        $longitudeConstraint = new ValidCoordinates(type: 'longitude');

        $this->context
            ->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate(0.0, $latitudeConstraint);
        $this->validator->validate(0, $longitudeConstraint);
    }

    public function testValidHighPrecisionCoordinates(): void
    {
        $latitudeConstraint = new ValidCoordinates(type: 'latitude');
        $longitudeConstraint = new ValidCoordinates(type: 'longitude');

        $this->context
            ->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate('48.8566140', $latitudeConstraint);
        $this->validator->validate('2.3522219', $longitudeConstraint);
    }

    public function testCustomErrorMessages(): void
    {
        $customMessage = 'Custom latitude error message';
        $constraint = new ValidCoordinates(
            type: 'latitude',
            invalidLatitudeMessage: $customMessage
        );

        $this->violationBuilder
            ->expects($this->once())
            ->method('setParameter')
            ->willReturnSelf();

        $this->violationBuilder
            ->expects($this->once())
            ->method('addViolation');

        $this->context
            ->expects($this->once())
            ->method('buildViolation')
            ->with($customMessage)
            ->willReturn($this->violationBuilder);

        $this->validator->validate(100.0, $constraint);
    }

    public function testValidStringNumericValue(): void
    {
        $constraint = new ValidCoordinates(type: 'latitude');

        $this->context
            ->expects($this->never())
            ->method('buildViolation');

        // String that can be converted to float
        $this->validator->validate('45.5', $constraint);
        $this->validator->validate('-12.345', $constraint);
        $this->validator->validate('0', $constraint);
    }

    public function testEdgeCaseLatitudeJustAboveMax(): void
    {
        $constraint = new ValidCoordinates(type: 'latitude');

        $this->violationBuilder
            ->method('setParameter')
            ->willReturnSelf();

        $this->violationBuilder
            ->expects($this->once())
            ->method('addViolation');

        $this->context
            ->expects($this->once())
            ->method('buildViolation')
            ->willReturn($this->violationBuilder);

        $this->validator->validate(90.0001, $constraint);
    }

    public function testEdgeCaseLongitudeJustAboveMax(): void
    {
        $constraint = new ValidCoordinates(type: 'longitude');

        $this->violationBuilder
            ->method('setParameter')
            ->willReturnSelf();

        $this->violationBuilder
            ->expects($this->once())
            ->method('addViolation');

        $this->context
            ->expects($this->once())
            ->method('buildViolation')
            ->willReturn($this->violationBuilder);

        $this->validator->validate(180.0001, $constraint);
    }
}
