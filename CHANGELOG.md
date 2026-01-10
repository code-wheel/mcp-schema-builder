# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.3] - 2025-01-09

### Added
- Comprehensive tests for 100% code coverage
- Tests for additional format validations (url, time, ipv4, ipv6)
- Tests for exclusiveMinimum/exclusiveMaximum validation
- Tests for stdClass object handling
- Tests for unknown type/format handling
- Tests for empty array type routing

## [1.1.2] - 2025-01-09

### Fixed
- Empty array handling in SchemaValidator
- PHPStan type annotation for isAssociativeArray
- CI integration test schema validation

## [1.1.0] - 2025-01-09

### Added
- `SchemaValidator` class for JSON Schema validation
  - Type checking (string, integer, number, boolean, array, object)
  - String constraints (minLength, maxLength, pattern, format)
  - Number constraints (minimum, maximum, exclusiveMinimum, exclusiveMaximum)
  - Array constraints (minItems, maxItems, uniqueItems, items schema)
  - Object constraints (required, additionalProperties)
  - Format validation (email, uri, uuid, date, date-time, ipv4, ipv6)
- `ValidationResult` class with error collection and utilities
  - `isValid()`, `isInvalid()`, `getErrors()`, `errorsFor()`
  - `toErrorBag()` integration with mcp-error-codes
  - `merge()` for combining results
- `ValidationError` DTO with factory methods
- `McpSchema` class with pre-built schema patterns
  - Identifiers: `entityId()`, `machineName()`, `uuid()`, `slug()`
  - Pagination: `pagination()`, `cursorPagination()`
  - Filtering: `statusFilter()`, `dateRange()`, `searchQuery()`, `sorting()`
  - Content: `title()`, `body()`, `tags()`, `metadata()`
  - Tool schemas: `listToolSchema()`, `getToolSchema()`, `createToolSchema()`, `updateToolSchema()`, `deleteToolSchema()`
  - `confirmation()` for destructive operations
- `SchemaBuilder::merge()` for composing schemas from fragments
- `SchemaBuilder::extend()` for creating independent copies

### Changed
- Updated CI to test PHP 8.1-8.4
- Added integration tests with mcp-error-codes

## [1.0.0] - 2025-01-07

### Added
- Initial release with `SchemaBuilder` fluent API
- `TypeMapper` for PHP-to-JSON-Schema type conversion
- `ToolSchemaBuilder` for MCP tool definitions
- Support for all JSON Schema types and constraints
- `toJson()` output method
