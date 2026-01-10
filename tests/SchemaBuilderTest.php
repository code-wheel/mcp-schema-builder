<?php

declare(strict_types=1);

namespace CodeWheel\McpSchemaBuilder\Tests;

use CodeWheel\McpSchemaBuilder\SchemaBuilder;
use PHPUnit\Framework\TestCase;

class SchemaBuilderTest extends TestCase
{
    public function testStringSchema(): void
    {
        $schema = SchemaBuilder::string()
            ->description('A test string')
            ->minLength(1)
            ->maxLength(100)
            ->build();

        $this->assertSame('string', $schema['type']);
        $this->assertSame('A test string', $schema['description']);
        $this->assertSame(1, $schema['minLength']);
        $this->assertSame(100, $schema['maxLength']);
    }

    public function testIntegerSchema(): void
    {
        $schema = SchemaBuilder::integer()
            ->minimum(0)
            ->maximum(100)
            ->build();

        $this->assertSame('integer', $schema['type']);
        $this->assertSame(0, $schema['minimum']);
        $this->assertSame(100, $schema['maximum']);
    }

    public function testEnumSchema(): void
    {
        $schema = SchemaBuilder::string()
            ->enum(['a', 'b', 'c'])
            ->build();

        $this->assertSame(['a', 'b', 'c'], $schema['enum']);
    }

    public function testObjectSchema(): void
    {
        $schema = SchemaBuilder::object()
            ->property('name', SchemaBuilder::string()->required())
            ->property('age', SchemaBuilder::integer())
            ->build();

        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey('name', $schema['properties']);
        $this->assertArrayHasKey('age', $schema['properties']);
        $this->assertSame(['name'], $schema['required']);
    }

    public function testArraySchema(): void
    {
        $schema = SchemaBuilder::array(SchemaBuilder::string())
            ->minItems(1)
            ->uniqueItems()
            ->build();

        $this->assertSame('array', $schema['type']);
        $this->assertSame(['type' => 'string'], $schema['items']);
        $this->assertSame(1, $schema['minItems']);
        $this->assertTrue($schema['uniqueItems']);
    }

    public function testNullableSchema(): void
    {
        $schema = SchemaBuilder::string()
            ->nullable()
            ->build();

        $this->assertSame(['string', 'null'], $schema['type']);
    }

    public function testFormatSchema(): void
    {
        $schema = SchemaBuilder::string()
            ->format('email')
            ->build();

        $this->assertSame('email', $schema['format']);
    }

    public function testDefaultValue(): void
    {
        $schema = SchemaBuilder::string()
            ->default('test')
            ->build();

        $this->assertSame('test', $schema['default']);
    }

    public function testToJson(): void
    {
        $schema = SchemaBuilder::string()->description('Test');
        $json = $schema->toJson(0);

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertSame('string', $decoded['type']);
    }

    public function testEmptyObjectProperties(): void
    {
        $schema = SchemaBuilder::object()->build();

        // Properties should be stdClass for correct JSON encoding.
        $this->assertInstanceOf(\stdClass::class, $schema['properties']);
    }

    public function testPatternConstraint(): void
    {
        $schema = SchemaBuilder::string()
            ->pattern('^[a-z]+$')
            ->build();

        $this->assertSame('^[a-z]+$', $schema['pattern']);
    }

    public function testFromArray(): void
    {
        $input = ['type' => 'string', 'minLength' => 5];
        $schema = SchemaBuilder::fromArray($input)->build();

        $this->assertSame('string', $schema['type']);
        $this->assertSame(5, $schema['minLength']);
    }

    public function testNumberSchema(): void
    {
        $schema = SchemaBuilder::number()
            ->minimum(0.0)
            ->maximum(1.0)
            ->build();

        $this->assertSame('number', $schema['type']);
        $this->assertSame(0.0, $schema['minimum']);
        $this->assertSame(1.0, $schema['maximum']);
    }

    public function testBooleanSchema(): void
    {
        $schema = SchemaBuilder::boolean()
            ->default(false)
            ->build();

        $this->assertSame('boolean', $schema['type']);
        $this->assertFalse($schema['default']);
    }

    public function testAnySchema(): void
    {
        $schema = SchemaBuilder::any()->build();

        $this->assertArrayNotHasKey('type', $schema);
    }

    public function testAnySchemaWithCustomProperties(): void
    {
        $schema = SchemaBuilder::any()
            ->with('type', 'null')
            ->description('A null value')
            ->build();

        $this->assertSame('null', $schema['type']);
        $this->assertSame('A null value', $schema['description']);
    }

    public function testExclusiveMinimum(): void
    {
        $schema = SchemaBuilder::integer()
            ->exclusiveMinimum(0)
            ->build();

        $this->assertSame(0, $schema['exclusiveMinimum']);
    }

    public function testExclusiveMaximum(): void
    {
        $schema = SchemaBuilder::integer()
            ->exclusiveMaximum(100)
            ->build();

        $this->assertSame(100, $schema['exclusiveMaximum']);
    }

    public function testMaxItems(): void
    {
        $schema = SchemaBuilder::array()
            ->maxItems(10)
            ->build();

        $this->assertSame(10, $schema['maxItems']);
    }

