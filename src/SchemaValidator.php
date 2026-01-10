<?php

declare(strict_types=1);

namespace CodeWheel\McpSchemaBuilder;

/**
 * Validates data against a JSON Schema.
 *
 * Supports the JSON Schema subset commonly used in MCP tool definitions:
 * - type, required, properties
 * - enum, format, pattern
 * - minimum, maximum, exclusiveMinimum, exclusiveMaximum
 * - minLength, maxLength
 * - minItems, maxItems, uniqueItems, items
 * - additionalProperties
 *
 * Example:
 * ```php
 * $schema = SchemaBuilder::object()
 *     ->property('email', SchemaBuilder::string()->format('email')->required())
 *     ->property('age', SchemaBuilder::integer()->minimum(0))
 *     ->build();
 *
 * $validator = new SchemaValidator($schema);
 * $result = $validator->validate(['email' => 'invalid', 'age' => -5]);
 *
 * if ($result->isInvalid()) {
 *     foreach ($result->getErrors() as $error) {
 *         echo "{$error->path}: {$error->message}\n";
 *     }
 * }
 * ```
 */
final class SchemaValidator
{
    /** @var array<string, mixed> */
    private array $schema;

    /**
     * @param array<string, mixed> $schema JSON Schema array.
     */
    public function __construct(array $schema)
    {
        $this->schema = $schema;
    }

    /**
     * Creates a validator from a SchemaBuilder.
     */
    public static function fromBuilder(SchemaBuilder $builder): self
    {
        return new self($builder->build());
    }

    /**
     * Validates data against the schema.
     *
     * @param mixed $data The data to validate.
     */
    public function validate(mixed $data): ValidationResult
    {
        $errors = $this->validateValue($data, $this->schema, '');
        return new ValidationResult($errors);
    }

    /**
     * Validates a value against a schema node.
     *
     * @param mixed $value The value to validate.
     * @param array<string, mixed> $schema The schema node.
     * @param string $path The current path (for error reporting).
     * @return ValidationError[]
     */
    private function validateValue(mixed $value, array $schema, string $path): array
    {
        $errors = [];

        // Check type
        if (isset($schema['type'])) {
            $typeErrors = $this->validateType($value, $schema['type'], $path);
            if (!empty($typeErrors)) {
                // If type is wrong, skip other validations
                return $typeErrors;
            }
        }

        // Check enum
        if (isset($schema['enum'])) {
            $errors = array_merge($errors, $this->validateEnum($value, $schema['enum'], $path));
        }

        // String validations
        if (is_string($value)) {
            $errors = array_merge($errors, $this->validateString($value, $schema, $path));
        }

        // Number validations
        if (is_int($value) || is_float($value)) {
            $errors = array_merge($errors, $this->validateNumber($value, $schema, $path));
        }

        // Array/Object validations - route empty arrays based on schema type
        $schemaType = $schema['type'] ?? null;
        $isEmptyArray = is_array($value) && empty($value);

        if (is_array($value)) {
            if ($isEmptyArray) {
                // Route empty arrays based on schema type
                if ($schemaType === 'array') {
                    $errors = array_merge($errors, $this->validateArray($value, $schema, $path));
                } elseif ($schemaType === 'object') {
                    $errors = array_merge($errors, $this->validateObject($value, $schema, $path));
                }
                // If no type specified, treat empty array as object (backward compatible)
                elseif ($schemaType === null) {
                    $errors = array_merge($errors, $this->validateObject($value, $schema, $path));
                }
            } elseif (!$this->isAssociativeArray($value)) {
                $errors = array_merge($errors, $this->validateArray($value, $schema, $path));
            } else {
                $errors = array_merge($errors, $this->validateObject($value, $schema, $path));
            }
        }

        // Also treat stdClass as object
        if ($value instanceof \stdClass) {
            $errors = array_merge($errors, $this->validateObject((array) $value, $schema, $path));
        }

        return $errors;
    }

    /**
     * Validates type constraint.
     *
     * @param string|string[] $type
     * @return ValidationError[]
     */
    private function validateType(mixed $value, string|array $type, string $path): array
    {
        $types = is_array($type) ? $type : [$type];

        foreach ($types as $t) {
            if ($this->matchesType($value, $t)) {
                return [];
            }
        }

        $expected = is_array($type) ? implode('|', $type) : $type;
        return [ValidationError::type($path, $expected, $value)];
    }

    /**
     * Checks if a value matches a JSON Schema type.
     */
    private function matchesType(mixed $value, string $type): bool
    {
        // Empty arrays match both 'array' and 'object' types
        if (is_array($value) && empty($value) && ($type === 'array' || $type === 'object')) {
            return true;
        }

        return match ($type) {
            'string' => is_string($value),
            'integer' => is_int($value),
            'number' => is_int($value) || is_float($value),
            'boolean' => is_bool($value),
            'array' => is_array($value) && !$this->isAssociativeArray($value),
            'object' => (is_array($value) && $this->isAssociativeArray($value)) || $value instanceof \stdClass,
            'null' => $value === null,
            default => true,
        };
    }

    /**
     * Validates string constraints.
     *
     * @param array<string, mixed> $schema
     * @return ValidationError[]
     */
    private function validateString(string $value, array $schema, string $path): array
    {
        $errors = [];
        $length = mb_strlen($value);

        if (isset($schema['minLength']) && $length < $schema['minLength']) {
            $errors[] = ValidationError::minLength($path, $schema['minLength'], $length);
        }

        if (isset($schema['maxLength']) && $length > $schema['maxLength']) {
            $errors[] = ValidationError::maxLength($path, $schema['maxLength'], $length);
        }

        if (isset($schema['pattern']) && !preg_match('/' . $schema['pattern'] . '/', $value)) {
            $errors[] = ValidationError::pattern($path, $schema['pattern']);
        }

        if (isset($schema['format'])) {
            $formatErrors = $this->validateFormat($value, $schema['format'], $path);
            $errors = array_merge($errors, $formatErrors);
        }

        return $errors;
    }

