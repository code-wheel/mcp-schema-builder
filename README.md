# MCP Schema Builder

[![CI](https://github.com/code-wheel/mcp-schema-builder/actions/workflows/ci.yml/badge.svg)](https://github.com/code-wheel/mcp-schema-builder/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/code-wheel/mcp-schema-builder/graph/badge.svg)](https://codecov.io/gh/code-wheel/mcp-schema-builder)
[![Latest Stable Version](https://poser.pugx.org/code-wheel/mcp-schema-builder/v)](https://packagist.org/packages/code-wheel/mcp-schema-builder)
[![License](https://poser.pugx.org/code-wheel/mcp-schema-builder/license)](https://packagist.org/packages/code-wheel/mcp-schema-builder)

A fluent JSON Schema builder for MCP (Model Context Protocol) tool definitions. Build type-safe schemas with a clean, chainable API.

## Installation

```bash
composer require code-wheel/mcp-schema-builder
```

## Quick Start

### Basic Schema Building

```php
use CodeWheel\McpSchemaBuilder\SchemaBuilder;

// Simple string parameter
$name = SchemaBuilder::string()
    ->description('The user name')
    ->minLength(1)
    ->maxLength(100)
    ->required()
    ->build();

// Number with constraints
$age = SchemaBuilder::integer()
    ->description('User age')
    ->minimum(0)
    ->maximum(150)
    ->build();

// Enum values
$role = SchemaBuilder::string()
    ->description('User role')
    ->enum(['admin', 'user', 'guest'])
    ->default('user')
    ->build();

// Array of strings
$tags = SchemaBuilder::array(SchemaBuilder::string())
    ->description('Tags')
    ->minItems(1)
    ->uniqueItems()
    ->build();
```

### Object Schemas

```php
$userSchema = SchemaBuilder::object()
    ->property('name', SchemaBuilder::string()->required())
    ->property('email', SchemaBuilder::string()->format('email')->required())
    ->property('age', SchemaBuilder::integer()->minimum(0))
    ->property('role', SchemaBuilder::string()->enum(['admin', 'user']))
    ->build();

// Result:
// {
//   "type": "object",
//   "properties": {
//     "name": {"type": "string"},
//     "email": {"type": "string", "format": "email"},
//     "age": {"type": "integer", "minimum": 0},
//     "role": {"type": "string", "enum": ["admin", "user"]}
//   },
//   "required": ["name", "email"]
// }
```

### MCP Tool Definitions

```php
use CodeWheel\McpSchemaBuilder\ToolSchemaBuilder;
use CodeWheel\McpSchemaBuilder\SchemaBuilder;

$tool = ToolSchemaBuilder::create('create-user', 'Create User')
    ->description('Creates a new user account in the system')
    ->readOnly(false)
    ->idempotent(false)
    ->destructive(false)
    ->parameter('name', SchemaBuilder::string()->minLength(1)->required())
    ->parameter('email', SchemaBuilder::string()->format('email')->required())
    ->parameter('role', SchemaBuilder::string()->enum(['admin', 'user'])->default('user'))
    ->parameter('notify', SchemaBuilder::boolean()->default(true))
    ->build();

// Result includes: name, label, description, inputSchema, annotations
```

### Type Mapping

Convert common type names to JSON Schema:

```php
use CodeWheel\McpSchemaBuilder\TypeMapper;

$mapper = new TypeMapper();

// Map type names
$mapper->mapType('string');    // 'string'
$mapper->mapType('int');       // 'integer'
$mapper->mapType('float');     // 'number'
$mapper->mapType('bool');      // 'boolean'
$mapper->mapType('email');     // 'string' (with format hint)
$mapper->mapType('datetime');  // 'string' (with format hint)

// Get format hints
$mapper->getFormat('email');     // 'email'
$mapper->getFormat('datetime');  // 'date-time'
$mapper->getFormat('uuid');      // 'uuid'

// Create schema from type
$schema = $mapper->toSchema('email');  // string with format: email

// Custom mappings
$mapper->addMapping('money', 'number');
```

## Schema Types

### String

```php
SchemaBuilder::string()
    ->description('...')
    ->minLength(1)
    ->maxLength(255)
    ->pattern('^[a-z]+$')
    ->format('email')  // or: uri, uuid, date, date-time, time
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
SchemaBuilder::array()
    ->items(SchemaBuilder::string())
    ->minItems(1)
    ->maxItems(10)
    ->uniqueItems();

// Or shorthand:
SchemaBuilder::array(SchemaBuilder::integer());
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

## MCP Annotations

Tool annotations help MCP clients understand tool behavior:

| Annotation | Description |
|------------|-------------|
| `readOnlyHint` | Tool doesn't modify state |
| `destructiveHint` | Tool may delete/destroy data |
| `idempotentHint` | Multiple calls have same effect |
| `openWorldHint` | May access external resources |

```php
ToolSchemaBuilder::create('delete-user', 'Delete User')
    ->readOnly(false)
    ->destructive(true)
    ->idempotent(true)  // Deleting twice has same effect
    ->openWorld(false)
    ->build();
```

## Output

All builders support multiple output formats:

```php
$builder = SchemaBuilder::object()
    ->property('name', SchemaBuilder::string());

// As array
$array = $builder->build();

// As JSON
$json = $builder->toJson();
$json = $builder->toJson(JSON_PRETTY_PRINT);
```

## License

MIT License - see [LICENSE](LICENSE) file.