    public function testItemsMethod(): void
    {
        $schema = SchemaBuilder::array()
            ->items(SchemaBuilder::integer()->minimum(0))
            ->build();

        $this->assertSame('integer', $schema['items']['type']);
        $this->assertSame(0, $schema['items']['minimum']);
    }

    public function testAdditionalPropertiesFalse(): void
    {
        $schema = SchemaBuilder::object()
            ->additionalProperties(false)
            ->build();

        $this->assertFalse($schema['additionalProperties']);
    }

    public function testAdditionalPropertiesWithSchema(): void
    {
        $schema = SchemaBuilder::object()
            ->additionalProperties(SchemaBuilder::string())
            ->build();

        $this->assertSame(['type' => 'string'], $schema['additionalProperties']);
    }

    public function testMinProperties(): void
    {
        $schema = SchemaBuilder::object()
            ->minProperties(1)
            ->build();

        $this->assertSame(1, $schema['minProperties']);
    }

    public function testMaxProperties(): void
    {
        $schema = SchemaBuilder::object()
            ->maxProperties(10)
            ->build();

        $this->assertSame(10, $schema['maxProperties']);
    }

    public function testWith(): void
    {
        $schema = SchemaBuilder::string()
            ->with('customKey', 'customValue')
            ->build();

        $this->assertSame('customValue', $schema['customKey']);
    }

    public function testIsRequired(): void
    {
        $requiredSchema = SchemaBuilder::string()->required();
        $optionalSchema = SchemaBuilder::string();

        $this->assertTrue($requiredSchema->isRequired());
        $this->assertFalse($optionalSchema->isRequired());
    }

    public function testArrayWithoutItems(): void
    {
        $schema = SchemaBuilder::array()->build();

        $this->assertSame('array', $schema['type']);
        $this->assertArrayNotHasKey('items', $schema);
    }

    public function testFluentInterface(): void
    {
        $builder = SchemaBuilder::string();

        $this->assertSame($builder, $builder->description('desc'));
        $this->assertSame($builder, $builder->default('val'));
        $this->assertSame($builder, $builder->required());
        $this->assertSame($builder, $builder->enum(['a', 'b']));
        $this->assertSame($builder, $builder->format('email'));
        $this->assertSame($builder, $builder->minimum(0));
        $this->assertSame($builder, $builder->maximum(100));
        $this->assertSame($builder, $builder->exclusiveMinimum(0));
        $this->assertSame($builder, $builder->exclusiveMaximum(100));
        $this->assertSame($builder, $builder->minLength(1));
        $this->assertSame($builder, $builder->maxLength(255));
        $this->assertSame($builder, $builder->pattern('^.*$'));
        $this->assertSame($builder, $builder->nullable());
        $this->assertSame($builder, $builder->with('key', 'value'));
    }

    public function testObjectFluentInterface(): void
    {
        $builder = SchemaBuilder::object();

        $this->assertSame($builder, $builder->property('x', SchemaBuilder::string()));
        $this->assertSame($builder, $builder->additionalProperties(false));
        $this->assertSame($builder, $builder->minProperties(1));
        $this->assertSame($builder, $builder->maxProperties(10));
    }

    public function testArrayFluentInterface(): void
    {
        $builder = SchemaBuilder::array();

        $this->assertSame($builder, $builder->minItems(1));
        $this->assertSame($builder, $builder->maxItems(10));
        $this->assertSame($builder, $builder->uniqueItems());
        $this->assertSame($builder, $builder->items(SchemaBuilder::string()));
    }

    public function testComplexNestedSchema(): void
    {
        $schema = SchemaBuilder::object()
            ->property('users', SchemaBuilder::array(
                SchemaBuilder::object()
                    ->property('name', SchemaBuilder::string()->required())
                    ->property('email', SchemaBuilder::string()->format('email'))
            ))
            ->build();

        $this->assertSame('object', $schema['type']);
        $this->assertSame('array', $schema['properties']['users']['type']);
        $this->assertSame('object', $schema['properties']['users']['items']['type']);
        $this->assertArrayHasKey('name', $schema['properties']['users']['items']['properties']);
    }

    public function testFromArrayWithoutType(): void
    {
        // fromArray preserves the input schema as-is
        $schema = SchemaBuilder::fromArray(['description' => 'No type'])->build();

        // Type is not added automatically when missing from input
        $this->assertArrayNotHasKey('type', $schema);
        $this->assertSame('No type', $schema['description']);
    }

    public function testUniqueItemsFalse(): void
    {
        $schema = SchemaBuilder::array()
            ->uniqueItems(false)
            ->build();

        $this->assertFalse($schema['uniqueItems']);
    }

    public function testRequiredDeduplication(): void
    {
        $schema = SchemaBuilder::object()
            ->property('name', SchemaBuilder::string()->required())
            ->property('name2', SchemaBuilder::string()->required())
            ->build();

        // Both should be in required
        $this->assertCount(2, $schema['required']);
    }

