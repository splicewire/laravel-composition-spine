<?php

namespace Splicewire\Composition\Wire;

/**
 * Shared spine wire DTO (ADR-0093 tier 5) — the vocabulary that crosses the `api/v1` wire.
 *
 * Typed view over the Determination compliance-check envelope (`{data: {...check...}}`) returned by
 * `declarations/check`, `/recheck`, `/revise` and `/result`, so consumers stop array-poking
 * `->json('data')`. Only the fields the determination flow returns are surfaced as properties; the full
 * record is kept in {@see $attributes} (the sanctioned superset seam — extra engine-rendered fields ride
 * here without the wire DTO having to narrow or diverge). `factsRevisionId` and `declarationHash` are
 * carried only on a revise response and are null otherwise.
 */
class ComplianceCheckData
{
    /**
     * @param  array<string, mixed>  $attributes  the raw `data` record.
     * @param  array<string, mixed>  $facts
     * @param  list<mixed>  $products
     * @param  list<mixed>  $claims
     * @param  list<mixed>  $requirements
     * @param  list<mixed>  $gaps
     * @param  list<mixed>  $warnings
     */
    public function __construct(
        public string $id,
        public ?string $business = null,
        public string $disclosureId = '',
        public ?string $checkedAt = null,
        public string $standing = '',
        public ?bool $changed = null,
        public ?string $recheckedAt = null,
        public int $checks = 0,
        public array $facts = [],
        public array $products = [],
        public array $claims = [],
        public array $requirements = [],
        public array $gaps = [],
        public array $warnings = [],
        public ?string $factsRevisionId = null,
        public ?string $declarationHash = null,
        public array $attributes = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data  the unwrapped `data` record.
     */
    public static function fromArray(array $data): self
    {
        return new static(
            id: (string) ($data['id'] ?? ''),
            business: isset($data['business']) ? (string) $data['business'] : null,
            disclosureId: (string) ($data['disclosureId'] ?? $data['disclosure_id'] ?? ''),
            checkedAt: isset($data['checkedAt']) ? (string) $data['checkedAt']
                : (isset($data['checked_at']) ? (string) $data['checked_at'] : null),
            standing: (string) ($data['standing'] ?? ''),
            changed: array_key_exists('changed', $data) && $data['changed'] !== null ? (bool) $data['changed'] : null,
            recheckedAt: isset($data['recheckedAt']) ? (string) $data['recheckedAt']
                : (isset($data['rechecked_at']) ? (string) $data['rechecked_at'] : null),
            checks: (int) ($data['checks'] ?? 0),
            facts: is_array($data['facts'] ?? null) ? $data['facts'] : [],
            products: is_array($data['products'] ?? null) ? $data['products'] : [],
            claims: is_array($data['claims'] ?? null) ? $data['claims'] : [],
            requirements: is_array($data['requirements'] ?? null) ? $data['requirements'] : [],
            gaps: is_array($data['gaps'] ?? null) ? $data['gaps'] : [],
            warnings: is_array($data['warnings'] ?? null) ? $data['warnings'] : [],
            factsRevisionId: isset($data['factsRevisionId']) ? (string) $data['factsRevisionId']
                : (isset($data['facts_revision_id']) ? (string) $data['facts_revision_id'] : null),
            declarationHash: isset($data['declarationHash']) ? (string) $data['declarationHash']
                : (isset($data['declaration_hash']) ? (string) $data['declaration_hash'] : null),
            attributes: $data,
        );
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }
}
