<?php

namespace Splicewire\Composition\NodeActions;

use Schemastud\DataSchemas\Attributes\Title;
use Spatie\LaravelData\Data;

/** A binding-owned revision reference, never a storage model. */
#[Title('Node action revision')]
class NodeActionRevisionData extends Data
{
    public function __construct(public string $reference, public NodeActionSnapshotData $before, public NodeActionSnapshotData $after) {}
}
