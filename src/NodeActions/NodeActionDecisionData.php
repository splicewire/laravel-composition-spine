<?php

namespace Splicewire\Composition\NodeActions;

use Schemastud\DataSchemas\Attributes\Title;
use Spatie\LaravelData\Data;

#[Title('Node action decision')]
class NodeActionDecisionData extends Data
{
    public function __construct(public string $decision, public NodeActionRevisionData $revision, public ?NodeActionVerdictData $verdict = null) {}
}
