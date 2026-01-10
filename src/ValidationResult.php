<?php

declare(strict_types=1);

namespace CodeWheel\McpSchemaBuilder;

/**
 * Result of schema validation.
 */
final class ValidationResult implements \Countable
{
    /**
     * @param ValidationError[] $errors
     */
    public function __construct(
        private readonly array $errors = [],
    ) {}

    /**
     * Creates a successful (no errors) result.
     */
    public static function valid(): self
    {
        return new self([]);
    }

    /**
     * Creates a result with errors.
     *
     * @param ValidationError[] $errors
     */
    public static function invalid(array $errors): self
    {
        return new self($errors);
    }

    /**
     * Checks if validation passed (no errors).
     */
    public function isValid(): bool
    {
        return empty($this->errors);
    }

    /**
     * Checks if validation failed (has errors).
     */
    public function isInvalid(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Returns all errors.
     *
     * @return ValidationError[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Returns errors for a specific path.
     *
     * @return ValidationError[]
     */
    public function errorsFor(string $path): array
    {
        return array_filter(
            $this->errors,
            static fn(ValidationError $e): bool => $e->path === $path
        );
    }

    /**
     * Returns the first error, or null if valid.
     */
    public function firstError(): ?ValidationError
    {
        return $this->errors[0] ?? null;
    }

    /**
     * Returns the number of errors.
     */
    public function count(): int
    {
        return count($this->errors);
    }

    /**
     * Merges another result into this one.
     */
    public function merge(ValidationResult $other): self
    {
        return new self(array_merge($this->errors, $other->errors));
    }

    /**
     * Converts to ErrorBag (requires mcp-error-codes package).
     *
     * @return \CodeWheel\McpErrorCodes\ErrorBag
     * @throws \RuntimeException If mcp-error-codes is not installed.
     */
    public function toErrorBag(): object
    {
        if (!class_exists(\CodeWheel\McpErrorCodes\ErrorBag::class)) {
            throw new \RuntimeException(
                'code-wheel/mcp-error-codes package is required for toErrorBag(). ' .
                'Install with: composer require code-wheel/mcp-error-codes'
            );
        }

        $bag = new \CodeWheel\McpErrorCodes\ErrorBag();

        foreach ($this->errors as $error) {
            $bag->add(
                \CodeWheel\McpErrorCodes\McpError::validation($error->path, $error->message)
                    ->withDetail('constraint', $error->constraint)
            );
        }

        return $bag;
    }

    /**
     * Converts to array format.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'valid' => $this->isValid(),
            'error_count' => count($this->errors),
            'errors' => array_map(
                static fn(ValidationError $e): array => $e->toArray(),
                $this->errors
            ),
        ];
    }

    /**
     * Gets a summary message.
     */
    public function getSummary(): string
    {
        if ($this->isValid()) {
            return 'Validation passed';
        }

        $count = count($this->errors);
        if ($count === 1) {
            return $this->errors[0]->path . ': ' . $this->errors[0]->message;
        }

        return "$count validation errors";
    }
}
