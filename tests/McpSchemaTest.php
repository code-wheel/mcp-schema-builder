<?php

declare(strict_types=1);

namespace CodeWheel\McpSchemaBuilder\Tests;

use CodeWheel\McpSchemaBuilder\McpSchema;
use CodeWheel\McpSchemaBuilder\SchemaBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CodeWheel\McpSchemaBuilder\McpSchema
 */
final class McpSchemaTest extends TestCase
{
    // =========================================================================
    // Identifier Pattern Tests
    // =========================================================================

    public function testEntityId(): void
    {
        $schema = McpSchema::entityId('node')->build();

        $this->assertSame('string', $schema['type']);
        $this->assertStringContainsString('node', $schema['description']);
        $this->assertStringContainsString('UUID', $schema['description']);
    }

    public function testEntityIdWithoutType(): void
    {
        $schema = McpSchema::entityId()->build();

        $this->assertSame('string', $schema['type']);
        $this->assertStringContainsString('entity ID', $schema['description']);
    }

    public function testMachineName(): void
    {
        $schema = McpSchema::machineName()->build();

        $this->assertSame('string', $schema['type']);
        $this->assertSame('^[a-z][a-z0-9_]*$', $schema['pattern']);
        $this->assertSame(1, $schema['minLength']);
        $this->assertSame(128, $schema['maxLength']);
    }

    public function testMachineNameWithDescription(): void
    {
        $schema = McpSchema::machineName('Custom description')->build();

        $this->assertSame('Custom description', $schema['description']);
    }

    public function testUuid(): void
    {
        $schema = McpSchema::uuid()->build();

        $this->assertSame('string', $schema['type']);
        $this->assertSame('uuid', $schema['format']);
    }

    public function testSlug(): void
    {
        $schema = McpSchema::slug()->build();

        $this->assertSame('string', $schema['type']);
        $this->assertSame('^[a-z0-9]+(?:-[a-z0-9]+)*$', $schema['pattern']);
        $this->assertSame(1, $schema['minLength']);
        $this->assertSame(255, $schema['maxLength']);
    }

    // =========================================================================
    // Pagination Pattern Tests
    // =========================================================================

    public function testPagination(): void
    {
        $schema = McpSchema::pagination()->build();

        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey('limit', $schema['properties']);
        $this->assertArrayHasKey('offset', $schema['properties']);

        $limit = $schema['properties']['limit'];
        $this->assertSame('integer', $limit['type']);
        $this->assertSame(1, $limit['minimum']);
        $this->assertSame(100, $limit['maximum']);
        $this->assertSame(50, $limit['default']);

        $offset = $schema['properties']['offset'];
        $this->assertSame('integer', $offset['type']);
        $this->assertSame(0, $offset['minimum']);
        $this->assertSame(0, $offset['default']);
    }

    public function testPaginationWithCustomLimits(): void
    {
        $schema = McpSchema::pagination(25, 200)->build();

        $limit = $schema['properties']['limit'];
        $this->assertSame(200, $limit['maximum']);
        $this->assertSame(25, $limit['default']);
    }

    public function testCursorPagination(): void
    {
        $schema = McpSchema::cursorPagination()->build();

        $this->assertArrayHasKey('limit', $schema['properties']);
        $this->assertArrayHasKey('cursor', $schema['properties']);
        $this->assertSame('string', $schema['properties']['cursor']['type']);
    }

    // =========================================================================
    // Filter Pattern Tests
    // =========================================================================

    public function testStatusFilter(): void
    {
        $schema = McpSchema::statusFilter()->build();

        $this->assertSame('string', $schema['type']);
        $this->assertSame(['published', 'draft', 'archived'], $schema['enum']);
    }

    public function testStatusFilterWithCustomStatuses(): void
    {
        $schema = McpSchema::statusFilter(['active', 'inactive'])->build();

        $this->assertSame(['active', 'inactive'], $schema['enum']);
    }

    public function testDateRange(): void
    {
        $schema = McpSchema::dateRange()->build();

        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey('from', $schema['properties']);
        $this->assertArrayHasKey('to', $schema['properties']);

        $this->assertSame('date', $schema['properties']['from']['format']);
        $this->assertSame('date', $schema['properties']['to']['format']);
    }

