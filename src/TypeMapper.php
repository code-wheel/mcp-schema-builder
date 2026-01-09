<?php

declare(strict_types=1);

namespace CodeWheel\McpSchemaBuilder;

/**
 * Maps common data types to JSON Schema types.
 */
final class TypeMapper
{
    /**
     * Default type mappings from common type names to JSON Schema types.
     *
     * @var array<string, string>
     */
    private const DEFAULT_MAPPINGS = [
        // Basic types
        'string' => 'string',
        'str' => 'string',
        'text' => 'string',
        'int' => 'integer',
        'integer' => 'integer',
        'float' => 'number',
        'double' => 'number',
        'number' => 'number',
        'numeric' => 'number',
        'bool' => 'boolean',
        'boolean' => 'boolean',
        'array' => 'array',
        'list' => 'array',
        'object' => 'object',
        'map' => 'object',
        'dict' => 'object',
        'dictionary' => 'object',

        // String formats (map to string with format hint)
        'email' => 'string',
        'uri' => 'string',
        'url' => 'string',
        'uuid' => 'string',
        'date' => 'string',
        'datetime' => 'string',
        'date-time' => 'string',
        'datetime_iso8601' => 'string',
        'time' => 'string',
        'timestamp' => 'integer',

        // Special types
        'mixed' => 'string',
        'any' => 'string',
        'null' => 'null',
    ];

    /**
     * Format hints for types that should include format.
     *
     * @var array<string, string>
     */
    private const FORMAT_HINTS = [
        'email' => 'email',
        'uri' => 'uri',
        'url' => 'uri',
        'uuid' => 'uuid',
        'date' => 'date',
        'datetime' => 'date-time',
        'date-time' => 'date-time',
        'datetime_iso8601' => 'date-time',
        'time' => 'time',
    ];

    /** @var array<string, string> */
    private array $mappings;

    /**
     * @param array<string, string> $customMappings Additional or override mappings.
     */
    public function __construct(array $customMappings = [])
    {
        $this->mappings = array_merge(self::DEFAULT_MAPPINGS, $customMappings);
    }

    /**
     * Maps a type name to its JSON Schema type.
     */
    public function mapType(string $type): string
    {
        $normalized = strtolower(trim($type));

        // Handle entity references as strings.
        if (str_starts_with($normalized, 'entity:') || str_starts_with($normalized, 'entity_reference:')) {
            return 'string';
        }

        return $this->mappings[$normalized] ?? 'string';
    }

    /**
     * Gets the format hint for a type, if any.
     */
    public function getFormat(string $type): ?string
    {
        $normalized = strtolower(trim($type));
        return self::FORMAT_HINTS[$normalized] ?? null;
    }

    /**
     * Creates a SchemaBuilder for the given type.
     */
    public function toSchema(string $type): SchemaBuilder
    {
        $jsonType = $this->mapType($type);

        $builder = match ($jsonType) {
            'string' => SchemaBuilder::string(),
            'integer' => SchemaBuilder::integer(),
            'number' => SchemaBuilder::number(),
            'boolean' => SchemaBuilder::boolean(),
            'array' => SchemaBuilder::array(),
            'object' => SchemaBuilder::object(),
            'null' => SchemaBuilder::any()->with('type', 'null'),
            default => SchemaBuilder::string(),
        };

        $format = $this->getFormat($type);
        if ($format !== null) {
            $builder->format($format);
        }

        return $builder;
    }

    /**
     * Checks if a type maps to a numeric JSON Schema type.
     */
    public function isNumeric(string $type): bool
    {
        $jsonType = $this->mapType($type);
        return in_array($jsonType, ['integer', 'number'], true);
    }

    /**
     * Checks if a type maps to a string JSON Schema type.
     */
    public function isString(string $type): bool
    {
        return $this->mapType($type) === 'string';
    }

    /**
     * Adds a custom type mapping.
     */
    public function addMapping(string $type, string $jsonSchemaType): self
    {
        $this->mappings[strtolower($type)] = $jsonSchemaType;
        return $this;
    }
}
