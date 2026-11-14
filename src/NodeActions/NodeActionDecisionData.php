<?php

namespace Splicewire\Composition\NodeActions;

use Spatie\LaravelData\Data;

class NodeActionDecisionData extends Data
{
    public function __construct(public string $decision, public NodeActionRevisionData $revision, public ?NodeActionVerdictData $verdict = null) {}
}
