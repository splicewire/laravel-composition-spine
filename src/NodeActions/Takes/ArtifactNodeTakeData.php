<?php

namespace Splicewire\Composition\NodeActions\Takes;

use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;

class ArtifactNodeTakeData extends NodeTakeData
{
    /** @param array<string, string|int|float|bool|null>|null $fingerprint */
    public function __construct(
        public string $id,
        public string $address,
        public string $medium,
        public string $model,
        public string $provider,
        public string $status,
        /** Fresh authorized retained-media URL from the host resolver, never the vendor's provenance URL. */
        public ?string $resultUrl,
        public ?int $seconds,
        public ?string $completedAt,
        #[\Schemastud\DataSchemas\Attributes\MapValues(['string', 'integer', 'number', 'boolean', 'null'])]
        public ?array $fingerprint,
        #[LiteralTypeScriptType("'artifact'")]
        public string $kind = 'artifact',
    ) {
        parent::__construct($kind);
    }
}
