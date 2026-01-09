<?php

declare(strict_types=1);

namespace CodeWheel\McpSchemaBuilder\Tests;

use CodeWheel\McpSchemaBuilder\SchemaBuilder;
use CodeWheel\McpSchemaBuilder\TypeMapper;
use PHPUnit\Framework\TestCase;

class TypeMapperTest extends TestCase
{
    private TypeMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new TypeMapper();
    }

    /**
     * @dataProvider basicTypeProvider
     */
    public function testMapTypeBasicTypes(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->mapper->mapType($input));
    }

    public static function basicTypeProvider(): array
    {
        return [
            'string' => ['string', 'string'],
            'str' => ['str', 'string'],
            'text' => ['text', 'string'],
            'int' => ['int', 'integer'],
            'integer' => ['integer', 'integer'],
            'float' => ['float', 'number'],
            'double' => ['double', 'number'],
            'number' => ['number', 'number'],
            'numeric' => ['numeric', 'number'],
            'bool' => ['bool', 'boolean'],
            'boolean' => ['boolean', 'boolean'],
            'array' => ['array', 'array'],
            'list' => ['list', 'array'],
            'object' => ['object', 'object'],
            'map' => ['map', 'object'],
            'dict' => ['dict', 'object'],
            'dictionary' => ['dictionary', 'object'],
            'email' => ['email', 'string'],
            'uri' => ['uri', 'string'],
            'url' => ['url', 'string'],
            'uuid' => ['uuid', 'string'],
            'date' => ['date', 'string'],
            'datetime' => ['datetime', 'string'],
            'date-time' => ['date-time', 'string'],
            'datetime_iso8601' => ['datetime_iso8601', 'string'],
            'time' => ['time', 'string'],
            'timestamp' => ['timestamp', 'integer'],
            'mixed' => ['mixed', 'string'],
            'any' => ['any', 'string'],
            'null' => ['null', 'null'],
        ];
    }

    public function testMapTypeNormalizesCase(): void
    {
        $this->assertSame('string', $this->mapper->mapType('STRING'));
        $this->assertSame('integer', $this->mapper->mapType('INT'));
        $this->assertSame('boolean', $this->mapper->mapType('Boolean'));
    }

    public function testMapTypeTrimsWhitespace(): void
    {
        $this->assertSame('string', $this->mapper->mapType('  string  '));
        $this->assertSame('integer', $this->mapper->mapType("\tint\n"));
    }

    public function testMapTypeHandlesEntityReferences(): void
    {
        $this->assertSame('string', $this->mapper->mapType('entity:node'));
        $this->assertSame('string', $this->mapper->mapType('entity:user'));
        $this->assertSame('string', $this->mapper->mapType('entity_reference:taxonomy_term'));
    }

    public function testMapTypeUnknownDefaultsToString(): void
    {
        $this->assertSame('string', $this->mapper->mapType('unknown_type'));
        $this->assertSame('string', $this->mapper->mapType('custom'));
    }

    /**
     * @dataProvider formatProvider
     */
    public function testGetFormat(string $type, ?string $expected): void
    {
        $this->assertSame($expected, $this->mapper->getFormat($type));
    }

    public static function formatProvider(): array
    {
        return [
            'email' => ['email', 'email'],
            'uri' => ['uri', 'uri'],
            'url' => ['url', 'uri'],
            'uuid' => ['uuid', 'uuid'],
            'date' => ['date', 'date'],
            'datetime' => ['datetime', 'date-time'],
            'date-time' => ['date-time', 'date-time'],
            'datetime_iso8601' => ['datetime_iso8601', 'date-time'],
            'time' => ['time', 'time'],
            'string no format' => ['string', null],
            'integer no format' => ['integer', null],
            'unknown no format' => ['unknown', null],
        ];
    }

    public function testGetFormatNormalizesCase(): void
    {
        $this->assertSame('email', $this->mapper->getFormat('EMAIL'));
        $this->assertSame('uuid', $this->mapper->getFormat('UUID'));
    }

    public function testToSchemaString(): void
    {
        $builder = $this->mapper->toSchema('string');
        $schema = $builder->build();

        $this->assertSame('string', $schema['type']);
    }

    public function testToSchemaInteger(): void
    {
        $builder = $this->mapper->toSchema('int');
        $schema = $builder->build();

        $this->assertSame('integer', $schema['type']);
    }

    public function testToSchemaNumber(): void
    {
        $builder = $this->mapper->toSchema('float');
        $schema = $builder->build();

        $this->assertSame('number', $schema['type']);
    }

    public function testToSchemaBoolean(): void
    {
        $builder = $this->mapper->toSchema('bool');
        $schema = $builder->build();

        $this->assertSame('boolean', $schema['type']);
    }

    public function testToSchemaArray(): void
    {
        $builder = $this->mapper->toSchema('array');
        $schema = $builder->build();

        $this->assertSame('array', $schema['type']);
    }

    public function testToSchemaObject(): void
    {
        $builder = $this->mapper->toSchema('object');
        $schema = $builder->build();

        $this->assertSame('object', $schema['type']);
    }

    public function testToSchemaNull(): void
    {
        $builder = $this->mapper->toSchema('null');
        $schema = $builder->build();

        $this->assertSame('null', $schema['type']);
    }

    public function testToSchemaWithFormat(): void
    {
        $builder = $this->mapper->toSchema('email');
        $schema = $builder->build();

        $this->assertSame('string', $schema['type']);
        $this->assertSame('email', $schema['format']);
    }

    public function testToSchemaDatetime(): void
    {
        $builder = $this->mapper->toSchema('datetime');
        $schema = $builder->build();

        $this->assertSame('string', $schema['type']);
        $this->assertSame('date-time', $schema['format']);
    }

    public function testIsNumeric(): void
    {
        $this->assertTrue($this->mapper->isNumeric('int'));
        $this->assertTrue($this->mapper->isNumeric('integer'));
        $this->assertTrue($this->mapper->isNumeric('float'));
        $this->assertTrue($this->mapper->isNumeric('double'));
        $this->assertTrue($this->mapper->isNumeric('number'));
        $this->assertTrue($this->mapper->isNumeric('timestamp'));

        $this->assertFalse($this->mapper->isNumeric('string'));
        $this->assertFalse($this->mapper->isNumeric('boolean'));
        $this->assertFalse($this->mapper->isNumeric('array'));
    }

    public function testIsString(): void
    {
        $this->assertTrue($this->mapper->isString('string'));
        $this->assertTrue($this->mapper->isString('str'));
        $this->assertTrue($this->mapper->isString('text'));
        $this->assertTrue($this->mapper->isString('email'));
        $this->assertTrue($this->mapper->isString('uuid'));

        $this->assertFalse($this->mapper->isString('integer'));
        $this->assertFalse($this->mapper->isString('boolean'));
        $this->assertFalse($this->mapper->isString('array'));
    }

    public function testAddMapping(): void
    {
        $result = $this->mapper->addMapping('money', 'number');

        $this->assertSame($this->mapper, $result); // Fluent interface
        $this->assertSame('number', $this->mapper->mapType('money'));
    }

    public function testAddMappingOverridesExisting(): void
    {
        $this->mapper->addMapping('string', 'integer');

        $this->assertSame('integer', $this->mapper->mapType('string'));
    }

    public function testCustomMappingsInConstructor(): void
    {
        $mapper = new TypeMapper(['custom_type' => 'array']);

        $this->assertSame('array', $mapper->mapType('custom_type'));
        // Defaults still work
        $this->assertSame('string', $mapper->mapType('string'));
    }

    public function testCustomMappingsOverrideDefaults(): void
    {
        $mapper = new TypeMapper(['string' => 'object']);

        $this->assertSame('object', $mapper->mapType('string'));
    }
}
