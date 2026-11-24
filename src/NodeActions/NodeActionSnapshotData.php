<?php

namespace Splicewire\Composition\NodeActions;

use Schemastud\DataSchemas\Attributes\Title;
use Spatie\LaravelData\Data;

/** The addressed truth and standing; projections are re-derived by the binding. */
#[Title('Node action snapshot')]
class NodeActionSnapshotData extends Data
{
    /** @param array<string, mixed> $truth @param array<string, mixed> $standing */
    public function __construct(public string $address, public array $truth, public array $standing) {}
}
