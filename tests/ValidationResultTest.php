<?php

declare(strict_types=1);

namespace CodeWheel\McpSchemaBuilder\Tests;

use CodeWheel\McpSchemaBuilder\ValidationError;
use CodeWheel\McpSchemaBuilder\ValidationResult;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CodeWheel\McpSchemaBuilder\ValidationResult
 */
final class ValidationResultTest extends TestCase
{
    public function testValidFactory(): void
    {
        $result = ValidationResult::valid();

        $this->assertTrue($result->isValid());
        $this->assertFalse($result->isInvalid());
        $this->assertEmpty($result->getErrors());
        $this->assertCount(0, $result);
    }

    public function testInvalidFactory(): void
    {
        $errors = [
            ValidationError::required('name'),
            ValidationError::required('email'),
        ];

        $result = ValidationResult::invalid($errors);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->isInvalid());
        $this->assertCount(2, $result->getErrors());
        $this->assertCount(2, $result);
    }

    public function testIsValidWithNoErrors(): void
    {
        $result = new ValidationResult([]);

        $this->assertTrue($result->isValid());
    }

    public function testIsValidWithErrors(): void
    {
        $result = new ValidationResult([
            ValidationError::required('field'),
        ]);

        $this->assertFalse($result->isValid());
    }

    public function testIsInvalidWithNoErrors(): void
    {
        $result = new ValidationResult([]);

        $this->assertFalse($result->isInvalid());
    }

    public function testIsInvalidWithErrors(): void
    {
        $result = new ValidationResult([
            ValidationError::required('field'),
        ]);

        $this->assertTrue($result->isInvalid());
    }

    public function testGetErrors(): void
    {
        $error1 = ValidationError::required('a');
        $error2 = ValidationError::required('b');

        $result = new ValidationResult([$error1, $error2]);

        $errors = $result->getErrors();
        $this->assertSame([$error1, $error2], $errors);
    }

    public function testErrorsFor(): void
    {
        $nameError = ValidationError::minLength('name', 3, 1);
        $emailError1 = ValidationError::required('email');
        $emailError2 = ValidationError::format('email', 'email', 'bad');

        $result = new ValidationResult([$nameError, $emailError1, $emailError2]);

        $emailErrors = $result->errorsFor('email');
        $this->assertCount(2, $emailErrors);

        $nameErrors = $result->errorsFor('name');
        $this->assertCount(1, $nameErrors);

        $noErrors = $result->errorsFor('nonexistent');
        $this->assertEmpty($noErrors);
    }

    public function testFirstError(): void
    {
        $first = ValidationError::required('first');
        $second = ValidationError::required('second');

        $result = new ValidationResult([$first, $second]);

        $this->assertSame($first, $result->firstError());
    }

    public function testFirstErrorWhenEmpty(): void
    {
        $result = ValidationResult::valid();

        $this->assertNull($result->firstError());
    }

    public function testCount(): void
    {
        $result = new ValidationResult([
            ValidationError::required('a'),
            ValidationError::required('b'),
            ValidationError::required('c'),
        ]);

        $this->assertSame(3, $result->count());
        $this->assertCount(3, $result); // Countable interface
    }

    public function testCountEmpty(): void
    {
        $result = ValidationResult::valid();

        $this->assertSame(0, $result->count());
    }

    public function testMerge(): void
    {
        $result1 = ValidationResult::invalid([
            ValidationError::required('a'),
        ]);

        $result2 = ValidationResult::invalid([
            ValidationError::required('b'),
            ValidationError::required('c'),
        ]);

        $merged = $result1->merge($result2);

        $this->assertCount(3, $merged);
        $this->assertSame('a', $merged->getErrors()[0]->path);
        $this->assertSame('b', $merged->getErrors()[1]->path);
        $this->assertSame('c', $merged->getErrors()[2]->path);
    }

    public function testMergeWithValid(): void
    {
        $result1 = ValidationResult::valid();
        $result2 = ValidationResult::invalid([
            ValidationError::required('field'),
        ]);

        $merged = $result1->merge($result2);

        $this->assertCount(1, $merged);
    }

    public function testMergePreservesOriginals(): void
    {
        $result1 = ValidationResult::invalid([
            ValidationError::required('a'),
        ]);

        $result2 = ValidationResult::invalid([
            ValidationError::required('b'),
        ]);

        $merged = $result1->merge($result2);

        // Original results should be unchanged
        $this->assertCount(1, $result1);
        $this->assertCount(1, $result2);
        $this->assertCount(2, $merged);
    }

    public function testToArray(): void
    {
        $result = ValidationResult::invalid([
            ValidationError::required('name'),
            ValidationError::format('email', 'email', 'bad'),
        ]);

        $array = $result->toArray();

        $this->assertFalse($array['valid']);
        $this->assertSame(2, $array['error_count']);
        $this->assertCount(2, $array['errors']);
        $this->assertSame('name', $array['errors'][0]['path']);
        $this->assertSame('email', $array['errors'][1]['path']);
    }

    public function testToArrayWhenValid(): void
    {
        $result = ValidationResult::valid();

        $array = $result->toArray();

        $this->assertTrue($array['valid']);
        $this->assertSame(0, $array['error_count']);
        $this->assertEmpty($array['errors']);
    }

    public function testGetSummaryWhenValid(): void
    {
        $result = ValidationResult::valid();

        $this->assertSame('Validation passed', $result->getSummary());
    }

    public function testGetSummaryWithOneError(): void
    {
        $result = ValidationResult::invalid([
            ValidationError::required('username'),
        ]);

        $summary = $result->getSummary();

        $this->assertSame('username: Required field is missing', $summary);
    }

    public function testGetSummaryWithMultipleErrors(): void
    {
        $result = ValidationResult::invalid([
            ValidationError::required('a'),
            ValidationError::required('b'),
            ValidationError::required('c'),
        ]);

        $summary = $result->getSummary();

        $this->assertSame('3 validation errors', $summary);
    }

    public function testToErrorBagThrowsWhenPackageNotInstalled(): void
    {
        // This test verifies the method exists and handles the case correctly
        // In the test environment, mcp-error-codes may or may not be installed
        $result = ValidationResult::invalid([
            ValidationError::required('field'),
        ]);

        // If mcp-error-codes is not installed, this should throw
        // If it IS installed (like in integration tests), it should work
        try {
            $bag = $result->toErrorBag();
            // Package is installed - verify it works
            $this->assertInstanceOf(\CodeWheel\McpErrorCodes\ErrorBag::class, $bag);
            $this->assertSame(1, $bag->count());
        } catch (\RuntimeException $e) {
            // Package not installed - verify correct error message
            $this->assertStringContainsString('code-wheel/mcp-error-codes', $e->getMessage());
        }
    }

    public function testToErrorBagWhenValid(): void
    {
        $result = ValidationResult::valid();

        try {
            $bag = $result->toErrorBag();
            $this->assertSame(0, $bag->count());
        } catch (\RuntimeException $e) {
            // Package not installed
            $this->assertStringContainsString('mcp-error-codes', $e->getMessage());
        }
    }

    public function testCountableInterface(): void
    {
        $result = ValidationResult::invalid([
            ValidationError::required('a'),
            ValidationError::required('b'),
        ]);

        // Test that it works with count() function
        $this->assertSame(2, count($result));
    }
}
