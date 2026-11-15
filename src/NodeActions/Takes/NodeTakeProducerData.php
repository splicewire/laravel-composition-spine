<?php

namespace Splicewire\Composition\NodeActions\Takes;

use Spatie\LaravelData\Data;

/** Null means the original recording did not capture generation metadata. */
class NodeTakeProducerData extends Data
{
    public function __construct(public ?string $model = null, public ?string $provider = null) {}
}
