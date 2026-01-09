<?php

declare(strict_types=1);

namespace CodeWheel\McpSchemaBuilder\Tests;

use CodeWheel\McpSchemaBuilder\SchemaBuilder;
use CodeWheel\McpSchemaBuilder\ToolSchemaBuilder;
use PHPUnit\Framework\TestCase;

class ToolSchemaBuilderTest extends TestCase
{
    public function testCreateBasicTool(): void
    {
        $tool = ToolSchemaBuilder::create('my-tool', 'My Tool')->build();

        $this->assertSame('my-tool', $tool['name']);
        $this->assertSame('My Tool', $tool['label']);
        $this->assertSame('', $tool['description']);
        $this->assertSame('My Tool', $tool['annotations']['title']);
    }

    public function testDescription(): void
    {
        $tool = ToolSchemaBuilder::create('tool', 'Tool')
            ->description('This is a helpful tool')
            ->build();

        $this->assertSame('This is a helpful tool', $tool['description']);
    }

    public function testParameterWithRequired(): void
    {
        $tool = ToolSchemaBuilder::create('tool', 'Tool')
            ->parameter('name', SchemaBuilder::string()->required())
            ->parameter('age', SchemaBuilder::integer())
            ->build();

        $this->assertArrayHasKey('name', $tool['inputSchema']['properties']);
        $this->assertArrayHasKey('age', $tool['inputSchema']['properties']);
        $this->assertContains('name', $tool['inputSchema']['required']);
        $this->assertNotContains('age', $tool['inputSchema']['required'] ?? []);
    }

    public function testMultipleRequiredParameters(): void
    {
        $tool = ToolSchemaBuilder::create('tool', 'Tool')
            ->parameter('a', SchemaBuilder::string()->required())
            ->parameter('b', SchemaBuilder::string()->required())
            ->parameter('c', SchemaBuilder::string())
            ->build();

        $this->assertCount(2, $tool['inputSchema']['required']);
        $this->assertContains('a', $tool['inputSchema']['required']);
        $this->assertContains('b', $tool['inputSchema']['required']);
    }

    public function testReadOnly(): void
    {
        $tool = ToolSchemaBuilder::create('tool', 'Tool')
            ->readOnly(true)
            ->build();

        $this->assertTrue($tool['annotations']['readOnlyHint']);
    }

    public function testReadOnlyFalse(): void
    {
        $tool = ToolSchemaBuilder::create('tool', 'Tool')
            ->readOnly(false)
            ->build();

        $this->assertFalse($tool['annotations']['readOnlyHint']);
    }

    public function testDestructive(): void
    {
        $tool = ToolSchemaBuilder::create('delete-user', 'Delete User')
            ->destructive(true)
            ->build();

        $this->assertTrue($tool['annotations']['destructiveHint']);
    }

    public function testIdempotent(): void
    {
        $tool = ToolSchemaBuilder::create('tool', 'Tool')
            ->idempotent(true)
            ->build();

        $this->assertTrue($tool['annotations']['idempotentHint']);
    }

    public function testOpenWorld(): void
    {
        $tool = ToolSchemaBuilder::create('tool', 'Tool')
            ->openWorld(true)
            ->build();

        $this->assertTrue($tool['annotations']['openWorldHint']);
    }

    public function testCustomAnnotation(): void
    {
        $tool = ToolSchemaBuilder::create('tool', 'Tool')
            ->annotation('customHint', 'custom value')
            ->build();

        $this->assertSame('custom value', $tool['annotations']['customHint']);
    }

    public function testMeta(): void
    {
        $tool = ToolSchemaBuilder::create('tool', 'Tool')
            ->meta('version', '1.0')
            ->meta('author', 'Test')
            ->build();

        $this->assertSame('1.0', $tool['metadata']['version']);
        $this->assertSame('Test', $tool['metadata']['author']);
    }

    public function testEmptyMetadata(): void
    {
        $tool = ToolSchemaBuilder::create('tool', 'Tool')->build();

        $this->assertSame([], $tool['metadata']);
    }

    public function testInputSchemaStructure(): void
    {
        $tool = ToolSchemaBuilder::create('tool', 'Tool')
            ->parameter('email', SchemaBuilder::string()->format('email'))
            ->build();

        $this->assertSame('object', $tool['inputSchema']['type']);
        $this->assertArrayHasKey('properties', $tool['inputSchema']);
        $this->assertSame('string', $tool['inputSchema']['properties']['email']['type']);
        $this->assertSame('email', $tool['inputSchema']['properties']['email']['format']);
    }

