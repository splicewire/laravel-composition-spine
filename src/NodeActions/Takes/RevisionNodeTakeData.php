<?php

namespace Splicewire\Composition\NodeActions\Takes;

use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;

class RevisionNodeTakeData extends NodeTakeData
{
    /** @param array<string, string|int|float|bool|null>|null $fingerprint */
    public function __construct(
        public string $id,
        public string $address,
        public string $cause,
        public ?string $correlation,
        public ?string $recordedAt,
        public NodeTakeBeforeData $before,
        public ?NodeTakeProducerData $producer,
        #[\Schemastud\DataSchemas\Attributes\MapValues(['string', 'integer', 'number', 'boolean', 'null'])]
        public ?array $fingerprint,
        #[LiteralTypeScriptType("'revision'")]
        public string $kind = 'revision',
    ) {
        parent::__construct($kind);
    }
}