    /**
     * Validates format constraint.
     *
     * @return ValidationError[]
     */
    private function validateFormat(string $value, string $format, string $path): array
    {
        $valid = match ($format) {
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'uri', 'url' => filter_var($value, FILTER_VALIDATE_URL) !== false,
            'uuid' => preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value) === 1,
            'date' => $this->isValidDate($value),
            'date-time' => $this->isValidDateTime($value),
            'time' => preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value) === 1,
            'ipv4' => filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false,
            'ipv6' => filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false,
            default => true, // Unknown formats are valid
        };

        if (!$valid) {
            return [ValidationError::format($path, $format, $value)];
        }

        return [];
    }

    /**
     * Validates number constraints.
     *
     * @param array<string, mixed> $schema
     * @return ValidationError[]
     */
    private function validateNumber(int|float $value, array $schema, string $path): array
    {
        $errors = [];

        if (isset($schema['minimum']) && $value < $schema['minimum']) {
            $errors[] = ValidationError::minimum($path, $schema['minimum'], $value);
        }

        if (isset($schema['maximum']) && $value > $schema['maximum']) {
            $errors[] = ValidationError::maximum($path, $schema['maximum'], $value);
        }

        if (isset($schema['exclusiveMinimum']) && $value <= $schema['exclusiveMinimum']) {
            $errors[] = ValidationError::minimum($path, $schema['exclusiveMinimum'], $value);
        }

        if (isset($schema['exclusiveMaximum']) && $value >= $schema['exclusiveMaximum']) {
            $errors[] = ValidationError::maximum($path, $schema['exclusiveMaximum'], $value);
        }

        return $errors;
    }

    /**
     * Validates array constraints.
     *
     * @param array<int, mixed> $value
     * @param array<string, mixed> $schema
     * @return ValidationError[]
     */
    private function validateArray(array $value, array $schema, string $path): array
    {
        $errors = [];
        $count = count($value);

        if (isset($schema['minItems']) && $count < $schema['minItems']) {
            $errors[] = ValidationError::minItems($path, $schema['minItems'], $count);
        }

        if (isset($schema['maxItems']) && $count > $schema['maxItems']) {
            $errors[] = ValidationError::maxItems($path, $schema['maxItems'], $count);
        }

        if (!empty($schema['uniqueItems']) && $count !== count(array_unique($value, SORT_REGULAR))) {
            $errors[] = ValidationError::uniqueItems($path);
        }

        // Validate items
        if (isset($schema['items']) && is_array($schema['items'])) {
            foreach ($value as $index => $item) {
                $itemPath = $path === '' ? "[$index]" : "{$path}[$index]";
                $errors = array_merge($errors, $this->validateValue($item, $schema['items'], $itemPath));
            }
        }

        return $errors;
    }

    /**
     * Validates object constraints.
     *
     * @param array<string, mixed> $value
     * @param array<string, mixed> $schema
     * @return ValidationError[]
     */
    private function validateObject(array $value, array $schema, string $path): array
    {
        $errors = [];

        // Check required properties
        if (isset($schema['required']) && is_array($schema['required'])) {
            foreach ($schema['required'] as $required) {
                if (!array_key_exists($required, $value)) {
                    $propPath = $path === '' ? $required : "{$path}.{$required}";
                    $errors[] = ValidationError::required($propPath);
                }
            }
        }

        // Validate properties
        if (isset($schema['properties']) && is_array($schema['properties'])) {
            foreach ($schema['properties'] as $propName => $propSchema) {
                if (array_key_exists($propName, $value)) {
                    $propPath = $path === '' ? $propName : "{$path}.{$propName}";
                    $errors = array_merge(
                        $errors,
                        $this->validateValue($value[$propName], $propSchema, $propPath)
                    );
                }
            }
        }

        // Check additionalProperties
        if (isset($schema['additionalProperties']) && $schema['additionalProperties'] === false) {
            $allowed = array_keys($schema['properties'] ?? []);
            foreach (array_keys($value) as $key) {
                if (!in_array($key, $allowed, true)) {
                    $errors[] = ValidationError::additionalProperty($path, (string) $key);
                }
            }
        }

        return $errors;
    }

    /**
     * Validates enum constraint.
     *
     * @param array<int, mixed> $allowed
     * @return ValidationError[]
     */
    private function validateEnum(mixed $value, array $allowed, string $path): array
    {
        if (!in_array($value, $allowed, true)) {
            return [ValidationError::enum($path, $allowed, $value)];
        }
        return [];
    }

    /**
     * Checks if an array is associative (object-like).
     *
     * @param array<mixed> $arr
     */
    private function isAssociativeArray(array $arr): bool
    {
        if (empty($arr)) {
            return false; // Empty array treated as array, not object
        }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * Validates a date string (YYYY-MM-DD).
     */
    private function isValidDate(string $value): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }
        $parts = explode('-', $value);
        return checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]);
    }

    /**
     * Validates a datetime string (ISO 8601).
     */
    private function isValidDateTime(string $value): bool
    {
        try {
            new \DateTimeImmutable($value);
            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
