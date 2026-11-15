<?php

namespace Splicewire\Composition\NodeActions\Takes;

use Spatie\LaravelData\Data;

class NodeTakeBeforeData extends Data
{
    /** @param array<string, mixed> $truth */
    public function __construct(public array $truth) {}
}
