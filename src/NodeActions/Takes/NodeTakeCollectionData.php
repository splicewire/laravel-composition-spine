<?php

namespace Splicewire\Composition\NodeActions\Takes;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

class NodeTakeCollectionData extends Data
{
    /** @param list<NodeTakeData> $data */
    public function __construct(#[DataCollectionOf(NodeTakeData::class)] public array $data) {}
}
