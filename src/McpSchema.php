<?php

declare(strict_types=1);

namespace CodeWheel\McpSchemaBuilder;

/**
 * Pre-built schema patterns for common MCP tool inputs.
 *
 * Provides ready-to-use schemas for patterns that appear frequently
 * in MCP tool definitions, reducing boilerplate and ensuring consistency.
 *
 * Example:
 * ```php
 * $schema = SchemaBuilder::object()
 *     ->property('entity_id', McpSchema::entityId('node'))
 *     ->property('machine_name', McpSchema::machineName())
 *     ->merge(McpSchema::pagination())
 *     ->build();
 * ```
 */
final class McpSchema
{
    // =========================================================================
    // Identifier Patterns
    // =========================================================================

    /**
     * Schema for entity IDs (numeric or string identifiers).
     *
     * @param string|null $entityType Optional entity type for description.
     */
    public static function entityId(?string $entityType = null): SchemaBuilder
    {
        $desc = $entityType !== null
            ? "The ID of the {$entityType} entity."
            : "The entity ID.";

        return SchemaBuilder::string()
            ->description($desc . " Can be numeric ID or UUID.");
    }

    /**
     * Schema for machine names (lowercase with underscores).
     *
     * @param string|null $description Optional custom description.
     */
    public static function machineName(?string $description = null): SchemaBuilder
    {
        return SchemaBuilder::string()
            ->pattern('^[a-z][a-z0-9_]*$')
            ->minLength(1)
            ->maxLength(128)
            ->description($description ?? "Machine name (lowercase letters, numbers, underscores).");
    }

    /**
     * Schema for UUIDs.
     */
    public static function uuid(): SchemaBuilder
    {
        return SchemaBuilder::string()
            ->format('uuid')
            ->description("UUID in standard format (xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx).");
    }

    /**
     * Schema for slugs (URL-safe identifiers).
     */
    public static function slug(): SchemaBuilder
    {
        return SchemaBuilder::string()
            ->pattern('^[a-z0-9]+(?:-[a-z0-9]+)*$')
            ->minLength(1)
            ->maxLength(255)
            ->description("URL-safe slug (lowercase letters, numbers, hyphens).");
    }

    // =========================================================================
    // Pagination Patterns
    // =========================================================================

    /**
     * Schema for pagination parameters.
     *
     * Returns an object schema with limit and offset properties.
     *
     * @param int $defaultLimit Default limit value.
     * @param int $maxLimit Maximum allowed limit.
     */
    public static function pagination(int $defaultLimit = 50, int $maxLimit = 100): SchemaBuilder
    {
        return SchemaBuilder::object()
            ->property('limit', SchemaBuilder::integer()
                ->minimum(1)
                ->maximum($maxLimit)
                ->default($defaultLimit)
                ->description("Maximum number of items to return (1-{$maxLimit})."))
            ->property('offset', SchemaBuilder::integer()
                ->minimum(0)
                ->default(0)
                ->description("Number of items to skip for pagination."));
    }

    /**
     * Schema for cursor-based pagination.
     */
    public static function cursorPagination(int $defaultLimit = 50): SchemaBuilder
    {
        return SchemaBuilder::object()
            ->property('limit', SchemaBuilder::integer()
                ->minimum(1)
                ->maximum(100)
                ->default($defaultLimit)
                ->description("Maximum number of items to return."))
            ->property('cursor', SchemaBuilder::string()
                ->description("Cursor from previous response for next page."));
    }

    // =========================================================================
    // Filter Patterns
    // =========================================================================

    /**
     * Schema for status filter.
     *
     * @param string[] $statuses Allowed status values.
     */
    public static function statusFilter(array $statuses = ['published', 'draft', 'archived']): SchemaBuilder
    {
        return SchemaBuilder::string()
            ->enum($statuses)
            ->description("Filter by status.");
    }

    /**
     * Schema for date range filter.
     */
    public static function dateRange(): SchemaBuilder
    {
        return SchemaBuilder::object()
            ->property('from', SchemaBuilder::string()
                ->format('date')
                ->description("Start date (YYYY-MM-DD)."))
            ->property('to', SchemaBuilder::string()
                ->format('date')
                ->description("End date (YYYY-MM-DD)."));
    }

    /**
     * Schema for a search/query string.
     *
     * @param int $minLength Minimum search string length.
     */
    public static function searchQuery(int $minLength = 1): SchemaBuilder
    {
        return SchemaBuilder::string()
            ->minLength($minLength)
            ->maxLength(255)
            ->description("Search query string.");
    }

    /**
     * Schema for sort parameters.
     *
     * @param string[] $sortableFields Fields that can be sorted.
     */
    public static function sorting(array $sortableFields): SchemaBuilder
    {
        return SchemaBuilder::object()
            ->property('sort_by', SchemaBuilder::string()
                ->enum($sortableFields)
                ->description("Field to sort by."))
            ->property('sort_order', SchemaBuilder::string()
                ->enum(['asc', 'desc'])
                ->default('asc')
                ->description("Sort direction."));
    }

