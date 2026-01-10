<?php

declare(strict_types=1);

namespace CodeWheel\McpSchemaBuilder\Tests;

use CodeWheel\McpSchemaBuilder\SchemaBuilder;
use CodeWheel\McpSchemaBuilder\SchemaValidator;
use CodeWheel\McpSchemaBuilder\ValidationResult;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CodeWheel\McpSchemaBuilder\SchemaValidator
 * @covers \CodeWheel\McpSchemaBuilder\ValidationResult
 * @covers \CodeWheel\McpSchemaBuilder\ValidationError
 */
final class SchemaValidatorTest extends TestCase
{
    public function testValidSimpleObject(): void
    {
        $schema = SchemaBuilder::object()
            ->property('name', SchemaBuilder::string()->required())
            ->property('age', SchemaBuilder::integer())
            ->build();

        $validator = new SchemaValidator($schema);
        $result = $validator->validate(['name' => 'John', 'age' => 30]);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    public function testMissingRequiredField(): void
    {
        $schema = SchemaBuilder::object()
            ->property('name', SchemaBuilder::string()->required())
            ->property('email', SchemaBuilder::string()->required())
            ->build();

        $validator = new SchemaValidator($schema);
        $result = $validator->validate(['name' => 'John']);

        $this->assertTrue($result->isInvalid());
        $this->assertCount(1, $result);

        $error = $result->firstError();
        $this->assertNotNull($error);
        $this->assertSame('email', $error->path);
        $this->assertSame('required', $error->constraint);
    }

    public function testInvalidType(): void
    {
        $schema = SchemaBuilder::object()
            ->property('age', SchemaBuilder::integer()->required())
            ->build();

        $validator = new SchemaValidator($schema);
        $result = $validator->validate(['age' => 'not a number']);

        $this->assertTrue($result->isInvalid());
        $error = $result->firstError();
        $this->assertSame('type', $error->constraint);
    }

    public function testStringMinLength(): void
    {
        $schema = SchemaBuilder::object()
            ->property('name', SchemaBuilder::string()->minLength(3)->required())
            ->build();

        $validator = new SchemaValidator($schema);
        $result = $validator->validate(['name' => 'Jo']);

        $this->assertTrue($result->isInvalid());
        $error = $result->firstError();
        $this->assertSame('minLength', $error->constraint);
    }

    public function testStringMaxLength(): void
    {
        $schema = SchemaBuilder::object()
            ->property('code', SchemaBuilder::string()->maxLength(5)->required())
            ->build();

        $validator = new SchemaValidator($schema);
        $result = $validator->validate(['code' => 'TOOLONG']);

        $this->assertTrue($result->isInvalid());
        $error = $result->firstError();
        $this->assertSame('maxLength', $error->constraint);
    }

    public function testStringPattern(): void
    {
        $schema = SchemaBuilder::object()
            ->property('code', SchemaBuilder::string()->pattern('^[A-Z]{3}$')->required())
            ->build();

        $validator = new SchemaValidator($schema);

        $valid = $validator->validate(['code' => 'ABC']);
        $this->assertTrue($valid->isValid());

        $invalid = $validator->validate(['code' => 'abc']);
        $this->assertTrue($invalid->isInvalid());
    }

    public function testEmailFormat(): void
    {
        $schema = SchemaBuilder::object()
            ->property('email', SchemaBuilder::string()->format('email')->required())
            ->build();

        $validator = new SchemaValidator($schema);

        $valid = $validator->validate(['email' => 'test@example.com']);
        $this->assertTrue($valid->isValid());

        $invalid = $validator->validate(['email' => 'not-an-email']);
        $this->assertTrue($invalid->isInvalid());
        $this->assertSame('format', $invalid->firstError()->constraint);
    }

    public function testUriFormat(): void
    {
        $schema = SchemaBuilder::object()
            ->property('website', SchemaBuilder::string()->format('uri')->required())
            ->build();

        $validator = new SchemaValidator($schema);

        $valid = $validator->validate(['website' => 'https://example.com']);
        $this->assertTrue($valid->isValid());

        $invalid = $validator->validate(['website' => 'not a url']);
        $this->assertTrue($invalid->isInvalid());
    }

    public function testNumberMinimum(): void
    {
        $schema = SchemaBuilder::object()
            ->property('age', SchemaBuilder::integer()->minimum(0)->required())
            ->build();

        $validator = new SchemaValidator($schema);

        $valid = $validator->validate(['age' => 0]);
        $this->assertTrue($valid->isValid());

        $invalid = $validator->validate(['age' => -1]);
        $this->assertTrue($invalid->isInvalid());
        $this->assertSame('minimum', $invalid->firstError()->constraint);
    }

    public function testNumberMaximum(): void
    {
        $schema = SchemaBuilder::object()
            ->property('score', SchemaBuilder::integer()->maximum(100)->required())
            ->build();

        $validator = new SchemaValidator($schema);

        $valid = $validator->validate(['score' => 100]);
        $this->assertTrue($valid->isValid());

        $invalid = $validator->validate(['score' => 101]);
        $this->assertTrue($invalid->isInvalid());
        $this->assertSame('maximum', $invalid->firstError()->constraint);
    }

    public function testEnum(): void
    {
        $schema = SchemaBuilder::object()
            ->property('status', SchemaBuilder::string()->enum(['draft', 'published', 'archived'])->required())
            ->build();

        $validator = new SchemaValidator($schema);

        $valid = $validator->validate(['status' => 'published']);
        $this->assertTrue($valid->isValid());

        $invalid = $validator->validate(['status' => 'unknown']);
        $this->assertTrue($invalid->isInvalid());
        $this->assertSame('enum', $invalid->firstError()->constraint);
    }

    public function testArrayValidation(): void
    {
        $schema = SchemaBuilder::object()
            ->property('tags', SchemaBuilder::array(SchemaBuilder::string())->minItems(1)->required())
            ->build();

        $validator = new SchemaValidator($schema);

        $valid = $validator->validate(['tags' => ['php', 'drupal']]);
        $this->assertTrue($valid->isValid());

        $empty = $validator->validate(['tags' => []]);
        $this->assertTrue($empty->isInvalid());
        $this->assertSame('minItems', $empty->firstError()->constraint);
    }

    public function testArrayItemsValidation(): void
    {
        $schema = SchemaBuilder::object()
            ->property('scores', SchemaBuilder::array(SchemaBuilder::integer()->minimum(0))->required())
            ->build();

        $validator = new SchemaValidator($schema);

        $valid = $validator->validate(['scores' => [10, 20, 30]]);
        $this->assertTrue($valid->isValid());

        $invalid = $validator->validate(['scores' => [10, -5, 30]]);
        $this->assertTrue($invalid->isInvalid());
        $this->assertStringContainsString('[1]', $invalid->firstError()->path);
    }

    public function testArrayMaxItems(): void
    {
        $schema = SchemaBuilder::object()
            ->property('items', SchemaBuilder::array()->maxItems(3)->required())
            ->build();

        $validator = new SchemaValidator($schema);

        $valid = $validator->validate(['items' => [1, 2, 3]]);
        $this->assertTrue($valid->isValid());

        $invalid = $validator->validate(['items' => [1, 2, 3, 4]]);
        $this->assertTrue($invalid->isInvalid());
        $this->assertSame('maxItems', $invalid->firstError()->constraint);
    }

    public function testUniqueItems(): void
    {
        $schema = SchemaBuilder::object()
            ->property('ids', SchemaBuilder::array()->uniqueItems()->required())
            ->build();

        $validator = new SchemaValidator($schema);

        $valid = $validator->validate(['ids' => [1, 2, 3]]);
        $this->assertTrue($valid->isValid());

        $invalid = $validator->validate(['ids' => [1, 2, 2]]);
        $this->assertTrue($invalid->isInvalid());
        $this->assertSame('uniqueItems', $invalid->firstError()->constraint);
    }

    public function testNestedObjects(): void
    {
        $addressSchema = SchemaBuilder::object()
            ->property('street', SchemaBuilder::string()->required())
            ->property('city', SchemaBuilder::string()->required());

        $schema = SchemaBuilder::object()
            ->property('name', SchemaBuilder::string()->required())
            ->property('address', $addressSchema->required())
            ->build();

        $validator = new SchemaValidator($schema);

        $valid = $validator->validate([
            'name' => 'John',
            'address' => ['street' => '123 Main St', 'city' => 'NYC'],
        ]);
        $this->assertTrue($valid->isValid());

        $invalid = $validator->validate([
            'name' => 'John',
            'address' => ['street' => '123 Main St'],
        ]);
        $this->assertTrue($invalid->isInvalid());
        $this->assertSame('address.city', $invalid->firstError()->path);
    }

    public function testAdditionalPropertiesFalse(): void
    {
        $schema = SchemaBuilder::object()
            ->property('name', SchemaBuilder::string()->required())
            ->additionalProperties(false)
            ->build();

        $validator = new SchemaValidator($schema);

        $valid = $validator->validate(['name' => 'John']);
        $this->assertTrue($valid->isValid());

        $invalid = $validator->validate(['name' => 'John', 'extra' => 'field']);
        $this->assertTrue($invalid->isInvalid());
        $this->assertSame('additionalProperties', $invalid->firstError()->constraint);
    }

    public function testNullableType(): void
    {
        $schema = SchemaBuilder::object()
            ->property('nickname', SchemaBuilder::string()->nullable()->required())
            ->build();

        $validator = new SchemaValidator($schema);

        $valid = $validator->validate(['nickname' => null]);
        $this->assertTrue($valid->isValid());

        $alsoValid = $validator->validate(['nickname' => 'Johnny']);
        $this->assertTrue($alsoValid->isValid());
    }

    public function testMultipleErrors(): void
    {
        $schema = SchemaBuilder::object()
            ->property('email', SchemaBuilder::string()->format('email')->required())
            ->property('age', SchemaBuilder::integer()->minimum(0)->required())
            ->build();

        $validator = new SchemaValidator($schema);
        $result = $validator->validate(['email' => 'invalid', 'age' => -5]);

        $this->assertTrue($result->isInvalid());
        $this->assertCount(2, $result);
    }

    public function testValidationResultMerge(): void
    {
        $result1 = ValidationResult::invalid([
            \CodeWheel\McpSchemaBuilder\ValidationError::required('field1'),
        ]);
        $result2 = ValidationResult::invalid([
            \CodeWheel\McpSchemaBuilder\ValidationError::required('field2'),
        ]);

        $merged = $result1->merge($result2);

        $this->assertCount(2, $merged);
    }

    public function testValidationResultErrorsFor(): void
    {
        $schema = SchemaBuilder::object()
            ->property('email', SchemaBuilder::string()->minLength(5)->maxLength(3)->required())
            ->build();

        $validator = new SchemaValidator($schema);
        $result = $validator->validate(['email' => 'ab']);

        // Should have minLength error
        $emailErrors = $result->errorsFor('email');
        $this->assertNotEmpty($emailErrors);
    }

    public function testFromBuilder(): void
    {
        $builder = SchemaBuilder::object()
            ->property('name', SchemaBuilder::string()->required());

        $validator = SchemaValidator::fromBuilder($builder);
        $result = $validator->validate(['name' => 'John']);

        $this->assertTrue($result->isValid());
    }

    public function testValidationResultToArray(): void
    {
        $schema = SchemaBuilder::object()
            ->property('name', SchemaBuilder::string()->required())
            ->build();

        $validator = new SchemaValidator($schema);
        $result = $validator->validate([]);

        $array = $result->toArray();

        $this->assertFalse($array['valid']);
        $this->assertSame(1, $array['error_count']);
        $this->assertCount(1, $array['errors']);
    }

    public function testValidationResultSummary(): void
    {
        $valid = ValidationResult::valid();
        $this->assertSame('Validation passed', $valid->getSummary());

        $schema = SchemaBuilder::object()
            ->property('a', SchemaBuilder::string()->required())
            ->property('b', SchemaBuilder::string()->required())
            ->build();

        $validator = new SchemaValidator($schema);
        $result = $validator->validate([]);

        $this->assertStringContainsString('2 validation errors', $result->getSummary());
    }

    public function testDateFormat(): void
    {
        $schema = SchemaBuilder::object()
            ->property('date', SchemaBuilder::string()->format('date')->required())
            ->build();

        $validator = new SchemaValidator($schema);

        $valid = $validator->validate(['date' => '2024-01-15']);
        $this->assertTrue($valid->isValid());

        $invalid = $validator->validate(['date' => '2024-13-45']);
        $this->assertTrue($invalid->isInvalid());
    }

    public function testDateTimeFormat(): void
    {
        $schema = SchemaBuilder::object()
            ->property('timestamp', SchemaBuilder::string()->format('date-time')->required())
            ->build();

        $validator = new SchemaValidator($schema);

        $valid = $validator->validate(['timestamp' => '2024-01-15T10:30:00Z']);
        $this->assertTrue($valid->isValid());

        $invalid = $validator->validate(['timestamp' => 'not a datetime']);
        $this->assertTrue($invalid->isInvalid());
    }

    public function testUuidFormat(): void
    {
        $schema = SchemaBuilder::object()
            ->property('id', SchemaBuilder::string()->format('uuid')->required())
            ->build();

        $validator = new SchemaValidator($schema);

        $valid = $validator->validate(['id' => '550e8400-e29b-41d4-a716-446655440000']);
        $this->assertTrue($valid->isValid());

        $invalid = $validator->validate(['id' => 'not-a-uuid']);
        $this->assertTrue($invalid->isInvalid());
    }

    public function testUrlFormat(): void
    {
        $schema = SchemaBuilder::object()
            ->property('link', SchemaBuilder::string()->format('url')->required())
            ->build();

        $validator = new SchemaValidator($schema);

        $valid = $validator->validate(['link' => 'https://example.com/path']);
        $this->assertTrue($valid->isValid());

        $invalid = $validator->validate(['link' => 'not a url']);
        $this->assertTrue($invalid->isInvalid());
        $this->assertSame('format', $invalid->firstError()->constraint);
    }

    public function testTimeFormat(): void
    {
        $schema = SchemaBuilder::object()
            ->property('time', SchemaBuilder::string()->format('time')->required())
            ->build();

        $validator = new SchemaValidator($schema);

        $valid = $validator->validate(['time' => '14:30']);
        $this->assertTrue($valid->isValid());

        $validWithSeconds = $validator->validate(['time' => '14:30:45']);
        $this->assertTrue($validWithSeconds->isValid());

        $invalid = $validator->validate(['time' => 'not a time']);
        $this->assertTrue($invalid->isInvalid());
    }

    public function testIpv4Format(): void
    {
        $schema = SchemaBuilder::object()
            ->property('ip', SchemaBuilder::string()->format('ipv4')->required())
            ->build();

        $validator = new SchemaValidator($schema);

        $valid = $validator->validate(['ip' => '192.168.1.1']);
        $this->assertTrue($valid->isValid());

        $invalid = $validator->validate(['ip' => '999.999.999.999']);
        $this->assertTrue($invalid->isInvalid());

        $invalidIpv6 = $validator->validate(['ip' => '::1']);
        $this->assertTrue($invalidIpv6->isInvalid());
    }

    public function testIpv6Format(): void
    {
        $schema = SchemaBuilder::object()
            ->property('ip', SchemaBuilder::string()->format('ipv6')->required())
            ->build();

        $validator = new SchemaValidator($schema);

        $valid = $validator->validate(['ip' => '::1']);
        $this->assertTrue($valid->isValid());

        $validFull = $validator->validate(['ip' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334']);
        $this->assertTrue($validFull->isValid());

        $invalid = $validator->validate(['ip' => '192.168.1.1']);
        $this->assertTrue($invalid->isInvalid());
    }

    public function testUnknownFormatIsValid(): void
    {
        $schema = SchemaBuilder::object()
            ->property('field', SchemaBuilder::string()->format('custom-format')->required())
            ->build();

        $validator = new SchemaValidator($schema);

        // Unknown formats should be considered valid (per JSON Schema spec)
        $result = $validator->validate(['field' => 'any value']);
        $this->assertTrue($result->isValid());
    }

    public function testExclusiveMinimum(): void
    {
        $schema = SchemaBuilder::object()
            ->property('value', SchemaBuilder::integer()->exclusiveMinimum(0)->required())
            ->build();

        $validator = new SchemaValidator($schema);

        $valid = $validator->validate(['value' => 1]);
        $this->assertTrue($valid->isValid());

        // Value equal to exclusiveMinimum is invalid
        $invalid = $validator->validate(['value' => 0]);
        $this->assertTrue($invalid->isInvalid());
        $this->assertSame('minimum', $invalid->firstError()->constraint);

        $alsoInvalid = $validator->validate(['value' => -1]);
        $this->assertTrue($alsoInvalid->isInvalid());
    }

    public function testExclusiveMaximum(): void
    {
        $schema = SchemaBuilder::object()
            ->property('value', SchemaBuilder::integer()->exclusiveMaximum(100)->required())
            ->build();

        $validator = new SchemaValidator($schema);

        $valid = $validator->validate(['value' => 99]);
        $this->assertTrue($valid->isValid());

        // Value equal to exclusiveMaximum is invalid
        $invalid = $validator->validate(['value' => 100]);
        $this->assertTrue($invalid->isInvalid());
        $this->assertSame('maximum', $invalid->firstError()->constraint);

        $alsoInvalid = $validator->validate(['value' => 101]);
        $this->assertTrue($alsoInvalid->isInvalid());
    }

    public function testStdClassAsObject(): void
    {
        $schema = SchemaBuilder::object()
            ->property('data', SchemaBuilder::object()
                ->property('name', SchemaBuilder::string()->required())
                ->required())
            ->build();

        $validator = new SchemaValidator($schema);

        // Test with stdClass
        $data = new \stdClass();
        $data->name = 'Test';

        $input = ['data' => $data];
        $result = $validator->validate($input);

        $this->assertTrue($result->isValid());
    }

    public function testStdClassValidationFails(): void
    {
        $schema = SchemaBuilder::object()
            ->property('data', SchemaBuilder::object()
                ->property('name', SchemaBuilder::string()->required())
                ->required())
            ->build();

        $validator = new SchemaValidator($schema);

        // Test with stdClass missing required field
        $data = new \stdClass();

        $input = ['data' => $data];
        $result = $validator->validate($input);

        $this->assertTrue($result->isInvalid());
        $this->assertSame('data.name', $result->firstError()->path);
    }

    public function testUnknownTypeDefaultsToTrue(): void
    {
        // Create a schema with a custom/unknown type
        $schema = [
            'type' => 'custom_type',
            'properties' => new \stdClass(),
        ];

        $validator = new SchemaValidator($schema);

        // Unknown types should match any value
        $result = $validator->validate('any value');
        $this->assertTrue($result->isValid());
    }

    public function testFloatValidation(): void
    {
        $schema = SchemaBuilder::object()
            ->property('value', SchemaBuilder::number()->minimum(0.5)->maximum(1.5)->required())
            ->build();

        $validator = new SchemaValidator($schema);

        $valid = $validator->validate(['value' => 1.0]);
        $this->assertTrue($valid->isValid());

        $invalidLow = $validator->validate(['value' => 0.4]);
        $this->assertTrue($invalidLow->isInvalid());

        $invalidHigh = $validator->validate(['value' => 1.6]);
        $this->assertTrue($invalidHigh->isInvalid());
    }

    public function testBooleanType(): void
    {
        $schema = SchemaBuilder::object()
            ->property('flag', SchemaBuilder::boolean()->required())
            ->build();

        $validator = new SchemaValidator($schema);

        $validTrue = $validator->validate(['flag' => true]);
        $this->assertTrue($validTrue->isValid());

        $validFalse = $validator->validate(['flag' => false]);
        $this->assertTrue($validFalse->isValid());

        $invalid = $validator->validate(['flag' => 'true']);
        $this->assertTrue($invalid->isInvalid());
    }

    public function testInvalidDateFormat(): void
    {
        $schema = SchemaBuilder::object()
            ->property('date', SchemaBuilder::string()->format('date')->required())
            ->build();

        $validator = new SchemaValidator($schema);

        // Invalid format (not YYYY-MM-DD)
        $invalidFormat = $validator->validate(['date' => '01/15/2024']);
        $this->assertTrue($invalidFormat->isInvalid());

        // Valid format but invalid date
        $invalidDate = $validator->validate(['date' => '2024-02-30']);
        $this->assertTrue($invalidDate->isInvalid());
    }

    public function testEmptyArrayAsArrayType(): void
    {
        $schema = SchemaBuilder::object()
            ->property('items', SchemaBuilder::array()->required())
            ->build();

        $validator = new SchemaValidator($schema);

        // Empty array should be valid for array type
        $result = $validator->validate(['items' => []]);
        $this->assertTrue($result->isValid());
    }

    public function testEmptyArrayAsObjectType(): void
    {
        $schema = SchemaBuilder::object()
            ->property('data', SchemaBuilder::object()->required())
            ->build();

        $validator = new SchemaValidator($schema);

        // Empty array should be valid for object type
        $result = $validator->validate(['data' => []]);
        $this->assertTrue($result->isValid());
    }

    public function testArrayPathFormat(): void
    {
        $schema = SchemaBuilder::array(
            SchemaBuilder::string()->minLength(2)
        )->build();

        $validator = new SchemaValidator($schema);

        $result = $validator->validate(['a']);
        $this->assertTrue($result->isInvalid());
        $this->assertSame('[0]', $result->firstError()->path);
    }
}
