<?php

namespace Splicewire\Composition\NodeActions;

use Spatie\LaravelData\Data;

/** The addressed truth and standing; projections are re-derived by the binding. */
class NodeActionSnapshotData extends Data
{
    /** @param array<string, mixed> $truth @param array<string, mixed> $standing */
    public function __construct(public string $address, public array $truth, public array $standing) {}
}