    // =========================================================================
    // Content Patterns
    // =========================================================================

    /**
     * Schema for content body/text.
     *
     * @param bool $allowHtml Whether HTML is allowed.
     */
    public static function body(bool $allowHtml = true): SchemaBuilder
    {
        $desc = $allowHtml
            ? "Content body. HTML is allowed."
            : "Content body. Plain text only.";

        return SchemaBuilder::string()
            ->description($desc);
    }

    /**
     * Schema for a title field.
     */
    public static function title(int $maxLength = 255): SchemaBuilder
    {
        return SchemaBuilder::string()
            ->minLength(1)
            ->maxLength($maxLength)
            ->description("Title or label.");
    }

    /**
     * Schema for tags/labels array.
     */
    public static function tags(): SchemaBuilder
    {
        return SchemaBuilder::array(SchemaBuilder::string())
            ->description("List of tags or labels.");
    }

    /**
     * Schema for key-value metadata.
     */
    public static function metadata(): SchemaBuilder
    {
        return SchemaBuilder::object()
            ->additionalProperties(SchemaBuilder::string())
            ->description("Key-value metadata pairs.");
    }

    // =========================================================================
    // Common Tool Schema Patterns
    // =========================================================================

    /**
     * Complete schema for a "list entities" tool.
     *
     * @param string[] $filterFields Optional filter field names.
     * @param string[] $sortFields Optional sortable field names.
     */
    public static function listToolSchema(
        array $filterFields = [],
        array $sortFields = [],
    ): SchemaBuilder {
        $schema = SchemaBuilder::object()
            ->property('query', self::searchQuery()->description("Optional search query."));

        // Add filter fields
        foreach ($filterFields as $field) {
            $schema->property($field, SchemaBuilder::string()
                ->description("Filter by {$field}."));
        }

        // Add sorting if fields provided
        if (!empty($sortFields)) {
            $schema->property('sort_by', SchemaBuilder::string()
                ->enum($sortFields)
                ->description("Field to sort by."));
            $schema->property('sort_order', SchemaBuilder::string()
                ->enum(['asc', 'desc'])
                ->default('asc')
                ->description("Sort direction."));
        }

        // Add pagination
        $schema->property('limit', SchemaBuilder::integer()
            ->minimum(1)
            ->maximum(100)
            ->default(50)
            ->description("Maximum items to return."));
        $schema->property('offset', SchemaBuilder::integer()
            ->minimum(0)
            ->default(0)
            ->description("Items to skip."));

        return $schema;
    }

    /**
     * Complete schema for a "get single entity" tool.
     *
     * @param string $idField Name of the ID field.
     * @param string|null $entityType Optional entity type for description.
     */
    public static function getToolSchema(
        string $idField = 'id',
        ?string $entityType = null,
    ): SchemaBuilder {
        return SchemaBuilder::object()
            ->property($idField, self::entityId($entityType)->required());
    }

    /**
     * Complete schema for a "create entity" tool.
     *
     * @param array<string, SchemaBuilder> $requiredFields Required fields.
     * @param array<string, SchemaBuilder> $optionalFields Optional fields.
     */
    public static function createToolSchema(
        array $requiredFields,
        array $optionalFields = [],
    ): SchemaBuilder {
        $schema = SchemaBuilder::object();

        foreach ($requiredFields as $name => $fieldSchema) {
            $schema->property($name, $fieldSchema->required());
        }

        foreach ($optionalFields as $name => $fieldSchema) {
            $schema->property($name, $fieldSchema);
        }

        return $schema;
    }

    /**
     * Complete schema for an "update entity" tool.
     *
     * @param string $idField Name of the ID field.
     * @param array<string, SchemaBuilder> $updateableFields Fields that can be updated.
     */
    public static function updateToolSchema(
        string $idField,
        array $updateableFields,
    ): SchemaBuilder {
        $schema = SchemaBuilder::object()
            ->property($idField, self::entityId()->required());

        foreach ($updateableFields as $name => $fieldSchema) {
            $schema->property($name, $fieldSchema);
        }

        return $schema;
    }

    /**
     * Complete schema for a "delete entity" tool.
     *
     * @param string $idField Name of the ID field.
     * @param bool $includeForce Whether to include force option.
     */
    public static function deleteToolSchema(
        string $idField = 'id',
        bool $includeForce = true,
    ): SchemaBuilder {
        $schema = SchemaBuilder::object()
            ->property($idField, self::entityId()->required());

        if ($includeForce) {
            $schema->property('force', SchemaBuilder::boolean()
                ->default(false)
                ->description("Force delete even if entity is in use."));
        }

        return $schema;
    }

    // =========================================================================
    // Confirmation Pattern
    // =========================================================================

    /**
     * Schema for destructive operation confirmation.
     */
    public static function confirmation(): SchemaBuilder
    {
        return SchemaBuilder::object()
            ->property('confirm', SchemaBuilder::boolean()
                ->description("Set to true to confirm this destructive operation.")
                ->required())
            ->property('reason', SchemaBuilder::string()
                ->maxLength(500)
                ->description("Optional reason for this action."));
    }
}
