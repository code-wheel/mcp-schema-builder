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
}