    public function testSearchQuery(): void
    {
        $schema = McpSchema::searchQuery()->build();

        $this->assertSame('string', $schema['type']);
        $this->assertSame(1, $schema['minLength']);
        $this->assertSame(255, $schema['maxLength']);
    }

    public function testSearchQueryWithMinLength(): void
    {
        $schema = McpSchema::searchQuery(3)->build();

        $this->assertSame(3, $schema['minLength']);
    }

    public function testSorting(): void
    {
        $schema = McpSchema::sorting(['created', 'updated', 'title'])->build();

        $this->assertSame('object', $schema['type']);
        $this->assertSame(['created', 'updated', 'title'], $schema['properties']['sort_by']['enum']);
        $this->assertSame(['asc', 'desc'], $schema['properties']['sort_order']['enum']);
        $this->assertSame('asc', $schema['properties']['sort_order']['default']);
    }

    // =========================================================================
    // Content Pattern Tests
    // =========================================================================

    public function testBody(): void
    {
        $schema = McpSchema::body()->build();

        $this->assertSame('string', $schema['type']);
        $this->assertStringContainsString('HTML is allowed', $schema['description']);
    }

    public function testBodyPlainText(): void
    {
        $schema = McpSchema::body(allowHtml: false)->build();

        $this->assertStringContainsString('Plain text only', $schema['description']);
    }

    public function testTitle(): void
    {
        $schema = McpSchema::title()->build();

        $this->assertSame('string', $schema['type']);
        $this->assertSame(1, $schema['minLength']);
        $this->assertSame(255, $schema['maxLength']);
    }

    public function testTitleWithCustomLength(): void
    {
        $schema = McpSchema::title(100)->build();

        $this->assertSame(100, $schema['maxLength']);
    }

    public function testTags(): void
    {
        $schema = McpSchema::tags()->build();

        $this->assertSame('array', $schema['type']);
        $this->assertSame('string', $schema['items']['type']);
    }

    public function testMetadata(): void
    {
        $schema = McpSchema::metadata()->build();

        $this->assertSame('object', $schema['type']);
        $this->assertSame('string', $schema['additionalProperties']['type']);
    }

    // =========================================================================
    // Tool Schema Pattern Tests
    // =========================================================================

    public function testListToolSchema(): void
    {
        $schema = McpSchema::listToolSchema()->build();

        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey('query', $schema['properties']);
        $this->assertArrayHasKey('limit', $schema['properties']);
        $this->assertArrayHasKey('offset', $schema['properties']);
    }

    public function testListToolSchemaWithFiltersAndSorting(): void
    {
        $schema = McpSchema::listToolSchema(
            filterFields: ['status', 'type'],
            sortFields: ['created', 'title'],
        )->build();

        $this->assertArrayHasKey('status', $schema['properties']);
        $this->assertArrayHasKey('type', $schema['properties']);
        $this->assertArrayHasKey('sort_by', $schema['properties']);
        $this->assertArrayHasKey('sort_order', $schema['properties']);

        $this->assertSame(['created', 'title'], $schema['properties']['sort_by']['enum']);
    }

    public function testGetToolSchema(): void
    {
        $schema = McpSchema::getToolSchema()->build();

        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey('id', $schema['properties']);
        $this->assertContains('id', $schema['required']);
    }

    public function testGetToolSchemaWithCustomField(): void
    {
        $schema = McpSchema::getToolSchema('node_id', 'node')->build();

        $this->assertArrayHasKey('node_id', $schema['properties']);
        $this->assertStringContainsString('node', $schema['properties']['node_id']['description']);
    }

    public function testCreateToolSchema(): void
    {
        $schema = McpSchema::createToolSchema(
            requiredFields: [
                'title' => SchemaBuilder::string(),
                'body' => SchemaBuilder::string(),
            ],
            optionalFields: [
                'status' => SchemaBuilder::string()->enum(['draft', 'published']),
            ],
        )->build();

        $this->assertArrayHasKey('title', $schema['properties']);
        $this->assertArrayHasKey('body', $schema['properties']);
        $this->assertArrayHasKey('status', $schema['properties']);

        $this->assertContains('title', $schema['required']);
        $this->assertContains('body', $schema['required']);
        $this->assertNotContains('status', $schema['required'] ?? []);
    }

