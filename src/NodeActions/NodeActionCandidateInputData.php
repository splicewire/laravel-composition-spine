<?php

namespace Splicewire\Composition\NodeActions;

use Spatie\LaravelData\Data;

/**
 * Server-assembled request for a candidate only. The caller owns the truth schema and hard gate;
 * provider/model credentials and inference policy belong to the authorized host implementation.
 * Neither a persistence writer nor a model/closure is serialized into the request.
 */
class NodeActionCandidateInputData extends Data
{
    /** @param array<string, mixed> $outputSchema @param array<string, mixed> $context */
    public function __construct(
        public NodeActionMode $mode,
        public NodeActionSnapshotData $before,
        public array $outputSchema,
        public ?string $instruction = null,
        public array $context = [],
    ) {}
}
