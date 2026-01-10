# MCP Schema Builder

[![CI](https://github.com/code-wheel/mcp-schema-builder/actions/workflows/ci.yml/badge.svg)](https://github.com/code-wheel/mcp-schema-builder/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/code-wheel/mcp-schema-builder/graph/badge.svg)](https://codecov.io/gh/code-wheel/mcp-schema-builder)
[![Latest Stable Version](https://poser.pugx.org/code-wheel/mcp-schema-builder/v)](https://packagist.org/packages/code-wheel/mcp-schema-builder)
[![License](https://poser.pugx.org/code-wheel/mcp-schema-builder/license)](https://packagist.org/packages/code-wheel/mcp-schema-builder)

A fluent JSON Schema builder with validation for MCP (Model Context Protocol) tool definitions. Build type-safe schemas, validate LLM-generated inputs, and use pre-built patterns for common MCP tools.

## Installation

```bash
composer require code-wheel/mcp-schema-builder
```

## Quick Start

### Building Schemas

```php
use CodeWheel\McpSchemaBuilder\SchemaBuilder;

$schema = SchemaBuilder::object()
    ->property('name', SchemaBuilder::string()->minLength(1)->required())
    ->property('email', SchemaBuilder::string()->format('email')->required())
    ->property('age', SchemaBuilder::integer()->minimum(0)->maximum(150))
    ->property('role', SchemaBuilder::string()->enum(['admin', 'user', 'guest'])->default('user'))
    ->build();
```

### Validating Input

```php
use CodeWheel\McpSchemaBuilder\SchemaValidator;

$validator = new SchemaValidator();
$result = $validator->validate($input, $schema);

if (!$result->isValid()) {
    foreach ($result->getErrors() as $error) {
        echo "{$error->path}: {$error->message}\n";
        // "email: Invalid email format"
        // "age: Value must be >= 0"
    }

    // Or convert to ErrorBag for tool responses
    return $result->toErrorBag()->toToolResult();
}
```

### MCP Presets

```php
use CodeWheel\McpSchemaBuilder\McpSchema;

// Common patterns for MCP tools
$schema = SchemaBuilder::object()
    ->property('node_id', McpSchema::entityId('node'))       // Entity ID with description
    ->property('bundle', McpSchema::machineName())           // Machine name pattern
    ->property('query', McpSchema::searchQuery(2))           // Search with min length
    ->merge(McpSchema::pagination(50, 100))                  // limit + offset
    ->merge(McpSchema::sorting(['created', 'title']))        // sort_by + sort_order
    ->build();

// Complete tool schemas
$listSchema = McpSchema::listToolSchema(
    filterFields: ['status', 'author'],
    sortFields: ['created', 'updated', 'title']
);

$getSchema = McpSchema::getToolSchema('node_id', 'node');

$createSchema = McpSchema::createToolSchema(
    requiredFields: ['title' => SchemaBuilder::string(), 'body' => SchemaBuilder::string()],
    optionalFields: ['status' => SchemaBuilder::string()->enum(['draft', 'published'])]
);

$deleteSchema = McpSchema::deleteToolSchema('node_id', includeForce: true);
```

### Schema Composition

```php
// Reusable schema fragments
$timestamps = SchemaBuilder::object()
    ->property('created', SchemaBuilder::string()->format('date-time'))
    ->property('updated', SchemaBuilder::string()->format('date-time'));

$author = SchemaBuilder::object()
    ->property('author_id', SchemaBuilder::string()->required())
    ->property('author_name', SchemaBuilder::string());

// Compose into larger schema
$contentSchema = SchemaBuilder::object()
    ->property('id', SchemaBuilder::string()->required())
    ->property('title', SchemaBuilder::string()->required())
    ->merge($timestamps)
    ->merge($author)
    ->build();

// Extend without modifying original
$extendedSchema = $timestamps->extend()
    ->property('deleted', SchemaBuilder::string()->format('date-time'))
    ->build();
```

## Schema Builder API

### String

```php
SchemaBuilder::string()
    ->description('Field description')
    ->minLength(1)
    ->maxLength(255)
    ->pattern('^[a-z]+$')
    ->format('email')  // email, uri, uuid, date, date-time
    ->enum(['a', 'b', 'c'])
    ->default('a')
    ->nullable()
    ->required();
```

### Integer / Number

```php
SchemaBuilder::integer()
    ->minimum(0)
    ->maximum(100)
    ->exclusiveMinimum(0)
    ->exclusiveMaximum(100);

SchemaBuilder::number()
    ->minimum(0.0)
    ->maximum(1.0);
```

### Boolean

```php
SchemaBuilder::boolean()
    ->default(false);
```

### Array

```php
SchemaBuilder::array(SchemaBuilder::string())
    ->minItems(1)
    ->maxItems(10)
    ->uniqueItems();
```

### Object

```php
SchemaBuilder::object()
    ->property('name', SchemaBuilder::string()->required())
    ->property('age', SchemaBuilder::integer())
    ->additionalProperties(false)
    ->minProperties(1)
    ->maxProperties(10);
```

## MCP Presets Reference

### Identifiers

| Method | Description |
|--------|-------------|
| `McpSchema::entityId($type)` | Entity ID (numeric or UUID) |
| `McpSchema::machineName($desc)` | Machine name pattern `[a-z][a-z0-9_]*` |
| `McpSchema::uuid()` | UUID format |
| `McpSchema::slug()` | URL-safe slug |

### Pagination & Filtering

| Method | Description |
|--------|-------------|
| `McpSchema::pagination($default, $max)` | limit + offset properties |
| `McpSchema::cursorPagination($default)` | limit + cursor properties |
| `McpSchema::sorting($fields)` | sort_by + sort_order properties |
| `McpSchema::searchQuery($minLen)` | Search query string |
| `McpSchema::statusFilter($statuses)` | Status enum filter |
| `McpSchema::dateRange()` | from + to date properties |

### Content

| Method | Description |
|--------|-------------|
| `McpSchema::title($maxLen)` | Title string with length limits |
| `McpSchema::body($allowHtml)` | Body/content field |
| `McpSchema::tags()` | Array of tag strings |
| `McpSchema::metadata()` | Key-value object |

### Complete Tool Schemas

| Method | Description |
|--------|-------------|
| `McpSchema::listToolSchema($filters, $sorts)` | List entities with pagination |
| `McpSchema::getToolSchema($idField, $type)` | Get single entity by ID |
| `McpSchema::createToolSchema($required, $optional)` | Create entity |
| `McpSchema::updateToolSchema($idField, $fields)` | Update entity |
| `McpSchema::deleteToolSchema($idField, $force)` | Delete entity |
| `McpSchema::confirmation()` | Destructive operation confirmation |

## Validation

The `SchemaValidator` validates:

- **Type checking**: string, integer, number, boolean, array, object
- **String constraints**: minLength, maxLength, pattern, format
- **Number constraints**: minimum, maximum, exclusiveMinimum, exclusiveMaximum
- **Array constraints**: minItems, maxItems, uniqueItems, items schema
- **Object constraints**: required properties, additionalProperties
- **Enum validation**: Value must be in allowed list
- **Format validation**: email, uri, uuid, date, date-time, ipv4, ipv6

```php
$validator = new SchemaValidator();
$result = $validator->validate($input, $schema);

$result->isValid();           // bool
$result->getErrors();         // ValidationError[]
$result->toErrorBag();        // ErrorBag (from mcp-error-codes)
```

## Integration with mcp-error-codes

```php
use CodeWheel\McpSchemaBuilder\SchemaValidator;
use CodeWheel\McpErrorCodes\ErrorBag;

$validator = new SchemaValidator();
$result = $validator->validate($input, $schema);

if (!$result->isValid()) {
    // Convert validation errors to ErrorBag
    $errorBag = $result->toErrorBag();

    // Return as MCP tool result
    return $errorBag->toToolResult();
}
```

## Integration with mcp-tool-gateway

```php
use CodeWheel\McpToolGateway\Middleware\ValidatingMiddleware;
use CodeWheel\McpToolGateway\Middleware\MiddlewarePipeline;
use CodeWheel\McpSchemaBuilder\SchemaValidator;

// Automatic validation before tool execution
$validator = new SchemaValidator();
$middleware = new ValidatingMiddleware($provider, $validator);

$pipeline = new MiddlewarePipeline($provider);
$pipeline->add($middleware);

// Invalid inputs are rejected before reaching tools
$result = $pipeline->execute('create_user', $input);
```

## License

MIT License - see [LICENSE](LICENSE) file.
