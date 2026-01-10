<?php

declare(strict_types=1);

namespace CodeWheel\McpSchemaBuilder;

/**
 * Represents a single validation error.
 */
final class ValidationError
{
    /**
     * @param string $path JSON path to the invalid value (e.g., "email", "user.name", "items[0]").
     * @param string $message Human-readable error message.
     * @param string $constraint The constraint that failed (e.g., "required", "type", "minLength").
     * @param mixed $value The invalid value (null if not available or sensitive).
     * @param mixed $expected The expected value or constraint (e.g., "string", 5 for minLength).
     */
    public function __construct(
        public readonly string $path,
        public readonly string $message,
        public readonly string $constraint,
        public readonly mixed $value = null,
        public readonly mixed $expected = null,
    ) {}

    /**
     * Creates a "required" error.
     */
    public static function required(string $path): self
    {
        return new self(
            $path,
            "Required field is missing",
            'required',
        );
    }

    /**
     * Creates a "type" error.
     */
    public static function type(string $path, string $expected, mixed $actual): self
    {
        $actualType = get_debug_type($actual);
        return new self(
            $path,
            "Expected $expected, got $actualType",
            'type',
            $actual,
            $expected,
        );
    }

    /**
     * Creates a "format" error.
     */
    public static function format(string $path, string $format, mixed $value): self
    {
        return new self(
            $path,
            "Value does not match format '$format'",
            'format',
            $value,
            $format,
        );
    }

    /**
     * Creates a "minimum" error.
     */
    public static function minimum(string $path, int|float $minimum, int|float $actual): self
    {
        return new self(
            $path,
            "Value must be >= $minimum",
            'minimum',
            $actual,
            $minimum,
        );
    }

    /**
     * Creates a "maximum" error.
     */
    public static function maximum(string $path, int|float $maximum, int|float $actual): self
    {
        return new self(
            $path,
            "Value must be <= $maximum",
            'maximum',
            $actual,
            $maximum,
        );
    }

    /**
     * Creates a "minLength" error.
     */
    public static function minLength(string $path, int $minLength, int $actual): self
    {
        return new self(
            $path,
            "String must be at least $minLength characters (got $actual)",
            'minLength',
            null,
            $minLength,
        );
    }

    /**
     * Creates a "maxLength" error.
     */
    public static function maxLength(string $path, int $maxLength, int $actual): self
    {
        return new self(
            $path,
            "String must be at most $maxLength characters (got $actual)",
            'maxLength',
            null,
            $maxLength,
        );
    }

    /**
     * Creates a "pattern" error.
     */
    public static function pattern(string $path, string $pattern): self
    {
        return new self(
            $path,
            "Value does not match pattern",
            'pattern',
            null,
            $pattern,
        );
    }

    /**
     * Creates an "enum" error.
     *
     * @param array<int, mixed> $allowed
     */
    public static function enum(string $path, array $allowed, mixed $actual): self
    {
        $allowedStr = implode(', ', array_map(fn($v) => json_encode($v), $allowed));
        return new self(
            $path,
            "Value must be one of: $allowedStr",
            'enum',
            $actual,
            $allowed,
        );
    }

    /**
     * Creates a "minItems" error.
     */
    public static function minItems(string $path, int $minItems, int $actual): self
    {
        return new self(
            $path,
            "Array must have at least $minItems items (got $actual)",
            'minItems',
            null,
            $minItems,
        );
    }

    /**
     * Creates a "maxItems" error.
     */
    public static function maxItems(string $path, int $maxItems, int $actual): self
    {
        return new self(
            $path,
            "Array must have at most $maxItems items (got $actual)",
            'maxItems',
            null,
            $maxItems,
        );
    }

    /**
     * Creates a "uniqueItems" error.
     */
    public static function uniqueItems(string $path): self
    {
        return new self(
            $path,
            "Array items must be unique",
            'uniqueItems',
        );
    }

    /**
     * Creates an "additionalProperties" error.
     */
    public static function additionalProperty(string $path, string $property): self
    {
        return new self(
            $path,
            "Unknown property '$property' is not allowed",
            'additionalProperties',
            null,
            $property,
        );
    }

    /**
     * Converts to array format.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'path' => $this->path,
            'message' => $this->message,
            'constraint' => $this->constraint,
        ];

        if ($this->expected !== null) {
            $result['expected'] = $this->expected;
        }

        return $result;
    }
}
