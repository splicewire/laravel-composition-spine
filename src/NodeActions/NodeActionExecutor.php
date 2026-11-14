<?php

namespace Splicewire\Composition\NodeActions;

/** Hosts bind an authorized implementation; the SDK ships no inference default. */
interface NodeActionExecutor
{
    public function refine(NodeActionSubject $subject, NodeActionGate $gate, NodeActionRecorder $recorder, string $instruction, ?string $correlation = null): NodeActionDecisionData;

    public function regenerate(NodeActionSubject $subject, NodeActionRecorder $recorder): NodeActionDecisionData;
}