    public function testToJsonWithDefaultFlags(): void
    {
        $schema = SchemaBuilder::string()->description('Test');
        $json = $schema->toJson();

        $this->assertStringContainsString("\n", $json); // Pretty print
        $this->assertStringNotContainsString('\/', $json); // Unescaped slashes
    }

    public function testNullableWithNoType(): void
    {
        $builder = SchemaBuilder::any();
        unset($builder->build()['type']); // any() removes type
        $schema = SchemaBuilder::any()->nullable()->build();

        // When there's no type, nullable should handle gracefully
        $this->assertArrayNotHasKey('type', $schema);
    }

    // =========================================================================
    // Merge and Extend Tests
    // =========================================================================

    public function testMergeProperties(): void
    {
        $base = SchemaBuilder::object()
            ->property('name', SchemaBuilder::string()->required());

        $other = SchemaBuilder::object()
            ->property('email', SchemaBuilder::string()->format('email'));

        $schema = $base->merge($other)->build();

        $this->assertArrayHasKey('name', $schema['properties']);
        $this->assertArrayHasKey('email', $schema['properties']);
        $this->assertContains('name', $schema['required']);
    }

    public function testMergeWithRequiredProperties(): void
    {
        $base = SchemaBuilder::object()
            ->property('id', SchemaBuilder::string()->required());

        $other = SchemaBuilder::object()
            ->property('created', SchemaBuilder::string()->required())
            ->property('updated', SchemaBuilder::string());

        $schema = $base->merge($other)->build();

        $this->assertContains('id', $schema['required']);
        $this->assertContains('created', $schema['required']);
        $this->assertNotContains('updated', $schema['required'] ?? []);
    }

    public function testMergeSchemaAttributes(): void
    {
        $base = SchemaBuilder::object()
            ->description('Base schema');

        $other = SchemaBuilder::object()
            ->with('custom', 'value')
            ->additionalProperties(false);

        $schema = $base->merge($other)->build();

        $this->assertSame('Base schema', $schema['description']);
        $this->assertSame('value', $schema['custom']);
        $this->assertFalse($schema['additionalProperties']);
    }

    public function testMergePreservesBaseType(): void
    {
        $base = SchemaBuilder::object()
            ->description('Base');

        $other = SchemaBuilder::object()
            ->description('Other')
            ->property('field', SchemaBuilder::string());

        $schema = $base->merge($other)->build();

        // Merge overwrites description from other
        $this->assertSame('Other', $schema['description']);
        $this->assertSame('object', $schema['type']);
    }

    public function testMergeIsFluentInterface(): void
    {
        $builder = SchemaBuilder::object();
        $other = SchemaBuilder::object();

        $this->assertSame($builder, $builder->merge($other));
    }

    public function testMergeDeduplicatesRequired(): void
    {
        $base = SchemaBuilder::object()
            ->property('id', SchemaBuilder::string()->required());

        // Merge same property name
        $other = SchemaBuilder::object()
            ->property('id', SchemaBuilder::integer()->required());

        $schema = $base->merge($other)->build();

        // Should only have one 'id' in required
        $required = array_filter($schema['required'], fn($r) => $r === 'id');
        $this->assertCount(1, $required);
    }

    public function testExtendCreatesIndependentCopy(): void
    {
        $original = SchemaBuilder::object()
            ->property('name', SchemaBuilder::string()->required())
            ->description('Original');

        $extended = $original->extend()
            ->property('age', SchemaBuilder::integer())
            ->description('Extended');

        $originalSchema = $original->build();
        $extendedSchema = $extended->build();

        // Original should NOT have age
        $this->assertArrayNotHasKey('age', $originalSchema['properties']);
        $this->assertSame('Original', $originalSchema['description']);

        // Extended should have both
        $this->assertArrayHasKey('name', $extendedSchema['properties']);
        $this->assertArrayHasKey('age', $extendedSchema['properties']);
        $this->assertSame('Extended', $extendedSchema['description']);
    }

    public function testExtendPreservesRequired(): void
    {
        $original = SchemaBuilder::object()
            ->property('id', SchemaBuilder::string()->required());

        $extended = $original->extend()
            ->property('data', SchemaBuilder::string()->required());

        $schema = $extended->build();

        $this->assertContains('id', $schema['required']);
        $this->assertContains('data', $schema['required']);
    }

    public function testChainMerges(): void
    {
        $pagination = SchemaBuilder::object()
            ->property('limit', SchemaBuilder::integer()->default(50))
            ->property('offset', SchemaBuilder::integer()->default(0));

        $sorting = SchemaBuilder::object()
            ->property('sort_by', SchemaBuilder::string())
            ->property('sort_order', SchemaBuilder::string()->enum(['asc', 'desc']));

        $filters = SchemaBuilder::object()
            ->property('status', SchemaBuilder::string())
            ->property('query', SchemaBuilder::string());

        $schema = SchemaBuilder::object()
            ->merge($pagination)
            ->merge($sorting)
            ->merge($filters)
            ->build();

        $this->assertCount(6, $schema['properties']);
        $this->assertArrayHasKey('limit', $schema['properties']);
        $this->assertArrayHasKey('sort_by', $schema['properties']);
        $this->assertArrayHasKey('status', $schema['properties']);
    }
}
