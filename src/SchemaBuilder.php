<?php

declare(strict_types=1);

namespace CodeWheel\McpSchemaBuilder;

/**
 * Fluent builder for JSON Schema compatible with MCP tools.
 *
 * Example:
 * ```php
 * $schema = SchemaBuilder::object()
 *     ->property('name', SchemaBuilder::string()->description('User name')->required())
 *     ->property('age', SchemaBuilder::integer()->minimum(0)->maximum(150))
 *     ->property('email', SchemaBuilder::string()->format('email'))
 *     ->property('role', SchemaBuilder::string()->enum(['admin', 'user', 'guest']))
 *     ->build();
 * ```
 */
class SchemaBuilder
{
    /** @var array<string, mixed> */
    private array $schema = [];

    /** @var array<string, SchemaBuilder> */
    private array $properties = [];

    /** @var string[] */
    private array $required = [];

    private bool $isRequired = false;

    private function __construct(string $type)
    {
        $this->schema['type'] = $type;
    }

    /**
     * Creates a string schema.
     */
    public static function string(): self
    {
        return new self('string');
    }

    /**
     * Creates an integer schema.
     */
    public static function integer(): self
    {
        return new self('integer');
    }

    /**
     * Creates a number (float) schema.
     */
    public static function number(): self
    {
        return new self('number');
    }

    /**
     * Creates a boolean schema.
     */
    public static function boolean(): self
    {
        return new self('boolean');
    }

    /**
     * Creates an array schema.
     */
    public static function array(?SchemaBuilder $items = null): self
    {
        $builder = new self('array');
        if ($items !== null) {
            $builder->schema['items'] = $items->build();
        }
        return $builder;
    }

    /**
     * Creates an object schema.
     */
    public static function object(): self
    {
        return new self('object');
    }

    /**
     * Creates a schema that allows any type.
     */
    public static function any(): self
    {
        $builder = new self('');
        unset($builder->schema['type']);
        return $builder;
    }

    /**
     * Creates a schema from an existing array.
     *
     * @param array<string, mixed> $schema
     */
    public static function fromArray(array $schema): self
    {
        $builder = new self($schema['type'] ?? 'object');
        $builder->schema = $schema;
        return $builder;
    }

    /**
     * Sets the description.
     */
    public function description(string $description): self
    {
        $this->schema['description'] = $description;
        return $this;
    }

    /**
     * Sets a default value.
     */
    public function default(mixed $value): self
    {
        $this->schema['default'] = $value;
        return $this;
    }

    /**
     * Marks this property as required (for use within objects).
     */
    public function required(): self
    {
        $this->isRequired = true;
        return $this;
    }

    /**
     * Sets enum values (for strings).
     *
     * @param array<int, string|int|float|bool> $values
     */
    public function enum(array $values): self
    {
        $this->schema['enum'] = array_values($values);
        return $this;
    }

    /**
     * Sets the format (e.g., 'email', 'uri', 'date-time').
     */
    public function format(string $format): self
    {
        $this->schema['format'] = $format;
        return $this;
    }

    /**
     * Sets minimum value (for numbers).
     */
    public function minimum(int|float $value): self
    {
        $this->schema['minimum'] = $value;
        return $this;
    }

    /**
     * Sets maximum value (for numbers).
     */
    public function maximum(int|float $value): self
    {
        $this->schema['maximum'] = $value;
        return $this;
    }

    /**
     * Sets exclusive minimum (for numbers).
     */
    public function exclusiveMinimum(int|float $value): self
    {
        $this->schema['exclusiveMinimum'] = $value;
        return $this;
    }

    /**
     * Sets exclusive maximum (for numbers).
     */
    public function exclusiveMaximum(int|float $value): self
    {
        $this->schema['exclusiveMaximum'] = $value;
        return $this;
    }

    /**
     * Sets minimum length (for strings).
     */
    public function minLength(int $value): self
    {
        $this->schema['minLength'] = $value;
        return $this;
    }

    /**
     * Sets maximum length (for strings).
     */
    public function maxLength(int $value): self
    {
        $this->schema['maxLength'] = $value;
        return $this;
    }

    /**
     * Sets a regex pattern (for strings).
     */
    public function pattern(string $pattern): self
    {
        $this->schema['pattern'] = $pattern;
        return $this;
    }

