<?php

namespace Splicewire\Composition\Io;

/**
 * Typed view over the schema-driven structured export
 * (`GET /api/v1/compositions/{id}/export?format=structured`, ADR-0041).
 *
 * The filled schema as JSON: the host-supplied generation `schema` echoed back
 * alongside the generated `nodes` — one per beat, each `{type, fields,
 * groundingTokens, path}` in grid order. The antenna renders these nodes itself
 * (a React page, a generated video, …); the platform never renders them.
 *
 * This lives in the shared composition spine-data package (not the HTTP client) so
 * the client AND any satellite share one drift-free I/O vocabulary: build it from a
 * decoded payload with {@see self::fromArray()}, walk the recursive grid with
 * {@see self::tree()}, and hydrate nodes into our 1:1 Data classes with
 * {@see self::hydrateNodes()}.
 */
class StructuredExport
{
    /**
     * @param  array<string, mixed>|null  $schema  the host-supplied generation schema, echoed back.
     * @param  array<int, array{type: ?string, fields: array<string, mixed>, groundingTokens: array<int, string>, path: string}>  $nodes
     * @param  float|null  $costUsd  this generation's LLM cost-of-goods in USD.
     * @param  array<string, float>  $costByMeter  cost-of-goods broken down per Meter.
     */
    public function __construct(
        public ?array $schema,
        public array $nodes,
        public ?float $costUsd = null,
        public array $costByMeter = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data  the decoded `{schema, nodes}` payload.
     */
    public static function fromArray(array $data): self
    {
        $rawNodes = is_array($data['nodes'] ?? null) ? array_values($data['nodes']) : [];

        $nodes = [];
        foreach ($rawNodes as $index => $node) {
            $nodes[] = [
                'type' => isset($node['type']) ? (string) $node['type'] : null,
                'fields' => is_array($node['fields'] ?? null) ? $node['fields'] : [],
                'groundingTokens' => is_array($node['groundingTokens'] ?? null) ? array_values($node['groundingTokens']) : [],
                // Older servers may not stamp a path; fall back to the flat index so
                // tree() still yields a flat forest rather than dropping the node.
                'path' => isset($node['path']) ? (string) $node['path'] : (string) $index,
            ];
        }

        // Transport adapters inherit this factory and must receive their own subtype.
        return new static(
            schema: is_array($data['schema'] ?? null) ? $data['schema'] : null,
            nodes: $nodes,
            costUsd: isset($data['cost_usd']) ? (float) $data['cost_usd'] : null,
            costByMeter: is_array($data['cost_by_meter'] ?? null) ? $data['cost_by_meter'] : [],
        );
    }

    /**
     * The first node of a given type, or null. Node `type` mirrors the property
     * key of the supplied schema (e.g. "soulUrge", "lifePath").
     *
     * @return array{type: ?string, fields: array<string, mixed>, groundingTokens: array<int, string>, path: string}|null
     */
    public function node(string $type): ?array
    {
        foreach ($this->nodes as $node) {
            if ($node['type'] === $type) {
                return $node;
            }
        }

        return null;
    }

    /**
     * Reconstruct the recursive antenna grid: the forest of root {@see StructuredNode}s
     * with descendants nested under them by dotted `path`. A flat (depth-1) export
     * yields one root per node; a recursive outline yields the full tree.
     *
     * @return array<int, StructuredNode>
     */
    public function tree(): array
    {
        return StructuredNode::tree($this->nodes);
    }

    /**
     * Hydrate every root node into one of our 1:1 `spatie/laravel-data` Data classes,
     * in grid order. The drift-free seam for a host turning a structured export back
     * into the typed leaves the schema was authored from.
     *
     * @template T of object
     *
     * @param  class-string<T>  $dataClass
     * @return array<int, T>
     */
    public function hydrateNodes(string $dataClass): array
    {
        return array_map(
            static fn (StructuredNode $node): object => $node->hydrate($dataClass),
            $this->tree(),
        );
    }
}
