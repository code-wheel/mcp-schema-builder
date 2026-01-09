<?php

declare(strict_types=1);

namespace CodeWheel\McpSchemaBuilder;

/**
 * Builds complete MCP tool definitions including schema and annotations.
 *
 * Example:
 * ```php
 * $tool = ToolSchemaBuilder::create('create-user', 'Create User')
 *     ->description('Creates a new user account')
 *     ->readOnly(false)
 *     ->destructive(false)
 *     ->idempotent(false)
 *     ->parameter('email', SchemaBuilder::string()->format('email')->required())
 *     ->parameter('name', SchemaBuilder::string()->minLength(1)->required())
 *     ->parameter('role', SchemaBuilder::string()->enum(['admin', 'user'])->default('user'))
 *     ->build();
 * ```
 */
class ToolSchemaBuilder
{
    private string $name;
    private string $label;
    private string $description = '';

    /** @var array<string, SchemaBuilder> */
    private array $parameters = [];

    /** @var string[] */
    private array $required = [];

    /** @var array<string, mixed> */
    private array $annotations = [];

    /** @var array<string, mixed> */
    private array $metadata = [];

    private function __construct(string $name, string $label)
    {
        $this->name = $name;
        $this->label = $label;
        $this->annotations['title'] = $label;
    }

    /**
     * Creates a new tool schema builder.
     */
    public static function create(string $name, string $label): self
    {
        return new self($name, $label);
    }

    /**
     * Sets the tool description.
     */
    public function description(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Adds a parameter to the tool.
     */
    public function parameter(string $name, SchemaBuilder $schema): self
    {
        $this->parameters[$name] = $schema;
        if ($schema->isRequired()) {
            $this->required[] = $name;
        }
        return $this;
    }

    /**
     * Sets whether this tool is read-only (no side effects).
     */
    public function readOnly(bool $value = true): self
    {
        $this->annotations['readOnlyHint'] = $value;
        return $this;
    }

    /**
     * Sets whether this tool is destructive.
     */
    public function destructive(bool $value = true): self
    {
        $this->annotations['destructiveHint'] = $value;
        return $this;
    }

    /**
     * Sets whether this tool is idempotent.
     */
    public function idempotent(bool $value = true): self
    {
        $this->annotations['idempotentHint'] = $value;
        return $this;
    }

    /**
     * Sets whether this tool may access open-world resources.
     */
    public function openWorld(bool $value = true): self
    {
        $this->annotations['openWorldHint'] = $value;
        return $this;
    }

    /**
     * Adds a custom annotation.
     */
    public function annotation(string $key, mixed $value): self
    {
        $this->annotations[$key] = $value;
        return $this;
    }

    /**
     * Adds metadata.
     */
    public function meta(string $key, mixed $value): self
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    /**
     * Builds the complete tool definition.
     *
     * @return array{
     *   name: string,
     *   label: string,
     *   description: string,
     *   inputSchema: array<string, mixed>,
     *   annotations: array<string, mixed>,
     *   metadata: array<string, mixed>,
     * }
     */
    public function build(): array
    {
        $properties = [];
        foreach ($this->parameters as $name => $builder) {
            $properties[$name] = $builder->build();
        }

        $inputSchema = [
            'type' => 'object',
            'properties' => !empty($properties) ? $properties : new \stdClass(),
        ];

        if (!empty($this->required)) {
            $inputSchema['required'] = array_unique($this->required);
        }

        return [
            'name' => $this->name,
            'label' => $this->label,
            'description' => $this->description,
            'inputSchema' => $inputSchema,
            'annotations' => $this->annotations,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Builds just the input schema.
     *
     * @return array<string, mixed>
     */
    public function buildInputSchema(): array
    {
        return $this->build()['inputSchema'];
    }

    /**
     * Builds just the annotations.
     *
     * @return array<string, mixed>
     */
    public function buildAnnotations(): array
    {
        return $this->annotations;
    }
}
