<?php

namespace Splicewire\Composition\Wire;

/**
 * Shared spine wire DTO (ADR-0093 tier 5) — the vocabulary that crosses the `api/v1` wire.
 *
 * Typed view over the Determination disclosure export (`GET /api/disclosures/{id}/export?format=json`),
 * which is emitted UNENVELOPED (the record is the body, not `{data: ...}`), so consumers stop array-poking
 * `->json(null)`. Only the fields the export returns are surfaced as properties; the full record is kept in
 * {@see $attributes} (the sanctioned superset seam). `referenceHash`/`assertionHash` fix the corpus and the
 * asserted determination the disclosure was rendered from.
 */
class DisclosureData
{
    /**
     * @param  array<string, mixed>  $attributes  the raw record.
     * @param  list<mixed>  $catalog
     * @param  list<mixed>  $requirements
     * @param  list<mixed>  $gaps
     * @param  list<mixed>  $verdicts
     * @param  list<mixed>  $awareness
     */
    public function __construct(
        public string $asOf,
        public array $catalog = [],
        public array $requirements = [],
        public array $gaps = [],
        public ?string $subject = null,
        public array $verdicts = [],
        public array $awareness = [],
        public ?string $referenceHash = null,
        public ?string $assertionHash = null,
        public array $attributes = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data  the unwrapped record (unenveloped body).
     */
    public static function fromArray(array $data): self
    {
        return new static(
            asOf: (string) ($data['asOf'] ?? $data['as_of'] ?? ''),
            catalog: is_array($data['catalog'] ?? null) ? $data['catalog'] : [],
            requirements: is_array($data['requirements'] ?? null) ? $data['requirements'] : [],
            gaps: is_array($data['gaps'] ?? null) ? $data['gaps'] : [],
            subject: isset($data['subject']) ? (string) $data['subject'] : null,
            verdicts: is_array($data['verdicts'] ?? null) ? $data['verdicts'] : [],
            awareness: is_array($data['awareness'] ?? null) ? $data['awareness'] : [],
            referenceHash: isset($data['referenceHash']) ? (string) $data['referenceHash']
                : (isset($data['reference_hash']) ? (string) $data['reference_hash'] : null),
            assertionHash: isset($data['assertionHash']) ? (string) $data['assertionHash']
                : (isset($data['assertion_hash']) ? (string) $data['assertion_hash'] : null),
            attributes: $data,
        );
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }
}