    public function testUpdateToolSchema(): void
    {
        $schema = McpSchema::updateToolSchema(
            idField: 'node_id',
            updateableFields: [
                'title' => SchemaBuilder::string(),
                'body' => SchemaBuilder::string(),
            ],
        )->build();

        $this->assertArrayHasKey('node_id', $schema['properties']);
        $this->assertArrayHasKey('title', $schema['properties']);
        $this->assertArrayHasKey('body', $schema['properties']);

        $this->assertContains('node_id', $schema['required']);
        // Updateable fields should NOT be required
        $this->assertNotContains('title', $schema['required']);
    }

    public function testDeleteToolSchema(): void
    {
        $schema = McpSchema::deleteToolSchema()->build();

        $this->assertArrayHasKey('id', $schema['properties']);
        $this->assertArrayHasKey('force', $schema['properties']);
        $this->assertContains('id', $schema['required']);

        $this->assertSame(false, $schema['properties']['force']['default']);
    }

    public function testDeleteToolSchemaWithoutForce(): void
    {
        $schema = McpSchema::deleteToolSchema('entity_id', includeForce: false)->build();

        $this->assertArrayHasKey('entity_id', $schema['properties']);
        $this->assertArrayNotHasKey('force', $schema['properties']);
    }

    // =========================================================================
    // Confirmation Pattern Tests
    // =========================================================================

    public function testConfirmation(): void
    {
        $schema = McpSchema::confirmation()->build();

        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey('confirm', $schema['properties']);
        $this->assertArrayHasKey('reason', $schema['properties']);

        $this->assertSame('boolean', $schema['properties']['confirm']['type']);
        $this->assertContains('confirm', $schema['required']);
    }

    // =========================================================================
    // Integration Tests
    // =========================================================================

    public function testMergeWithPagination(): void
    {
        $schema = SchemaBuilder::object()
            ->property('name', SchemaBuilder::string()->required())
            ->merge(McpSchema::pagination())
            ->build();

        $this->assertArrayHasKey('name', $schema['properties']);
        $this->assertArrayHasKey('limit', $schema['properties']);
        $this->assertArrayHasKey('offset', $schema['properties']);
        $this->assertContains('name', $schema['required']);
    }

    public function testMergeWithSorting(): void
    {
        $schema = SchemaBuilder::object()
            ->property('query', SchemaBuilder::string())
            ->merge(McpSchema::sorting(['created', 'updated']))
            ->merge(McpSchema::pagination())
            ->build();

        $this->assertArrayHasKey('query', $schema['properties']);
        $this->assertArrayHasKey('sort_by', $schema['properties']);
        $this->assertArrayHasKey('sort_order', $schema['properties']);
        $this->assertArrayHasKey('limit', $schema['properties']);
        $this->assertArrayHasKey('offset', $schema['properties']);
    }

    public function testComplexToolSchema(): void
    {
        // Build a complete "list nodes" tool schema
        $schema = SchemaBuilder::object()
            ->property('bundle', SchemaBuilder::string()
                ->enum(['article', 'page', 'event'])
                ->description('Filter by content type'))
            ->property('author_id', McpSchema::entityId('user'))
            ->property('query', McpSchema::searchQuery(2))
            ->merge(McpSchema::dateRange())
            ->merge(McpSchema::sorting(['created', 'changed', 'title']))
            ->merge(McpSchema::pagination(25, 50))
            ->build();

        // Verify all properties exist
        $this->assertArrayHasKey('bundle', $schema['properties']);
        $this->assertArrayHasKey('author_id', $schema['properties']);
        $this->assertArrayHasKey('query', $schema['properties']);
        $this->assertArrayHasKey('from', $schema['properties']);
        $this->assertArrayHasKey('to', $schema['properties']);
        $this->assertArrayHasKey('sort_by', $schema['properties']);
        $this->assertArrayHasKey('sort_order', $schema['properties']);
        $this->assertArrayHasKey('limit', $schema['properties']);
        $this->assertArrayHasKey('offset', $schema['properties']);

        // Verify specific configurations
        $this->assertSame(['article', 'page', 'event'], $schema['properties']['bundle']['enum']);
        $this->assertSame(2, $schema['properties']['query']['minLength']);
        $this->assertSame(50, $schema['properties']['limit']['maximum']);
        $this->assertSame(25, $schema['properties']['limit']['default']);
    }
}
