<?php

declare(strict_types=1);

namespace CodeWheel\McpSchemaBuilder\Tests;

use CodeWheel\McpSchemaBuilder\ValidationError;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CodeWheel\McpSchemaBuilder\ValidationError
 */
final class ValidationErrorTest extends TestCase
{
    public function testConstruction(): void
    {
        $error = new ValidationError(
            path: 'user.email',
            message: 'Invalid email format',
            constraint: 'format',
            value: 'not-an-email',
            expected: 'email',
        );

        $this->assertSame('user.email', $error->path);
        $this->assertSame('Invalid email format', $error->message);
        $this->assertSame('format', $error->constraint);
        $this->assertSame('not-an-email', $error->value);
        $this->assertSame('email', $error->expected);
    }

    public function testConstructionWithDefaults(): void
    {
        $error = new ValidationError(
            path: 'field',
            message: 'Error',
            constraint: 'custom',
        );

        $this->assertNull($error->value);
        $this->assertNull($error->expected);
    }

    public function testRequiredFactory(): void
    {
        $error = ValidationError::required('username');

        $this->assertSame('username', $error->path);
        $this->assertSame('Required field is missing', $error->message);
        $this->assertSame('required', $error->constraint);
    }

    public function testTypeFactory(): void
    {
        $error = ValidationError::type('age', 'integer', 'not a number');

        $this->assertSame('age', $error->path);
        $this->assertSame('Expected integer, got string', $error->message);
        $this->assertSame('type', $error->constraint);
        $this->assertSame('not a number', $error->value);
        $this->assertSame('integer', $error->expected);
    }

    public function testTypeFactoryWithArray(): void
    {
        $error = ValidationError::type('items', 'array', 'string value');

        $this->assertStringContainsString('Expected array', $error->message);
    }

    public function testFormatFactory(): void
    {
        $error = ValidationError::format('email', 'email', 'invalid-email');

        $this->assertSame('email', $error->path);
        $this->assertSame("Value does not match format 'email'", $error->message);
        $this->assertSame('format', $error->constraint);
        $this->assertSame('invalid-email', $error->value);
        $this->assertSame('email', $error->expected);
    }

    public function testMinimumFactory(): void
    {
        $error = ValidationError::minimum('age', 18, 15);

        $this->assertSame('age', $error->path);
        $this->assertSame('Value must be >= 18', $error->message);
        $this->assertSame('minimum', $error->constraint);
        $this->assertSame(15, $error->value);
        $this->assertSame(18, $error->expected);
    }

    public function testMinimumFactoryWithFloat(): void
    {
        $error = ValidationError::minimum('price', 0.01, 0.0);

        $this->assertSame('Value must be >= 0.01', $error->message);
        $this->assertSame(0.0, $error->value);
        $this->assertSame(0.01, $error->expected);
    }

    public function testMaximumFactory(): void
    {
        $error = ValidationError::maximum('score', 100, 150);

        $this->assertSame('score', $error->path);
        $this->assertSame('Value must be <= 100', $error->message);
        $this->assertSame('maximum', $error->constraint);
        $this->assertSame(150, $error->value);
        $this->assertSame(100, $error->expected);
    }

    public function testMaximumFactoryWithFloat(): void
    {
        $error = ValidationError::maximum('percentage', 99.9, 100.5);

        $this->assertSame('Value must be <= 99.9', $error->message);
    }

    public function testMinLengthFactory(): void
    {
        $error = ValidationError::minLength('password', 8, 5);

        $this->assertSame('password', $error->path);
        $this->assertSame('String must be at least 8 characters (got 5)', $error->message);
        $this->assertSame('minLength', $error->constraint);
        $this->assertNull($error->value);
        $this->assertSame(8, $error->expected);
    }

    public function testMaxLengthFactory(): void
    {
        $error = ValidationError::maxLength('username', 20, 25);

        $this->assertSame('username', $error->path);
        $this->assertSame('String must be at most 20 characters (got 25)', $error->message);
        $this->assertSame('maxLength', $error->constraint);
        $this->assertNull($error->value);
        $this->assertSame(20, $error->expected);
    }

    public function testPatternFactory(): void
    {
        $error = ValidationError::pattern('code', '^[A-Z]{3}$');

        $this->assertSame('code', $error->path);
        $this->assertSame('Value does not match pattern', $error->message);
        $this->assertSame('pattern', $error->constraint);
        $this->assertNull($error->value);
        $this->assertSame('^[A-Z]{3}$', $error->expected);
    }

    public function testEnumFactory(): void
    {
        $allowed = ['draft', 'published', 'archived'];
        $error = ValidationError::enum('status', $allowed, 'invalid');

        $this->assertSame('status', $error->path);
        $this->assertSame('Value must be one of: "draft", "published", "archived"', $error->message);
        $this->assertSame('enum', $error->constraint);
        $this->assertSame('invalid', $error->value);
        $this->assertSame($allowed, $error->expected);
    }

    public function testEnumFactoryWithMixedTypes(): void
    {
        $allowed = [1, 2, 'three'];
        $error = ValidationError::enum('choice', $allowed, 4);

        $this->assertSame('Value must be one of: 1, 2, "three"', $error->message);
    }

    public function testMinItemsFactory(): void
    {
        $error = ValidationError::minItems('tags', 1, 0);

        $this->assertSame('tags', $error->path);
        $this->assertSame('Array must have at least 1 items (got 0)', $error->message);
        $this->assertSame('minItems', $error->constraint);
        $this->assertNull($error->value);
        $this->assertSame(1, $error->expected);
    }

    public function testMaxItemsFactory(): void
    {
        $error = ValidationError::maxItems('items', 10, 15);

        $this->assertSame('items', $error->path);
        $this->assertSame('Array must have at most 10 items (got 15)', $error->message);
        $this->assertSame('maxItems', $error->constraint);
        $this->assertNull($error->value);
        $this->assertSame(10, $error->expected);
    }

    public function testUniqueItemsFactory(): void
    {
        $error = ValidationError::uniqueItems('ids');

        $this->assertSame('ids', $error->path);
        $this->assertSame('Array items must be unique', $error->message);
        $this->assertSame('uniqueItems', $error->constraint);
    }

    public function testAdditionalPropertyFactory(): void
    {
        $error = ValidationError::additionalProperty('user', 'unknownField');

        $this->assertSame('user', $error->path);
        $this->assertSame("Unknown property 'unknownField' is not allowed", $error->message);
        $this->assertSame('additionalProperties', $error->constraint);
        $this->assertNull($error->value);
        $this->assertSame('unknownField', $error->expected);
    }

    public function testToArrayWithAllFields(): void
    {
        $error = new ValidationError(
            path: 'email',
            message: 'Invalid format',
            constraint: 'format',
            value: 'bad',
            expected: 'email',
        );

        $array = $error->toArray();

        $this->assertSame('email', $array['path']);
        $this->assertSame('Invalid format', $array['message']);
        $this->assertSame('format', $array['constraint']);
        $this->assertSame('email', $array['expected']);
        $this->assertArrayNotHasKey('value', $array); // value is not included
    }

    public function testToArrayWithoutExpected(): void
    {
        $error = new ValidationError(
            path: 'field',
            message: 'Error',
            constraint: 'custom',
        );

        $array = $error->toArray();

        $this->assertArrayNotHasKey('expected', $array);
    }

    public function testToArrayFromRequiredFactory(): void
    {
        $error = ValidationError::required('name');
        $array = $error->toArray();

        $this->assertSame('name', $array['path']);
        $this->assertSame('required', $array['constraint']);
        $this->assertArrayNotHasKey('expected', $array);
    }
}
