<?php

namespace Splicewire\Composition\NodeActions;

/** Hosts bind a real authorized producer; the SDK contains no inference implementation or default. */
interface NodeActionCandidateProducer
{
    public function produce(NodeActionCandidateInputData $input): NodeActionCandidateData;
}
