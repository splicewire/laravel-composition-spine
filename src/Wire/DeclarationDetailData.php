<?php

namespace Splicewire\Composition\Wire;

/**
 * Shared spine wire DTO (ADR-0093 tier 5) — the vocabulary that crosses the `api/v1` wire.
 *
 * Typed view over the Determination declaration-detail envelope (`{data: {...declaration...}}`) returned by
 * `GET /api/declarations/{id}`, so consumers stop array-poking `->json('data')`. Only the fields the detail
 * flow returns are surfaced as properties; the full record is kept in {@see $attributes} (the sanctioned
 * superset seam). `declaration` is the declared facts record and `disclosures` the determination timeline.
 */
class DeclarationDetailData
{
    /**
     * @param  array<string, mixed>  $attributes  the raw `data` record.
     * @param  array<string, mixed>  $declaration
     * @param  list<array<string, mixed>>  $disclosures
     */
    public function __construct(
        public string $id,
        public ?string $label = null,
        public string $subVertical = '',
        public string $declarationHash = '',
        public ?string $declaredAt = null,
        public ?string $latestStatus = null,
        public bool $redetermined = false,
        public array $declaration = [],
        public array $disclosures = [],
        public array $attributes = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data  the unwrapped `data` record.
     */
    public static function fromArray(array $data): self
    {
        return new static(
            id: (string) ($data['id'] ?? ''),
            label: isset($data['label']) ? (string) $data['label'] : null,
            subVertical: (string) ($data['subVertical'] ?? $data['sub_vertical'] ?? ''),
            declarationHash: (string) ($data['declarationHash'] ?? $data['declaration_hash'] ?? ''),
            declaredAt: isset($data['declaredAt']) ? (string) $data['declaredAt']
                : (isset($data['declared_at']) ? (string) $data['declared_at'] : null),
            latestStatus: isset($data['latestStatus']) ? (string) $data['latestStatus']
                : (isset($data['latest_status']) ? (string) $data['latest_status'] : null),
            redetermined: (bool) ($data['redetermined'] ?? false),
            declaration: is_array($data['declaration'] ?? null) ? $data['declaration'] : [],
            disclosures: is_array($data['disclosures'] ?? null) ? $data['disclosures'] : [],
            attributes: $data,
        );
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }
}