    /**
     * Sets minimum items (for arrays).
     */
    public function minItems(int $value): self
    {
        $this->schema['minItems'] = $value;
        return $this;
    }

    /**
     * Sets maximum items (for arrays).
     */
    public function maxItems(int $value): self
    {
        $this->schema['maxItems'] = $value;
        return $this;
    }

    /**
     * Sets unique items constraint (for arrays).
     */
    public function uniqueItems(bool $value = true): self
    {
        $this->schema['uniqueItems'] = $value;
        return $this;
    }

    /**
     * Sets items schema (for arrays).
     */
    public function items(SchemaBuilder $items): self
    {
        $this->schema['items'] = $items->build();
        return $this;
    }

    /**
     * Adds a property to an object schema.
     */
    public function property(string $name, SchemaBuilder $schema): self
    {
        $this->properties[$name] = $schema;
        if ($schema->isRequired) {
            $this->required[] = $name;
        }
        return $this;
    }

    /**
     * Sets additional properties constraint (for objects).
     */
    public function additionalProperties(bool|SchemaBuilder $value): self
    {
        $this->schema['additionalProperties'] = $value instanceof SchemaBuilder
            ? $value->build()
            : $value;
        return $this;
    }

    /**
     * Sets minimum properties (for objects).
     */
    public function minProperties(int $value): self
    {
        $this->schema['minProperties'] = $value;
        return $this;
    }

    /**
     * Sets maximum properties (for objects).
     */
    public function maxProperties(int $value): self
    {
        $this->schema['maxProperties'] = $value;
        return $this;
    }

    /**
     * Makes the schema nullable.
     */
    public function nullable(): self
    {
        $currentType = $this->schema['type'] ?? null;
        if ($currentType !== null) {
            $this->schema['type'] = [$currentType, 'null'];
        }
        return $this;
    }

    /**
     * Adds a custom schema property.
     */
    public function with(string $key, mixed $value): self
    {
        $this->schema[$key] = $value;
        return $this;
    }

    /**
     * Checks if this schema is marked as required.
     */
    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    /**
     * Merges another object schema's properties into this one.
     *
     * Useful for composing schemas from reusable fragments:
     * ```php
     * $timestampSchema = SchemaBuilder::object()
     *     ->property('created', SchemaBuilder::string()->format('date-time'))
     *     ->property('updated', SchemaBuilder::string()->format('date-time'));
     *
     * $contentSchema = SchemaBuilder::object()
     *     ->property('title', SchemaBuilder::string()->required())
     *     ->merge($timestampSchema)
     *     ->build();
     * ```
     *
     * @param SchemaBuilder $other The schema to merge properties from.
     * @return self Returns $this for chaining.
     */
    public function merge(SchemaBuilder $other): self
    {
        // Merge properties
        foreach ($other->properties as $name => $propSchema) {
            $this->properties[$name] = $propSchema;
            if ($propSchema->isRequired) {
                $this->required[] = $name;
            }
        }

        // Merge schema-level attributes (except type and properties)
        foreach ($other->schema as $key => $value) {
            if (!in_array($key, ['type', 'properties', 'required'], true)) {
                $this->schema[$key] = $value;
            }
        }

        // Merge required array
        $this->required = array_unique(array_merge($this->required, $other->required));

        return $this;
    }

    /**
     * Creates a copy of this schema that can be extended.
     *
     * @return self A new SchemaBuilder with the same configuration.
     */
    public function extend(): self
    {
        $new = new self($this->schema['type'] ?? 'object');
        $new->schema = $this->schema;
        $new->properties = $this->properties;
        $new->required = $this->required;
        return $new;
    }

    /**
     * Builds the final JSON Schema array.
     *
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $schema = $this->schema;

        // Build properties for object types.
        if (($this->schema['type'] ?? '') === 'object') {
            if (!empty($this->properties)) {
                $props = [];
                foreach ($this->properties as $name => $builder) {
                    $props[$name] = $builder->build();
                }
                $schema['properties'] = $props;
            } else {
                // Use stdClass for empty properties to ensure JSON encodes as {}.
                $schema['properties'] = new \stdClass();
            }
        }

        // Add required array for objects.
        if (!empty($this->required)) {
            $schema['required'] = array_unique($this->required);
        }

        return $schema;
    }

    /**
     * Converts the schema to JSON.
     */
    public function toJson(int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES): string
    {
        return json_encode($this->build(), $flags) ?: '{}';
    }
}