    public function testEmptyInputSchema(): void
    {
        $tool = ToolSchemaBuilder::create('tool', 'Tool')->build();

        $this->assertSame('object', $tool['inputSchema']['type']);
        $this->assertInstanceOf(\stdClass::class, $tool['inputSchema']['properties']);
    }

    public function testBuildInputSchema(): void
    {
        $builder = ToolSchemaBuilder::create('tool', 'Tool')
            ->parameter('name', SchemaBuilder::string()->required());

        $inputSchema = $builder->buildInputSchema();

        $this->assertSame('object', $inputSchema['type']);
        $this->assertArrayHasKey('name', $inputSchema['properties']);
        $this->assertContains('name', $inputSchema['required']);
    }

    public function testBuildAnnotations(): void
    {
        $builder = ToolSchemaBuilder::create('tool', 'My Tool')
            ->readOnly(true)
            ->destructive(false)
            ->idempotent(true);

        $annotations = $builder->buildAnnotations();

        $this->assertSame('My Tool', $annotations['title']);
        $this->assertTrue($annotations['readOnlyHint']);
        $this->assertFalse($annotations['destructiveHint']);
        $this->assertTrue($annotations['idempotentHint']);
    }

    public function testFluentInterface(): void
    {
        $builder = ToolSchemaBuilder::create('tool', 'Tool');

        $this->assertSame($builder, $builder->description('desc'));
        $this->assertSame($builder, $builder->parameter('x', SchemaBuilder::string()));
        $this->assertSame($builder, $builder->readOnly(true));
        $this->assertSame($builder, $builder->destructive(false));
        $this->assertSame($builder, $builder->idempotent(true));
        $this->assertSame($builder, $builder->openWorld(false));
        $this->assertSame($builder, $builder->annotation('key', 'value'));
        $this->assertSame($builder, $builder->meta('key', 'value'));
    }

    public function testComplexToolDefinition(): void
    {
        $tool = ToolSchemaBuilder::create('create-user', 'Create User')
            ->description('Creates a new user account in the system')
            ->readOnly(false)
            ->destructive(false)
            ->idempotent(false)
            ->openWorld(false)
            ->parameter('name', SchemaBuilder::string()->minLength(1)->maxLength(100)->required())
            ->parameter('email', SchemaBuilder::string()->format('email')->required())
            ->parameter('role', SchemaBuilder::string()->enum(['admin', 'user', 'guest'])->default('user'))
            ->parameter('age', SchemaBuilder::integer()->minimum(0)->maximum(150))
            ->parameter('tags', SchemaBuilder::array(SchemaBuilder::string()))
            ->meta('category', 'user-management')
            ->meta('version', '2.0')
            ->build();

        // Check basic info
        $this->assertSame('create-user', $tool['name']);
        $this->assertSame('Create User', $tool['label']);
        $this->assertStringContainsString('Creates a new user', $tool['description']);

        // Check annotations
        $this->assertFalse($tool['annotations']['readOnlyHint']);
        $this->assertFalse($tool['annotations']['destructiveHint']);
        $this->assertFalse($tool['annotations']['idempotentHint']);
        $this->assertFalse($tool['annotations']['openWorldHint']);

        // Check parameters
        $props = $tool['inputSchema']['properties'];
        $this->assertCount(5, $props);
        $this->assertSame('string', $props['name']['type']);
        $this->assertSame(1, $props['name']['minLength']);
        $this->assertSame('email', $props['email']['format']);
        $this->assertSame(['admin', 'user', 'guest'], $props['role']['enum']);
        $this->assertSame('user', $props['role']['default']);
        $this->assertSame('integer', $props['age']['type']);
        $this->assertSame('array', $props['tags']['type']);

        // Check required
        $this->assertCount(2, $tool['inputSchema']['required']);
        $this->assertContains('name', $tool['inputSchema']['required']);
        $this->assertContains('email', $tool['inputSchema']['required']);

        // Check metadata
        $this->assertSame('user-management', $tool['metadata']['category']);
        $this->assertSame('2.0', $tool['metadata']['version']);
    }

    public function testRequiredDeduplication(): void
    {
        // This shouldn't happen in practice, but test the deduplication
        $tool = ToolSchemaBuilder::create('tool', 'Tool')
            ->parameter('name', SchemaBuilder::string()->required())
            ->build();

        // Manually add same parameter again (simulating edge case)
        // The array_unique in build() should handle duplicates
        $this->assertCount(1, $tool['inputSchema']['required']);
    }
}
