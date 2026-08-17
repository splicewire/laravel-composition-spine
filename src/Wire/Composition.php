<?php

namespace Splicewire\Composition\Wire;

/**
 * Shared spine wire DTO (ADR-0093 tier 5) — the vocabulary that crosses the `api/v1` wire.
 *
 * Typed view over the Compositions `GET /api/v1/compositions/{id}` JSON envelope
 * (`{data: {...composition...}}`), so consumers stop array-poking `->json('data')`. Only the
 * fields the generate/publish/export flow returns are surfaced as properties; the full record is
 * kept in {@see $attributes} (the sanctioned superset seam — extra engine-rendered fields ride
 * here without the wire DTO having to narrow or diverge).
 */
class Composition
{
    /**
     * @param  array<string, mixed>  $attributes  the raw `data` record.
     * @param  float|null  $costUsd  this generation's LLM cost-of-goods in USD (issue 06).
     * @param  array<string, float>  $costByMeter  cost-of-goods broken down per Meter.
     * @param  string|null  $renderStatus  whole-composition render lifecycle: pending|completed|failed; null when never rendered.
     * @param  array<string, mixed>|null  $render  render detail on success/failure.
     * @param  array<string, mixed>|null  $steering  how this composition was steered — `axis => {key, hash}`
     *                                               as {@see \Splicewire\Composition\Steering\ResolvedSteering::fingerprint()}
     *                                               renders it, carried up from the {@see \Splicewire\Composition\Angles\ComposedShape}.
     *                                               Null when the server stamped none. Surfaced as a
     *                                               property rather than left to $attributes because
     *                                               attribution is the point of steering: without it,
     *                                               "which change broke this piece" is a re-derivation
     *                                               instead of a diff.
     */
    public function __construct(
        public string $id,
        public ?string $status = null,
        public ?string $profile = null,
        public ?string $title = null,
        public array $attributes = [],
        public ?float $costUsd = null,
        public array $costByMeter = [],
        public ?string $renderStatus = null,
        public ?array $render = null,
        public ?array $steering = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data  the unwrapped `data` record.
     */
    public static function fromArray(array $data): self
    {
        return new static(
            id: (string) ($data['id'] ?? ''),
            status: isset($data['status']) ? (string) $data['status'] : null,
            profile: isset($data['profile']) ? (string) $data['profile'] : null,
            title: isset($data['title']) ? (string) $data['title'] : null,
            attributes: $data,
            // The server stamps cost under either the snake-cased model shape (cost_usd) or the
            // camelCased DTO shape (costUsd).
            costUsd: isset($data['cost_usd']) ? (float) $data['cost_usd']
                : (isset($data['costUsd']) ? (float) $data['costUsd'] : null),
            costByMeter: is_array($data['cost_by_meter'] ?? null) ? $data['cost_by_meter']
                : (is_array($data['costByMeter'] ?? null) ? $data['costByMeter'] : []),
            renderStatus: isset($data['renderStatus']) ? (string) $data['renderStatus']
                : (isset($data['render_status']) ? (string) $data['render_status'] : null),
            render: is_array($data['render'] ?? null) ? $data['render'] : null,
            steering: is_array($data['steering'] ?? null) ? $data['steering'] : null,
        );
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
